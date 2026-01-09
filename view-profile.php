<?php
header("Content-Type: application/json");
include "db.php";

/* Read JSON or GET */
$data = json_decode(file_get_contents("php://input"), true);

$role = $data['role'] ?? $_GET['role'] ?? null;
$id   = $data['id']   ?? $_GET['id']   ?? null;

if (!$role || !$id) {
    echo json_encode([
        "status" => "error",
        "message" => "role and id required"
    ]);
    exit;
}

/* CUSTOMER PROFILE */
if ($role === "customer") {

    $q = mysqli_query($conn, "SELECT user_id,name,email,phone,address,created_at 
                              FROM users WHERE user_id='$id'");

    if (mysqli_num_rows($q) == 0) {
        echo json_encode(["status"=>"error","message"=>"Customer not found"]);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "role" => "customer",
        "profile" => mysqli_fetch_assoc($q)
    ]);
    exit;
}

/* BAKER PROFILE */
if ($role === "baker") {

    $q = mysqli_query($conn, "SELECT baker_id,name,shop_name,email,phone,address,created_at 
                              FROM bakers WHERE baker_id='$id'");

    if (mysqli_num_rows($q) == 0) {
        echo json_encode(["status"=>"error","message"=>"Baker not found"]);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "role" => "baker",
        "profile" => mysqli_fetch_assoc($q)
    ]);
    exit;
}

/* DELIVERY PROFILE */
if ($role === "delivery") {

    $q = mysqli_query($conn, "SELECT delivery_id,name,email,phone,vehicle,created_at 
                              FROM delivery_persons WHERE delivery_id='$id'");

    if (mysqli_num_rows($q) == 0) {
        echo json_encode(["status"=>"error","message"=>"Delivery person not found"]);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "role" => "delivery",
        "profile" => mysqli_fetch_assoc($q)
    ]);
    exit;
}

/* Invalid role */
echo json_encode([
    "status" => "error",
    "message" => "Invalid role"
]);
?>
