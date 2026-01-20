<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'db.php';

// Get user_id
$user_id = intval($_POST['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

// Check if image was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "error", "message" => "No image uploaded or upload error"]);
    exit;
}

$file = $_FILES['image'];
$filename = $file['name'];
$tmpPath = $file['tmp_name'];
$fileSize = $file['size'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$fileType = mime_content_type($tmpPath);

if (!in_array($fileType, $allowedTypes)) {
    echo json_encode(["status" => "error", "message" => "Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed."]);
    exit;
}

// Validate file size (max 5MB)
if ($fileSize > 5 * 1024 * 1024) {
    echo json_encode(["status" => "error", "message" => "File size too large. Maximum is 5MB."]);
    exit;
}

// Create upload directory if not exists
$uploadDir = 'uploads/customers/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($filename, PATHINFO_EXTENSION);
$newFilename = 'customer_' . $user_id . '_' . time() . '.' . $extension;
$uploadPath = $uploadDir . $newFilename;

// Move uploaded file
if (move_uploaded_file($tmpPath, $uploadPath)) {
    // Update database with new image path
    $imagePath = mysqli_real_escape_string($conn, $uploadPath);
    
    // Check if profile_image column exists
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'profile_image'");
    if ($check_column && mysqli_num_rows($check_column) == 0) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN profile_image VARCHAR(500) NULL");
    }
    
    // Try with user_id first
    $sql = "UPDATE users SET profile_image = '$imagePath' WHERE user_id = $user_id";
    $result = mysqli_query($conn, $sql);
    
    // If no rows updated, try with id column
    if (mysqli_affected_rows($conn) == 0) {
        $sql = "UPDATE users SET profile_image = '$imagePath' WHERE id = $user_id";
        mysqli_query($conn, $sql);
    }
    
    echo json_encode([
        "status" => "success",
        "message" => "Image uploaded successfully",
        "image_url" => $uploadPath
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to move uploaded file"]);
}

mysqli_close($conn);
?>
