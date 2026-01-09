<?php
// Suppress PHP warnings/notices from being output
error_reporting(0);
header("Content-Type: application/json");
include "db.php";

// Check if chat_messages table exists, create if not
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'chat_messages'");
if (mysqli_num_rows($tableCheck) == 0) {
    // Create the table
    $createTable = "
        CREATE TABLE IF NOT EXISTS `chat_messages` (
            `message_id` INT AUTO_INCREMENT PRIMARY KEY,
            `baker_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `sender_type` ENUM('customer', 'baker') NOT NULL,
            `message` TEXT,
            `image_url` VARCHAR(255),
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_baker_user` (`baker_id`, `user_id`),
            INDEX `idx_created` (`created_at`)
        )";
    mysqli_query($conn, $createTable);
}

// Get POST data
$baker_id = $_POST['baker_id'] ?? null;
$user_id = $_POST['user_id'] ?? null;
$sender_type = $_POST['sender_type'] ?? null;
$message = $_POST['message'] ?? null;
$image_url = $_POST['image_url'] ?? null;

if (!$baker_id || !$user_id || !$sender_type) {
    echo json_encode([
        "status" => "error",
        "message" => "baker_id, user_id, and sender_type required"
    ]);
    exit;
}

if (empty($message) && empty($image_url)) {
    echo json_encode([
        "status" => "error",
        "message" => "message or image_url required"
    ]);
    exit;
}

// Escape input
$baker_id = mysqli_real_escape_string($conn, $baker_id);
$user_id = mysqli_real_escape_string($conn, $user_id);
$sender_type = mysqli_real_escape_string($conn, $sender_type);
$message = $message ? mysqli_real_escape_string($conn, $message) : null;
$image_url = $image_url ? mysqli_real_escape_string($conn, $image_url) : null;

// Insert message
$msg_val = $message ? "'$message'" : "NULL";
$img_val = $image_url ? "'$image_url'" : "NULL";

$sql = "INSERT INTO chat_messages (baker_id, user_id, sender_type, message, image_url, created_at)
    VALUES ('$baker_id', '$user_id', '$sender_type', $msg_val, $img_val, NOW())";

$result = mysqli_query($conn, $sql);

if ($result) {
    echo json_encode([
        "status" => "success",
        "message" => "Message sent"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to send message: " . mysqli_error($conn)
    ]);
}
?>
