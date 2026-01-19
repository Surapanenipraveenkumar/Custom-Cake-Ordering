<?php
// test-chat.php - Test if chat messages are being saved

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

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

// Check how many messages exist
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM chat_messages");
$count = 0;
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $count = $row['total'];
}

// Get last 5 messages
$messages = [];
$msgs = mysqli_query($conn, "SELECT * FROM chat_messages ORDER BY created_at DESC LIMIT 5");
if ($msgs) {
    while ($row = mysqli_fetch_assoc($msgs)) {
        $messages[] = $row;
    }
}

echo json_encode([
    "status" => "success",
    "total_messages" => $count,
    "last_5_messages" => $messages,
    "table_exists" => true
], JSON_PRETTY_PRINT);
?>
