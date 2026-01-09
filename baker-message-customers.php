<?php
header("Content-Type: application/json");

// Enable errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include "db.php";

// Get baker_id from GET parameter
$baker_id = $_GET['baker_id'] ?? null;

if (!$baker_id) {
    echo json_encode([
        "status" => "error",
        "message" => "baker_id required"
    ]);
    exit;
}

$baker_id = mysqli_real_escape_string($conn, $baker_id);

// Check if chat_messages table exists, create if not
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'chat_messages'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
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

// Simpler query - just get unique users who have messaged this baker
$query = mysqli_query($conn, "
    SELECT DISTINCT
        u.user_id,
        u.name
    FROM chat_messages cm
    JOIN users u ON cm.user_id = u.user_id
    WHERE cm.baker_id = '$baker_id'
    GROUP BY u.user_id
");

if (!$query) {
    echo json_encode([
        "status" => "error",
        "message" => "Query failed: " . mysqli_error($conn)
    ]);
    exit;
}

$customers = [];

while ($row = mysqli_fetch_assoc($query)) {
    // Get the last message for this customer
    $lastMsgQuery = mysqli_query($conn, "
        SELECT message, created_at, TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_ago
        FROM chat_messages 
        WHERE baker_id = '$baker_id' AND user_id = '{$row['user_id']}'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    
    $lastMsg = mysqli_fetch_assoc($lastMsgQuery);
    
    // Calculate time ago
    $minutes = (int)($lastMsg['minutes_ago'] ?? 0);
    if ($minutes < 60) {
        $time_ago = $minutes . "m ago";
    } elseif ($minutes < 1440) {
        $time_ago = floor($minutes / 60) . "h ago";
    } else {
        $time_ago = floor($minutes / 1440) . "d ago";
    }
    
    // Truncate last message for preview
    $last_message = $lastMsg['message'] ?? "Sent an image";
    if (strlen($last_message) > 50) {
        $last_message = substr($last_message, 0, 47) . "...";
    }
    
    $customers[] = [
        "user_id" => (int)$row['user_id'],
        "name" => $row['name'] ?? "Customer",
        "profile_image" => "",
        "last_message" => $last_message,
        "last_message_time" => $lastMsg['created_at'] ?? "",
        "time_ago" => $time_ago
    ];
}

echo json_encode([
    "status" => "success",
    "customers" => $customers
]);
?>
