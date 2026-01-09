<?php
header("Content-Type: application/json");
include "db.php";

/* Read JSON OR GET */
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "user_id required"
    ]);
    exit;
}

$q = mysqli_query($conn, "
    SELECT * FROM orders
    WHERE user_id='$user_id'
    ORDER BY order_id DESC
");

$orders = [];
while ($row = mysqli_fetch_assoc($q)) {
    $orders[] = $row;
}

echo json_encode([
    "status" => "success",
    "orders" => $orders
]);
?>
