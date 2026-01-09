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
$cake['cake_id'] = (int)$cake['cake_id'];
$cake['price'] = (int)$cake['price'];
$cake['availability'] = (int)$cake['availability'];

// Fetch shapes
$shapes = [];
$shapesQuery = mysqli_query($conn, "SELECT shape FROM cake_shapes WHERE cake_id = '$cake_id'");
while ($row = mysqli_fetch_assoc($shapesQuery)) {
    $shapes[] = $row['shape'];
}

// Fetch colours
$colours = [];
$coloursQuery = mysqli_query($conn, "SELECT colour FROM cake_colours WHERE cake_id = '$cake_id'");
while ($row = mysqli_fetch_assoc($coloursQuery)) {
    $colours[] = $row['colour'];
}

// Fetch flavours
$flavours = [];
$flavoursQuery = mysqli_query($conn, "SELECT flavour FROM cake_flavours WHERE cake_id = '$cake_id'");
while ($row = mysqli_fetch_assoc($flavoursQuery)) {
    $flavours[] = $row['flavour'];
}

// Fetch toppings
$toppings = [];
$toppingsQuery = mysqli_query($conn, "SELECT topping FROM cake_toppings WHERE cake_id = '$cake_id'");
while ($row = mysqli_fetch_assoc($toppingsQuery)) {
    $toppings[] = $row['topping'];
}

// Add customization options to cake object
$cake['shapes'] = $shapes;
$cake['colours'] = $colours;
$cake['flavours'] = $flavours;
$cake['toppings'] = $toppings;

echo json_encode([
    "status" => "success",
    "cake" => $cake
]);
?>
