<?php
// baker-message-customers.php - Get customers baker has chatted with

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

$baker_id = isset($_GET['baker_id']) ? intval($_GET['baker_id']) : 0;

if ($baker_id <= 0) {
    echo json_encode(["status" => "error", "message" => "baker_id required"]);
    exit;
}

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

// Get customers with chat history
$query = "
    SELECT DISTINCT u.user_id, u.name, u.profile_image,
        (SELECT message FROM chat_messages WHERE baker_id = $baker_id AND user_id = u.user_id ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM chat_messages WHERE baker_id = $baker_id AND user_id = u.user_id ORDER BY created_at DESC LIMIT 1) as last_time
    FROM chat_messages cm
    JOIN users u ON cm.user_id = u.user_id
    WHERE cm.baker_id = $baker_id
    GROUP BY u.user_id
    ORDER BY last_time DESC
";

$result = mysqli_query($conn, $query);
$customers = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $timeAgo = "2m ago";
        if ($row['last_time']) {
            $diff = time() - strtotime($row['last_time']);
            if ($diff < 3600) $timeAgo = floor($diff/60) . "m ago";
            elseif ($diff < 86400) $timeAgo = floor($diff/3600) . "h ago";
            else $timeAgo = floor($diff/86400) . "d ago";
        }
        
        $customers[] = [
            "user_id" => (int)$row['user_id'],
            "name" => $row['name'] ?? "Customer",
            "profile_image" => $row['profile_image'] ?? "",
            "last_message" => $row['last_message'] ?? "Click to open chat",
            "last_message_time" => $timeAgo,
            "time_ago" => $timeAgo
        ];
    }
}

echo json_encode(["status" => "success", "customers" => $customers]);
?>
