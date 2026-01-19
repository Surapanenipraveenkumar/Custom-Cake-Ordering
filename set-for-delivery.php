<?php
// set-for-delivery.php
// Baker marks order as ready for delivery
// Finds nearest online delivery partner and sends notification

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

try {

// Use db.php for connection
include "db.php";

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// Get input
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);
if (!$data) $data = $_POST;

$order_id = isset($data['order_id']) ? intval($data['order_id']) : 0;
$baker_id = isset($data['baker_id']) ? intval($data['baker_id']) : 0;

error_log("set-for-delivery: order_id=$order_id, baker_id=$baker_id");

if ($order_id <= 0 || $baker_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Missing order_id or baker_id"]);
    exit;
}

// Verify order belongs to this baker and is in ready status
$check = mysqli_query($conn, "SELECT order_id, status FROM orders WHERE order_id = $order_id AND baker_id = $baker_id");
if (!$check || mysqli_num_rows($check) == 0) {
    error_log("set-for-delivery: Order $order_id not found for baker $baker_id");
    echo json_encode(["status" => "error", "message" => "Order not found for this baker"]);
    exit;
}

$orderRow = mysqli_fetch_assoc($check);
$currentStatus = strtolower($orderRow['status']);
error_log("set-for-delivery: Current order status = $currentStatus");

