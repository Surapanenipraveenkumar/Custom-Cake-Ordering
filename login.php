<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
// Suppress PHP warnings
error_reporting(0);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include "db.php";

// Check if connection was successful (db.php handles error output usually, but let's be safe)
if (!isset($conn) || $conn->connect_error) {
    // db.php might have already output error and exited, but if not:
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if ($email == "" || $password == "") {
    echo json_encode([
        "status" => "error",
        "message" => "Email and password required"
    ]);
    exit;
}

/* CUSTOMER LOGIN */
$stmt = $conn->prepare("SELECT user_id, name, password FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 1) {
    $row = $res->fetch_assoc();

    // ✅ password_verify MUST match password_hash from register.php
    if (password_verify($password, $row['password'])) {
        echo json_encode([
            "status" => "success",
            "message" => "Login successful",
            "user_id" => (int)$row['user_id'],
            "name" => $row['name']
        ]);
        exit;
    }
}

echo json_encode([
    "status" => "error",
    "message" => "Invalid email or password"
]);
exit;
?>
