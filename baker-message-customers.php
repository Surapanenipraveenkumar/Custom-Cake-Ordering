<?php
// baker-message-customers.php
// Get list of customers who have ACTUALLY chatted with this baker

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

include "db.php";

$baker_id = isset($_GET['baker_id']) ? intval($_GET['baker_id']) : 0;

if ($baker_id <= 0) {
    echo json_encode(["status" => "error", "message" => "baker_id required"]);
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

// Get customers that have actually chatted with this baker
$query = "
    SELECT 
        u.user_id,
        u.name,
        u.profile_image,
        cm.message as last_message,
        cm.created_at as last_time
    FROM (
        SELECT user_id, MAX(message_id) as max_id
        FROM chat_messages 
        WHERE baker_id = $baker_id
        GROUP BY user_id
    ) latest
    INNER JOIN chat_messages cm ON cm.message_id = latest.max_id
    INNER JOIN users u ON u.user_id = latest.user_id
    ORDER BY cm.created_at DESC
";

$result = mysqli_query($conn, $query);

$customers = [];
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
            "user_id" => (int)$row['user_id'],
            "name" => $row['name'] ?? "Customer",
            "profile_image" => $row['profile_image'] ?? "",
            "last_message" => $row['last_message'] ?? "Click to open chat",
            "last_message_time" => $timeAgo,
            "time_ago" => $timeAgo
        ];
    }
}

echo json_encode([
    "status" => "success",
    "customers" => $customers
]);

mysqli_close($conn);
?>
