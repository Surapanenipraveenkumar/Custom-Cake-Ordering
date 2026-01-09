<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
include "db.php";

/* Read JSON */
$data = json_decode(file_get_contents("php://input"), true);

$user_id  = $data['user_id'] ?? null;
$cake_id  = $data['cake_id'] ?? null;
$quantity = $data['quantity'] ?? 1;

// Customization options
$weight   = mysqli_real_escape_string($conn, $data['weight'] ?? '');
$shape    = mysqli_real_escape_string($conn, $data['shape'] ?? '');
$color    = mysqli_real_escape_string($conn, $data['color'] ?? '');
$flavor   = mysqli_real_escape_string($conn, $data['flavor'] ?? '');
$toppings = mysqli_real_escape_string($conn, $data['toppings'] ?? '');

if (!$user_id || !$cake_id) {
    echo json_encode([
        "status" => "error",
        "message" => "user_id and cake_id required"
    ]);
    exit;
}

/* Check cake exists & availability */
$check = mysqli_query($conn, "SELECT availability FROM cakes WHERE cake_id='$cake_id'");
if (mysqli_num_rows($check) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Cake not found"
    ]);
    exit;
}

$row = mysqli_fetch_assoc($check);
if ($row['availability'] == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Cake not available"
    ]);
    exit;
}

/* Insert into cart with customization */
$sql = "INSERT INTO cart (user_id, cake_id, quantity, weight, shape, color, flavor, toppings)
        VALUES ('$user_id', '$cake_id', '$quantity', '$weight', '$shape', '$color', '$flavor', '$toppings')";

$result = mysqli_query($conn, $sql);

if ($result) {
    echo json_encode([
        "status" => "success",
        "message" => "Cake added to cart"
    ]);
} else {
    // If customization columns don't exist, try basic insert
    mysqli_query($conn, "
        INSERT INTO cart (user_id, cake_id, quantity)
        VALUES ('$user_id','$cake_id','$quantity')
    ");
    echo json_encode([
        "status" => "success",
        "message" => "Cake added to cart"
    ]);
}
?>
