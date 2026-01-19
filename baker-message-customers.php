<?php
// baker-message-customers.php
// Get list of customers who have chatted with or ordered from a baker

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

include "db.php";

$baker_id = $_GET['baker_id'] ?? $_POST['baker_id'] ?? null;

if (!$baker_id) {
    echo json_encode([
        "status" => "error",
        "message" => "baker_id required"
    ]);
    exit;
}

$baker_id = intval($baker_id);
error_log("baker-message-customers: baker_id=$baker_id");

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

$customers = [];

// First, try to get customers with chat messages
$chatQuery = mysqli_query($conn, "
    SELECT DISTINCT 
        u.user_id,
        u.name,
        u.profile_image,
        (SELECT cm.message FROM chat_messages cm
         WHERE cm.baker_id = $baker_id AND cm.user_id = u.user_id
         ORDER BY cm.created_at DESC LIMIT 1) as last_message,
        (SELECT DATE_FORMAT(cm.created_at, '%h:%i %p') FROM chat_messages cm
         WHERE cm.baker_id = $baker_id AND cm.user_id = u.user_id
         ORDER BY cm.created_at DESC LIMIT 1) as last_message_time,
        (SELECT cm.created_at FROM chat_messages cm
         WHERE cm.baker_id = $baker_id AND cm.user_id = u.user_id
         ORDER BY cm.created_at DESC LIMIT 1) as last_active
    FROM chat_messages m
    JOIN users u ON m.user_id = u.user_id
    WHERE m.baker_id = $baker_id
    GROUP BY u.user_id, u.name, u.profile_image
    ORDER BY last_active DESC
");

if ($chatQuery) {
    while ($row = mysqli_fetch_assoc($chatQuery)) {
        // Calculate time ago
        $lastActive = strtotime($row['last_active']);
        $diff = time() - $lastActive;
        
        if ($diff < 60) {
            $timeAgo = "Just now";
        } elseif ($diff < 3600) {
            $timeAgo = floor($diff / 60) . "m ago";
        } elseif ($diff < 86400) {
            $timeAgo = floor($diff / 3600) . "h ago";
        } else {
            $timeAgo = floor($diff / 86400) . "d ago";
        }
        
        $customers[] = [
            "user_id" => (int)$row['user_id'],
            "name" => $row['name'] ?? "Customer",
            "profile_image" => $row['profile_image'] ?? "",
            "last_message" => $row['last_message'] ?? "Click to open chat",
            "last_message_time" => $row['last_message_time'] ?? "",
            "time_ago" => $timeAgo
        ];
    }
}

error_log("baker-message-customers: Found " . count($customers) . " customers from chat");

// If no chat messages, get customers from orders
if (empty($customers)) {
    error_log("baker-message-customers: No chats found, checking orders...");
    
    $ordersQuery = mysqli_query($conn, "
        SELECT DISTINCT 
            u.user_id, 
            u.name, 
            u.profile_image,
            o.created_at as order_date
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.baker_id = $baker_id
        ORDER BY o.created_at DESC
        LIMIT 20
    ");
    
    if ($ordersQuery) {
        while ($row = mysqli_fetch_assoc($ordersQuery)) {
            // Calculate time ago
            $orderTime = strtotime($row['order_date']);
            $diff = time() - $orderTime;
            
            if ($diff < 60) {
                $timeAgo = "Just now";
            } elseif ($diff < 3600) {
                $timeAgo = floor($diff / 60) . "m ago";
            } elseif ($diff < 86400) {
                $timeAgo = floor($diff / 3600) . "h ago";
            } else {
                $timeAgo = floor($diff / 86400) . "d ago";
            }
            
            $customers[] = [
                "user_id" => (int)$row['user_id'],
                "name" => $row['name'] ?? "Customer",
                "profile_image" => $row['profile_image'] ?? "",
                "last_message" => "Click to open chat",
                "last_message_time" => $timeAgo,
                "time_ago" => $timeAgo
            ];
        }
        error_log("baker-message-customers: Found " . count($customers) . " customers from orders");
    } else {
        error_log("baker-message-customers: Orders query error: " . mysqli_error($conn));
    }
}

echo json_encode([
    "status" => "success",
    "customers" => $customers
]);

mysqli_close($conn);
?>
