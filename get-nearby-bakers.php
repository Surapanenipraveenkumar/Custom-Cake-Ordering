<?php
// API to get nearby bakers based on customer location
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

include "db.php";

// Get parameters
$latitude = isset($_GET['latitude']) ? floatval($_GET['latitude']) : 0;
$longitude = isset($_GET['longitude']) ? floatval($_GET['longitude']) : 0;
$radius = isset($_GET['radius']) ? floatval($_GET['radius']) : 10; // Default 10km

if ($latitude == 0 || $longitude == 0) {
    echo json_encode(array(
        "status" => "error",
        "message" => "latitude and longitude required"
    ));
    exit;
}

// Haversine formula to calculate distance in kilometers
// Get all ONLINE bakers with their distance from the customer
$query = mysqli_query($conn, "
    SELECT 
        b.baker_id,
        b.shop_name,
        b.owner_name,
        b.email,
        b.phone,
        b.address,
        b.profile_image,
        b.latitude,
        b.longitude,
        b.specialty,
        b.years_experience,
        b.is_online,
        (
            6371 * acos(
                cos(radians($latitude)) * cos(radians(b.latitude)) * 
                cos(radians(b.longitude) - radians($longitude)) + 
                sin(radians($latitude)) * sin(radians(b.latitude))
            )
        ) AS distance
    FROM bakers b
    WHERE b.latitude IS NOT NULL 
      AND b.longitude IS NOT NULL
      AND (b.is_online = 1 OR b.is_online IS NULL)
    HAVING distance <= $radius
    ORDER BY distance ASC
");

$bakers = array();

if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        // Get average rating for this baker
        $rating = 4.5;
        $reviewCount = 0;
        $ratingQuery = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE baker_id = " . $row['baker_id']);
        if ($ratingQuery) {
            $ratingResult = mysqli_fetch_assoc($ratingQuery);
            $reviewCount = (int)$ratingResult['count'];
        }

        $bakers[] = array(
            "baker_id" => (int)$row['baker_id'],
            "shop_name" => isset($row['shop_name']) ? $row['shop_name'] : "Baker",
            "owner_name" => isset($row['owner_name']) ? $row['owner_name'] : "",
            "address" => isset($row['address']) ? $row['address'] : "",
            "profile_image" => isset($row['profile_image']) ? $row['profile_image'] : "",
            "latitude" => (float)$row['latitude'],
            "longitude" => (float)$row['longitude'],
            "specialty" => isset($row['specialty']) ? $row['specialty'] : "Custom Cakes",
            "years_experience" => isset($row['years_experience']) ? (int)$row['years_experience'] : 0,
            "distance" => round((float)$row['distance'], 1),
            "rating" => $rating,
            "review_count" => $reviewCount
        );
    }
}

echo json_encode(array(
    "status" => "success",
    "bakers" => $bakers,
    "count" => count($bakers)
));
?>
