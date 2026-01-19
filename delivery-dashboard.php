<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include "db.php";

$delivery_id = $_GET['delivery_id'] ?? null;

if (!$delivery_id) {
    echo json_encode([
        "status" => "error",
        "message" => "delivery_id required"
    ]);
    exit;
}

$delivery_id = mysqli_real_escape_string($conn, $delivery_id);

// Check which delivery table exists - match set-for-delivery.php logic
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'delivery_partners'");
$delivery_table = ($table_check && mysqli_num_rows($table_check) > 0) ? 'delivery_partners' : 'delivery_persons';

error_log("delivery-dashboard: Using table $delivery_table for delivery_id=$delivery_id");

// Get delivery person info
$deliveryQuery = mysqli_query($conn, "SELECT * FROM $delivery_table WHERE delivery_id = '$delivery_id'");
if (!$deliveryQuery || mysqli_num_rows($deliveryQuery) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Delivery person not found"
    ]);
    exit;
}

$delivery = mysqli_fetch_assoc($deliveryQuery);
$is_online = isset($delivery['is_online']) ? (int)$delivery['is_online'] : 1;
$delivery_service_area = strtolower(trim($delivery['service_area'] ?? ''));
$delivery_lat = isset($delivery['latitude']) ? (float)$delivery['latitude'] : 0;
$delivery_lng = isset($delivery['longitude']) ? (float)$delivery['longitude'] : 0;

// Add delivery columns to orders table if not exists
$checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'delivery_id'");
if (mysqli_num_rows($checkColumn) == 0) {
    mysqli_query($conn, "ALTER TABLE orders ADD COLUMN delivery_id INT DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE orders ADD COLUMN delivery_status VARCHAR(50) DEFAULT 'pending'");
    mysqli_query($conn, "ALTER TABLE orders ADD COLUMN picked_up_at DATETIME DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE orders ADD COLUMN delivered_at DATETIME DEFAULT NULL");
}

// Get today's date
$today = date('Y-m-d');

// Today's deliveries count
$todayDeliveriesQuery = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM orders 
    WHERE delivery_id = '$delivery_id' 
    AND delivery_status = 'delivered'
    AND DATE(delivered_at) = '$today'
");
$todayDeliveries = mysqli_fetch_assoc($todayDeliveriesQuery)['count'] ?? 0;

// Today's earnings (assume ₹50 per delivery)
$todayEarnings = $todayDeliveries * 50;

// Total deliveries
$totalDeliveriesQuery = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM orders 
    WHERE delivery_id = '$delivery_id' 
    AND delivery_status = 'delivered'
");
$totalDeliveries = mysqli_fetch_assoc($totalDeliveriesQuery)['count'] ?? 0;

// ==================== CHECK IF DELIVERY PARTNER IS ONLINE ====================
// If delivery partner is offline, return empty orders list
$pendingOrders = [];

if ($is_online == 0) {
    // Delivery partner is OFFLINE - don't show any available orders
    echo json_encode([
        "status" => "success",
        "delivery_name" => $delivery['name'],
        "is_online" => 0,
        "today_deliveries" => (int)$todayDeliveries,
        "today_earnings" => (float)$todayEarnings,
        "total_deliveries" => (int)$totalDeliveries,
        "pending_orders" => [], // Empty orders for offline partners
        "message" => "You are offline. Go online to see available orders."
    ]);
    exit;
}

// ==================== ONLINE PARTNER - SHOW NEARBY ORDERS ====================

