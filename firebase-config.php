<?php
// firebase-config.php
// Firebase Cloud Messaging configuration using service account

// Load service account credentials
$serviceAccountPath = __DIR__ . '/firebase-service-account.json';

if (file_exists($serviceAccountPath)) {
    $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
    
    // Define Firebase constants
    define('FIREBASE_PROJECT_ID', $serviceAccount['project_id'] ?? '');
    define('FIREBASE_PRIVATE_KEY', $serviceAccount['private_key'] ?? '');
    define('FIREBASE_CLIENT_EMAIL', $serviceAccount['client_email'] ?? '');
    define('FIREBASE_TOKEN_URI', $serviceAccount['token_uri'] ?? 'https://oauth2.googleapis.com/token');
} else {
    // Fallback - define empty constants
    define('FIREBASE_PROJECT_ID', '');
    define('FIREBASE_PRIVATE_KEY', '');
    define('FIREBASE_CLIENT_EMAIL', '');
    define('FIREBASE_TOKEN_URI', 'https://oauth2.googleapis.com/token');
}

/**
 * Generate a JWT token for Firebase authentication
 */
function generateJWT() {
    $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    
    $now = time();
    $payload = base64_encode(json_encode([
        'iss' => FIREBASE_CLIENT_EMAIL,
        'sub' => FIREBASE_CLIENT_EMAIL,
        'aud' => FIREBASE_TOKEN_URI,
        'iat' => $now,
        'exp' => $now + 3600,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging'
    ]));
    
    $signatureInput = str_replace(['+', '/', '='], ['-', '_', ''], $header) . '.' . 
                      str_replace(['+', '/', '='], ['-', '_', ''], $payload);
    
    // Sign with private key
    $privateKey = openssl_pkey_get_private(FIREBASE_PRIVATE_KEY);
    if (!$privateKey) {
        error_log("FCM: Failed to parse private key");
        return null;
    }
    
    openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $signatureInput . '.' . $signature;
}

/**
 * Get OAuth2 access token for Firebase
 */
function getAccessToken() {
    static $cachedToken = null;
    static $tokenExpiry = 0;
    
    // Return cached token if still valid
    if ($cachedToken && time() < $tokenExpiry - 60) {
        return $cachedToken;
    }
    
    $jwt = generateJWT();
    if (!$jwt) {
        return null;
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => FIREBASE_TOKEN_URI,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        $cachedToken = $data['access_token'] ?? null;
        $tokenExpiry = time() + ($data['expires_in'] ?? 3600);
        return $cachedToken;
    }
    
    error_log("FCM: Failed to get access token. HTTP $httpCode: $response");
    return null;
}
?>
