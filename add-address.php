<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
include "db.php";

/* Read JSON */
$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;
$label = $data['label'] ?? 'Home';
$full_address = $data['full_address'] ?? null;
$pincode = $data['pincode'] ?? '';
$landmark = $data['landmark'] ?? '';
$phone = $data['phone'] ?? '';
$is_default = $data['is_default'] ?? 0;

if (!$user_id || !$full_address) {
    echo json_encode([
        "status" => "error",
        "message" => "user_id and full_address are required"
    ]);
    exit;
}

// If setting as default, unset other defaults first
if ($is_default) {
    mysqli_query($conn, "UPDATE addresses SET is_default = 0 WHERE user_id = '$user_id'");
}

// Insert new address
$sql = "INSERT INTO addresses (user_id, label, full_address, pincode, landmark, phone, is_default) 
        VALUES ('$user_id', '$label', '$full_address', '$pincode', '$landmark', '$phone', '$is_default')";

if (mysqli_query($conn, $sql)) {
    $address_id = mysqli_insert_id($conn);
    echo json_encode([
        "status" => "success",
        "message" => "Address added successfully",
        "address_id" => $address_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to add address: " . mysqli_error($conn)
    ]);
}
?>