// Add ready_for_delivery column if not exists
mysqli_query($conn, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS ready_for_delivery TINYINT(1) DEFAULT 0");

// Fetch all eligible orders first, then filter by proximity
$pendingOrdersQuery = mysqli_query($conn, "
    SELECT o.*, 
           u.name as customer_name, u.phone as customer_phone,
           b.shop_name as baker_name, b.address as baker_address, b.phone as baker_phone,
           b.latitude as baker_lat, b.longitude as baker_lng
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    LEFT JOIN bakers b ON o.baker_id = b.baker_id
    WHERE o.ready_for_delivery = 1
    AND o.delivery_address IS NOT NULL 
    AND o.delivery_address != ''
    AND LOWER(o.delivery_address) NOT LIKE '%pickup%'
    AND LOWER(o.delivery_address) NOT LIKE '%pick up%'
    AND LOWER(o.delivery_address) NOT LIKE '%pickup from store%'
    AND (
        (o.delivery_id IS NULL AND o.delivery_status = 'pending')
        OR 
        (o.delivery_id = '$delivery_id' AND o.delivery_status IN ('assigned', 'picked_up', 'out_for_delivery'))
    )
    ORDER BY 
        CASE WHEN o.delivery_id = '$delivery_id' THEN 0 ELSE 1 END,
        o.created_at DESC
    LIMIT 50
");

// Function to calculate distance between two coordinates (in km)
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    if ($lat1 == 0 || $lng1 == 0 || $lat2 == 0 || $lng2 == 0) {
        return 999; // Large distance if coordinates not available
    }
    
    $earthRadius = 6371; // Earth's radius in kilometers
    
    $latDiff = deg2rad($lat2 - $lat1);
    $lngDiff = deg2rad($lng2 - $lng1);
    
    $a = sin($latDiff / 2) * sin($latDiff / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lngDiff / 2) * sin($lngDiff / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

// Maximum delivery radius in km
$MAX_DELIVERY_RADIUS_KM = 10;

while ($row = mysqli_fetch_assoc($pendingOrdersQuery)) {
    $order_is_assigned_to_me = ($row['delivery_id'] == $delivery_id);
    
    // ALWAYS show orders that are already assigned to this delivery partner
    if ($order_is_assigned_to_me) {
        $pendingOrders[] = [
            "order_id" => (int)$row['order_id'],
            "customer_name" => $row['customer_name'] ?? "Customer",
            "customer_phone" => $row['customer_phone'] ?? "",
            "customer_address" => $row['delivery_address'] ?? "",
            "baker_name" => $row['baker_name'] ?? "Baker",
            "baker_address" => $row['baker_address'] ?? "",
            "baker_phone" => $row['baker_phone'] ?? "",
            "total_amount" => (float)$row['total_amount'],
            "delivery_status" => $row['delivery_status'],
            "created_at" => $row['created_at'],
            "is_assigned" => true
        ];
        continue;
    }
    
    // For unassigned orders, check proximity
    $include_order = false;
    
    // Method 1: Check by service area (if available)
    if (!empty($delivery_service_area)) {
        $baker_address = strtolower($row['baker_address'] ?? '');
        $customer_address = strtolower($row['delivery_address'] ?? '');
        
        // Check if service area matches baker location or customer location
        if (strpos($baker_address, $delivery_service_area) !== false ||
            strpos($customer_address, $delivery_service_area) !== false) {
            $include_order = true;
        }
    }
    
    // Method 2: Check by GPS coordinates (if available and service area didn't match)
    if (!$include_order && $delivery_lat != 0 && $delivery_lng != 0) {
        $baker_lat = isset($row['baker_lat']) ? (float)$row['baker_lat'] : 0;
        $baker_lng = isset($row['baker_lng']) ? (float)$row['baker_lng'] : 0;
        
        $distance = calculateDistance($delivery_lat, $delivery_lng, $baker_lat, $baker_lng);
        
        if ($distance <= $MAX_DELIVERY_RADIUS_KM) {
            $include_order = true;
        }
    }
    
    // Method 3: If no location data available, include order (fallback)
    if (!$include_order && empty($delivery_service_area) && $delivery_lat == 0) {
        $include_order = true;
    }
    
    if ($include_order) {
        $pendingOrders[] = [
            "order_id" => (int)$row['order_id'],
            "customer_name" => $row['customer_name'] ?? "Customer",
            "customer_phone" => $row['customer_phone'] ?? "",
            "customer_address" => $row['delivery_address'] ?? "",
            "baker_name" => $row['baker_name'] ?? "Baker",
            "baker_address" => $row['baker_address'] ?? "",
            "baker_phone" => $row['baker_phone'] ?? "",
            "total_amount" => (float)$row['total_amount'],
            "delivery_status" => $row['delivery_status'],
            "created_at" => $row['created_at'],
            "is_assigned" => false
        ];
    }
}

echo json_encode([
    "status" => "success",
    "delivery_name" => $delivery['name'],
    "is_online" => (int)$is_online,
    "today_deliveries" => (int)$todayDeliveries,
    "today_earnings" => (float)$todayEarnings,
    "total_deliveries" => (int)$totalDeliveries,
    "pending_orders" => $pendingOrders
]);
?>
