<?php
// Suppress PHP warnings/notices from being output
error_reporting(0);
header("Content-Type: application/json");
include "db.php";

// Check if this is a multipart form (with image) or regular form
$shop_name = isset($_POST['shop_name']) ? $_POST['shop_name'] : null;
$owner_name = isset($_POST['owner_name']) ? $_POST['owner_name'] : null;
$email = isset($_POST['email']) ? $_POST['email'] : null;
$phone = isset($_POST['phone']) ? $_POST['phone'] : null;
$password = isset($_POST['password']) ? $_POST['password'] : null;
$address = isset($_POST['address']) ? $_POST['address'] : "";
$latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
$longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
$specialty = isset($_POST['specialty']) ? $_POST['specialty'] : "Custom Cakes";
$years_experience = isset($_POST['years_experience']) ? intval($_POST['years_experience']) : 0;

// Validation
if (!$shop_name || !$owner_name || !$email || !$phone || !$password) {
    echo json_encode(array(
        "status" => "error",
        "message" => "All fields are required"
    ));
    exit;
}

// Escape input
$shop_name = mysqli_real_escape_string($conn, $shop_name);
$owner_name = mysqli_real_escape_string($conn, $owner_name);
$email = mysqli_real_escape_string($conn, $email);
$phone = mysqli_real_escape_string($conn, $phone);
$password_raw = $password;
$address = mysqli_real_escape_string($conn, $address);
$specialty = mysqli_real_escape_string($conn, $specialty);

// Check if email already exists
$checkEmail = mysqli_query($conn, "SELECT baker_id FROM bakers WHERE email = '$email'");
if ($checkEmail && mysqli_num_rows($checkEmail) > 0) {
    echo json_encode(array(
        "status" => "error",
        "message" => "Email already registered"
    ));
    exit;
}

// Handle profile image upload if provided
$profile_image = "";
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    
    if (in_array($fileExt, $allowedExtensions) && $file['size'] <= 5 * 1024 * 1024) {
        $uploadDir = "uploads/profiles/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $newFileName = "baker_" . time() . "_" . uniqid() . "." . $fileExt;
        $uploadPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $profile_image = $uploadPath;
        }
    }
}

// Hash password (using MD5 for simplicity - in production use password_hash)
$hashed_password = md5($password_raw);

// Build insert query with location, specialty, and experience
$sql = "INSERT INTO bakers (shop_name, owner_name, email, phone, password, address, latitude, longitude, specialty, years_experience";
$values = "('$shop_name', '$owner_name', '$email', '$phone', '$hashed_password', '$address', $latitude, $longitude, '$specialty', $years_experience";

// Add profile_image if we have one
if (!empty($profile_image)) {
    $profile_image = mysqli_real_escape_string($conn, $profile_image);
    $sql .= ", profile_image";
    $values .= ", '$profile_image'";
}

$sql .= ") VALUES " . $values . ")";

$result = mysqli_query($conn, $sql);

if ($result) {
    $baker_id = mysqli_insert_id($conn);
    echo json_encode(array(
        "status" => "success",
        "message" => "Account created successfully",
        "baker_id" => $baker_id
    ));
} else {
    // If insert failed with location, try without location columns
    $sql_simple = "INSERT INTO bakers (email, password, shop_name, owner_name, phone, address) 
                   VALUES ('$email', '$hashed_password', '$shop_name', '$owner_name', '$phone', '$address')";
    
    $result_simple = mysqli_query($conn, $sql_simple);
    
    if ($result_simple) {
        $baker_id = mysqli_insert_id($conn);
        echo json_encode(array(
            "status" => "success",
            "message" => "Account created successfully (location not saved - run SQL update)",
            "baker_id" => $baker_id
        ));
    } else {
        echo json_encode(array(
            "status" => "error",
            "message" => "Database error: " . mysqli_error($conn)
        ));
    }
}
?>
