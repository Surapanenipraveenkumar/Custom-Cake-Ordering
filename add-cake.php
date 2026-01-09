<?php
header("Content-Type: application/json");
include "db.php";

// Read JSON input from Android app
$data = json_decode(file_get_contents("php://input"), true);

$baker_id    = $data['baker_id'] ?? null;
$cake_name   = $data['cake_name'] ?? '';
$description = $data['description'] ?? '';
$price       = $data['price'] ?? 0;
$image       = $data['image'] ?? '';

// Get customization options arrays
$shapes   = $data['shapes'] ?? [];
$colours  = $data['colours'] ?? [];
$flavours = $data['flavours'] ?? [];
$toppings = $data['toppings'] ?? [];

// Validate required fields
if (!$baker_id || !$cake_name || !$price) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields (baker_id, cake_name, price)"
    ]);
    exit;
}

// Escape strings for SQL
$cake_name   = mysqli_real_escape_string($conn, $cake_name);
$description = mysqli_real_escape_string($conn, $description);
$image       = mysqli_real_escape_string($conn, $image);

// Insert cake into cakes table
$insertCake = mysqli_query($conn, "
    INSERT INTO cakes (baker_id, cake_name, description, price, image, availability)
    VALUES ('$baker_id', '$cake_name', '$description', '$price', '$image', 1)
");

if (!$insertCake) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to add cake: " . mysqli_error($conn)
    ]);
    exit;
}

// Get the newly inserted cake_id
$cake_id = mysqli_insert_id($conn);

// Insert customization options
foreach ($shapes as $shape) {
    $shape = mysqli_real_escape_string($conn, $shape);
    mysqli_query($conn, "INSERT INTO cake_shapes (cake_id, shape) VALUES ('$cake_id', '$shape')");
}

foreach ($colours as $colour) {
    $colour = mysqli_real_escape_string($conn, $colour);
    mysqli_query($conn, "INSERT INTO cake_colours (cake_id, colour) VALUES ('$cake_id', '$colour')");
}

foreach ($flavours as $flavour) {
    $flavour = mysqli_real_escape_string($conn, $flavour);
    mysqli_query($conn, "INSERT INTO cake_flavours (cake_id, flavour) VALUES ('$cake_id', '$flavour')");
}

foreach ($toppings as $topping) {
    $topping = mysqli_real_escape_string($conn, $topping);
    mysqli_query($conn, "INSERT INTO cake_toppings (cake_id, topping) VALUES ('$cake_id', '$topping')");
}

echo json_encode([
    "status" => "success",
    "message" => "Cake added successfully",
    "cake_id" => $cake_id
]);
?>
