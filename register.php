<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include "db.php";

// Accept JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    $data = $_POST;
}

// Check required fields
if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Name, email and password are required"
    ]);
    exit;
}

$name = mysqli_real_escape_string($conn, $data['name']);
$email = mysqli_real_escape_string($conn, $data['email']);
$phone = mysqli_real_escape_string($conn, $data['phone'] ?? '');
$address = mysqli_real_escape_string($conn, $data['address'] ?? '');
$password = $data['password'];

// Email already exists check
$check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'");
if ($check && mysqli_num_rows($check) > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Email already exists"
    ]);
    exit;
}

// Hash password
$hash = password_hash($password, PASSWORD_DEFAULT);

// Insert customer
$sql = "INSERT INTO users (name, email, phone, address, password) 
        VALUES ('$name', '$email', '$phone', '$address', '$hash')";

if (mysqli_query($conn, $sql)) {
    $user_id = mysqli_insert_id($conn);
    echo json_encode([
        "status" => "success",
        "message" => "Registration successful",
        "user_id" => $user_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Registration failed: " . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
