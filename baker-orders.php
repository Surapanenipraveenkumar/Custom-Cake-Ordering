<?php
header("Content-Type: application/json");
include "db.php";

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Get baker_id from GET parameter
$baker_id = $_GET['baker_id'] ?? null;

if (!$baker_id) {
    echo json_encode([
        "status" => "error",
        "message" => "baker_id required"
    ]);
    exit;
}

$baker_id = intval($baker_id);

// Fetch orders for this baker - directly from orders table
// First try with order_items, if that returns no data, fall back to orders table directly
$query = mysqli_query($conn, "
    SELECT 
        o.order_id,
        o.user_id,
        o.total_amount,
        o.status,
        o.delivery_address,
        o.delivery_date,
        o.delivery_time,
        o.payment_method,
        DATE_FORMAT(o.created_at, '%Y-%m-%d at %H:%i') as order_date,
        u.name as customer_name,
        u.email as customer_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE o.baker_id = $baker_id
    ORDER BY o.created_at DESC
");

if (!$query) {
    error_log("baker-orders.php: Query failed: " . mysqli_error($conn));
    echo json_encode([
        "status" => "error",
        "message" => "Query failed: " . mysqli_error($conn)
    ]);
    exit;
}

$orders = [];

while ($row = mysqli_fetch_assoc($query)) {
    $order_id = (int)$row['order_id'];
    
    // Try to get cake info from order_items first
    $cake_name = "Cake Order";
    $cake_image = "";
    $cake_id = 0;
    $price = (float)$row['total_amount'];
    $quantity = 1;
    
    // Check if order_items table exists and has data for this order
    $items_query = mysqli_query($conn, "
        SELECT oi.cake_id, oi.quantity, oi.price, c.cake_name, c.image as cake_image
        FROM order_items oi
        LEFT JOIN cakes c ON oi.cake_id = c.cake_id
        WHERE oi.order_id = $order_id
        LIMIT 1
    ");
    
    if ($items_query && mysqli_num_rows($items_query) > 0) {
        $item = mysqli_fetch_assoc($items_query);
        $cake_id = (int)$item['cake_id'];
        $cake_name = $item['cake_name'] ?? "Cake Order";
        $cake_image = $item['cake_image'] ?? "";
        $price = (float)$item['price'];
        $quantity = (int)$item['quantity'];
    } else {
        // Fallback: Get any cake from this baker to show info
        $cake_query = mysqli_query($conn, "
            SELECT cake_id, cake_name, image, price 
            FROM cakes 
            WHERE baker_id = $baker_id 
            LIMIT 1
        ");
        if ($cake_query && mysqli_num_rows($cake_query) > 0) {
            $cake = mysqli_fetch_assoc($cake_query);
            $cake_id = (int)$cake['cake_id'];
            $cake_name = $cake['cake_name'];
            $cake_image = $cake['image'] ?? "";
        }
    }
    
    $orders[] = [
        "order_id" => $order_id,
        "order_id_str" => "ORD" . str_pad($order_id, 8, "0", STR_PAD_LEFT),
        "cake_id" => $cake_id,
        "cake_name" => $cake_name,
        "cake_image" => $cake_image,
        "customer_name" => $row['customer_name'] ?? "Customer",
        "customer_email" => $row['customer_email'] ?? "",
        "price" => $price,
        "quantity" => $quantity,
        "order_date" => $row['order_date'] ?? "",
        "delivery_type" => !empty($row['delivery_address']) ? "delivery" : "pickup",
        "delivery_address" => $row['delivery_address'] ?? "",
        "delivery_date" => $row['delivery_date'] ?? "",
        "delivery_time" => $row['delivery_time'] ?? "",
        "payment_method" => $row['payment_method'] ?? "",
        "status" => $row['status'] ?? "pending",
        "total_amount" => (float)$row['total_amount']
    ];
}

error_log("baker-orders.php: Found " . count($orders) . " orders for baker_id=$baker_id");

echo json_encode([
    "status" => "success",
    "orders" => $orders
]);
?>
