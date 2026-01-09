<?php
header("Content-Type: application/json");
include "db.php";

/* Get cart_id from GET params */
$cart_id = $_GET['cart_id'] ?? null;

if (!$cart_id) {
    echo json_encode([
        "status" => "error",
        "message" => "cart_id required"
    ]);
    exit;
}

/* Delete cart item */
$sql = "DELETE FROM cart WHERE cart_id = '$cart_id'";
$result = mysqli_query($conn, $sql);

if ($result) {
    echo json_encode([
        "status" => "success",
        "message" => "Item removed from cart"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to remove item"
    ]);
}
?>
