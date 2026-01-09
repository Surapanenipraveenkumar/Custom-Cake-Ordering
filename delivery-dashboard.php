<?php
header("Content-Type: application/json");
include "db.php";

$delivery_id = $_GET['delivery_id'] ?? null;

if (!$delivery_id) {
    echo json_encode([
        "status" => "error",
        "message" => "delivery_id required"
    ]);
    exit;
}

$delivery_id = mysqli_real_escape_string($conn, $delivery_id);

// Check if delivery_persons table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'delivery_persons'");
if (mysqli_num_rows($tableCheck) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Delivery person not found"
    ]);
    exit;
}

// Get delivery person info
$deliveryQuery = mysqli_query($conn, "SELECT * FROM delivery_persons WHERE delivery_id = '$delivery_id'");
if (mysqli_num_rows($deliveryQuery) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Delivery person not found"
    ]);
    exit;
}

$delivery = mysqli_fetch_assoc($deliveryQuery);

// Add delivery columns to orders table if not exists
$checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'delivery_id'");
if (mysqli_num_rows($checkColumn) == 0) {
    mysqli_query($conn, "ALTER TABLE orders ADD COLUMN delivery_id INT DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE orders ADD COLUMN delivery_status VARCHAR(50) DEFAULT 'pending'");
    mysqli_query($conn, "ALTER TABLE orders ADD COLUMN picked_up_at DATETIME DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE orders ADD COLUMN delivered_at DATETIME DEFAULT NULL");
}

// Get today's date
$today = date('Y-m-d');

// Today's deliveries count
$todayDeliveriesQuery = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM orders 
    WHERE delivery_id = '$delivery_id' 
    AND delivery_status = 'delivered'
    AND DATE(delivered_at) = '$today'
");
$todayDeliveries = mysqli_fetch_assoc($todayDeliveriesQuery)['count'] ?? 0;

// Today's earnings (assume ₹50 per delivery)
$todayEarnings = $todayDeliveries * 50;

// Total deliveries
$totalDeliveriesQuery = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM orders 
    WHERE delivery_id = '$delivery_id' 
    AND delivery_status = 'delivered'
");
$totalDeliveries = mysqli_fetch_assoc($totalDeliveriesQuery)['count'] ?? 0;

// Pending orders - ONLY orders marked ready for delivery by baker
// Add ready_for_delivery column if not exists
mysqli_query($conn, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS ready_for_delivery TINYINT(1) DEFAULT 0");

$pendingOrdersQuery = mysqli_query($conn, "
    SELECT o.*, 
           u.name as customer_name, u.phone as customer_phone,
           b.shop_name as baker_name, b.address as baker_address, b.phone as baker_phone
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    LEFT JOIN bakers b ON o.baker_id = b.baker_id
    WHERE o.ready_for_delivery = 1
    AND o.delivery_address IS NOT NULL 
    AND o.delivery_address != ''
    AND LOWER(o.delivery_address) NOT LIKE '%pickup%'
    AND LOWER(o.delivery_address) NOT LIKE '%pick up%'
    AND LOWER(o.delivery_address) NOT LIKE '%pickup from store%'
    AND (
        (o.delivery_id IS NULL AND o.delivery_status = 'pending')
        OR 
        (o.delivery_id = '$delivery_id' AND o.delivery_status IN ('assigned', 'picked_up', 'out_for_delivery'))
    )
    ORDER BY 
        CASE WHEN o.delivery_id = '$delivery_id' THEN 0 ELSE 1 END,
        o.created_at DESC
    LIMIT 30
");

$pendingOrders = [];
while ($row = mysqli_fetch_assoc($pendingOrdersQuery)) {
    $pendingOrders[] = [
        "order_id" => (int)$row['order_id'],
        "customer_name" => $row['customer_name'] ?? "Customer",
        "customer_phone" => $row['customer_phone'] ?? "",
        "customer_address" => $row['delivery_address'] ?? "",
        "baker_name" => $row['baker_name'] ?? "Baker",
        "baker_address" => $row['baker_address'] ?? "",
        "baker_phone" => $row['baker_phone'] ?? "",
        "total_amount" => (float)$row['total_amount'],
        "delivery_status" => $row['delivery_status'],
        "created_at" => $row['created_at'],
        "is_assigned" => $row['delivery_id'] == $delivery_id
    ];
}

echo json_encode([
    "status" => "success",
    "delivery_name" => $delivery['name'],
    "is_online" => (int)$delivery['is_online'],
    "today_deliveries" => (int)$todayDeliveries,
    "today_earnings" => (float)$todayEarnings,
    "total_deliveries" => (int)$totalDeliveries,
    "pending_orders" => $pendingOrders
]);
?>
