<?php
// fcm-helper.php
// Firebase Cloud Messaging Helper using HTTP v1 API
// Supports modern FCM with OAuth2 authentication

require_once 'firebase-config.php';

/**
 * Get OAuth2 Access Token for Firebase
 * Uses Service Account credentials to generate JWT and exchange for access token
 */
function getFirebaseAccessToken() {
    $now = time();
    $expiry = $now + 3600; // Token valid for 1 hour
    
    // JWT Header (URL-safe base64)
    $header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode([
        'alg' => 'RS256',
        'typ' => 'JWT'
    ])));
    
    // JWT Claim Set (URL-safe base64)
    $claim = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode([
        'iss' => FIREBASE_CLIENT_EMAIL,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $expiry
    ])));
    
    // Create signature
    $base = $header . '.' . $claim;
    $privateKey = openssl_pkey_get_private(FIREBASE_PRIVATE_KEY);
    
    if (!$privateKey) {
        error_log("FCM Helper: Failed to load private key");
        return null;
    }
    
    openssl_sign($base, $signature, $privateKey, 'SHA256');
    $signatureB64 = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    $jwt = $base . '.' . $signatureB64;
    
    // Exchange JWT for access token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("FCM Helper: cURL error getting token: $error");
        return null;
    }
    
    if ($httpCode != 200) {
        error_log("FCM Helper: Failed to get access token. HTTP $httpCode: $response");
        return null;
    }
    
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

/**
 * Send FCM Push Notification using HTTP v1 API
 * 
 * @param string $fcmToken - Device FCM token
 * @param string $title - Notification title
 * @param string $body - Notification body
 * @param array $data - Additional data payload
 * @return bool - Success status
 */
function sendFCMNotificationV2($fcmToken, $title, $body, $data = []) {
    if (empty($fcmToken)) {
        error_log("FCM Helper: Empty FCM token");
        return false;
    }
    
    $accessToken = getFirebaseAccessToken();
    if (!$accessToken) {
        error_log("FCM Helper: Could not get access token");
        return false;
    }
    
    $projectId = FIREBASE_PROJECT_ID;
    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    
    // Build Android-specific config
    $android = [
        'priority' => 'high',
        'notification' => [
            'sound' => 'default',
            'channel_id' => 'orders_channel',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ]
    ];
    
    // Build message payload
    $message = [
        'token' => $fcmToken,
        'notification' => [
            'title' => $title,
            'body' => $body
        ],
        'android' => $android
    ];
    
    // Add data payload if provided
    if (!empty($data)) {
        // Convert all values to strings (FCM requirement)
        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[$key] = strval($value);
        }
        // Also include title and body in data for foreground handling
        $stringData['title'] = $title;
        $stringData['body'] = $body;
        $message['data'] = $stringData;
    }
    
    $payload = json_encode(['message' => $message]);
    
    error_log("FCM Helper: Sending to URL: $url");
    error_log("FCM Helper: Token prefix: " . substr($fcmToken, 0, 30) . "...");
    
    // Send request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("FCM Helper: cURL error: $error");
        return false;
    }
    
    if ($httpCode == 200) {
        error_log("FCM Helper: ✅ Notification sent successfully");
        return true;
    } else {
        error_log("FCM Helper: ❌ Failed to send. HTTP $httpCode: $response");
        return false;
    }
}

/**
 * Send notification to a user by type and ID
 * Looks up FCM token from database
 * 
 * @param mysqli $conn - Database connection
 * @param string $userType - 'customer', 'baker', or 'delivery'
 * @param int $userId - User ID
 * @param string $title - Notification title
 * @param string $body - Notification body
 * @param array $data - Additional data payload
 * @return bool - Success status
 */
function sendFCMToUserV2($conn, $userType, $userId, $title, $body, $data = []) {
    $fcmToken = null;
    
    switch ($userType) {
        case 'customer':
            $result = mysqli_query($conn, "SELECT fcm_token FROM users WHERE user_id = " . intval($userId));
            break;
        case 'baker':
            $result = mysqli_query($conn, "SELECT fcm_token FROM bakers WHERE baker_id = " . intval($userId));
            break;
        case 'delivery':
            // Check both possible table names
            $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'delivery_partners'");
            $table = (mysqli_num_rows($tableCheck) > 0) ? 'delivery_partners' : 'delivery_persons';
            $result = mysqli_query($conn, "SELECT fcm_token FROM $table WHERE delivery_id = " . intval($userId));
            break;
        default:
            error_log("FCM Helper: Unknown user type: $userType");
            return false;
    }
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $fcmToken = $row['fcm_token'] ?? null;
    }
    
    if (empty($fcmToken)) {
        error_log("FCM Helper: No FCM token found for $userType ID $userId");
        return false;
    }
    
    return sendFCMNotificationV2($fcmToken, $title, $body, $data);
}
?>
