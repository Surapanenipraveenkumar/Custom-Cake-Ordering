<?php
header("Content-Type: application/json");
include "db.php";

/* Read JSON input */
$data = json_decode(file_get_contents("php://input"), true);

$cart_id  = $data['cart_id'] ?? null;
$quantity = $data['quantity'] ?? null;

if (!$cart_id || !$quantity || $quantity < 1) {
    echo json_encode([
        "status" => "error",
        "message" => "cart_id and valid quantity required"
    ]);
    exit;
}

/* Check cart item exists */
$check = mysqli_query($conn, "SELECT cart_id FROM cart WHERE cart_id='$cart_id'");
if (mysqli_num_rows($check) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Cart item not found"
    ]);
    exit;
}

/* Update quantity */
mysqli_query($conn, "UPDATE cart SET quantity='$quantity' WHERE cart_id='$cart_id'");

echo json_encode([
    "status" => "success",
    "message" => "Cart quantity updated successfully"
]);
?>
