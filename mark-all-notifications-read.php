<?php
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json");
include "db.php";

$baker_id = $_GET['baker_id'] ?? null;

if (!$baker_id) {
    echo json_encode([
        "status" => "error",
        "message" => "baker_id required"
    ]);
    exit;
}

$baker_id = mysqli_real_escape_string($conn, $baker_id);

$update = mysqli_query($conn, "
    UPDATE notifications 
    SET is_read = 1 
    WHERE user_type = 'baker' AND user_id = '$baker_id' AND is_read = 0
");

echo json_encode([
    "status" => "success",
    "message" => "All notifications marked as read"
]);
?>
