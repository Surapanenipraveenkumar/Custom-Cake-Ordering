<?php
header("Content-Type: application/json");
include "db.php";

/* Accept JSON or Form Data */
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    $data = $_POST;
}

/* Check required fields */
$required = ['name','email','phone','address','password','cpassword'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode([
            "status" => "error",
            "message" => "$field is required"
        ]);
        exit;
    }
}

$name = mysqli_real_escape_string($conn, $data['name']);
$email = mysqli_real_escape_string($conn, $data['email']);
$phone = mysqli_real_escape_string($conn, $data['phone']);
$address = mysqli_real_escape_string($conn, $data['address']);
$password = $data['password'];
$cpassword = $data['cpassword'];

/* Password match check */
if ($password !== $cpassword) {
    echo json_encode([
        "status" => "error",
        "message" => "Passwords do not match"
    ]);
    exit;
}

/* Email already exists */
$check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'");
if (mysqli_num_rows($check) > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Email already exists"
    ]);
    exit;
}

/* Hash password */
$hash = password_hash($password, PASSWORD_DEFAULT);

/* Insert customer */
$sql = "INSERT INTO users (name, email, phone, address, password)
        VALUES ('$name','$email','$phone','$address','$hash')";

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        "status" => "success",
        "message" => "Customer registered successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Registration failed"
    ]);
}
?>
