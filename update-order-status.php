<?php
/**
 * Update Order Status API - Simple Version
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include "db.php";

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "DB connection failed"]);
    exit;
}

// Get data from JSON or POST
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Fallback to POST data
if (!$data) {
    $data = $_POST;
}

$order_id = intval($data['order_id'] ?? 0);
$status = $data['status'] ?? '';

// Log for debugging
error_log("update-order-status: order_id=$order_id, status=$status, raw=$rawData");

if ($order_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid order_id: $order_id"]);
    exit;
}

if (empty($status)) {
    echo json_encode(["status" => "error", "message" => "Status is required"]);
    exit;
}

$status = mysqli_real_escape_string($conn, $status);

// Update order
$sql = "UPDATE orders SET status = '$status' WHERE order_id = $order_id";
$result = mysqli_query($conn, $sql);

if ($result) {
    echo json_encode([
        "status" => "success",
        "message" => "Order status updated to $status"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Update failed: " . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
