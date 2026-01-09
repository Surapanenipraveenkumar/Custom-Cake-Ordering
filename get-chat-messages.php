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

// Get parameters
$baker_id = $_GET['baker_id'] ?? null;
$user_id = $_GET['user_id'] ?? null;

if (!$baker_id || !$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "baker_id and user_id required"
    ]);
    exit;
}

// Escape input
$baker_id = mysqli_real_escape_string($conn, $baker_id);
$user_id = mysqli_real_escape_string($conn, $user_id);

// Fetch chat messages between baker and user
$query = mysqli_query($conn, "
    SELECT 
        message_id,
        sender_type,
        message,
        image_url,
        created_at,
        DATE_FORMAT(created_at, '%H:%i') as time
    FROM chat_messages
    WHERE baker_id = '$baker_id' AND user_id = '$user_id'
    ORDER BY created_at ASC
");

if (!$query) {
    echo json_encode([
        "status" => "success",
        "messages" => [],
        "debug" => "Query failed: " . mysqli_error($conn)
    ]);
    exit;
}

$messages = [];

while ($row = mysqli_fetch_assoc($query)) {
    $messages[] = [
        "message_id" => (int)$row['message_id'],
        "sender_type" => $row['sender_type'],
        "message" => $row['message'],
        "image_url" => $row['image_url'],
        "created_at" => $row['created_at'],
        "time" => $row['time']
    ];
}

echo json_encode([
    "status" => "success",
    "messages" => $messages
]);
?>
