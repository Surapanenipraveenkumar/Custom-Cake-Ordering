<?php
/**
 * Send Broadcast Notification API
 * Sends push notifications to all users of a specific type or all users
 */
ini_set('display_errors', 0);
error_reporting(0);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include "db.php";
include "send-push-notification.php";

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "DB connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) $data = $_POST;

$title = $data['title'] ?? '';
$message = $data['message'] ?? '';
$user_type = $data['user_type'] ?? 'all'; // all, customer, baker, delivery
$save_notification = $data['save_notification'] ?? true;

if (empty($title) || empty($message)) {
    echo json_encode(["status" => "error", "message" => "title and message required"]);
    exit;
}

// Create notifications table if not exists
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_type VARCHAR(20) NOT NULL,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        order_id INT DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$push_count = 0;
$inapp_count = 0;
$title_escaped = mysqli_real_escape_string($conn, $title);
$message_escaped = mysqli_real_escape_string($conn, $message);

// ========== SEND TO CUSTOMERS ==========
if ($user_type === 'all' || $user_type === 'customer') {
    $result = mysqli_query($conn, "SELECT id, fcm_token FROM users WHERE fcm_token IS NOT NULL AND fcm_token != ''");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Push notification
            if (!empty($row['fcm_token']) && function_exists('sendPushNotification')) {
                if (sendPushNotification($row['fcm_token'], $title, $message, ['type' => 'broadcast'])) {
                    $push_count++;
                }
            }
            
            // In-app notification
            if ($save_notification) {
                mysqli_query($conn, "
                    INSERT INTO notifications (user_type, user_id, type, title, message)
                    VALUES ('customer', {$row['id']}, 'broadcast', '$title_escaped', '$message_escaped')
                ");
                $inapp_count++;
            }
        }
    }
}

// ========== SEND TO BAKERS ==========
if ($user_type === 'all' || $user_type === 'baker') {
    $result = mysqli_query($conn, "SELECT id, fcm_token FROM bakers WHERE fcm_token IS NOT NULL AND fcm_token != ''");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Push notification
            if (!empty($row['fcm_token']) && function_exists('sendPushNotification')) {
                if (sendPushNotification($row['fcm_token'], $title, $message, ['type' => 'broadcast'])) {
                    $push_count++;
                }
            }
            
            // In-app notification
            if ($save_notification) {
                mysqli_query($conn, "
                    INSERT INTO notifications (user_type, user_id, type, title, message)
                    VALUES ('baker', {$row['id']}, 'broadcast', '$title_escaped', '$message_escaped')
                ");
                $inapp_count++;
            }
        }
    }
}

// ========== SEND TO DELIVERY PARTNERS ==========
if ($user_type === 'all' || $user_type === 'delivery') {
    $result = mysqli_query($conn, "SELECT delivery_id, fcm_token FROM delivery_persons WHERE fcm_token IS NOT NULL AND fcm_token != ''");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Push notification
            if (!empty($row['fcm_token']) && function_exists('sendPushNotification')) {
                if (sendPushNotification($row['fcm_token'], $title, $message, ['type' => 'broadcast'])) {
                    $push_count++;
                }
            }
            
            // In-app notification
            if ($save_notification) {
                mysqli_query($conn, "
                    INSERT INTO notifications (user_type, user_id, type, title, message)
                    VALUES ('delivery', {$row['delivery_id']}, 'broadcast', '$title_escaped', '$message_escaped')
                ");
                $inapp_count++;
            }
        }
    }
}

echo json_encode([
    "status" => "success",
    "message" => "Broadcast sent",
    "push_notifications_sent" => $push_count,
    "inapp_notifications_created" => $inapp_count
]);

mysqli_close($conn);
?>
