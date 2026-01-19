<?php
/**
 * Debug script to check delivery notification system
 * Access via: your-server/debug-delivery-notifications.php
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "DB connection failed"]);
    exit;
}

$debug_info = [];

// 1. Check which delivery table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'delivery_partners'");
$delivery_table = ($table_check && mysqli_num_rows($table_check) > 0) ? 'delivery_partners' : 'delivery_persons';
$debug_info['delivery_table'] = $delivery_table;

// 2. Check total delivery partners
$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM $delivery_table");
$total_row = mysqli_fetch_assoc($total_query);
$debug_info['total_delivery_partners'] = (int)$total_row['total'];

// 3. Check online delivery partners
$online_query = mysqli_query($conn, "SELECT delivery_id, name, is_online, fcm_token FROM $delivery_table");
$delivery_partners = [];
$online_count = 0;
while ($row = mysqli_fetch_assoc($online_query)) {
    $delivery_partners[] = [
        'delivery_id' => (int)$row['delivery_id'],
        'name' => $row['name'],
        'is_online' => (int)$row['is_online'],
        'has_fcm_token' => !empty($row['fcm_token'])
    ];
    if ($row['is_online'] == 1) $online_count++;
}
$debug_info['online_delivery_partners_count'] = $online_count;
$debug_info['delivery_partners'] = $delivery_partners;

// 4. Check recent delivery notifications
$notif_query = mysqli_query($conn, "
    SELECT notification_id, user_type, user_id, type, title, order_id, is_read, created_at 
    FROM notifications 
    WHERE user_type = 'delivery' 
    ORDER BY created_at DESC 
    LIMIT 20
");
$notifications = [];
while ($row = mysqli_fetch_assoc($notif_query)) {
    $notifications[] = $row;
}
$debug_info['recent_delivery_notifications_count'] = count($notifications);
$debug_info['recent_delivery_notifications'] = $notifications;

// 5. Check orders ready for delivery
$orders_query = mysqli_query($conn, "
    SELECT order_id, status, delivery_status, ready_for_delivery, ready_for_delivery_at, delivery_id 
    FROM orders 
    WHERE ready_for_delivery = 1 
    ORDER BY ready_for_delivery_at DESC 
    LIMIT 10
");
$orders = [];
while ($row = mysqli_fetch_assoc($orders_query)) {
    $orders[] = $row;
}
$debug_info['orders_ready_for_delivery_count'] = count($orders);
$debug_info['orders_ready_for_delivery'] = $orders;

// 6. Check if is_online column exists in delivery table
$col_check = mysqli_query($conn, "SHOW COLUMNS FROM $delivery_table LIKE 'is_online'");
$debug_info['is_online_column_exists'] = ($col_check && mysqli_num_rows($col_check) > 0);

echo json_encode([
    "status" => "success",
    "debug_info" => $debug_info
], JSON_PRETTY_PRINT);

mysqli_close($conn);
?>
