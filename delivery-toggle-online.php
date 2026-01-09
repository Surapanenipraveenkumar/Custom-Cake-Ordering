<?php
header("Content-Type: application/json");
include "db.php";

$delivery_id = $_GET['delivery_id'] ?? null;
$is_online = $_GET['is_online'] ?? null;

if (!$delivery_id || $is_online === null) {
    echo json_encode([
        "status" => "error",
        "message" => "delivery_id and is_online required"
    ]);
    exit;
}

$delivery_id = mysqli_real_escape_string($conn, $delivery_id);
$is_online = (int)$is_online;

$result = mysqli_query($conn, "
    UPDATE delivery_persons 
    SET is_online = '$is_online' 
    WHERE delivery_id = '$delivery_id'
");

if ($result) {
    echo json_encode([
        "status" => "success",
        "message" => $is_online ? "You are now online" : "You are now offline",
        "is_online" => $is_online
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Update failed"
    ]);
}
?>
