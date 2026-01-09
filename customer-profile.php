<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

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

// Get user profile
$profileQ = mysqli_query($conn, "
    SELECT user_id, name, email, phone, address, created_at 
    FROM users 
    WHERE user_id = $user_id
");

if (!$profileQ || mysqli_num_rows($profileQ) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "User not found"
    ]);
    exit;
}

$profile = mysqli_fetch_assoc($profileQ);

// Get order statistics
$totalOrdersQ = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id");
$totalOrders = mysqli_fetch_assoc($totalOrdersQ)['count'] ?? 0;

$pendingOrdersQ = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id AND status IN ('pending', 'in_progress')");
$pendingOrders = mysqli_fetch_assoc($pendingOrdersQ)['count'] ?? 0;

$deliveredOrdersQ = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id AND status = 'delivered'");
$deliveredOrders = mysqli_fetch_assoc($deliveredOrdersQ)['count'] ?? 0;

// Get recent orders (last 5)
$recentOrdersQ = mysqli_query($conn, "
    SELECT 
        o.order_id,
        o.total_amount,
        o.status,
        o.delivery_address,
        DATE_FORMAT(o.created_at, '%Y-%m-%d') as order_date,
        DATE_FORMAT(o.created_at, '%h:%i %p') as order_time
    FROM orders o
    WHERE o.user_id = $user_id
    ORDER BY o.created_at DESC
    LIMIT 5
");

$recentOrders = [];
while ($row = mysqli_fetch_assoc($recentOrdersQ)) {
    $order_id = (int)$row['order_id'];
    
    // Get first cake from order_items or fallback
    $cake_name = "Cake Order";
    $cake_image = "";
    
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
    
    $recentOrders[] = [
        "order_id" => $order_id,
        "order_id_str" => "ORD" . str_pad($order_id, 8, "0", STR_PAD_LEFT),
        "cake_name" => $cake_name,
        "cake_image" => $cake_image,
        "total_amount" => (float)$row['total_amount'],
        "status" => $row['status'],
        "order_date" => $row['order_date'],
        "order_time" => $row['order_time']
    ];
}

// Format member since date
$memberSince = date("F Y", strtotime($profile['created_at']));

echo json_encode([
    "status" => "success",
    "profile" => [
        "user_id" => (int)$profile['user_id'],
        "name" => $profile['name'],
        "email" => $profile['email'],
        "phone" => $profile['phone'] ?? "",
        "address" => $profile['address'] ?? ""
    ],
    "stats" => [
        "total_orders" => (int)$totalOrders,
        "pending_orders" => (int)$pendingOrders,
        "delivered_orders" => (int)$deliveredOrders
    ],
    "recent_orders" => $recentOrders,
    "member_since" => $memberSince
]);
?>
