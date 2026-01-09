<?php
header("Content-Type: application/json");
include "db.php";

/* Read JSON input */
$data = json_decode(file_get_contents("php://input"), true);

$role = $data['role'] ?? null;
$id   = $data['id']   ?? null;

if (!$role || !$id) {
    echo json_encode([
        "status" => "error",
        "message" => "role and id required"
    ]);
    exit;
}

/* ================= CUSTOMER ================= */
if ($role === "customer") {

    $name    = $data['name']    ?? null;
    $email   = $data['email']   ?? null;
    $phone   = $data['phone']   ?? null;
    $address = $data['address'] ?? null;

    if (!$name || !$email || !$phone || !$address) {
        echo json_encode(["status"=>"error","message"=>"Missing fields"]);
        exit;
    }

    mysqli_query($conn, "UPDATE users SET 
        name='$name', email='$email', phone='$phone', address='$address'
        WHERE user_id='$id'");

    echo json_encode([
        "status"=>"success",
        "message"=>"Customer profile updated"
    ]);
    exit;
}

/* ================= BAKER ================= */
if ($role === "baker") {

    $name      = $data['name']      ?? null;
    $shop_name = $data['shop_name'] ?? null;
    $email     = $data['email']     ?? null;
    $phone     = $data['phone']     ?? null;
    $address   = $data['address']   ?? null;

    if (!$name || !$shop_name || !$email || !$phone || !$address) {
        echo json_encode(["status"=>"error","message"=>"Missing fields"]);
        exit;
    }

    mysqli_query($conn, "UPDATE bakers SET 
        name='$name', shop_name='$shop_name', email='$email',
        phone='$phone', address='$address'
        WHERE baker_id='$id'");

    echo json_encode([
        "status"=>"success",
        "message"=>"Baker profile updated"
    ]);
    exit;
}

/* ================= DELIVERY ================= */
if ($role === "delivery") {

    $name    = $data['name']    ?? null;
    $email   = $data['email']   ?? null;
    $phone   = $data['phone']   ?? null;
    $vehicle = $data['vehicle'] ?? null;

    if (!$name || !$email || !$phone || !$vehicle) {
        echo json_encode(["status"=>"error","message"=>"Missing fields"]);
        exit;
    }

    mysqli_query($conn, "UPDATE delivery_persons SET 
        name='$name', email='$email', phone='$phone', vehicle='$vehicle'
        WHERE delivery_id='$id'");

    echo json_encode([
        "status"=>"success",
        "message"=>"Delivery profile updated"
    ]);
    exit;
}

/* ================= INVALID ROLE ================= */
echo json_encode([
    "status"=>"error",
    "message"=>"Invalid role"
]);
?>
