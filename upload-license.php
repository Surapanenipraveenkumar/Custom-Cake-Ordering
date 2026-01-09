<?php
// Suppress PHP warnings/notices from breaking JSON output
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json");
include "db.php";

$delivery_id = $_POST['delivery_id'] ?? null;

if (!$delivery_id) {
    echo json_encode([
        "status" => "error",
        "message" => "delivery_id required"
    ]);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['license_image']) || $_FILES['license_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "status" => "error",
        "message" => "No license image uploaded"
    ]);
    exit;
}

$file = $_FILES['license_image'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileError = $file['error'];

// Check file size (max 5MB)
if ($fileSize > 5 * 1024 * 1024) {
    echo json_encode([
        "status" => "error",
        "message" => "File size too large. Max 5MB allowed."
    ]);
    exit;
}

// Get file extension
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid file type. Only JPG, PNG, GIF allowed."
    ]);
    exit;
}

// Create uploads directory if not exists
$uploadDir = 'uploads/license/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$newFileName = "license_" . $delivery_id . "_" . time() . "." . $fileExtension;
$uploadPath = $uploadDir . $newFileName;

// Move uploaded file
if (move_uploaded_file($fileTmpName, $uploadPath)) {
    // Get server URL for the image
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . $host . dirname($_SERVER['REQUEST_URI']) . '/';
    $imageUrl = $baseUrl . $uploadPath;
    
    // Update database with license image path
    $delivery_id = mysqli_real_escape_string($conn, $delivery_id);
    
    // Check if license_image column exists, if not add it
    $checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM delivery_persons LIKE 'license_image'");
    if (mysqli_num_rows($checkColumn) == 0) {
        mysqli_query($conn, "ALTER TABLE delivery_persons ADD COLUMN license_image VARCHAR(500) DEFAULT NULL");
    }
    
    $updateQuery = mysqli_query($conn, "
        UPDATE delivery_persons 
        SET license_image = '$imageUrl'
        WHERE delivery_id = '$delivery_id'
    ");
    
    if ($updateQuery) {
        echo json_encode([
            "status" => "success",
            "message" => "License uploaded successfully",
            "license_image" => $imageUrl
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to update database"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to save file"
    ]);
}
?>
