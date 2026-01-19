<?php
// send-push-notification.php
// Firebase Cloud Messaging HTTP v1 API implementation

include_once __DIR__ . '/firebase-config.php';

/**
 * Get FCM token from database for a specific user
 * 
 * @param mysqli $conn Database connection
 * @param string $userType User type: 'customer', 'baker', or 'delivery'
 * @param int $userId User ID
 * @return string|null FCM token or null if not found
 */
function getFcmToken($conn, $userType, $userId) {
    switch ($userType) {
        case 'customer':
            // Try user_id first, then id column
            $query = mysqli_query($conn, "SELECT fcm_token FROM users WHERE user_id = $userId AND fcm_token IS NOT NULL AND fcm_token != ''");
            if (!$query || mysqli_num_rows($query) == 0) {
                $query = mysqli_query($conn, "SELECT fcm_token FROM users WHERE id = $userId AND fcm_token IS NOT NULL AND fcm_token != ''");
            }
            break;
        case 'baker':
            $query = mysqli_query($conn, "SELECT fcm_token FROM bakers WHERE baker_id = $userId AND fcm_token IS NOT NULL AND fcm_token != ''");
            break;
        case 'delivery':
            // Try delivery_partners first, then delivery_persons
            $query = mysqli_query($conn, "SELECT fcm_token FROM delivery_partners WHERE delivery_id = $userId AND fcm_token IS NOT NULL AND fcm_token != ''");
            if (!$query || mysqli_num_rows($query) == 0) {
                $query = mysqli_query($conn, "SELECT fcm_token FROM delivery_persons WHERE delivery_id = $userId AND fcm_token IS NOT NULL AND fcm_token != ''");
            }
            break;
        default:
            return null;
    }
    
    if ($query && $row = mysqli_fetch_assoc($query)) {
        return $row['fcm_token'];
    }
    
    return null;
}

/**
 * Send push notification via Firebase Cloud Messaging HTTP v1 API
 * 
 * @param string $token FCM device token
 * @param string $title Notification title
 * @param string $body Notification body
 * @param array $data Additional data payload
 * @return bool Success status
 */
function sendPushNotification($token, $title, $body, $data = []) {
    if (empty($token)) {
        error_log("FCM: Empty token provided");
        return false;
    }
    
    $accessToken = getAccessToken();
    if (!$accessToken) {
        error_log("FCM: Failed to get access token");
        return false;
    }
    
    $projectId = FIREBASE_PROJECT_ID;
    $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";
    
    // Build message payload
    $message = [
        'message' => [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body
            ],
            'data' => array_map('strval', $data), // Ensure all values are strings
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'channel_id' => $data['type'] ?? 'orders_channel'
                ]
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1
                    ]
                ]
            ]
        ]
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($message),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if (!empty($curlError)) {
        error_log("FCM: cURL error: $curlError");
        return false;
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        error_log("FCM: Notification sent successfully to " . substr($token, 0, 20) . "...");
        return true;
    }
    
    error_log("FCM: Failed to send notification. HTTP $httpCode: $response");
    return false;
}

/**
 * Send notification to all online delivery partners
 * 
 * @param mysqli $conn Database connection
 * @param string $title Notification title
 * @param string $body Notification body
 * @param array $data Additional data
 * @return int Number of notifications sent
 */
function sendToAllDeliveryPartners($conn, $title, $body, $data = []) {
    $sent = 0;
    
    // Check which table exists
    $check = mysqli_query($conn, "SHOW TABLES LIKE 'delivery_partners'");
    $table = ($check && mysqli_num_rows($check) > 0) ? 'delivery_partners' : 'delivery_persons';
    
    $query = mysqli_query($conn, "SELECT fcm_token FROM $table WHERE is_online = 1 AND fcm_token IS NOT NULL AND fcm_token != ''");
    
    if ($query) {
        while ($row = mysqli_fetch_assoc($query)) {
            if (sendPushNotification($row['fcm_token'], $title, $body, $data)) {
                $sent++;
            }
        }
    }
    
    return $sent;
}
?>
