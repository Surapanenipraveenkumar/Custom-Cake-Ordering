<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'db.php';

// Get JSON input
$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit;
}

$baker_id = intval($data['baker_id'] ?? 0);

if ($baker_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Baker ID required"]);
    exit;
}

// Get fields to update
$shop_name = mysqli_real_escape_string($conn, $data['shop_name'] ?? '');
$owner_name = mysqli_real_escape_string($conn, $data['owner_name'] ?? '');
$email = mysqli_real_escape_string($conn, $data['email'] ?? '');
$phone = mysqli_real_escape_string($conn, $data['phone'] ?? '');
$address = mysqli_real_escape_string($conn, $data['address'] ?? '');
$specialty = mysqli_real_escape_string($conn, $data['specialty'] ?? '');
$description = mysqli_real_escape_string($conn, $data['description'] ?? '');
$latitude = floatval($data['latitude'] ?? 0);
$longitude = floatval($data['longitude'] ?? 0);

// Validate required fields
if (empty($shop_name)) {
    echo json_encode(["status" => "error", "message" => "Shop name is required"]);
    exit;
}

if (empty($owner_name)) {
    echo json_encode(["status" => "error", "message" => "Owner name is required"]);
    exit;
}

if (empty($phone)) {
    echo json_encode(["status" => "error", "message" => "Phone is required"]);
    exit;
}

// Update baker profile with location
$sql = "UPDATE bakers SET 
    shop_name = '$shop_name',
    owner_name = '$owner_name',
    email = '$email',
    phone = '$phone',
    address = '$address',
    specialty = '$specialty',
    description = '$description',
    latitude = $latitude,
    longitude = $longitude
    WHERE baker_id = $baker_id";

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        "status" => "success",
        "message" => "Profile updated successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update profile: " . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
