<?php
// send-chat-message.php - Send a chat message

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include "db.php";

// Create table if not exists
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS chat_messages (
        message_id INT AUTO_INCREMENT PRIMARY KEY,
        baker_id INT NOT NULL,
        user_id INT NOT NULL,
        sender_type ENUM('customer', 'baker') NOT NULL,
        message TEXT,
        image_url VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_baker_user (baker_id, user_id)
    )
");

// Get POST data
$baker_id = $_POST['baker_id'] ?? null;
$user_id = $_POST['user_id'] ?? null;
$sender_type = $_POST['sender_type'] ?? null;
$message = $_POST['message'] ?? null;
$image_url = $_POST['image_url'] ?? null;

// Log for debugging
error_log("send-chat-message: baker_id=$baker_id, user_id=$user_id, sender_type=$sender_type, message=$message");

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
$baker_id = intval($baker_id);
$user_id = intval($user_id);
$sender_type = mysqli_real_escape_string($conn, $sender_type);
$message = $message ? mysqli_real_escape_string($conn, $message) : null;
$image_url = $image_url ? mysqli_real_escape_string($conn, $image_url) : null;

// Build query
$msg_val = $message ? "'$message'" : "NULL";
$img_val = $image_url ? "'$image_url'" : "NULL";

$sql = "INSERT INTO chat_messages (baker_id, user_id, sender_type, message, image_url, created_at)
        VALUES ($baker_id, $user_id, '$sender_type', $msg_val, $img_val, NOW())";

$result = mysqli_query($conn, $sql);

if ($result) {
    echo json_encode([
        "status" => "success",
        "message" => "Message sent",
        "message_id" => mysqli_insert_id($conn)
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to send: " . mysqli_error($conn)
    ]);
}
?>
