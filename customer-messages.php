<?php
// Prevent any output before JSON
ob_start();

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// Clear any output buffer
ob_end_clean();

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

// Get distinct bakers the customer has messaged
$query = "
    SELECT DISTINCT 
        b.baker_id,
        b.shop_name,
        b.shop_image,
        (SELECT message FROM messages 
         WHERE (sender_id = $user_id AND receiver_id = b.baker_id) 
            OR (sender_id = b.baker_id AND receiver_id = $user_id)
         ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT DATE_FORMAT(created_at, '%h:%i %p') FROM messages 
         WHERE (sender_id = $user_id AND receiver_id = b.baker_id) 
            OR (sender_id = b.baker_id AND receiver_id = $user_id)
         ORDER BY created_at DESC LIMIT 1) as last_message_time
    FROM messages m
    JOIN bakers b ON (m.sender_id = b.baker_id OR m.receiver_id = b.baker_id)
    WHERE m.sender_id = $user_id OR m.receiver_id = $user_id
    ORDER BY (SELECT MAX(created_at) FROM messages 
              WHERE (sender_id = $user_id AND receiver_id = b.baker_id) 
                 OR (sender_id = b.baker_id AND receiver_id = $user_id)) DESC
";

$result = mysqli_query($conn, $query);

$bakers = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $bakers[] = [
            "baker_id" => (int)$row['baker_id'],
            "shop_name" => $row['shop_name'] ?? "Baker",
            "shop_image" => $row['shop_image'] ?? "",
            "last_message" => $row['last_message'] ?? "",
            "last_message_time" => $row['last_message_time'] ?? ""
        ];
    }
}

echo json_encode([
    "status" => "success",
    "bakers" => $bakers
]);
