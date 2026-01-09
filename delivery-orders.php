<?php
header("Content-Type: application/json");
include "db.php";

$delivery_id = $_GET['delivery_id'] ?? null;
$status = $_GET['status'] ?? 'all'; // all, pending, picked_up, delivered

if (!$delivery_id) {
    echo json_encode([
        "status" => "error",
        "message" => "delivery_id required"
    ]);
    exit;
}

$delivery_id = mysqli_real_escape_string($conn, $delivery_id);
$status = mysqli_real_escape_string($conn, $status);

// Build query based on status filter
$statusFilter = "";
if ($status == 'pending') {
    $statusFilter = "AND o.delivery_status IN ('pending', 'assigned')";
} elseif ($status == 'picked_up') {
    $statusFilter = "AND o.delivery_status = 'picked_up'";
} elseif ($status == 'delivered') {
    $statusFilter = "AND o.delivery_status = 'delivered'";
}

// Get orders for this delivery person
$ordersQuery = mysqli_query($conn, "
    SELECT o.*, 
           u.name as customer_name, u.phone as customer_phone,
           b.shop_name as baker_name, b.address as baker_address, b.phone as baker_phone,
           b.latitude as baker_lat, b.longitude as baker_lng
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    LEFT JOIN bakers b ON o.baker_id = b.baker_id
    WHERE o.delivery_id = '$delivery_id'
    $statusFilter
    ORDER BY o.created_at DESC
");

if (!$ordersQuery) {
    echo json_encode([
        "status" => "error",
        "message" => "Query failed: " . mysqli_error($conn)
    ]);
    exit;
}

$orders = [];
while ($row = mysqli_fetch_assoc($ordersQuery)) {
    $orders[] = [
        "order_id" => (int)$row['order_id'],
        "customer_name" => $row['customer_name'] ?? "Customer",
        "customer_phone" => $row['customer_phone'] ?? "",
        "customer_address" => $row['delivery_address'] ?? "",
        "baker_name" => $row['baker_name'] ?? "Baker",
        "baker_address" => $row['baker_address'] ?? "",
        "baker_phone" => $row['baker_phone'] ?? "",
        "baker_lat" => (float)($row['baker_lat'] ?? 0),
        "baker_lng" => (float)($row['baker_lng'] ?? 0),
        "total_amount" => (float)$row['total_amount'],
        "delivery_status" => $row['delivery_status'],
        "order_status" => $row['status'],
        "created_at" => $row['created_at'],
        "picked_up_at" => $row['picked_up_at'],
        "delivered_at" => $row['delivered_at']
    ];
}

echo json_encode([
    "status" => "success",
    "orders" => $orders
]);
?>
