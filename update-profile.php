<?php
// Prevent any output before JSON
ob_start();

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// Clear any output buffer
ob_end_clean();

include "db.php";

// Read JSON or POST
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? $_POST['user_id'] ?? null;
$name = $data['name'] ?? $_POST['name'] ?? null;
$email = $data['email'] ?? $_POST['email'] ?? null;
$phone = $data['phone'] ?? $_POST['phone'] ?? null;
$address = $data['address'] ?? $_POST['address'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "user_id required"
    ]);
    exit;
}

if (empty($name) || empty($email)) {
    echo json_encode([
        "status" => "error",
        "message" => "Name and email are required"
    ]);
    exit;
}

$user_id = intval($user_id);
$name = mysqli_real_escape_string($conn, $name);
$email = mysqli_real_escape_string($conn, $email);
$phone = mysqli_real_escape_string($conn, $phone ?? '');
$address = mysqli_real_escape_string($conn, $address ?? '');

// Check if email already exists for another user
$checkEmail = mysqli_query($conn, "SELECT user_id FROM users WHERE email = '$email' AND user_id != $user_id");
if ($checkEmail && mysqli_num_rows($checkEmail) > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Email already in use by another account"
    ]);
    exit;
}

// Update user profile
$updateQuery = "UPDATE users SET 
    name = '$name',
    email = '$email',
    phone = '$phone',
    address = '$address'
    WHERE user_id = $user_id";

$result = mysqli_query($conn, $updateQuery);

if (!$result) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update profile: " . mysqli_error($conn)
    ]);
    exit;
}

if (mysqli_affected_rows($conn) >= 0) {
    echo json_encode([
        "status" => "success",
        "message" => "Profile updated successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "No changes made"
    ]);
}
