<?php
header("Content-Type: application/json");
include "db.php";

$baker_id = $_GET['baker_id'] ?? null;

if (!$baker_id) {
    echo json_encode([
        "status" => "error",
        "message" => "baker_id required"
    ]);
    exit;
}

/* Monthly Income */
$incomeQ = mysqli_query($conn, "
    SELECT IFNULL(SUM(total_amount),0) AS income
    FROM orders
    WHERE baker_id='$baker_id'
");

$income = mysqli_fetch_assoc($incomeQ)['income'];

/* Total Orders */
$orderQ = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM orders
    WHERE baker_id='$baker_id'
");

$totalOrders = mysqli_fetch_assoc($orderQ)['total'];

/* Pending Orders */
$pendingQ = mysqli_query($conn, "
    SELECT COUNT(*) AS pending
    FROM orders
    WHERE baker_id='$baker_id'
    AND status='Pending'
");

$pending = mysqli_fetch_assoc($pendingQ)['pending'];

echo json_encode([
    "status" => "success",
    "monthlyIncome" => $income,
    "totalOrders" => $totalOrders,
    "pendingOrders" => $pending
]);
exit;
?>
