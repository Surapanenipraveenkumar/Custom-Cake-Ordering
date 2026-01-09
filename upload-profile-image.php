<?php
// Suppress PHP warnings/notices from being output
error_reporting(0);
header("Content-Type: application/json");
include "db.php";

// Check if baker_id is provided
$baker_id = $_POST['baker_id'] ?? null;

if (!$baker_id) {
    echo json_encode([
        "status" => "error",
        "message" => "baker_id required"
    ]);
    exit;
}

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
$uploadDir = "uploads/profiles/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$newFileName = "baker_" . $baker_id . "_" . time() . "." . $fileExt;
$uploadPath = $uploadDir . $newFileName;

if (move_uploaded_file($fileTmpName, $uploadPath)) {
    // Update baker's profile_image in database
    $baker_id = mysqli_real_escape_string($conn, $baker_id);
    $imageUrl = mysqli_real_escape_string($conn, $uploadPath);
    
    $updateQuery = mysqli_query($conn, "
        UPDATE bakers SET profile_image = '$imageUrl' WHERE baker_id = '$baker_id'
    ");
    
    if ($updateQuery) {
        echo json_encode([
            "status" => "success",
            "message" => "Profile image uploaded successfully",
            "image_url" => $uploadPath
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "message" => "Image uploaded but database not updated",
            "image_url" => $uploadPath
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to upload image. Check folder permissions."
    ]);
}
?>
