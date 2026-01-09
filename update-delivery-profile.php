<?php
header("Content-Type: application/json");
include "db.php";

// Accept JSON or form data
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    $data = $_POST;
}

$delivery_id = $data['delivery_id'] ?? null;
$name = $data['name'] ?? null;
$phone = $data['phone'] ?? null;
$vehicle = $data['vehicle'] ?? null;
$vehicle_number = $data['vehicle_number'] ?? null;
$service_area = $data['service_area'] ?? null;

if (!$delivery_id) {
    echo json_encode([
        "status" => "error",
        "message" => "delivery_id is required"
    ]);
    exit;
}

$delivery_id = mysqli_real_escape_string($conn, $delivery_id);

// Check if delivery person exists
$check = mysqli_query($conn, "SELECT * FROM delivery_persons WHERE delivery_id = '$delivery_id'");
if (mysqli_num_rows($check) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Delivery person not found"
    ]);
    exit;
}

// Add service_area and vehicle_number columns if not exist
mysqli_query($conn, "ALTER TABLE delivery_persons ADD COLUMN IF NOT EXISTS service_area VARCHAR(255) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE delivery_persons ADD COLUMN IF NOT EXISTS vehicle_number VARCHAR(50) DEFAULT NULL");

// Build update query
$updates = [];
if ($name) {
    $name = mysqli_real_escape_string($conn, $name);
    $updates[] = "name = '$name'";
}
if ($phone) {
    $phone = mysqli_real_escape_string($conn, $phone);
    $updates[] = "phone = '$phone'";
}
if ($vehicle) {
    $vehicle = mysqli_real_escape_string($conn, $vehicle);
    $updates[] = "vehicle = '$vehicle'";
}
if ($vehicle_number !== null) {
    $vehicle_number = mysqli_real_escape_string($conn, $vehicle_number);
    $updates[] = "vehicle_number = '$vehicle_number'";
}
if ($service_area !== null) {
    $service_area = mysqli_real_escape_string($conn, $service_area);
    $updates[] = "service_area = '$service_area'";
}

if (empty($updates)) {
    echo json_encode([
        "status" => "error",
        "message" => "No fields to update"
    ]);
    exit;
}

$updateQuery = "UPDATE delivery_persons SET " . implode(", ", $updates) . " WHERE delivery_id = '$delivery_id'";
$result = mysqli_query($conn, $updateQuery);

if ($result) {
    echo json_encode([
        "status" => "success",
        "message" => "Profile updated successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Update failed: " . mysqli_error($conn)
    ]);
}
?>
