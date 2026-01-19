<?php
/**
 * Update Order Status API - With Notifications
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include "db.php";

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "DB connection failed"]);
    exit;
}

// Include push notification helper
if (file_exists("send-push-notification.php")) {
    include_once "send-push-notification.php";
}

// Get data from JSON or POST
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Fallback to POST data
if (!$data) {
    $data = $_POST;
}

$order_id = intval($data['order_id'] ?? 0);
$status = $data['status'] ?? '';

error_log("update-order-status: order_id=$order_id, status=$status");

if ($order_id <= 0 || empty($status)) {
    echo json_encode(["status" => "error", "message" => "order_id and status required"]);
    exit;
}

$status = mysqli_real_escape_string($conn, $status);

// Get order details for notification
$orderQ = mysqli_query($conn, "SELECT user_id, baker_id FROM orders WHERE order_id = $order_id");
if (!$orderQ || mysqli_num_rows($orderQ) == 0) {
    echo json_encode(["status" => "error", "message" => "Order not found"]);
    exit;
}
$order = mysqli_fetch_assoc($orderQ);
$user_id = intval($order['user_id']);
$baker_id = intval($order['baker_id']);

// Update order status
$sql = "UPDATE orders SET status = '$status' WHERE order_id = $order_id";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(["status" => "error", "message" => "Update failed: " . mysqli_error($conn)]);
    exit;
}

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

// Define notification content based on status
$notification_title = "";
$notification_message = "";
$notification_type = "order_status";

switch ($status) {
    case 'confirmed':
        $notification_title = "Order Confirmed! ✅";
        $notification_message = "Your order #$order_id has been confirmed by the baker!";
        break;
    case 'preparing':
        $notification_title = "Baking Started! 👨‍🍳";
        $notification_message = "Your order #$order_id is being prepared!";
        break;
    case 'ready':
    case 'ready_for_delivery':
        $notification_title = "Order Ready! 📦";
        $notification_message = "Your order #$order_id is ready for pickup/delivery!";
        break;
    case 'out_for_delivery':
        $notification_title = "On the Way! 🚗";
        $notification_message = "Your order #$order_id is out for delivery!";
        break;
    case 'delivered':
        $notification_title = "Order Delivered! 🎉";
        $notification_message = "Your order #$order_id has been delivered. Enjoy!";
        break;
    case 'cancelled':
        $notification_title = "Order Cancelled ❌";
        $notification_message = "Your order #$order_id has been cancelled.";
        break;
    default:
        $notification_title = "Order Update";
        $notification_message = "Your order #$order_id status: $status";
}

// Insert notification for customer
if (!empty($notification_title)) {
    $title_escaped = mysqli_real_escape_string($conn, $notification_title);
    $msg_escaped = mysqli_real_escape_string($conn, $notification_message);
    
    mysqli_query($conn, "
        INSERT INTO notifications (user_type, user_id, type, title, message, order_id)
        VALUES ('customer', $user_id, '$notification_type', '$title_escaped', '$msg_escaped', $order_id)
    ");
    
    // Send push notification to customer
    if (function_exists('sendPushNotification')) {
        $fcm_token = getFcmToken($conn, 'customer', $user_id);
        if ($fcm_token) {
            sendPushNotification(
                $fcm_token,
                $notification_title,
                $notification_message,
                ['type' => 'order_status', 'order_id' => strval($order_id), 'status' => $status]
            );
        }
    }
}

echo json_encode([
    "status" => "success",
    "message" => "Order status updated to $status"
]);

mysqli_close($conn);
?>
