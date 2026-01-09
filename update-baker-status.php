<?php
// API to update baker online/offline status
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

include "db.php";

// Get parameters
$baker_id = isset($_GET['baker_id']) ? intval($_GET['baker_id']) : (isset($_POST['baker_id']) ? intval($_POST['baker_id']) : 0);
$is_online = isset($_GET['is_online']) ? intval($_GET['is_online']) : (isset($_POST['is_online']) ? intval($_POST['is_online']) : -1);

if ($baker_id <= 0) {
    echo json_encode(array(
        "status" => "error",
        "message" => "baker_id required"
    ));
    exit;
}

if ($is_online < 0) {
    echo json_encode(array(
        "status" => "error",
        "message" => "is_online required (0 or 1)"
    ));
    exit;
}

// Update baker online status
$sql = "UPDATE bakers SET is_online = $is_online WHERE baker_id = $baker_id";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_affected_rows($conn) >= 0) {
    $statusText = $is_online == 1 ? "online" : "offline";
    echo json_encode(array(
        "status" => "success",
        "message" => "Baker is now $statusText",
        "is_online" => $is_online == 1
    ));
} else {
    echo json_encode(array(
        "status" => "error",
        "message" => "Failed to update status: " . mysqli_error($conn)
    ));
}
?>
