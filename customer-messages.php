<?php
// customer-messages.php
// Get list of bakers the customer has chatted with

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

// Get distinct bakers the customer has messaged
$query = "
    SELECT DISTINCT 
        b.baker_id,
        b.shop_name,
        b.shop_image,
        (SELECT cm.message FROM chat_messages cm
         WHERE cm.user_id = $user_id AND cm.baker_id = b.baker_id
         ORDER BY cm.created_at DESC LIMIT 1) as last_message,
        (SELECT DATE_FORMAT(cm.created_at, '%h:%i %p') FROM chat_messages cm
         WHERE cm.user_id = $user_id AND cm.baker_id = b.baker_id
         ORDER BY cm.created_at DESC LIMIT 1) as last_message_time,
        (SELECT cm.created_at FROM chat_messages cm
         WHERE cm.user_id = $user_id AND cm.baker_id = b.baker_id
         ORDER BY cm.created_at DESC LIMIT 1) as last_message_date
    FROM chat_messages m
    JOIN bakers b ON m.baker_id = b.baker_id
    WHERE m.user_id = $user_id
    GROUP BY b.baker_id, b.shop_name, b.shop_image
    ORDER BY last_message_date DESC
";

$result = mysqli_query($conn, $query);

$bakers = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $bakers[] = [
            "baker_id" => (int)$row['baker_id'],
            "shop_name" => $row['shop_name'] ?? "Baker",
            "shop_image" => $row['shop_image'] ?? "",
            "last_message" => $row['last_message'] ?? "No messages yet",
            "last_message_time" => $row['last_message_time'] ?? ""
        ];
    }
} else {
    error_log("customer-messages.php error: " . mysqli_error($conn));
}

// If no chat messages, also include bakers from orders (so customer can start chat)
if (empty($bakers)) {
    $ordersQuery = mysqli_query($conn, "
        SELECT DISTINCT b.baker_id, b.shop_name, b.shop_image
        FROM orders o
        JOIN bakers b ON o.baker_id = b.baker_id
        WHERE o.user_id = $user_id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    
    if ($ordersQuery) {
        while ($row = mysqli_fetch_assoc($ordersQuery)) {
            $bakers[] = [
                "baker_id" => (int)$row['baker_id'],
                "shop_name" => $row['shop_name'] ?? "Baker",
                "shop_image" => $row['shop_image'] ?? "",
                "last_message" => "Start a conversation",
                "last_message_time" => ""
            ];
        }
    }
}

echo json_encode([
    "status" => "success",
    "bakers" => $bakers
]);

mysqli_close($conn);
?>
