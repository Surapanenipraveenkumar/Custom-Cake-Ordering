<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

// Check if chat_messages table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'chat_messages'");
if (!$table_check || mysqli_num_rows($table_check) == 0) {
    echo json_encode([
        "status" => "error", 
        "message" => "chat_messages table does not exist!",
        "fix" => "Run the SQL to create the table"
    ]);
    exit;
}

// Get all messages from chat_messages
$messages = mysqli_query($conn, "SELECT * FROM chat_messages ORDER BY created_at DESC LIMIT 20");

$data = [];
if ($messages) {
    while ($row = mysqli_fetch_assoc($messages)) {
        $data[] = $row;
    }
}

// Get column info
$columns = mysqli_query($conn, "SHOW COLUMNS FROM chat_messages");
$cols = [];
while ($col = mysqli_fetch_assoc($columns)) {
    $cols[] = $col['Field'];
}

// Count total messages
$count = mysqli_query($conn, "SELECT COUNT(*) as total FROM chat_messages");
$total = mysqli_fetch_assoc($count)['total'];

echo json_encode([
    "status" => "success",
    "table_exists" => true,
    "columns" => $cols,
    "total_messages" => (int)$total,
    "recent_messages" => $data
], JSON_PRETTY_PRINT);
?>
