<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
include "db.php";

/* Read JSON or GET */
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "user_id required"
    ]);
    exit;
}

/* Fetch cart items with cake details and customization */
$sql = "
    SELECT 
        cart.cart_id,
        cart.quantity,
        cart.weight,
        cart.shape,
        cart.color,
        cart.flavor,
        cart.toppings,
        cakes.cake_id,
        cakes.cake_name,
        cakes.price,
        cakes.image,
        cakes.availability
    FROM cart
    JOIN cakes ON cart.cake_id = cakes.cake_id
    WHERE cart.user_id = '$user_id'
";

$result = mysqli_query($conn, $sql);

$cart_items = [];
$total = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $row['item_total'] = $row['price'] * $row['quantity'];
    $total += $row['item_total'];
    $cart_items[] = $row;
}

echo json_encode([
    "status" => "success",
    "cart" => $cart_items,
    "cart_total" => $total
]);
?>
