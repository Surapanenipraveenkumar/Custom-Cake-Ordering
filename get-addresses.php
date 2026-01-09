<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
include "db.php";

/* Read JSON or GET */
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "user_id required"
    ]);
    exit;
}

/* Fetch addresses for user */
$sql = "SELECT * FROM addresses WHERE user_id = '$user_id' ORDER BY is_default DESC, address_id DESC";
$result = mysqli_query($conn, $sql);

$addresses = [];
while ($row = mysqli_fetch_assoc($result)) {
    $addresses[] = $row;
}

echo json_encode([
    "status" => "success",
    "addresses" => $addresses
]);
?>
