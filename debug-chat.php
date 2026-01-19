<?php
// debug-chat.php - Debug chat messages table

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

// Create table if not exists
$createResult = mysqli_query($conn, "
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

// Check if table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'chat_messages'");
$tableExists = mysqli_num_rows($tableCheck) > 0;

// Count messages
$countResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM chat_messages");
$total = 0;
if ($countResult) {
    $row = mysqli_fetch_assoc($countResult);
    $total = $row['total'];
}

// Get all messages
$allMessages = [];
$allResult = mysqli_query($conn, "SELECT * FROM chat_messages ORDER BY created_at DESC LIMIT 10");
if ($allResult) {
    while ($row = mysqli_fetch_assoc($allResult)) {
        $allMessages[] = $row;
    }
}

// Insert a test message
$testInsert = mysqli_query($conn, "
    INSERT INTO chat_messages (baker_id, user_id, sender_type, message, created_at)
    VALUES (1, 1, 'customer', 'Test message from debug script', NOW())
");

$insertSuccess = $testInsert ? true : false;
$insertError = mysqli_error($conn);
$newId = mysqli_insert_id($conn);

echo json_encode([
    "table_exists" => $tableExists,
    "table_created" => $createResult ? true : false,
    "total_messages_before" => $total,
    "test_insert_success" => $insertSuccess,
    "test_insert_error" => $insertError,
    "new_message_id" => $newId,
    "last_10_messages" => $allMessages
], JSON_PRETTY_PRINT);
?>
