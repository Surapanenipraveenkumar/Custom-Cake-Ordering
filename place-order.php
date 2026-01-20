<?php
/**
 * Place Order API - Debug Version
 */

// Force JSON output no matter what
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Disable error display and report all errors to log
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Custom error handler to always return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    http_response_code(200); // Return 200 even on error
    echo json_encode(["status" => "error", "message" => "PHP Error: $errstr"]);
    exit;
});

set_exception_handler(function($e) {
    error_log("PHP Exception: " . $e->getMessage());
    http_response_code(200);
    echo json_encode(["status" => "error", "message" => "Exception: " . $e->getMessage()]);
    exit;
});

// Include database
include "db.php";

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "DB connection failed"]);
    exit;
}

// Read input
$raw = file_get_contents("php://input");
error_log("place-order.php: Raw input: " . $raw);

$data = json_decode($raw, true);
if (!$data) $data = $_POST;

// Validate user_id
if (!isset($data['user_id']) || intval($data['user_id']) <= 0) {
    echo json_encode(["status" => "error", "message" => "Valid user_id required"]);
    exit;
}

$user_id = intval($data['user_id']);
$delivery_fee = intval($data['delivery_fee'] ?? 0);
$delivery_address = mysqli_real_escape_string($conn, $data['delivery_address'] ?? '');
$delivery_date = mysqli_real_escape_string($conn, $data['delivery_date'] ?? '');
$delivery_time = mysqli_real_escape_string($conn, $data['delivery_time'] ?? '');
$payment_method = mysqli_real_escape_string($conn, $data['payment_method'] ?? 'online');

error_log("place-order.php: user_id=$user_id");

