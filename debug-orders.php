<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

// Check if orders table exists and has data
$ordersQ = mysqli_query($conn, "SELECT order_id, user_id, total_amount, status FROM orders ORDER BY order_id DESC LIMIT 10");

if (!$ordersQ) {
    echo json_encode([
        "status" => "error",
        "message" => "Query failed: " . mysqli_error($conn)
    ]);
    exit;
}

$orders = [];
while ($row = mysqli_fetch_assoc($ordersQ)) {
    $orders[] = $row;
}

echo json_encode([
    "status" => "success",
    "message" => "Found " . count($orders) . " orders in database",
    "orders" => $orders,
    "tip" => "Use one of these order_id values to test: order-details.php?order_id=X"
]);
?>
