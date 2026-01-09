<?php
// Suppress PHP warnings/notices from breaking JSON output
error_reporting(0);
ini_set('display_errors', 0);

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

$email = mysqli_real_escape_string($conn, $data['email'] ?? '');
$user_type = $data['user_type'] ?? 'customer';
$action = $data['action'] ?? 'verify';
$new_password = $data['new_password'] ?? '';

if (empty($email)) {
    echo json_encode(["status" => "error", "message" => "Email is required"]);
    exit;
}

// Determine table based on user type
switch ($user_type) {
    case 'baker':
        $table = 'bakers';
        $id_column = 'baker_id';
        break;
    case 'delivery':
        $table = 'delivery_persons';
        $id_column = 'delivery_id';
        break;
    default:
        $table = 'users';
        $id_column = 'user_id';
}

// Verify email exists
$check = mysqli_query($conn, "SELECT $id_column, email FROM $table WHERE email = '$email'");

if (!$check || mysqli_num_rows($check) == 0) {
    // Try with 'id' column for users table
    if ($table == 'users') {
        $check = mysqli_query($conn, "SELECT id, email FROM $table WHERE email = '$email'");
        $id_column = 'id';
    }
    
    if (!$check || mysqli_num_rows($check) == 0) {
        echo json_encode(["status" => "error", "message" => "Email not registered"]);
        exit;
    }
}

$user = mysqli_fetch_assoc($check);
$user_id = $user[$id_column];

if ($action === 'verify') {
    // Just verify email exists
    echo json_encode([
        "status" => "success",
        "message" => "Email verified"
    ]);
} elseif ($action === 'reset') {
    // Reset password
    if (empty($new_password)) {
        echo json_encode(["status" => "error", "message" => "New password is required"]);
        exit;
    }
    
    if (strlen($new_password) < 6) {
        echo json_encode(["status" => "error", "message" => "Password must be at least 6 characters"]);
        exit;
    }
    
    // Hash the password using password_hash (matches password_verify in login)
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $update = mysqli_query($conn, "UPDATE $table SET password = '$hashed_password' WHERE $id_column = $user_id");
    
    if ($update) {
        echo json_encode([
            "status" => "success",
            "message" => "Password reset successful"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to reset password: " . mysqli_error($conn)
        ]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
}

mysqli_close($conn);
?>
