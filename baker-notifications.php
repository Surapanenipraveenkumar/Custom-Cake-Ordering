<?php
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

if (!$conn) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit;
}

$baker_id = $_GET['baker_id'] ?? null;

if (!$baker_id) {
    echo json_encode([
        "status" => "error",
        "message" => "baker_id required"
    ]);
    exit;
}

$baker_id = intval($baker_id);

// Check if notifications table exists, create if not
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    mysqli_query($conn, "
        CREATE TABLE notifications (
            notification_id INT AUTO_INCREMENT PRIMARY KEY,
            user_type VARCHAR(20) NOT NULL,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT,
            type VARCHAR(50) DEFAULT 'system',
            order_id INT DEFAULT NULL,
            is_read TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

// Get notifications for this baker
$query = mysqli_query($conn, "
    SELECT notification_id, title, message, type, is_read, created_at, order_id
    FROM notifications
    WHERE user_type = 'baker' AND user_id = $baker_id
    ORDER BY created_at DESC
    LIMIT 50
");

$notifications = [];

if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        // Calculate time ago
        $createdAt = strtotime($row['created_at']);
        $now = time();
        $diff = $now - $createdAt;
        
        if ($diff < 60) {
            $timeAgo = "Just now";
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            $timeAgo = $mins . " min ago";
        } elseif ($diff < 86400) {
            $hrs = floor($diff / 3600);
            $timeAgo = $hrs . " hr ago";
        } else {
            $days = floor($diff / 86400);
            $timeAgo = $days . " day" . ($days > 1 ? "s" : "") . " ago";
        }
        
        $notifications[] = [
            "notification_id" => (int)$row['notification_id'],
            "title" => $row['title'] ?? "",
            "message" => $row['message'] ?? "",
            "type" => $row['type'] ?? "system",
            "is_read" => (int)($row['is_read'] ?? 0),
            "created_at" => $row['created_at'] ?? "",
            "order_id" => $row['order_id'] ? (int)$row['order_id'] : null,
            "time_ago" => $timeAgo
        ];
    }
}

// Get unread count
$unreadCount = 0;
$unreadQuery = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM notifications
    WHERE user_type = 'baker' AND user_id = $baker_id AND is_read = 0
");
if ($unreadQuery) {
    $unreadRow = mysqli_fetch_assoc($unreadQuery);
    $unreadCount = (int)($unreadRow['count'] ?? 0);
}

echo json_encode([
    "status" => "success",
    "notifications" => $notifications,
    "unread_count" => $unreadCount
]);
?>
