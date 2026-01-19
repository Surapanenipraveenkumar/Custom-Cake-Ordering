<?php
// customer-messages.php - Get bakers customer has chatted with

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    echo json_encode(["status" => "error", "message" => "user_id required"]);
    exit;
}

$query = mysqli_query($conn, "
    SELECT DISTINCT b.baker_id, b.shop_name, b.shop_image,
        (SELECT message FROM chat_messages WHERE user_id = $user_id AND baker_id = b.baker_id ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM chat_messages WHERE user_id = $user_id AND baker_id = b.baker_id ORDER BY created_at DESC LIMIT 1) as last_time
    FROM chat_messages cm
    JOIN bakers b ON cm.baker_id = b.baker_id
    WHERE cm.user_id = $user_id
    GROUP BY b.baker_id
    ORDER BY last_time DESC
");

$bakers = [];
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $timeAgo = "";
        if ($row['last_time']) {
            $diff = time() - strtotime($row['last_time']);
            if ($diff < 60) $timeAgo = "Just now";
            elseif ($diff < 3600) $timeAgo = floor($diff/60) . "m ago";
            elseif ($diff < 86400) $timeAgo = floor($diff/3600) . "h ago";
            else $timeAgo = floor($diff/86400) . "d ago";
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

echo json_encode(["status" => "success", "bakers" => $bakers]);
?>