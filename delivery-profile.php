<?php
// Suppress PHP warnings/notices from breaking JSON output
error_reporting(0);
ini_set('display_errors', 0);

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

// Get delivery person profile
$query = mysqli_query($conn, "SELECT * FROM delivery_persons WHERE delivery_id = '$delivery_id'");

if (mysqli_num_rows($query) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Delivery person not found"
    ]);
    exit;
}

$delivery = mysqli_fetch_assoc($query);

// Get total deliveries
$totalQuery = mysqli_query($conn, "
    SELECT COUNT(*) as total FROM orders 
    WHERE delivery_id = '$delivery_id' AND delivery_status = 'delivered'
");
$totalDeliveries = mysqli_fetch_assoc($totalQuery)['total'] ?? 0;

// Get this month's earnings (₹50 per delivery)
$monthStart = date('Y-m-01');
$monthEarningsQuery = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM orders 
    WHERE delivery_id = '$delivery_id' 
    AND delivery_status = 'delivered'
    AND delivered_at >= '$monthStart'
");
$monthDeliveries = mysqli_fetch_assoc($monthEarningsQuery)['count'] ?? 0;
$monthEarnings = $monthDeliveries * 50;

// Get total earnings
$totalEarnings = $totalDeliveries * 50;

// Calculate rating (placeholder - you can implement actual rating later)
$rating = 4.5;

echo json_encode([
    "status" => "success",
    "delivery_id" => (int)$delivery['delivery_id'],
    "name" => $delivery['name'],
    "email" => $delivery['email'],
    "phone" => $delivery['phone'],
    "vehicle" => $delivery['vehicle'],
    "vehicle_number" => $delivery['vehicle_number'] ?? "",
    "service_area" => $delivery['service_area'] ?? "",
    "is_online" => (int)$delivery['is_online'],
    "total_deliveries" => (int)$totalDeliveries,
    "total_earnings" => (float)$totalEarnings,
    "month_deliveries" => (int)$monthDeliveries,
    "month_earnings" => (float)$monthEarnings,
    "rating" => (float)$rating,
    "created_at" => $delivery['created_at'] ?? null,
    "license_image" => $delivery['license_image'] ?? null,
    "license_verified" => isset($delivery['license_verified']) ? (bool)$delivery['license_verified'] : false
]);
?>
