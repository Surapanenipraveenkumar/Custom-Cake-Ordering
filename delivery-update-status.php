<?php
/**
 * Delivery Update Status API
 * Delivery partner updates order status, sends notifications to customer
 */

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {

include "db.php";

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "DB connection failed"]);
    exit;
}

// Include push notification file safely
if (file_exists("send-push-notification.php")) {
    include_once "send-push-notification.php";
}

$rawInput = file_get_contents("php://input");
error_log("delivery-update-status: Raw input: $rawInput");

$data = json_decode($rawInput, true);
if (!$data) $data = $_POST;

$order_id = intval($data['order_id'] ?? 0);
$delivery_id = intval($data['delivery_id'] ?? 0);
$action = mysqli_real_escape_string($conn, $data['action'] ?? '');

error_log("delivery-update-status: order_id=$order_id, delivery_id=$delivery_id, action=$action");

if ($order_id <= 0 || empty($action)) {
    echo json_encode(["status" => "error", "message" => "order_id and action required"]);
    exit;
}

// Add delivery_id column if not exists (for accept action)
$col_check = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'delivery_id'");
if (mysqli_num_rows($col_check) == 0) {
    mysqli_query($conn, "ALTER TABLE orders ADD COLUMN delivery_id INT DEFAULT NULL");
}

// Get order details
$orderQ = mysqli_query($conn, "SELECT user_id, baker_id, status FROM orders WHERE order_id = $order_id");
if (!$orderQ || mysqli_num_rows($orderQ) == 0) {
    error_log("delivery-update-status: Order not found: $order_id");
    echo json_encode(["status" => "error", "message" => "Order not found"]);
    exit;
}
$order = mysqli_fetch_assoc($orderQ);
$user_id = intval($order['user_id']);
error_log("delivery-update-status: Found order, user_id=$user_id, current_status=" . $order['status']);

// Create notifications table if not exists
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

// Handle different actions
switch ($action) {
    case 'accept':
    case 'assigned':
        // Assign delivery partner to order
        $update_query = "UPDATE orders SET delivery_id = $delivery_id, delivery_status = 'assigned', status = 'out_for_delivery' WHERE order_id = $order_id";
        error_log("delivery-update-status: Executing: $update_query");
        $result = mysqli_query($conn, $update_query);
        
        if (!$result) {
            error_log("delivery-update-status: Update failed: " . mysqli_error($conn));
            echo json_encode(["status" => "error", "message" => "Update failed: " . mysqli_error($conn)]);
            exit;
        }
        
        $affected = mysqli_affected_rows($conn);
        error_log("delivery-update-status: Affected rows: $affected");
        
        $title = "Delivery Assigned!";
        $message = "A delivery partner has been assigned to your order #$order_id";
        break;
        
    case 'pickup':
    case 'picked_up':
        mysqli_query($conn, "UPDATE orders SET delivery_status = 'picked_up', status = 'out_for_delivery', picked_up_at = NOW() WHERE order_id = $order_id");
        
        $title = "Order Picked Up!";
        $message = "Your order #$order_id has been picked up and is on the way!";
        break;
        
    case 'deliver':
    case 'delivered':
        mysqli_query($conn, "UPDATE orders SET delivery_status = 'delivered', status = 'delivered', delivered_at = NOW() WHERE order_id = $order_id");
        
        $title = "Order Delivered!";
        $message = "Your order #$order_id has been delivered. Enjoy!";
        break;
        
    case 'reject':
        // Delivery partner rejected - no notification needed
        echo json_encode(["status" => "success", "message" => "Order rejected"]);
        mysqli_close($conn);
        exit;
        
    default:
        error_log("delivery-update-status: Invalid action: $action");
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
        exit;
}

// ========== IN-APP NOTIFICATION ==========
$title_escaped = mysqli_real_escape_string($conn, $title);
$message_escaped = mysqli_real_escape_string($conn, $message);
mysqli_query($conn, "
    INSERT INTO notifications (user_type, user_id, type, title, message, order_id)
    VALUES ('customer', $user_id, 'delivery_update', '$title_escaped', '$message_escaped', $order_id)
");

// ========== PUSH NOTIFICATION ==========
if (function_exists('notifyCustomerDeliveryUpdate')) {
    notifyCustomerDeliveryUpdate($conn, $user_id, $order_id, $action);
}

echo json_encode([
    "status" => "success",
    "message" => "Delivery status updated: $action"
]);

error_log("delivery-update-status: SUCCESS - action=$action, order_id=$order_id");

mysqli_close($conn);

} catch (Exception $e) {
    error_log("delivery-update-status: Exception: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Exception: " . $e->getMessage()
    ]);
}
?>
