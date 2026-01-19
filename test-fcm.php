<?php
// test-fcm.php
// Test Firebase Cloud Messaging to verify notifications are working
// Access via browser: http://your-server/test-fcm.php?user_type=customer&user_id=1

header("Content-Type: application/json");
include "db.php";
include_once "firebase-config.php";
include_once "send-push-notification.php";

$user_type = $_GET['user_type'] ?? 'customer';
$user_id = intval($_GET['user_id'] ?? 0);
$test_token = $_GET['token'] ?? null;

$result = [
    "firebase_project" => FIREBASE_PROJECT_ID,
    "firebase_email" => FIREBASE_CLIENT_EMAIL,
    "tests" => []
];

// Test 1: Check Firebase Config
$result["tests"]["config"] = [
    "status" => !empty(FIREBASE_PROJECT_ID) && !empty(FIREBASE_PRIVATE_KEY) ? "pass" : "fail",
    "message" => "Firebase configuration loaded"
];

// Test 2: Get Access Token
$accessToken = getAccessToken();
$result["tests"]["access_token"] = [
    "status" => !empty($accessToken) ? "pass" : "fail",
    "message" => !empty($accessToken) ? "Access token obtained successfully" : "Failed to get access token"
];

// Test 3: Get FCM Token from Database
$fcmToken = null;
if ($user_id > 0 || $test_token) {
    if ($test_token) {
        $fcmToken = $test_token;
        $result["tests"]["fcm_token_source"] = ["status" => "pass", "message" => "Using provided test token"];
    } else {
        $fcmToken = getFcmToken($conn, $user_type, $user_id);
        $result["tests"]["fcm_token_db"] = [
            "status" => !empty($fcmToken) ? "pass" : "fail",
            "message" => !empty($fcmToken) 
                ? "FCM token found: " . substr($fcmToken, 0, 30) . "..." 
                : "No FCM token found for $user_type ID $user_id"
        ];
    }
}

// Test 4: Send Test Notification
if (!empty($accessToken) && !empty($fcmToken)) {
    $testTitle = "Test Notification 🎂";
    $testBody = "FCM is working! Sent at " . date("Y-m-d H:i:s");
    
    $sendResult = sendPushNotification($fcmToken, $testTitle, $testBody, [
        "type" => "test",
        "timestamp" => strval(time())
    ]);
    
    $result["tests"]["send_notification"] = [
        "status" => $sendResult ? "pass" : "fail",
        "message" => $sendResult ? "Notification sent successfully!" : "Failed to send notification"
    ];
} else {
    $result["tests"]["send_notification"] = [
        "status" => "skip",
        "message" => "Skipped - missing access token or FCM token"
    ];
}

// Summary
$passed = 0;
$failed = 0;
foreach ($result["tests"] as $test) {
    if ($test["status"] === "pass") $passed++;
    elseif ($test["status"] === "fail") $failed++;
}
$result["summary"] = [
    "passed" => $passed,
    "failed" => $failed,
    "total" => count($result["tests"])
];

// Usage instructions
$result["usage"] = [
    "test_customer" => "?user_type=customer&user_id=1",
    "test_baker" => "?user_type=baker&user_id=1",
    "test_delivery" => "?user_type=delivery&user_id=1",
    "test_with_token" => "?token=YOUR_FCM_TOKEN_HERE"
];

echo json_encode($result, JSON_PRETTY_PRINT);
?>
