<?php
/**
 * Test Notifications API
 * Use this to verify notifications are working correctly
 * Call: test-notifications.php?user_type=customer&user_id=1&title=Test&message=Hello
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "DB connection failed"]);
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_type, user_id)
    )
");

$user_type = $_GET['user_type'] ?? 'customer';
$user_id = intval($_GET['user_id'] ?? 1);
$title = $_GET['title'] ?? 'Test Notification';
$message = $_GET['message'] ?? 'This is a test notification from the server.';
$type = $_GET['type'] ?? 'test';

// Escape
$user_type = mysqli_real_escape_string($conn, $user_type);
$title = mysqli_real_escape_string($conn, $title);
$message = mysqli_real_escape_string($conn, $message);
$type = mysqli_real_escape_string($conn, $type);

// Insert test notification
$sql = "INSERT INTO notifications (user_type, user_id, type, title, message) 
        VALUES ('$user_type', $user_id, '$type', '$title', '$message')";

if (mysqli_query($conn, $sql)) {
    $notification_id = mysqli_insert_id($conn);
    
    // Optional: Send push notification
    $send_push = isset($_GET['push']) && $_GET['push'] == '1';
    $push_result = false;
    
    if ($send_push && file_exists("send-push-notification.php")) {
        include_once "send-push-notification.php";
        
        if (function_exists('getFcmToken') && function_exists('sendPushNotification')) {
            $fcm_token = getFcmToken($conn, $user_type, $user_id);
            if ($fcm_token) {
                $push_result = sendPushNotification($fcm_token, $title, $message, ['type' => $type]);
            }
        }
    }
    
    echo json_encode([
        "status" => "success",
        "message" => "Test notification created",
        "notification_id" => $notification_id,
        "push_sent" => $push_result
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to create notification: " . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
