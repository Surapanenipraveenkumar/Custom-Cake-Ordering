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
$order_id = $data['order_id'] ?? $_GET['order_id'] ?? null;

if (!$order_id) {
    echo json_encode([
        "status" => "error",
        "message" => "order_id required"
    ]);
    exit;
}

$order_id = intval($order_id);

// Get order details
$orderQ = mysqli_query($conn, "
    SELECT 
        o.order_id,
        o.user_id,
        o.baker_id,
        o.total_amount,
        o.status,
        o.delivery_address,
        o.delivery_date,
        o.delivery_time,
        o.payment_method,
        DATE_FORMAT(o.created_at, '%d %b %Y') as order_date,
        DATE_FORMAT(o.created_at, '%h:%i %p') as order_time_formatted,
        b.shop_name as baker_name,
        b.phone as baker_phone,
        b.address as baker_address,
        u.name as customer_name,
        u.email as customer_email,
        u.phone as customer_phone
    FROM orders o
    LEFT JOIN bakers b ON o.baker_id = b.baker_id
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = $order_id
");

if (!$orderQ || mysqli_num_rows($orderQ) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Order not found"
    ]);
    exit;
}

$order = mysqli_fetch_assoc($orderQ);

// Get order items
$items = [];

// Check if order_items table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'order_items'");
$hasOrderItemsTable = ($table_check && mysqli_num_rows($table_check) > 0);

if ($hasOrderItemsTable) {
    $itemsQ = mysqli_query($conn, "
        SELECT 
            oi.cake_id,
            oi.quantity,
            oi.price,
            c.cake_name,
            c.image
        FROM order_items oi
        LEFT JOIN cakes c ON oi.cake_id = c.cake_id
        WHERE oi.order_id = $order_id
    ");

    if ($itemsQ && mysqli_num_rows($itemsQ) > 0) {
        while ($item = mysqli_fetch_assoc($itemsQ)) {
            $items[] = [
                "cake_id" => (int)($item['cake_id'] ?? 0),
                "cake_name" => $item['cake_name'] ?? "Cake",
                "cake_image" => $item['image'] ?? "",
                "quantity" => (int)($item['quantity'] ?? 1),
                "price" => (float)($item['price'] ?? 0),
                "customization" => ""
            ];
        }
    }
}

// If no items found, create a default item
if (empty($items)) {
    $baker_id = (int)$order['baker_id'];
    $defaultCakeName = "Custom Cake Order";
    $defaultImage = "";
    
    if ($baker_id > 0) {
        $cakeQ = mysqli_query($conn, "SELECT cake_name, image FROM cakes WHERE baker_id = $baker_id LIMIT 1");
        if ($cakeQ && mysqli_num_rows($cakeQ) > 0) {
            $cake = mysqli_fetch_assoc($cakeQ);
            $defaultCakeName = $cake['cake_name'] ?? "Custom Cake Order";
            $defaultImage = $cake['image'] ?? "";
        }
    }
    
    $items[] = [
        "cake_id" => 0,
        "cake_name" => $defaultCakeName,
        "cake_image" => $defaultImage,
        "quantity" => 1,
        "price" => max(0, (float)$order['total_amount'] - 50),
        "customization" => ""
    ];
}

// Calculate subtotal
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$deliveryFee = 50;
$total = (float)$order['total_amount'];

echo json_encode([
    "status" => "success",
    "order" => [
        "order_id" => (int)$order['order_id'],
        "order_id_str" => "ORD" . str_pad($order['order_id'], 8, "0", STR_PAD_LEFT),
        "status" => $order['status'] ?? "pending",
        "order_date" => $order['order_date'] ?? "",
        "order_time" => $order['order_time_formatted'] ?? "",
        "baker_name" => $order['baker_name'] ?? "Unknown Baker",
        "baker_phone" => $order['baker_phone'] ?? "",
        "baker_address" => $order['baker_address'] ?? "",
        "customer_name" => $order['customer_name'] ?? "",
        "customer_email" => $order['customer_email'] ?? "",
        "customer_phone" => $order['customer_phone'] ?? "",
        "delivery_address" => $order['delivery_address'] ?? "N/A",
        "delivery_date" => $order['delivery_date'] ?? "",
        "delivery_time" => $order['delivery_time'] ?? "",
        "payment_method" => $order['payment_method'] ?? "Cash",
        "items" => $items,
        "subtotal" => $subtotal,
        "delivery_fee" => $deliveryFee,
        "total_amount" => $total
    ]
]);
