<?php
header("Content-Type: application/json");
include "db.php";

// Accept JSON or form data
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    $data = $_POST;
}

// Required fields
if (empty($data['email']) || empty($data['password'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Email and password are required"
    ]);
    exit;
}

$email = mysqli_real_escape_string($conn, $data['email']);
$password = $data['password'];

// Check if delivery_persons table exists, create if not
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'delivery_persons'");
if (mysqli_num_rows($tableCheck) == 0) {
    $createTable = "
        CREATE TABLE IF NOT EXISTS `delivery_persons` (
            `delivery_id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `phone` VARCHAR(20) NOT NULL,
            `vehicle` VARCHAR(50) NOT NULL,
            `email` VARCHAR(100) UNIQUE NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `is_online` TINYINT(1) DEFAULT 0,
            `latitude` DECIMAL(10, 8),
            `longitude` DECIMAL(11, 8),
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
    mysqli_query($conn, $createTable);
}

// Find delivery person by email
$query = mysqli_query($conn, "SELECT * FROM delivery_persons WHERE email='$email'");

if (mysqli_num_rows($query) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email or password"
    ]);
    exit;
}

$delivery = mysqli_fetch_assoc($query);

// Verify password
if (!password_verify($password, $delivery['password'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email or password"
    ]);
    exit;
}

// Success - return delivery person info
echo json_encode([
    "status" => "success",
    "delivery_id" => (int)$delivery['delivery_id'],
    "name" => $delivery['name'],
    "phone" => $delivery['phone'],
    "vehicle" => $delivery['vehicle'],
    "email" => $delivery['email']
]);
?>
