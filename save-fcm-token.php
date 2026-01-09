<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'db.php';

// Get JSON input
$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit;
}

$user_type = $data['user_type'] ?? '';
$user_id = intval($data['user_id'] ?? 0);
$fcm_token = $data['fcm_token'] ?? '';

// Validate inputs
if (empty($user_type) || $user_id <= 0 || empty($fcm_token)) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

// Determine which table to update based on user type
switch ($user_type) {
    case 'customer':
        $table = 'users';
        $id_column = 'user_id';
        break;
    case 'baker':
        $table = 'bakers';
        $id_column = 'baker_id';
        break;
    case 'delivery':
        // Check which delivery table exists
        $check_partners = mysqli_query($conn, "SHOW TABLES LIKE 'delivery_partners'");
        if ($check_partners && mysqli_num_rows($check_partners) > 0) {
            $table = 'delivery_partners';
        } else {
            $table = 'delivery_persons';
        }
        $id_column = 'delivery_id';
        break;
    default:
        echo json_encode(["status" => "error", "message" => "Invalid user type"]);
        exit;
}

// Check if fcm_token column exists, if not create it
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE 'fcm_token'");
if ($check_column && mysqli_num_rows($check_column) == 0) {
    mysqli_query($conn, "ALTER TABLE $table ADD COLUMN fcm_token VARCHAR(500) NULL");
}

// Sanitize token
$fcm_token = mysqli_real_escape_string($conn, $fcm_token);

// Update the FCM token
$sql = "UPDATE $table SET fcm_token = '$fcm_token' WHERE $id_column = $user_id";

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        "status" => "success",
        "message" => "FCM token saved successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to save FCM token: " . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
