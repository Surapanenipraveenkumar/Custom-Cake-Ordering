<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

$delivery_id = isset($_POST['delivery_id']) ? intval($_POST['delivery_id']) : 0;

if ($delivery_id <= 0) {
    echo json_encode(["status" => "error", "message" => "delivery_id required"]);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['license']) || $_FILES['license']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "error", "message" => "No license image uploaded"]);
    exit;
}

$file = $_FILES['license'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(["status" => "error", "message" => "Only JPG and PNG files allowed"]);
    exit;
}

// Validate file size (5MB max)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(["status" => "error", "message" => "File size must be less than 5MB"]);
    exit;
}

// Create upload directory if not exists
$upload_dir = "licenses/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = "license_" . $delivery_id . "_" . time() . "." . $extension;
$filepath = $upload_dir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Update database with license info
    $update = mysqli_query($conn, "
        UPDATE delivery_partners 
        SET license_image = '$filepath', 
            license_verified = 1,
            license_uploaded_at = NOW()
        WHERE delivery_id = $delivery_id
    ");
    
    if (!$update) {
        // Try alternative table name
        $update = mysqli_query($conn, "
            UPDATE delivery_persons 
            SET license_image = '$filepath', 
                license_verified = 1,
                license_uploaded_at = NOW()
            WHERE delivery_id = $delivery_id
        ");
    }
    
    if ($update && mysqli_affected_rows($conn) > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "License uploaded successfully",
            "license_image" => $filepath,
            "verified" => true
        ]);
    } else {
        // The columns might not exist, try adding them
        mysqli_query($conn, "ALTER TABLE delivery_partners ADD COLUMN license_image VARCHAR(255)");
        mysqli_query($conn, "ALTER TABLE delivery_partners ADD COLUMN license_verified TINYINT(1) DEFAULT 0");
        mysqli_query($conn, "ALTER TABLE delivery_partners ADD COLUMN license_uploaded_at DATETIME");
        
        // Try update again
        $update = mysqli_query($conn, "
            UPDATE delivery_partners 
            SET license_image = '$filepath', 
                license_verified = 1,
                license_uploaded_at = NOW()
            WHERE delivery_id = $delivery_id
        ");
        
        if ($update) {
            echo json_encode([
                "status" => "success",
                "message" => "License uploaded successfully",
                "license_image" => $filepath,
                "verified" => true
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to update database: " . mysqli_error($conn)
            ]);
        }
    }
} else {
    echo json_encode(["status" => "error", "message" => "Failed to upload file"]);
}
?>
