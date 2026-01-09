<?php
// API to add/remove cake from favorites
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

include "db.php";

// Read JSON or POST/GET
$data = json_decode(file_get_contents("php://input"), true);
$user_id = isset($data['user_id']) ? $data['user_id'] : (isset($_GET['user_id']) ? $_GET['user_id'] : (isset($_POST['user_id']) ? $_POST['user_id'] : null));
$cake_id = isset($data['cake_id']) ? $data['cake_id'] : (isset($_GET['cake_id']) ? $_GET['cake_id'] : (isset($_POST['cake_id']) ? $_POST['cake_id'] : null));
$action = isset($data['action']) ? $data['action'] : (isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : 'toggle'));

if (!$user_id || !$cake_id) {
    echo json_encode(array(
        "status" => "error",
        "message" => "user_id and cake_id required"
    ));
    exit;
}

$user_id = intval($user_id);
$cake_id = intval($cake_id);

// Create favorites table if not exists
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS favorites (
        favorite_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        cake_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_favorite (user_id, cake_id)
    )
");

// Check if already favorited
$checkQuery = mysqli_query($conn, "SELECT * FROM favorites WHERE user_id = $user_id AND cake_id = $cake_id");
$isFavorite = mysqli_num_rows($checkQuery) > 0;

if ($action == 'add' || ($action == 'toggle' && !$isFavorite)) {
    // Add to favorites
    mysqli_query($conn, "INSERT IGNORE INTO favorites (user_id, cake_id) VALUES ($user_id, $cake_id)");
    echo json_encode(array(
        "status" => "success",
        "message" => "Added to favorites",
        "is_favorite" => true
    ));
} else if ($action == 'remove' || ($action == 'toggle' && $isFavorite)) {
    // Remove from favorites
    mysqli_query($conn, "DELETE FROM favorites WHERE user_id = $user_id AND cake_id = $cake_id");
    echo json_encode(array(
        "status" => "success",
        "message" => "Removed from favorites",
        "is_favorite" => false
    ));
} else if ($action == 'check') {
    // Just check if favorited
    echo json_encode(array(
        "status" => "success",
        "is_favorite" => $isFavorite
    ));
} else {
    echo json_encode(array(
        "status" => "error",
        "message" => "Invalid action"
    ));
}
?>
