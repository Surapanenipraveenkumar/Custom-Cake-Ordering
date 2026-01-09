<?php
header("Content-Type: application/json");
include "db.php";

// Get baker_id from GET parameter (matches Android @Query)
$baker_id = $_GET['baker_id'] ?? null;

if (!$baker_id) {
    echo json_encode([
        "status" => "error",
        "message" => "baker_id required"
    ]);
    exit;
}

// Fetch cakes with order count by joining with order_items
$query = mysqli_query($conn, "
    SELECT c.cake_id, c.cake_name, c.price, c.image, c.availability,
           COALESCE(SUM(oi.quantity), 0) AS orders
    FROM cakes c
    LEFT JOIN order_items oi ON c.cake_id = oi.cake_id
    WHERE c.baker_id = '$baker_id'
    GROUP BY c.cake_id
    ORDER BY c.cake_id DESC
");

$cakes = [];

while ($row = mysqli_fetch_assoc($query)) {
    $row['orders'] = (int)$row['orders'];
    $row['price'] = (int)$row['price'];
    $cakes[] = $row;
}

echo json_encode([
    "status" => "success",
    "cakes" => $cakes
]);
?>
