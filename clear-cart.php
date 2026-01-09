<?php
header("Content-Type: application/json");
include "db.php";

/* Read JSON input */
$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "user_id required"
    ]);
    exit;
}

/* Check if cart has items */
$check = mysqli_query($conn, "SELECT cart_id FROM cart WHERE user_id='$user_id'");
if (mysqli_num_rows($check) == 0) {
    echo json_encode([
        "status" => "success",
        "message" => "Cart already empty"
    ]);
    exit;
}

/* Delete cart items */
mysqli_query($conn, "DELETE FROM cart WHERE user_id='$user_id'");

echo json_encode([
    "status" => "success",
    "message" => "Cart cleared successfully"
]);
?>
