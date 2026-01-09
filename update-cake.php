<?php
header("Content-Type: application/json");
include "db.php";

/* Read JSON input */
$data = json_decode(file_get_contents("php://input"), true);

$cake_id = $data['cake_id'] ?? null;

if (!$cake_id) {
    echo json_encode([
        "status" => "error",
        "message" => "cake_id required"
    ]);
    exit;
}

/* Check cake exists */
$check = mysqli_query($conn, "SELECT image FROM cakes WHERE cake_id='$cake_id'");
if (mysqli_num_rows($check) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Cake not found"
    ]);
    exit;
}

$existing = mysqli_fetch_assoc($check);
$old_image = $existing['image'];

/* Get fields */
$cake_name   = mysqli_real_escape_string($conn, $data['cake_name'] ?? '');
$description = mysqli_real_escape_string($conn, $data['description'] ?? '');
$price       = $data['price'] ?? null;
$availability = isset($data['availability']) ? $data['availability'] : 1;

/* Image (URL) — OPTIONAL */
$image = isset($data['image']) && $data['image'] !== ''
    ? mysqli_real_escape_string($conn, $data['image'])
    : $old_image;

$shapes   = $data['shapes']   ?? [];
$colours  = $data['colours']  ?? [];
$flavours = $data['flavours'] ?? [];
$toppings = $data['toppings'] ?? [];

if (!$cake_name || !$price) {
    echo json_encode([
        "status" => "error",
        "message" => "cake_name and price required"
    ]);
    exit;
}

/* Update cake (INCLUDING image & availability) */
$update = mysqli_query($conn, "
    UPDATE cakes SET
        cake_name    = '$cake_name',
        description  = '$description',
        price        = '$price',
        image        = '$image',
        availability = '$availability'
    WHERE cake_id = '$cake_id'
");

if (!$update) {
    echo json_encode([
        "status" => "error",
        "message" => "Cake update failed"
    ]);
    exit;
}

/* Delete old customization options */
mysqli_query($conn, "DELETE FROM cake_shapes WHERE cake_id='$cake_id'");
mysqli_query($conn, "DELETE FROM cake_colours WHERE cake_id='$cake_id'");
mysqli_query($conn, "DELETE FROM cake_flavours WHERE cake_id='$cake_id'");
mysqli_query($conn, "DELETE FROM cake_toppings WHERE cake_id='$cake_id'");

/* Insert new customization options */
foreach ($shapes as $s) {
    mysqli_query($conn,
        "INSERT INTO cake_shapes (cake_id, shape)
         VALUES ('$cake_id','" . mysqli_real_escape_string($conn, $s) . "')"
    );
}
foreach ($colours as $c) {
    mysqli_query($conn,
        "INSERT INTO cake_colours (cake_id, colour)
         VALUES ('$cake_id','" . mysqli_real_escape_string($conn, $c) . "')"
    );
}
foreach ($flavours as $f) {
    mysqli_query($conn,
        "INSERT INTO cake_flavours (cake_id, flavour)
         VALUES ('$cake_id','" . mysqli_real_escape_string($conn, $f) . "')"
    );
}
foreach ($toppings as $t) {
    mysqli_query($conn,
        "INSERT INTO cake_toppings (cake_id, topping)
         VALUES ('$cake_id','" . mysqli_real_escape_string($conn, $t) . "')"
    );
}

echo json_encode([
    "status" => "success",
    "message" => "Cake updated successfully"
]);
?>