// Helper function to add column only if it doesn't exist
function addColumnIfNotExists($conn, $table, $column, $definition) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($result && mysqli_num_rows($result) == 0) {
        mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

// Add columns if they don't exist
addColumnIfNotExists($conn, 'orders', 'delivery_status', "VARCHAR(50) DEFAULT 'pending'");
addColumnIfNotExists($conn, 'orders', 'ready_for_delivery', "TINYINT(1) DEFAULT 0");
addColumnIfNotExists($conn, 'orders', 'ready_for_delivery_at', "DATETIME DEFAULT NULL");
addColumnIfNotExists($conn, 'orders', 'assigned_delivery_id', "INT DEFAULT NULL");

// Get baker info for notification (safely handle missing columns)
$baker_result = mysqli_query($conn, "SELECT shop_name FROM bakers WHERE baker_id = $baker_id");
$baker = mysqli_fetch_assoc($baker_result);
$shop_name = $baker['shop_name'] ?? 'Baker';
$baker_lat = 0;
$baker_lng = 0;

// Try to get location if columns exist
$loc_result = @mysqli_query($conn, "SELECT latitude, longitude FROM bakers WHERE baker_id = $baker_id");
if ($loc_result && $loc_row = mysqli_fetch_assoc($loc_result)) {
    $baker_lat = floatval($loc_row['latitude'] ?? 0);
    $baker_lng = floatval($loc_row['longitude'] ?? 0);
}

// Check which delivery table exists - delivery_partners or delivery_persons
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'delivery_partners'");
$delivery_table = (mysqli_num_rows($table_check) > 0) ? 'delivery_partners' : 'delivery_persons';

error_log("set-for-delivery: Using delivery table: $delivery_table");

// Ensure fcm_token column exists in the delivery table
addColumnIfNotExists($conn, $delivery_table, 'fcm_token', 'VARCHAR(255) DEFAULT NULL');
addColumnIfNotExists($conn, $delivery_table, 'is_online', 'TINYINT(1) DEFAULT 0');
addColumnIfNotExists($conn, $delivery_table, 'latitude', 'DECIMAL(10, 8) DEFAULT NULL');
addColumnIfNotExists($conn, $delivery_table, 'longitude', 'DECIMAL(11, 8) DEFAULT NULL');

// Get ALL online delivery partners (not just one)
$count_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM $delivery_table WHERE is_online = 1");
$count_row = mysqli_fetch_assoc($count_query);
$online_count = $count_row['cnt'] ?? 0;
error_log("set-for-delivery: Online delivery partners count: $online_count");

// Query ALL online delivery partners
$all_delivery_partners = [];
if ($baker_lat != 0 && $baker_lng != 0) {
    // Get all online partners sorted by distance
    $delivery_query = "SELECT delivery_id, name, fcm_token,
        (6371 * acos(cos(radians($baker_lat)) * cos(radians(latitude)) * 
         cos(radians(longitude) - radians($baker_lng)) + 
         sin(radians($baker_lat)) * sin(radians(latitude)))) AS distance
        FROM $delivery_table 
        WHERE is_online = 1 
        ORDER BY distance ASC";
} else {
    // No location, get all online delivery partners
    $delivery_query = "SELECT delivery_id, name, fcm_token 
        FROM $delivery_table 
        WHERE is_online = 1";
}

error_log("set-for-delivery: Delivery query: $delivery_query");

$delivery_result = @mysqli_query($conn, $delivery_query);
if ($delivery_result) {
    while ($delivery = mysqli_fetch_assoc($delivery_result)) {
        $all_delivery_partners[] = [
            'delivery_id' => intval($delivery['delivery_id']),
            'name' => $delivery['name'],
            'fcm_token' => $delivery['fcm_token'] ?? ''
        ];
    }
    error_log("set-for-delivery: Found " . count($all_delivery_partners) . " online delivery partners");
} else {
    error_log("set-for-delivery: Query failed: " . mysqli_error($conn));
}

// Update order status - DO NOT assign delivery partner yet, let them accept
$update_sql = "UPDATE orders SET 
    status = 'ready_for_pickup',
    delivery_status = 'pending',
    ready_for_delivery = 1,
    ready_for_delivery_at = NOW()
    WHERE order_id = $order_id";

error_log("set-for-delivery: Executing SQL: $update_sql");

$update_result = mysqli_query($conn, $update_sql);

if (!$update_result) {
    $error_msg = mysqli_error($conn);
    error_log("set-for-delivery: Update failed - $error_msg");
    echo json_encode(["status" => "error", "message" => "Update failed: " . $error_msg]);
    exit;
}

// Verify the update actually worked
$affected = mysqli_affected_rows($conn);
error_log("set-for-delivery: Affected rows = $affected");

if ($affected == 0) {
    error_log("set-for-delivery: No rows affected - order may already be set for delivery");
}

// Create notifications table if needed
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_type VARCHAR(20) NOT NULL,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        order_id INT DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Send notifications to ALL online delivery partners
$title = "New Delivery Available! 🛵";
$msg = "Order #$order_id from $shop_name is ready for pickup!";
$title_esc = mysqli_real_escape_string($conn, $title);
$msg_esc = mysqli_real_escape_string($conn, $msg);

$notified_count = 0;
$fcm_success_count = 0;

if (count($all_delivery_partners) > 0) {
    // Include FCM helper
    if (file_exists("send-push-notification.php")) {
        include_once "send-push-notification.php";
    }
    
    foreach ($all_delivery_partners as $partner) {
        $delivery_id = $partner['delivery_id'];
        $delivery_name = $partner['name'];
        $fcm_token = $partner['fcm_token'];
        
        error_log("set-for-delivery: Sending notification to delivery_id=$delivery_id ($delivery_name)");
        
        // In-app notification for this delivery partner
        $notif_result = mysqli_query($conn, "
            INSERT INTO notifications (user_type, user_id, type, title, message, order_id)
            VALUES ('delivery', $delivery_id, 'new_delivery', '$title_esc', '$msg_esc', $order_id)
        ");
        
        if ($notif_result) {
            $notified_count++;
            error_log("set-for-delivery: In-app notification sent to delivery_id=$delivery_id");
        } else {
            error_log("set-for-delivery: Failed to insert notification for delivery_id=$delivery_id: " . mysqli_error($conn));
        }
        
        // FCM push notification for this delivery partner
        if (!empty($fcm_token) && function_exists('sendPushNotification')) {
            $fcm_result = sendPushNotification(
                $fcm_token, 
                $title, 
                $msg, 
                ['type' => 'new_delivery', 'order_id' => strval($order_id)]
            );
            if ($fcm_result) {
                $fcm_success_count++;
                error_log("set-for-delivery: FCM sent to delivery_id=$delivery_id");
            } else {
                error_log("set-for-delivery: FCM failed for delivery_id=$delivery_id");
            }
        }
    }
    
    error_log("set-for-delivery: Notified $notified_count delivery partners, FCM success: $fcm_success_count");
    
    echo json_encode([
        "status" => "success",
        "message" => "Order is available for pickup! Notified $notified_count delivery partners.",
        "notified_count" => $notified_count,
        "fcm_sent" => $fcm_success_count
    ]);
} else {
    error_log("set-for-delivery: No online delivery partners to notify");
    echo json_encode([
        "status" => "success",
        "message" => "Order is ready. No delivery partners online.",
        "notified_count" => 0
    ]);
}

mysqli_close($conn);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Exception: " . $e->getMessage()
    ]);
}
?>
