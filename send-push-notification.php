<?php
/**
 * Firebase Cloud Messaging (FCM) v1 API Push Notification Helper
 * Uses service account authentication for sending push notifications
 */

// Include Firebase configuration
require_once 'firebase-config.php';

/**
 * Get OAuth2 access token for FCM v1 API
 */
function getAccessToken() {
    // Create JWT header (URL-safe base64)
    $header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])));
    
    // Create JWT claims (URL-safe base64)
    $now = time();
    $claims = [
        'iss' => FIREBASE_CLIENT_EMAIL,
        'sub' => FIREBASE_CLIENT_EMAIL,
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging'
    ];
    $payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($claims)));
    
    // Create signature input (already URL-safe)
    $signatureInput = $header . '.' . $payload;
    
    $privateKey = openssl_pkey_get_private(FIREBASE_PRIVATE_KEY);
    if (!$privateKey) {
        error_log("FCM: Invalid private key");
        return null;
    }
    
    openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $signatureB64 = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    $jwt = $signatureInput . '.' . $signatureB64;
    
    // Exchange JWT for access token
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://oauth2.googleapis.com/token',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("FCM: Token request failed: " . $response);
        return null;
    }
    
    $tokenData = json_decode($response, true);
    return $tokenData['access_token'] ?? null;
}

/**
 * Send push notification via FCM v1 API
 * 
 * @param string $fcmToken Device FCM token
 * @param string $title Notification title
 * @param string $body Notification body
 * @param array $data Additional data payload
 * @return bool Success status
 */
function sendPushNotification($fcmToken, $title, $body, $data = []) {
    if (empty($fcmToken)) {
        error_log("FCM: Empty token");
        return false;
    }
    
    $accessToken = getAccessToken();
    if (!$accessToken) {
        error_log("FCM: Could not get access token");
        return false;
    }
    
    $message = [
        'message' => [
            'token' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body' => $body
            ],
            'android' => [
                'notification' => [
                    'icon' => 'ic_notification',
                    'color' => '#EC4899',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'channel_id' => 'cake_connect_channel'
                ],
                'priority' => 'high'
            ],
            'data' => array_merge($data, [
                'title' => $title,
                'body' => $body,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ])
        ]
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://fcm.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID . '/messages:send',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($message),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        error_log("FCM: Push sent successfully to token: " . substr($fcmToken, 0, 20) . "...");
        return true;
    } else {
        error_log("FCM: Push failed ($httpCode): " . $response);
        return false;
    }
}

/**
 * Get FCM token for a user
 */
function getFcmToken($conn, $userType, $userId) {
    $userId = intval($userId);
    
    switch ($userType) {
        case 'customer':
            $result = mysqli_query($conn, "SELECT fcm_token FROM users WHERE user_id = $userId");
            break;
        case 'baker':
            $result = mysqli_query($conn, "SELECT fcm_token FROM bakers WHERE baker_id = $userId");
            break;
        case 'delivery':
            // Check which delivery table exists
            $check_partners = mysqli_query($conn, "SHOW TABLES LIKE 'delivery_partners'");
            if ($check_partners && mysqli_num_rows($check_partners) > 0) {
                $result = mysqli_query($conn, "SELECT fcm_token FROM delivery_partners WHERE delivery_id = $userId");
            } else {
                $result = mysqli_query($conn, "SELECT fcm_token FROM delivery_persons WHERE delivery_id = $userId");
            }
            break;
        default:
            return null;
    }
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['fcm_token'] ?? null;
    }
    return null;
}

/**
 * Notify baker about new order
 */
function notifyBakerNewOrder($conn, $bakerId, $orderId, $customerName) {
    $fcmToken = getFcmToken($conn, 'baker', $bakerId);
    if ($fcmToken) {
        return sendPushNotification(
            $fcmToken,
            "New Order! 🎂",
            "You have a new order #$orderId from $customerName",
            ['order_id' => strval($orderId), 'type' => 'new_order']
        );
    }
    return false;
}

/**
 * Notify customer about order status update
 */
function notifyCustomerOrderStatus($conn, $userId, $orderId, $status) {
    $fcmToken = getFcmToken($conn, 'customer', $userId);
    if ($fcmToken) {
        $titles = [
            'confirmed' => 'Order Confirmed! ✅',
            'preparing' => 'Baking in Progress! 🍳',
            'ready' => 'Order Ready! 📦',
            'out_for_delivery' => 'Out for Delivery! 🚗',
            'delivered' => 'Order Delivered! 🎉',
            'cancelled' => 'Order Cancelled ❌'
        ];
        
        $messages = [
            'confirmed' => "Your order #$orderId has been confirmed by the baker!",
            'preparing' => "Your order #$orderId is being prepared!",
            'ready' => "Your order #$orderId is ready for pickup/delivery!",
            'out_for_delivery' => "Your order #$orderId is on its way!",
            'delivered' => "Your order #$orderId has been delivered!",
            'cancelled' => "Your order #$orderId has been cancelled."
        ];
        
        $title = $titles[$status] ?? "Order Update";
        $message = $messages[$status] ?? "Your order #$orderId status: $status";
        
        return sendPushNotification(
            $fcmToken,
            $title,
            $message,
            ['order_id' => strval($orderId), 'type' => 'order_status', 'status' => $status]
        );
    }
    return false;
}

