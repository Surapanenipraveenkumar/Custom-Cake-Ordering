<?php
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include "db.php";

// Handle both GET and POST requests
$notification_id = $_GET['notification_id'] ?? null;
$mark_all = false;
$user_id = null;
$user_type = null;

// Check for POST data
if (!$notification_id) {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    if ($data) {
        $notification_id = $data['notification_id'] ?? null;
        $mark_all = $data['mark_all'] ?? false;
        $user_id = $data['user_id'] ?? null;
        $user_type = $data['user_type'] ?? null;
    }
}

// Mark all as read for a user
if ($mark_all && $user_id && $user_type) {
    $user_id = intval($user_id);
    $user_type = mysqli_real_escape_string($conn, $user_type);
    
    $update = mysqli_query($conn, "
        UPDATE notifications 
        SET is_read = 1 
        WHERE user_type = '$user_type' AND user_id = $user_id
    ");
    
    echo json_encode([
        "status" => "success",
        "message" => "All notifications marked as read"
    ]);
    exit;
}

// Mark single notification as read
if (!$notification_id) {
    echo json_encode([
        "status" => "error",
        "message" => "notification_id required"
    ]);
    exit;
}

$notification_id = intval($notification_id);

$update = mysqli_query($conn, "
    UPDATE notifications 
    SET is_read = 1 
    WHERE notification_id = $notification_id
");

if ($update) {
    echo json_encode([
        "status" => "success",
        "message" => "Notification marked as read"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update"
    ]);
}
?>
