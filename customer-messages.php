<?php
// customer-messages.php
// Get list of bakers the customer has ACTUALLY chatted with

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

include "db.php";

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    echo json_encode(["status" => "error", "message" => "user_id required"]);
    exit;
}

// Create chat_messages table if it doesn't exist
mysqli_query($conn, "
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
    )
");

// Get bakers that this customer has actually chatted with
$query = "
    SELECT 
        b.baker_id,
        b.shop_name,
        b.shop_image,
        cm.message as last_message,
        cm.created_at as last_time
    FROM (
        SELECT baker_id, MAX(message_id) as max_id
        FROM chat_messages 
        WHERE user_id = $user_id
        GROUP BY baker_id
    ) latest
    INNER JOIN chat_messages cm ON cm.message_id = latest.max_id
    INNER JOIN bakers b ON b.baker_id = latest.baker_id
    ORDER BY cm.created_at DESC
";

$result = mysqli_query($conn, $query);

$bakers = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Calculate time ago
        $msgTime = strtotime($row['last_time']);
        $diff = time() - $msgTime;
        
        if ($diff < 60) {
            $timeAgo = "Just now";
        } elseif ($diff < 3600) {
            $timeAgo = floor($diff / 60) . "m ago";
        } elseif ($diff < 86400) {
            $timeAgo = floor($diff / 3600) . "h ago";
        } else {
            $timeAgo = floor($diff / 86400) . "d ago";
        }
        
        $bakers[] = [
            "baker_id" => (int)$row['baker_id'],
            "shop_name" => $row['shop_name'] ?? "Baker",
            "shop_image" => $row['shop_image'] ?? "",
            "last_message" => $row['last_message'] ?? "Click to open chat",
            "last_message_time" => $timeAgo
        ];
    }
}

echo json_encode([
    "status" => "success",
    "bakers" => $bakers
]);

mysqli_close($conn);
?>
