<?php
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);
$order_id = $data['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(["status"=>"error","message"=>"order_id required"]);
    exit;
}

/* Check order */
$q = mysqli_query($conn, "
    SELECT status FROM orders WHERE order_id='$order_id'
");

if (mysqli_num_rows($q) == 0) {
    echo json_encode(["status"=>"error","message"=>"Order not found"]);
    exit;
}

$order = mysqli_fetch_assoc($q);

if ($order['status'] != 'PLACED') {
    echo json_encode([
        "status"=>"error",
        "message"=>"Order cannot be cancelled now"
    ]);
    exit;
}

/* Cancel order */
mysqli_query($conn, "
    UPDATE orders SET status='REJECTED' WHERE order_id='$order_id'
");

echo json_encode([
    "status"=>"success",
    "message"=>"Order cancelled"
]);
?>
