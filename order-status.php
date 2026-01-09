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

$order_id = mysqli_real_escape_string($conn, $order_id);

$query = mysqli_query($conn, "
    SELECT order_status, delivery_status 
    FROM orders 
    WHERE order_id = '$order_id'
");

if (!$query || mysqli_num_rows($query) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Order not found"
    ]);
    exit;
}

$order = mysqli_fetch_assoc($query);

echo json_encode([
    "status" => "success",
    "order_id" => (int)$order_id,
    "order_status" => $order['order_status'],
    "delivery_status" => $order['delivery_status'] ?? null
]);
?>
