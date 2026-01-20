<?php
// debug-fcm.php - Debug Push Notifications
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include 'db.php';

$result = [
    "status" => "checking",
    "timestamp" => date("Y-m-d H:i:s"),
    "checks" => []
];

// 1. Check firebase-service-account.json exists
$serviceAccountPath = __DIR__ . '/firebase-service-account.json';
if (file_exists($serviceAccountPath)) {
    $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
    $result["checks"]["service_account"] = [
        "status" => "found",
        "project_id" => $serviceAccount['project_id'] ?? "MISSING",
        "client_email" => $serviceAccount['client_email'] ?? "MISSING"
    ];
} else {
    $result["checks"]["service_account"] = [
        "status" => "NOT FOUND",
        "error" => "firebase-service-account.json file not found at: $serviceAccountPath"
    ];
}

// 2. Check FCM tokens in database
$result["checks"]["tokens"] = [];

// Check users (customers)
$q = mysqli_query($conn, "SELECT COUNT(*) as total, COUNT(CASE WHEN fcm_token IS NOT NULL AND fcm_token != '' THEN 1 END) as with_token FROM users");
if ($q && $row = mysqli_fetch_assoc($q)) {
    $result["checks"]["tokens"]["customers"] = [
        "total" => (int)$row['total'],
        "with_fcm_token" => (int)$row['with_token']
    ];
}

// Check bakers
$q = mysqli_query($conn, "SELECT COUNT(*) as total, COUNT(CASE WHEN fcm_token IS NOT NULL AND fcm_token != '' THEN 1 END) as with_token FROM bakers");
if ($q && $row = mysqli_fetch_assoc($q)) {
    $result["checks"]["tokens"]["bakers"] = [
        "total" => (int)$row['total'],
        "with_fcm_token" => (int)$row['with_token']
    ];
}

// Check delivery partners
$table = "delivery_partners";
$check = mysqli_query($conn, "SHOW TABLES LIKE 'delivery_partners'");
if (!$check || mysqli_num_rows($check) == 0) {
    $table = "delivery_persons";
}
$q = mysqli_query($conn, "SELECT COUNT(*) as total, COUNT(CASE WHEN fcm_token IS NOT NULL AND fcm_token != '' THEN 1 END) as with_token FROM $table");
if ($q && $row = mysqli_fetch_assoc($q)) {
    $result["checks"]["tokens"]["delivery"] = [
        "total" => (int)$row['total'],
        "with_fcm_token" => (int)$row['with_token']
    ];
}

// 3. Test Firebase Authentication
include_once 'firebase-config.php';

if (defined('FIREBASE_PROJECT_ID') && !empty(FIREBASE_PROJECT_ID)) {
    $result["checks"]["firebase_config"] = [
        "status" => "loaded",
        "project_id" => FIREBASE_PROJECT_ID
    ];
    
    // Try to get access token
    $accessToken = getAccessToken();
    if ($accessToken) {
        $result["checks"]["firebase_auth"] = [
            "status" => "SUCCESS",
            "token_preview" => substr($accessToken, 0, 50) . "..."
        ];
    } else {
        $result["checks"]["firebase_auth"] = [
            "status" => "FAILED",
            "error" => "Could not get access token. Check: 1) Service account file, 2) Private key format, 3) Server has openssl extension"
        ];
    }
} else {
    $result["checks"]["firebase_config"] = [
        "status" => "FAILED",
        "error" => "Firebase config not loaded properly"
    ];
}

// 4. Check if openssl is available
$result["checks"]["server"] = [
    "openssl" => extension_loaded('openssl') ? "enabled" : "DISABLED - FCM will not work!",
    "curl" => extension_loaded('curl') ? "enabled" : "DISABLED - FCM will not work!"
];

// 5. Show sample tokens for testing
$result["sample_tokens"] = [];

$q = mysqli_query($conn, "SELECT user_id, name, fcm_token FROM users WHERE fcm_token IS NOT NULL AND fcm_token != '' LIMIT 2");
if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
        $result["sample_tokens"]["customers"][] = [
            "user_id" => $row['user_id'],
            "name" => $row['name'],
            "token_preview" => substr($row['fcm_token'], 0, 40) . "..."
        ];
    }
}

$q = mysqli_query($conn, "SELECT baker_id, shop_name, fcm_token FROM bakers WHERE fcm_token IS NOT NULL AND fcm_token != '' LIMIT 2");
if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
        $result["sample_tokens"]["bakers"][] = [
            "baker_id" => $row['baker_id'],
            "shop_name" => $row['shop_name'],
            "token_preview" => substr($row['fcm_token'], 0, 40) . "..."
        ];
    }
}

// Final status
$issues = [];
if (!file_exists($serviceAccountPath)) {
    $issues[] = "Service account file missing";
}
if (!extension_loaded('openssl')) {
    $issues[] = "OpenSSL extension not loaded";
}
if (!extension_loaded('curl')) {
    $issues[] = "cURL extension not loaded";
}
if (empty($result["checks"]["firebase_auth"]["status"]) || $result["checks"]["firebase_auth"]["status"] !== "SUCCESS") {
    $issues[] = "Firebase authentication failed";
}

$result["status"] = empty($issues) ? "all_checks_passed" : "issues_found";
$result["issues"] = $issues;

echo json_encode($result, JSON_PRETTY_PRINT);
mysqli_close($conn);
?>
