<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include "db.php";

// Check cart table structure
$result = mysqli_query($conn, "DESCRIBE cart");
$columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $columns[] = $row['Field'];
}

// Check cart contents
$cart = mysqli_query($conn, "SELECT * FROM cart WHERE user_id = 1");
$items = [];
while ($row = mysqli_fetch_assoc($cart)) {
    $items[] = $row;
}

// Try to add to cart manually
$add = mysqli_query($conn, "INSERT INTO cart (user_id, cake_id, quantity) VALUES (1, 19, 1)");
$insert_error = $add ? null : mysqli_error($conn);

// Check again
$cart2 = mysqli_query($conn, "SELECT * FROM cart WHERE user_id = 1");
$items2 = [];
while ($row = mysqli_fetch_assoc($cart2)) {
    $items2[] = $row;
}

echo json_encode([
    "columns" => $columns,
    "cart_before_insert" => $items,
    "insert_error" => $insert_error,
    "cart_after_insert" => $items2,
    "insert_id" => mysqli_insert_id($conn)
]);

mysqli_close($conn);
?>
