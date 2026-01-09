<?php
header("Content-Type: application/json");
include "db.php";

// Get cake_id from GET parameter (Android uses @GET with @Query)
$cake_id = $_GET['cake_id'] ?? null;

if (!$cake_id) {
    echo json_encode([
        "status" => "error",
        "message" => "cake_id required"
    ]);
    exit;
}

// Check if cake exists
$check = mysqli_query($conn, "SELECT cake_id, image FROM cakes WHERE cake_id = '$cake_id'");
if (mysqli_num_rows($check) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Cake not found"
    ]);
    exit;
}

$row = mysqli_fetch_assoc($check);

// Delete from cart first (foreign key constraint)
mysqli_query($conn, "DELETE FROM cart WHERE cake_id = '$cake_id'");

// Update order references to NULL (preserve order history)
mysqli_query($conn, "UPDATE order_items SET cake_id = NULL WHERE cake_id = '$cake_id'");
mysqli_query($conn, "UPDATE orders SET cake_id = NULL WHERE cake_id = '$cake_id'");

// Delete customization options
mysqli_query($conn, "DELETE FROM cake_shapes WHERE cake_id = '$cake_id'");
mysqli_query($conn, "DELETE FROM cake_colours WHERE cake_id = '$cake_id'");
mysqli_query($conn, "DELETE FROM cake_flavours WHERE cake_id = '$cake_id'");
mysqli_query($conn, "DELETE FROM cake_toppings WHERE cake_id = '$cake_id'");

// Delete image file if exists
if ($row && !empty($row['image'])) {
    $imagePath = "uploads/" . $row['image'];
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
}

// Delete cake
$result = mysqli_query($conn, "DELETE FROM cakes WHERE cake_id = '$cake_id'");

if ($result) {
    echo json_encode([
        "status" => "success",
        "message" => "Cake deleted successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete cake: " . mysqli_error($conn)
    ]);
}
?>
