<?php
// Prevent any output before JSON
ob_start();

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// Clear any output buffer
ob_end_clean();

include "db.php";

// Read JSON or GET
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "user_id required"
    ]);
    exit;
}

$user_id = intval($user_id);

// Get all orders for this customer
$ordersQ = mysqli_query($conn, "
    SELECT 
        o.order_id,
        o.baker_id,
        o.total_amount,
        o.status,
        o.delivery_address,
        o.payment_method,
        DATE_FORMAT(o.created_at, '%d/%m/%Y') as order_date,
        DATE_FORMAT(o.created_at, '%h:%i %p') as order_time,
        b.shop_name as baker_name
    FROM orders o
    LEFT JOIN bakers b ON o.baker_id = b.baker_id
    WHERE o.user_id = $user_id
    ORDER BY o.created_at DESC
");

if (!$ordersQ) {
    echo json_encode([
        "status" => "error",
        "message" => "Query failed: " . mysqli_error($conn)
    ]);
    exit;
}

$orders = [];
$totalOrders = 0;
$pendingCount = 0;
$inProgressCount = 0;
$deliveredCount = 0;
$cancelledCount = 0;

while ($row = mysqli_fetch_assoc($ordersQ)) {
    $order_id = (int)$row['order_id'];
    $totalOrders++;
    
    // Count by status
    $status = strtolower($row['status'] ?? 'pending');
    switch ($status) {
        case 'pending':
            $pendingCount++;
            break;
        case 'in_progress':
        case 'in progress':
            $inProgressCount++;
            break;
        case 'ready':
        case 'delivered':
            $deliveredCount++;
            break;
        case 'cancelled':
            $cancelledCount++;
            break;
    }
    
    // Get cake details from order_items
    $cake_name = "Cake Order";
    $cake_image = "";
    
    // Check if order_items table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'order_items'");
    if ($table_check && mysqli_num_rows($table_check) > 0) {
        $itemQ = mysqli_query($conn, "
            SELECT oi.cake_id, c.cake_name, c.image 
            FROM order_items oi 
            LEFT JOIN cakes c ON oi.cake_id = c.cake_id 
            WHERE oi.order_id = $order_id 
            LIMIT 1
        ");
        
        if ($itemQ && mysqli_num_rows($itemQ) > 0) {
            $item = mysqli_fetch_assoc($itemQ);
            $cake_name = $item['cake_name'] ?? "Cake Order";
            $cake_image = $item['image'] ?? "";
        }
    }
    
    // If still no cake info, try to get from baker's cakes
    if ($cake_image == "" && isset($row['baker_id'])) {
        $baker_id = (int)$row['baker_id'];
        $cakeQ = mysqli_query($conn, "SELECT cake_name, image FROM cakes WHERE baker_id = $baker_id LIMIT 1");
        if ($cakeQ && mysqli_num_rows($cakeQ) > 0) {
            $cake = mysqli_fetch_assoc($cakeQ);
            if ($cake_name == "Cake Order") {
                $cake_name = $cake['cake_name'] ?? "Cake Order";
            }
            $cake_image = $cake['image'] ?? "";
        }
    }
    
    $orders[] = [
        "order_id" => $order_id,
        "order_id_str" => "#" . str_pad($order_id, 5, "0", STR_PAD_LEFT),
        "baker_name" => $row['baker_name'] ?? "Unknown Baker",
        "cake_name" => $cake_name,
        "cake_image" => $cake_image,
        "total_amount" => (float)($row['total_amount'] ?? 0),
        "status" => $row['status'] ?? "pending",
        "order_date" => $row['order_date'] ?? "",
        "order_time" => $row['order_time'] ?? "",
        "payment_method" => $row['payment_method'] ?? "",
        "delivery_address" => $row['delivery_address'] ?? ""
    ];
}

echo json_encode([
    "status" => "success",
    "total_orders" => $totalOrders,
    "stats" => [
        "pending" => $pendingCount,
        "in_progress" => $inProgressCount,
        "delivered" => $deliveredCount,
        "cancelled" => $cancelledCount
    ],
    "orders" => $orders
]);
