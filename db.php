<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "Custom-Cake";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    header("Content-Type: application/json");
    echo json_encode(array(
        "status" => "error",
        "message" => "Database connection failed: " . mysqli_connect_error()
    ));
    exit;
}
?>