/**
 * Notify customer about delivery update
 */
function notifyCustomerDeliveryUpdate($conn, $userId, $orderId, $action) {
    $fcmToken = getFcmToken($conn, 'customer', $userId);
    if ($fcmToken) {
        $titles = [
            'assigned' => 'Delivery Assigned! 🛵',
            'picked_up' => 'Order Picked Up! 📦',
            'delivered' => 'Order Delivered! 🎉'
        ];
        
        $messages = [
            'assigned' => "A delivery partner has been assigned to your order #$orderId",
            'picked_up' => "Your order #$orderId has been picked up and is on the way!",
            'delivered' => "Your order #$orderId has been delivered. Enjoy!"
        ];
        
        $title = $titles[$action] ?? "Delivery Update";
        $message = $messages[$action] ?? "Delivery update for order #$orderId";
        
        return sendPushNotification(
            $fcmToken,
            $title,
            $message,
            ['order_id' => strval($orderId), 'type' => 'delivery_update', 'action' => $action]
        );
    }
    return false;
}

/**
 * Send push notification to all users of a specific type
 */
function sendToAllUsers($conn, $userType, $title, $body, $data = []) {
    $count = 0;
    
    switch ($userType) {
        case 'customer':
            $result = mysqli_query($conn, "SELECT user_id as id, fcm_token FROM users WHERE fcm_token IS NOT NULL AND fcm_token != ''");
            break;
        case 'baker':
            $result = mysqli_query($conn, "SELECT baker_id as id, fcm_token FROM bakers WHERE fcm_token IS NOT NULL AND fcm_token != ''");
            break;
        case 'delivery':
            // Check which delivery table exists
            $check_partners = mysqli_query($conn, "SHOW TABLES LIKE 'delivery_partners'");
            if ($check_partners && mysqli_num_rows($check_partners) > 0) {
                $result = mysqli_query($conn, "SELECT delivery_id as id, fcm_token FROM delivery_partners WHERE fcm_token IS NOT NULL AND fcm_token != ''");
            } else {
                $result = mysqli_query($conn, "SELECT delivery_id as id, fcm_token FROM delivery_persons WHERE fcm_token IS NOT NULL AND fcm_token != ''");
            }
            break;
        case 'all':
            // Send to all user types
            $count += sendToAllUsers($conn, 'customer', $title, $body, $data);
            $count += sendToAllUsers($conn, 'baker', $title, $body, $data);
            $count += sendToAllUsers($conn, 'delivery', $title, $body, $data);
            return $count;
        default:
            return 0;
    }
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            if (sendPushNotification($row['fcm_token'], $title, $body, $data)) {
                $count++;
            }
        }
    }
    
    return $count;
}

/**
 * Notify all online delivery partners about a new delivery order
 * @param mysqli $conn Database connection
 * @param int $orderId Order ID
 * @param string $shopName Baker's shop name
 * @return array Count of in-app and FCM notifications sent
 */
function notifyNewDeliveryOrder($conn, $orderId, $shopName) {
    $results = ['in_app' => 0, 'fcm' => 0];
    
    // Check which delivery table exists
    $check_partners = mysqli_query($conn, "SHOW TABLES LIKE 'delivery_partners'");
    $delivery_table = ($check_partners && mysqli_num_rows($check_partners) > 0) ? 'delivery_partners' : 'delivery_persons';
    
    // Get all online delivery partners
    $result = mysqli_query($conn, "SELECT delivery_id, name, fcm_token FROM $delivery_table WHERE is_online = 1");
    
    if (!$result) {
        error_log("notifyNewDeliveryOrder: Query failed: " . mysqli_error($conn));
        return $results;
    }
    
    $title = "New Delivery Available! 🛵";
    $message = "Order #$orderId from $shopName is ready for pickup!";
    $title_esc = mysqli_real_escape_string($conn, $title);
    $msg_esc = mysqli_real_escape_string($conn, $message);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $delivery_id = intval($row['delivery_id']);
        $fcm_token = $row['fcm_token'] ?? '';
        
        // Insert in-app notification
        $notif_query = "INSERT INTO notifications (user_type, user_id, type, title, message, order_id) 
                       VALUES ('delivery', $delivery_id, 'new_delivery', '$title_esc', '$msg_esc', $orderId)";
        if (mysqli_query($conn, $notif_query)) {
            $results['in_app']++;
        }
        
        // Send FCM push notification
        if (!empty($fcm_token)) {
            if (sendPushNotification($fcm_token, $title, $message, ['type' => 'new_delivery', 'order_id' => strval($orderId)])) {
                $results['fcm']++;
            }
        }
    }
    
    error_log("notifyNewDeliveryOrder: Sent {$results['in_app']} in-app and {$results['fcm']} FCM notifications");
    return $results;
}
?>
