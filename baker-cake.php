<?php
header("Content-Type: application/json");
include "db.php";

/* Read JSON input */
$data = json_decode(file_get_contents("php://input"), true);

$baker_id = $data['baker_id'] ?? null;

if (!$baker_id) {
    echo json_encode([
        "status" => "error",
        "message" => "baker_id required"
    ]);
    exit;
}

/* Fetch cakes created by this baker */
$query = mysqli_query($conn, "
    SELECT cake_id, cake_name, price, image, availability, created_at
    FROM cakes
    WHERE baker_id = '$baker_id'
    ORDER BY cake_id DESC
");

$cakes = [];

while ($row = mysqli_fetch_assoc($query)) {
    $cakes[] = $row;
}

echo json_encode([
    "status" => "success",
    "cakes" => $cakes
]);
exit;
?>
