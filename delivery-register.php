<?php
header("Content-Type: application/json");
include "db.php";

/* Accept JSON or form data */
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    $data = $_POST;
}

/* Required fields */
$required = ['name','phone','vehicle','email','password','cpassword'];
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
$phone = mysqli_real_escape_string($conn, $data['phone']);
$vehicle = mysqli_real_escape_string($conn, $data['vehicle']);
$email = mysqli_real_escape_string($conn, $data['email']);
$password = $data['password'];
$cpassword = $data['cpassword'];

/* Password match */
if ($password !== $cpassword) {
    echo json_encode([
        "status" => "error",
        "message" => "Passwords do not match"
    ]);
    exit;
}

/* Email exists check */
$check = mysqli_query($conn, "SELECT delivery_id FROM delivery_persons WHERE email='$email'");
if (mysqli_num_rows($check) > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Email already exists"
    ]);
    exit;
}

/* Hash password */
$hash = password_hash($password, PASSWORD_DEFAULT);

/* Insert delivery person */
$sql = "INSERT INTO delivery_persons (name, phone, vehicle, email, password)
        VALUES ('$name','$phone','$vehicle','$email','$hash')";

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        "status" => "success",
        "message" => "Delivery person registered successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Registration failed"
    ]);
}
?>
