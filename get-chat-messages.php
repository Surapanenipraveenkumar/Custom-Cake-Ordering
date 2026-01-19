<?php
// get-chat-messages.php - Get chat messages between baker and customer

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

$baker_id = isset($_GET['baker_id']) ? intval($_GET['baker_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($baker_id <= 0 || $user_id <= 0) {
    echo json_encode(["status" => "error", "message" => "baker_id and user_id required"]);
    exit;
}

$query = mysqli_query($conn, "
    SELECT message_id, sender_type, message, image_url, created_at,
           DATE_FORMAT(created_at, '%H:%i') as time
    FROM chat_messages
    WHERE baker_id = $baker_id AND user_id = $user_id
    ORDER BY created_at ASC
");

$messages = [];
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $messages[] = [
            "message_id" => (int)$row['message_id'],
            "sender_type" => $row['sender_type'],
            "message" => $row['message'],
            "image_url" => $row['image_url'],
            "created_at" => $row['created_at'],
            "time" => $row['time']
        ];
    }
}

echo json_encode(["status" => "success", "messages" => $messages]);
?>
