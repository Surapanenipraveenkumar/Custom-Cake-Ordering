<?php
// Suppress PHP warnings/notices from being output
error_reporting(0);
header("Content-Type: application/json");
include "db.php";

/* Read JSON input */
$data = json_decode(file_get_contents("php://input"), true);

/* Accept baker_id from JSON or GET - optional now */
$baker_id = $data['baker_id'] ?? $_GET['baker_id'] ?? null;

$cakes = [];

// If baker_id is provided, get cakes for that baker only; otherwise get all cakes
if ($baker_id) {
    $q = mysqli_query($conn, "
        SELECT c.*, b.shop_name as baker_name 
        FROM cakes c 
        LEFT JOIN bakers b ON c.baker_id = b.baker_id 
        WHERE c.baker_id='$baker_id' 
        ORDER BY c.cake_id DESC
    ");
} else {
    // Get all cakes from all bakers
    $q = mysqli_query($conn, "
        SELECT c.*, b.shop_name as baker_name 
        FROM cakes c 
        LEFT JOIN bakers b ON c.baker_id = b.baker_id 
        ORDER BY c.cake_id DESC
    ");
}

while ($cake = mysqli_fetch_assoc($q)) {

    $cake_id = $cake['cake_id'];

    // If baker_name is null, use 'Unknown Baker'
    $cake['baker'] = $cake['baker_name'] ?? 'Unknown Baker';

    $cake['shapes'] = array_column(
        mysqli_fetch_all(
            mysqli_query($conn, "SELECT shape FROM cake_shapes WHERE cake_id='$cake_id'"),
            MYSQLI_ASSOC
        ),
        'shape'
    );

    $cake['colours'] = array_column(
        mysqli_fetch_all(
            mysqli_query($conn, "SELECT colour FROM cake_colours WHERE cake_id='$cake_id'"),
            MYSQLI_ASSOC
        ),
        'colour'
    );

    $cake['flavours'] = array_column(
        mysqli_fetch_all(
            mysqli_query($conn, "SELECT flavour FROM cake_flavours WHERE cake_id='$cake_id'"),
            MYSQLI_ASSOC
        ),
        'flavour'
    );

    $cake['toppings'] = array_column(
        mysqli_fetch_all(
            mysqli_query($conn, "SELECT topping FROM cake_toppings WHERE cake_id='$cake_id'"),
            MYSQLI_ASSOC
        ),
        'topping'
    );

    $cakes[] = $cake;
}

echo json_encode([
    "status" => "success",
    "data" => $cakes
]);
?>
