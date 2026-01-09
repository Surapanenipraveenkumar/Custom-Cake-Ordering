<?php
header("Content-Type: application/json");
include "db.php";

/* Accept JSON or form data */
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    $data = $_POST;
}

/* Required fields */
$required = ['name','shop_name','phone','email','address','password','cpassword'];
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
$shop_name = mysqli_real_escape_string($conn, $data['shop_name']);
$phone = mysqli_real_escape_string($conn, $data['phone']);
$email = mysqli_real_escape_string($conn, $data['email']);
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

/* Email check */
$check = mysqli_query($conn, "SELECT baker_id FROM bakers WHERE email='$email'");
if (mysqli_num_rows($check) > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Email already exists"
    ]);
    exit;
}

/* Hash password */
$hash = password_hash($password, PASSWORD_DEFAULT);

/* Insert baker */
$sql = "INSERT INTO bakers (name, shop_name, phone, email, address, password)
        VALUES ('$name','$shop_name','$phone','$email','$address','$hash')";

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        "status" => "success",
        "message" => "Baker registered successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Registration failed"
    ]);
}
?>
