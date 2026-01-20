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
$table = '';
$id_column = '';

switch ($user_type) {
    case 'baker':
        $table = 'bakers';
        $id_column = 'baker_id';
        break;
    case 'delivery':
        // Check which table exists for delivery
        $check_dp = mysqli_query($conn, "SHOW TABLES LIKE 'delivery_partners'");
        if ($check_dp && mysqli_num_rows($check_dp) > 0) {
            $table = 'delivery_partners';
        } else {
            $table = 'delivery_persons';
        }
        $id_column = 'delivery_id';
        break;
    default:
        $table = 'users';
        $id_column = 'user_id';
}

// Verify email exists - try different column names
$user = null;
$found = false;

// Try primary id column first
$check = mysqli_query($conn, "SELECT * FROM $table WHERE email = '$email' LIMIT 1");
if ($check && mysqli_num_rows($check) > 0) {
    $user = mysqli_fetch_assoc($check);
    $found = true;
}

// If not found and it's users table, try with 'id' column
if (!$found && $table == 'users') {
    // Check if user_id column exists
    $col_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'user_id'");
    if (!$col_check || mysqli_num_rows($col_check) == 0) {
        $id_column = 'id';
    }
    
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email' LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        $user = mysqli_fetch_assoc($check);
        $found = true;
    }
}

if (!$found || !$user) {
    echo json_encode(["status" => "error", "message" => "Email not registered in $user_type accounts"]);
    exit;
}

// Get user ID from the found record
$user_id = $user[$id_column] ?? $user['id'] ?? $user['user_id'] ?? 0;

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
    
    // Update password - use email since we're sure of it
    $update = mysqli_query($conn, "UPDATE $table SET password = '$hashed_password' WHERE email = '$email'");
    
    if ($update && mysqli_affected_rows($conn) > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Password reset successful"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to reset password"
        ]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
}

mysqli_close($conn);
?>
