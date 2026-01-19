<?php
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json");
include "db.php";

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    echo json_encode([
        "status" => "error",
        "message" => "order_id required"
    ]);
    exit;
}

$order_id = intval($order_id);

// Check if order exists
$checkQuery = mysqli_query($conn, "SELECT * FROM orders WHERE order_id = $order_id");
if (!$checkQuery || mysqli_num_rows($checkQuery) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Order not found"
    ]);
    exit;
}

$order = mysqli_fetch_assoc($checkQuery);
$baker_id = isset($order['baker_id']) ? intval($order['baker_id']) : 0;
$user_id = isset($order['user_id']) ? intval($order['user_id']) : 0;
$delivery_id = isset($order['delivery_id']) ? intval($order['delivery_id']) : 0;

// Get current status - the column is named 'status' in this database
$currentStatus = strtolower($order['status'] ?? '');

// Cannot cancel if already delivered or cancelled
if ($currentStatus == 'delivered') {
    echo json_encode([
        "status" => "error",
        "message" => "Cannot cancel a delivered order"
    ]);
    exit;
}

if ($currentStatus == 'cancelled') {
    echo json_encode([
        "status" => "error",
        "message" => "Order is already cancelled"
    ]);
    exit;
}

// Update order status to cancelled (column is 'status')
$update = mysqli_query($conn, "UPDATE orders SET status = 'cancelled' WHERE order_id = $order_id");

if (!$update) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit;
}

// ==================== IN-APP NOTIFICATIONS ====================

// Create notification for baker about cancelled order
if ($baker_id > 0) {
    $title = "Order Cancelled ❌";
    $message = "Order #$order_id has been cancelled by the customer.";
    mysqli_query($conn, "INSERT INTO notifications (user_type, user_id, type, title, message, order_id, is_read, created_at) 
                         VALUES ('baker', $baker_id, 'order_cancelled', '$title', '$message', $order_id, 0, NOW())");
}

// Create notification for delivery partner if assigned
if ($delivery_id > 0) {
    $title = "Order Cancelled ❌";
    $message = "Order #$order_id has been cancelled. Please disregard this delivery.";
    mysqli_query($conn, "INSERT INTO notifications (user_type, user_id, type, title, message, order_id, is_read, created_at) 
                         VALUES ('delivery', $delivery_id, 'order_cancelled', '$title', '$message', $order_id, 0, NOW())");
}

// Create notification for customer
if ($user_id > 0) {
    $title = "Order Cancelled";
    $message = "Your order #$order_id has been cancelled successfully.";
    mysqli_query($conn, "INSERT INTO notifications (user_type, user_id, type, title, message, order_id, is_read, created_at) 
                         VALUES ('customer', $user_id, 'order_cancelled', '$title', '$message', $order_id, 0, NOW())");
}

// ==================== FCM PUSH NOTIFICATIONS ====================

// Function to send FCM push notification
function sendFCMNotification($fcmToken, $title, $body, $orderId) {
    if (empty($fcmToken)) return false;
    
    // Firebase Server Key - should be stored securely
    $serverKey = 'YOUR_FIREBASE_SERVER_KEY'; // Replace with actual key or load from config
    
    $url = 'https://fcm.googleapis.com/fcm/send';
    
    $notification = [
        'title' => $title,
        'body' => $body,
        'sound' => 'default',
        'click_action' => 'OPEN_ORDER_DETAILS'
    ];
    
    $data = [
        'order_id' => $orderId,
        'type' => 'order_cancelled',
        'title' => $title,
        'body' => $body
    ];
    
    $payload = [
        'to' => $fcmToken,
        'notification' => $notification,
        'data' => $data,
        'priority' => 'high'
    ];
    
    $headers = [
        'Authorization: key=' . $serverKey,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}

// Send push notification to baker
if ($baker_id > 0) {
    $bakerQuery = mysqli_query($conn, "SELECT fcm_token FROM bakers WHERE baker_id = $baker_id");
    if ($bakerQuery && $bakerRow = mysqli_fetch_assoc($bakerQuery)) {
        if (!empty($bakerRow['fcm_token'])) {
            sendFCMNotification(
                $bakerRow['fcm_token'],
                "Order Cancelled ❌",
                "Order #$order_id has been cancelled by the customer.",
                $order_id
            );
        }
    }
}

// Send push notification to delivery partner
if ($delivery_id > 0) {
    $deliveryQuery = mysqli_query($conn, "SELECT fcm_token FROM delivery_partners WHERE delivery_id = $delivery_id");
    if ($deliveryQuery && $deliveryRow = mysqli_fetch_assoc($deliveryQuery)) {
        if (!empty($deliveryRow['fcm_token'])) {
            sendFCMNotification(
                $deliveryRow['fcm_token'],
                "Order Cancelled ❌",
                "Order #$order_id has been cancelled. Please disregard this delivery.",
                $order_id
            );
        }
    }
}

echo json_encode([
    "status" => "success",
    "message" => "Order cancelled successfully"
]);
?>
