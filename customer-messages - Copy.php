<?php
// customer-messages.php - Get bakers customer has chatted with
// FIXED VERSION - simpler query that works

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    echo json_encode(["status" => "error", "message" => "user_id required"]);
    exit;
}

// First get distinct baker_ids from chat_messages
$baker_ids_query = mysqli_query($conn, "
    SELECT DISTINCT baker_id 
    FROM chat_messages 
    WHERE user_id = $user_id
");

$bakers = [];

if ($baker_ids_query && mysqli_num_rows($baker_ids_query) > 0) {
    while ($row = mysqli_fetch_assoc($baker_ids_query)) {
        $baker_id = (int)$row['baker_id'];
        
        // Get baker details
        $baker_query = mysqli_query($conn, "SELECT baker_id, shop_name, profile_image FROM bakers WHERE baker_id = $baker_id");
        
        $shop_name = "Baker #$baker_id";
        $shop_image = "";
        
        if ($baker_query && mysqli_num_rows($baker_query) > 0) {
            $baker_data = mysqli_fetch_assoc($baker_query);
            $shop_name = $baker_data['shop_name'] ?? $shop_name;
            $shop_image = $baker_data['profile_image'] ?? "";
        }
        
        // Get last message
        $last_msg_query = mysqli_query($conn, "
            SELECT message, created_at 
            FROM chat_messages 
            WHERE user_id = $user_id AND baker_id = $baker_id 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        
        $last_message = "Click to open chat";
        $last_time = "";
        $timeAgo = "";
        
        if ($last_msg_query && mysqli_num_rows($last_msg_query) > 0) {
            $msg_data = mysqli_fetch_assoc($last_msg_query);
            $last_message = $msg_data['message'] ?? "Click to open chat";
            $last_time = $msg_data['created_at'];
            
            if ($last_time) {
                $diff = time() - strtotime($last_time);
                if ($diff < 60) $timeAgo = "Just now";
                elseif ($diff < 3600) $timeAgo = floor($diff/60) . "m ago";
                elseif ($diff < 86400) $timeAgo = floor($diff/3600) . "h ago";
                else $timeAgo = floor($diff/86400) . "d ago";
            }
        }
        
        $bakers[] = [
            "baker_id" => $baker_id,
            "shop_name" => $shop_name,
            "shop_image" => $shop_image,
            "last_message" => $last_message,
            "last_message_time" => $timeAgo
        ];
    }
}

echo json_encode(["status" => "success", "bakers" => $bakers]);
?>
