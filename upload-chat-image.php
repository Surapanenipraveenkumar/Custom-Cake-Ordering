<?php
// Suppress PHP warnings/notices from being output
error_reporting(0);
header("Content-Type: application/json");
include "db.php";

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $error_message = "No image uploaded";
    if (isset($_FILES['image'])) {
        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = "File too large";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = "File partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = "No file selected";
                break;
            default:
                $error_message = "Upload error code: " . $_FILES['image']['error'];
        }
    }
    echo json_encode([
        "status" => "error",
        "message" => $error_message
    ]);
    exit;
}

$file = $_FILES['image'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileError = $file['error'];

// Get file extension
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($fileExt, $allowedExtensions)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid file type. Allowed: jpg, jpeg, png, gif, webp"
    ]);
    exit;
}

// Max file size 5MB
if ($fileSize > 5 * 1024 * 1024) {
    echo json_encode([
        "status" => "error",
        "message" => "File size too large. Max 5MB allowed"
    ]);
    exit;
}

// Create uploads directory if it doesn't exist
$uploadDir = "uploads/chat/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$newFileName = uniqid('chat_', true) . '.' . $fileExt;
$uploadPath = $uploadDir . $newFileName;

if (move_uploaded_file($fileTmpName, $uploadPath)) {
    // Return the URL for the uploaded image
    $imageUrl = $uploadPath;
    
    echo json_encode([
        "status" => "success",
        "message" => "Image uploaded successfully",
        "image_url" => $imageUrl
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to upload image. Check folder permissions."
    ]);
}
?>
