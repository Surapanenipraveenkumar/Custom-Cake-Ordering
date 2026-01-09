<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

include "db.php";

/* ---------- GET cake_id ---------- */
$cake_id = $_GET['cake_id'] ?? null;

if (!$cake_id) {
    echo json_encode([
        "status" => "error",
        "message" => "cake_id required"
    ]);
    exit;
}

/* ---------- CHECK CAKE EXISTS ---------- */
$check = mysqli_query($conn, "SELECT cake_id FROM cakes WHERE cake_id='$cake_id' AND availability=1");
if (mysqli_num_rows($check) === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Cake not found or unavailable"
    ]);
    exit;
}

/* ---------- FETCH CUSTOMIZATION OPTIONS ---------- */
$shapes = array_column(
    mysqli_fetch_all(
        mysqli_query($conn, "SELECT shape FROM cake_shapes WHERE cake_id='$cake_id'"),
        MYSQLI_ASSOC
    ),
    'shape'
);

$colours = array_column(
    mysqli_fetch_all(
        mysqli_query($conn, "SELECT colour FROM cake_colours WHERE cake_id='$cake_id'"),
        MYSQLI_ASSOC
    ),
    'colour'
);

$flavours = array_column(
    mysqli_fetch_all(
        mysqli_query($conn, "SELECT flavour FROM cake_flavours WHERE cake_id='$cake_id'"),
        MYSQLI_ASSOC
    ),
    'flavour'
);

$toppings = array_column(
    mysqli_fetch_all(
        mysqli_query($conn, "SELECT topping FROM cake_toppings WHERE cake_id='$cake_id'"),
        MYSQLI_ASSOC
    ),
    'topping'
);

/* ---------- RESPONSE ---------- */
echo json_encode([
    "status" => "success",
    "cake_id" => $cake_id,
    "customization_options" => [
        "shapes"   => $shapes,
        "colours"  => $colours,
        "flavours" => $flavours,
        "toppings" => $toppings
    ]
]);
exit;
?>
