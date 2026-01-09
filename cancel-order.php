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

// Create notification for baker about cancelled order
if ($baker_id > 0) {
    $title = "Order Cancelled";
    $message = "Order #$order_id has been cancelled by the customer.";
    mysqli_query($conn, "INSERT INTO notifications (user_type, user_id, type, title, message, order_id, is_read, created_at) 
                         VALUES ('baker', $baker_id, 'order_cancelled', '$title', '$message', $order_id, 0, NOW())");
}

// Create notification for customer
if ($user_id > 0) {
    $title = "Order Cancelled";
    $message = "Your order #$order_id has been cancelled successfully.";
    mysqli_query($conn, "INSERT INTO notifications (user_type, user_id, type, title, message, order_id, is_read, created_at) 
                         VALUES ('customer', $user_id, 'order_cancelled', '$title', '$message', $order_id, 0, NOW())");
}

echo json_encode([
    "status" => "success",
    "message" => "Order cancelled successfully"
]);
?>
