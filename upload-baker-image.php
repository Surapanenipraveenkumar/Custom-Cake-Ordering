<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'db.php';

// Get baker_id
$baker_id = intval($_POST['baker_id'] ?? 0);

if ($baker_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Baker ID required"]);
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
$uploadDir = 'uploads/bakers/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($filename, PATHINFO_EXTENSION);
$newFilename = 'baker_' . $baker_id . '_' . time() . '.' . $extension;
$uploadPath = $uploadDir . $newFilename;

// Move uploaded file
if (move_uploaded_file($tmpPath, $uploadPath)) {
    // Update database with new image path
    $imagePath = mysqli_real_escape_string($conn, $uploadPath);
    $sql = "UPDATE bakers SET image = '$imagePath' WHERE baker_id = $baker_id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode([
            "status" => "success",
            "message" => "Image uploaded successfully",
            "image_url" => $uploadPath
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to update database: " . mysqli_error($conn)
        ]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Failed to move uploaded file"]);
}

mysqli_close($conn);
?>
