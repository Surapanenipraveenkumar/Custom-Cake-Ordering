<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Suppress PHP warnings
error_reporting(0);
ini_set('display_errors', 0);

include "db.php";

// Check database connection
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

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

$name = trim($data['name']);
$email = trim($data['email']);
$phone = trim($data['phone'] ?? '');
$address = trim($data['address'] ?? '');
$password = $data['password'];

// Check if email already exists using prepared statement
$check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
if (!$check_stmt) {
    echo json_encode(["status" => "error", "message" => "Query error"]);
    exit;
}

$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Email already registered"
    ]);
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert new user using prepared statement
$insert_stmt = $conn->prepare("INSERT INTO users (name, email, phone, address, password) VALUES (?, ?, ?, ?, ?)");
if (!$insert_stmt) {
    echo json_encode(["status" => "error", "message" => "Insert error: " . $conn->error]);
    exit;
}

$insert_stmt->bind_param("sssss", $name, $email, $phone, $address, $hashed_password);

if ($insert_stmt->execute()) {
    $user_id = $conn->insert_id;
    echo json_encode([
        "status" => "success",
        "message" => "Registration successful",
        "user_id" => $user_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Registration failed: " . $insert_stmt->error
    ]);
}

$conn->close();
?>
