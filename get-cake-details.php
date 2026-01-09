<?php
header("Content-Type: application/json");
include "db.php";

// Get cake_id from GET parameter
$cake_id = $_GET['cake_id'] ?? null;

if (!$cake_id) {
    echo json_encode([
        "status" => "error",
        "message" => "cake_id required"
    ]);
    exit;
}

// Fetch cake details
$query = mysqli_query($conn, "
    SELECT cake_id, cake_name, description, price, image, availability
    FROM cakes
    WHERE cake_id = '$cake_id'
");

if (mysqli_num_rows($query) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Cake not found"
    ]);
    exit;
}

$cake = mysqli_fetch_assoc($query);
$cake['price'] = (int)$cake['price'];
$cake['availability'] = (int)$cake['availability'];

echo json_encode([
    "status" => "success",
    "cake" => $cake
]);
?>