// Get cart items WITH customization options
$cartQ = mysqli_query($conn, "
    SELECT cart.cart_id, cart.cake_id, cart.quantity,
           cart.weight, cart.shape, cart.color, cart.flavor, cart.toppings,
           cakes.cake_name, cakes.price, cakes.baker_id, cakes.image
    FROM cart
    JOIN cakes ON cart.cake_id = cakes.cake_id
    WHERE cart.user_id = $user_id
");

if (!$cartQ) {
    echo json_encode(["status" => "error", "message" => "Cart query failed: " . mysqli_error($conn)]);
    exit;
}

$cart_count = mysqli_num_rows($cartQ);
error_log("place-order.php: Cart has $cart_count items");

if ($cart_count == 0) {
    echo json_encode(["status" => "error", "message" => "Cart is empty"]);
    exit;
}

$subtotal = 0;
$baker_id = 0;
$items = [];

while ($row = mysqli_fetch_assoc($cartQ)) {
    $item_total = floatval($row['price']) * intval($row['quantity']);
    $subtotal += $item_total;
    $baker_id = intval($row['baker_id']);
    
    // Build customization options string
    $custom_parts = [];
    if (!empty($row['weight'])) $custom_parts[] = "Weight: " . $row['weight'];
    if (!empty($row['shape'])) $custom_parts[] = "Shape: " . $row['shape'];
    if (!empty($row['color'])) $custom_parts[] = "Color: " . $row['color'];
    if (!empty($row['flavor'])) $custom_parts[] = "Flavor: " . $row['flavor'];
    if (!empty($row['toppings'])) $custom_parts[] = "Toppings: " . $row['toppings'];
    $custom_options = implode(", ", $custom_parts);
    
    $items[] = [
        "cake_id" => intval($row['cake_id']),
        "cake_name" => $row['cake_name'],
        "price" => floatval($row['price']),
        "quantity" => intval($row['quantity']),
        "image" => $row['image'],
        "item_total" => $item_total,
        "custom_options" => $custom_options
    ];
}

$total = $subtotal + $delivery_fee;
error_log("place-order.php: total=$total, baker_id=$baker_id");

// Insert order
$sql = "INSERT INTO orders (user_id, baker_id, total_amount, status, delivery_address, delivery_date, delivery_time, payment_method) 
        VALUES ($user_id, $baker_id, $total, 'pending', '$delivery_address', '$delivery_date', '$delivery_time', '$payment_method')";

if (!mysqli_query($conn, $sql)) {
    echo json_encode(["status" => "error", "message" => "Order insert failed: " . mysqli_error($conn)]);
    exit;
}

$order_id = mysqli_insert_id($conn);
$order_id_str = "ORD" . str_pad($order_id, 8, "0", STR_PAD_LEFT);
error_log("place-order.php: Order created: $order_id");

// Insert order items if table exists (with custom_options)
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'order_items'");
if ($table_check && mysqli_num_rows($table_check) > 0) {
    // Ensure custom_options column exists
    $col_check = mysqli_query($conn, "SHOW COLUMNS FROM order_items LIKE 'custom_options'");
    if (!$col_check || mysqli_num_rows($col_check) == 0) {
        mysqli_query($conn, "ALTER TABLE order_items ADD COLUMN custom_options TEXT NULL");
    }
    
    foreach ($items as $item) {
        $custom_opt = mysqli_real_escape_string($conn, $item['custom_options'] ?? '');
        mysqli_query($conn, "INSERT INTO order_items (order_id, cake_id, quantity, price, custom_options) VALUES ($order_id, {$item['cake_id']}, {$item['quantity']}, {$item['price']}, '$custom_opt')");
    }
}

// Clear cart
mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");
error_log("place-order.php: Cart cleared");

// Get customer name
$customer_name = "Customer";
$cq = mysqli_query($conn, "SELECT name FROM users WHERE user_id = $user_id");
if (!$cq) {
    // Try with 'id' column if user_id fails
    $cq = mysqli_query($conn, "SELECT name FROM users WHERE id = $user_id");
}
if ($cq && mysqli_num_rows($cq) > 0) {
    $cr = mysqli_fetch_assoc($cq);
    $customer_name = $cr['name'] ?? "Customer";
}

// Create notifications table
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_type VARCHAR(20) NOT NULL,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        order_id INT DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Add notifications
$title1 = mysqli_real_escape_string($conn, "New Order!");
$msg1 = mysqli_real_escape_string($conn, "You have a new order #$order_id from $customer_name");
mysqli_query($conn, "INSERT INTO notifications (user_type, user_id, type, title, message, order_id) VALUES ('baker', $baker_id, 'new_order', '$title1', '$msg1', $order_id)");

$title2 = mysqli_real_escape_string($conn, "Order Placed!");
$msg2 = mysqli_real_escape_string($conn, "Your order #$order_id has been placed successfully!");
mysqli_query($conn, "INSERT INTO notifications (user_type, user_id, type, title, message, order_id) VALUES ('customer', $user_id, 'order_placed', '$title2', '$msg2', $order_id)");

// ✅ SEND FIREBASE PUSH NOTIFICATIONS
include_once 'send-push-notification.php';

// Send push to baker
$baker_token_q = mysqli_query($conn, "SELECT fcm_token FROM bakers WHERE baker_id = $baker_id AND fcm_token IS NOT NULL AND fcm_token != ''");
if ($baker_token_q && mysqli_num_rows($baker_token_q) > 0) {
    $baker_token_row = mysqli_fetch_assoc($baker_token_q);
    $baker_fcm_token = $baker_token_row['fcm_token'];
    if (!empty($baker_fcm_token)) {
        $result = sendPushNotification(
            $baker_fcm_token,
            "🎂 New Order!",
            "You have a new order #$order_id from $customer_name",
            ['type' => 'new_order', 'order_id' => strval($order_id), 'user_type' => 'baker']
        );
        error_log("FCM to baker: " . ($result ? "SUCCESS" : "FAILED"));
    }
}

// Send push to customer
$customer_token_q = mysqli_query($conn, "SELECT fcm_token FROM users WHERE user_id = $user_id AND fcm_token IS NOT NULL AND fcm_token != ''");
if (!$customer_token_q) {
    // Try with 'id' column
    $customer_token_q = mysqli_query($conn, "SELECT fcm_token FROM users WHERE id = $user_id AND fcm_token IS NOT NULL AND fcm_token != ''");
}
if ($customer_token_q && mysqli_num_rows($customer_token_q) > 0) {
    $customer_token_row = mysqli_fetch_assoc($customer_token_q);
    $customer_fcm_token = $customer_token_row['fcm_token'];
    if (!empty($customer_fcm_token)) {
        $result = sendPushNotification(
            $customer_fcm_token,
            "✅ Order Placed!",
            "Your order #$order_id has been placed successfully!",
            ['type' => 'order_placed', 'order_id' => strval($order_id), 'user_type' => 'customer']
        );
        error_log("FCM to customer: " . ($result ? "SUCCESS" : "FAILED"));
    }
}

error_log("place-order.php: SUCCESS - Order $order_id");

// Success!
echo json_encode([
    "status" => "success",
    "message" => "Order placed",
    "order_id" => $order_id,
    "order_id_str" => $order_id_str,
    "subtotal" => $subtotal,
    "delivery_fee" => $delivery_fee,
    "total_amount" => $total,
    "items" => $items
]);

mysqli_close($conn);
?>
