<?php
// customer-messages.php
// Get list of bakers the customer has chatted with or ordered from

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

include "db.php";

$user_id = $_GET['user_id'] ?? $_POST['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "user_id required"
    ]);
    exit;
}

$user_id = intval($user_id);
error_log("customer-messages: user_id=$user_id");

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

$bakers = [];

// First, try to get bakers with chat messages
$chatQuery = mysqli_query($conn, "
    SELECT DISTINCT 
        b.baker_id,
        b.shop_name,
        b.shop_image,
        (SELECT cm.message FROM chat_messages cm
         WHERE cm.user_id = $user_id AND cm.baker_id = b.baker_id
         ORDER BY cm.created_at DESC LIMIT 1) as last_message,
        (SELECT DATE_FORMAT(cm.created_at, '%h:%i %p') FROM chat_messages cm
         WHERE cm.user_id = $user_id AND cm.baker_id = b.baker_id
         ORDER BY cm.created_at DESC LIMIT 1) as last_message_time
    FROM chat_messages m
    JOIN bakers b ON m.baker_id = b.baker_id
    WHERE m.user_id = $user_id
    GROUP BY b.baker_id, b.shop_name, b.shop_image
    ORDER BY (SELECT MAX(created_at) FROM chat_messages 
              WHERE user_id = $user_id AND baker_id = b.baker_id) DESC
");

if ($chatQuery) {
    while ($row = mysqli_fetch_assoc($chatQuery)) {
        $bakers[] = [
            "baker_id" => (int)$row['baker_id'],
            "shop_name" => $row['shop_name'] ?? "Baker",
            "shop_image" => $row['shop_image'] ?? "",
            "last_message" => $row['last_message'] ?? "Click to open chat",
            "last_message_time" => $row['last_message_time'] ?? ""
        ];
    }
}

error_log("customer-messages: Found " . count($bakers) . " bakers from chat");

// If no chat messages, get bakers from orders
if (empty($bakers)) {
    error_log("customer-messages: No chats found, checking orders...");
    
    $ordersQuery = mysqli_query($conn, "
        SELECT DISTINCT 
            b.baker_id, 
            b.shop_name, 
            b.shop_image,
            o.created_at as order_date
        FROM orders o
        JOIN bakers b ON o.baker_id = b.baker_id
        WHERE o.user_id = $user_id
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
            
            $bakers[] = [
                "baker_id" => (int)$row['baker_id'],
                "shop_name" => $row['shop_name'] ?? "Baker",
                "shop_image" => $row['shop_image'] ?? "",
                "last_message" => "Click to open chat",
                "last_message_time" => $timeAgo
            ];
        }
        error_log("customer-messages: Found " . count($bakers) . " bakers from orders");
    } else {
        error_log("customer-messages: Orders query error: " . mysqli_error($conn));
    }
}

echo json_encode([
    "status" => "success",
    "bakers" => $bakers
]);

mysqli_close($conn);
?>
