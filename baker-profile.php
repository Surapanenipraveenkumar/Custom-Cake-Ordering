<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

// Check database connection
if (!$conn) {
    echo json_encode(array(
        "status" => "error",
        "message" => "Database connection failed"
    ));
    exit;
}

// Get baker_id from GET parameter
$baker_id = isset($_GET['baker_id']) ? intval($_GET['baker_id']) : 0;

if ($baker_id <= 0) {
    echo json_encode(array(
        "status" => "error",
        "message" => "baker_id required"
    ));
    exit;
}

// Fetch baker profile
$query = mysqli_query($conn, "SELECT * FROM bakers WHERE baker_id = $baker_id");

if (!$query) {
    echo json_encode(array(
        "status" => "error",
        "message" => "Database query error: " . mysqli_error($conn)
    ));
    exit;
}

if (mysqli_num_rows($query) == 0) {
    echo json_encode(array(
        "status" => "error",
        "message" => "Baker not found with ID: $baker_id"
    ));
    exit;
}

$baker = mysqli_fetch_assoc($query);

// Get total orders count
$total_orders = 0;
$ordersQuery = mysqli_query($conn, "SELECT COUNT(*) as total_orders FROM orders WHERE baker_id = $baker_id");
if ($ordersQuery) {
    $ordersResult = mysqli_fetch_assoc($ordersQuery);
    if ($ordersResult) {
        $total_orders = (int)$ordersResult['total_orders'];
    }
}

// Get average rating - default 4.5
$rating = 4.5;

// Get monthly income
$monthly_income = 0.0;
$incomeQuery = mysqli_query($conn, "SELECT SUM(total_amount) as monthly_income FROM orders WHERE baker_id = $baker_id");
if ($incomeQuery) {
    $incomeResult = mysqli_fetch_assoc($incomeQuery);
    if ($incomeResult && $incomeResult['monthly_income']) {
        $monthly_income = (float)$incomeResult['monthly_income'];
    }
}

// Get values with fallbacks for missing columns
$shop_name = isset($baker['shop_name']) ? $baker['shop_name'] : (isset($baker['name']) ? $baker['name'] : "My Bakery");
$owner_name = isset($baker['owner_name']) ? $baker['owner_name'] : (isset($baker['name']) ? $baker['name'] : "Owner");
$email = isset($baker['email']) ? $baker['email'] : "";
$phone = isset($baker['phone']) ? $baker['phone'] : (isset($baker['mobile']) ? $baker['mobile'] : "");
$address = isset($baker['address']) ? $baker['address'] : "";
$description = isset($baker['description']) ? $baker['description'] : "We specialize in custom cakes for all occasions.";
$profile_image = isset($baker['profile_image']) ? $baker['profile_image'] : (isset($baker['image']) ? $baker['image'] : "");
$specialty = isset($baker['specialty']) ? $baker['specialty'] : "Custom Cakes";
$years_experience = isset($baker['years_experience']) ? (int)$baker['years_experience'] : 0;
$latitude = isset($baker['latitude']) ? (float)$baker['latitude'] : 0;
$longitude = isset($baker['longitude']) ? (float)$baker['longitude'] : 0;
$is_online = isset($baker['is_online']) ? (int)$baker['is_online'] : 1;

// Build response
$response = array(
    "status" => "success",
    "baker" => array(
        "baker_id" => (int)$baker['baker_id'],
        "shop_name" => $shop_name,
        "owner_name" => $owner_name,
        "email" => $email,
        "phone" => $phone,
        "address" => $address,
        "description" => $description,
        "profile_image" => $profile_image,
        "specialty" => $specialty,
        "years_experience" => $years_experience,
        "latitude" => $latitude,
        "longitude" => $longitude,
        "is_online" => $is_online == 1,
        "total_orders" => $total_orders,
        "rating" => $rating,
        "monthly_income" => $monthly_income
    )
);

echo json_encode($response);
?>
