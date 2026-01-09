<?php
// Suppress PHP warnings/notices from being output
error_reporting(0);
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode([
        "status" => "error",
        "message" => "Email and password required"
    ]);
    exit;
}

// Escape input
$email = mysqli_real_escape_string($conn, $email);

$q = mysqli_query($conn,
    "SELECT baker_id, password, shop_name FROM bakers WHERE email='$email'"
);

if ($q && mysqli_num_rows($q) == 1) {
    $row = mysqli_fetch_assoc($q);
    $stored_password = $row['password'];
    
    // Check password - support both MD5 and password_hash formats
    $password_valid = false;
    
    // First try MD5 (for accounts created with new registration)
    if ($stored_password === md5($password)) {
        $password_valid = true;
    }
    // Then try password_verify (for accounts created with password_hash)
    else if (password_verify($password, $stored_password)) {
        $password_valid = true;
    }
    // Also try plain text comparison (for old accounts)
    else if ($stored_password === $password) {
        $password_valid = true;
    }
    
    if ($password_valid) {
        echo json_encode([
            "status" => "success",
            "baker_id" => (int)$row['baker_id'],
            "shop_name" => $row['shop_name'] ?? "Baker"
        ]);
        exit;
    }
}

echo json_encode([
    "status" => "error",
    "message" => "Invalid email or password"
]);
?>
