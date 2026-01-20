<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Suppress PHP warnings/notices from breaking JSON output
error_reporting(0);
ini_set('display_errors', 0);

include 'db.php';

// Check database connection
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// Get JSON input
$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit;
}

$email = trim($data['email'] ?? '');
$user_type = $data['user_type'] ?? 'customer';
$action = $data['action'] ?? 'verify';
$new_password = $data['new_password'] ?? '';

if (empty($email)) {
    echo json_encode(["status" => "error", "message" => "Email is required"]);
    exit;
}

// Determine table and ID column based on user type
switch ($user_type) {
    case 'baker':
        $table = 'bakers';
        $id_column = 'baker_id';
        break;
    case 'delivery':
        // Check which table exists
        $check_table = $conn->query("SHOW TABLES LIKE 'delivery_partners'");
        if ($check_table && $check_table->num_rows > 0) {
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

// Verify email exists using prepared statement (same style as login.php)
$stmt = $conn->prepare("SELECT * FROM $table WHERE email = ?");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Query error: " . $conn->error]);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "Email not registered"]);
    exit;
}

$user = $result->fetch_assoc();

if ($action === 'verify') {
    // Just verify email exists
    echo json_encode([
        "status" => "success",
        "message" => "Email verified"
    ]);
    exit;
} 

if ($action === 'reset') {
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
    
    // Update password using prepared statement
    $update_stmt = $conn->prepare("UPDATE $table SET password = ? WHERE email = ?");
    if (!$update_stmt) {
        echo json_encode(["status" => "error", "message" => "Update error: " . $conn->error]);
        exit;
    }
    
    $update_stmt->bind_param("ss", $hashed_password, $email);
    
    if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
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
    exit;
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
$conn->close();
?>
