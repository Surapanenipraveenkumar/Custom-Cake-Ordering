<?php
// debug-customer-messages.php - Debug version to see what's happening

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 1;

// Check chat_messages table
$chat_check = mysqli_query($conn, "SELECT DISTINCT baker_id FROM chat_messages WHERE user_id = $user_id");
$chat_baker_ids = [];
if ($chat_check) {
    while ($row = mysqli_fetch_assoc($chat_check)) {
        $chat_baker_ids[] = $row['baker_id'];
    }
}

// Check if these bakers exist
$bakers_exist = [];
foreach ($chat_baker_ids as $bid) {
    $check = mysqli_query($conn, "SELECT baker_id, shop_name FROM bakers WHERE baker_id = $bid");
    if ($check && $row = mysqli_fetch_assoc($check)) {
        $bakers_exist[] = $row;
    }
}

// The main query
$query = mysqli_query($conn, "
    SELECT DISTINCT b.baker_id, b.shop_name, b.profile_image,
        (SELECT message FROM chat_messages WHERE user_id = $user_id AND baker_id = b.baker_id ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM chat_messages WHERE user_id = $user_id AND baker_id = b.baker_id ORDER BY created_at DESC LIMIT 1) as last_time
    FROM chat_messages cm
    JOIN bakers b ON cm.baker_id = b.baker_id
    WHERE cm.user_id = $user_id
    GROUP BY b.baker_id
    ORDER BY last_time DESC
");

$query_error = mysqli_error($conn);

$bakers = [];
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $bakers[] = $row;
    }
}

echo json_encode([
    "user_id" => $user_id,
    "chat_baker_ids" => $chat_baker_ids,
    "bakers_in_db" => $bakers_exist,
    "query_error" => $query_error,
    "final_result" => $bakers
], JSON_PRETTY_PRINT);
?>
