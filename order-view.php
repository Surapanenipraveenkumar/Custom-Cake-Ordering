<?php
header("Content-Type: application/json");
include "db.php";

/* Read JSON OR GET */
$data = json_decode(file_get_contents("php://input"), true);
$order_id = $data['order_id'] ?? $_GET['order_id'] ?? null;

if (!$order_id) {
    echo json_encode([
        "status" => "error",
        "message" => "order_id required"
    ]);
    exit;
}

/* Fetch order */
$o = mysqli_query($conn, "SELECT * FROM orders WHERE order_id='$order_id'");
if (mysqli_num_rows($o) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Order not found"
    ]);
    exit;
}
$order = mysqli_fetch_assoc($o);

/* Fetch order items */
$i = mysqli_query($conn, "
    SELECT 
        order_items.order_item_id,
        order_items.quantity,
        order_items.price,
        cakes.cake_name
    FROM order_items
    JOIN cakes ON order_items.cake_id = cakes.cake_id
    WHERE order_items.order_id='$order_id'
");

$items = [];
while ($row = mysqli_fetch_assoc($i)) {
    $items[] = $row;
}

echo json_encode([
    "status" => "success",
    "order" => $order,
    "items" => $items
]);
?>
