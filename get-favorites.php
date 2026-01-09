<?php
// API to get user's favorite cakes
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

include "db.php";

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : (isset($_POST['user_id']) ? $_POST['user_id'] : null);

if (!$user_id) {
    echo json_encode(array(
        "status" => "error",
        "message" => "user_id required"
    ));
    exit;
}

$user_id = intval($user_id);

// Get favorited cakes with cake details
$query = mysqli_query($conn, "
    SELECT 
        c.cake_id,
        c.cake_name,
        c.price,
        c.image,
        c.description,
        c.baker_id,
        b.shop_name as baker_name,
        f.created_at as favorited_at
    FROM favorites f
    JOIN cakes c ON f.cake_id = c.cake_id
    LEFT JOIN bakers b ON c.baker_id = b.baker_id
    WHERE f.user_id = $user_id
    ORDER BY f.created_at DESC
");

$favorites = array();
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $favorites[] = array(
            "cake_id" => (int)$row['cake_id'],
            "cake_name" => isset($row['cake_name']) ? $row['cake_name'] : "Cake",
            "price" => (float)$row['price'],
            "image" => isset($row['image']) ? $row['image'] : "",
            "description" => isset($row['description']) ? $row['description'] : "",
            "baker_id" => (int)$row['baker_id'],
            "baker_name" => isset($row['baker_name']) ? $row['baker_name'] : "Baker",
            "favorited_at" => isset($row['favorited_at']) ? $row['favorited_at'] : ""
        );
    }
}

echo json_encode(array(
    "status" => "success",
    "favorites" => $favorites,
    "count" => count($favorites)
));
?>
