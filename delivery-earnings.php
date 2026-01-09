<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include 'db.php';

$delivery_id = isset($_GET['delivery_id']) ? intval($_GET['delivery_id']) : 0;

if ($delivery_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid delivery ID"]);
    exit;
}

// Get today's date
$today = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week'));
$monthStart = date('Y-m-01');

// Today's earnings (₹50 per delivery)
$todayQuery = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM orders 
    WHERE delivery_id = '$delivery_id' 
    AND delivery_status = 'delivered'
    AND DATE(delivered_at) = '$today'
");
$todayCount = mysqli_fetch_assoc($todayQuery)['count'] ?? 0;
$todayEarnings = $todayCount * 50;

// Week's earnings
$weekQuery = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM orders 
    WHERE delivery_id = '$delivery_id' 
    AND delivery_status = 'delivered'
    AND DATE(delivered_at) >= '$weekStart'
");
$weekCount = mysqli_fetch_assoc($weekQuery)['count'] ?? 0;
$weekEarnings = $weekCount * 50;

// Month's earnings
$monthQuery = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM orders 
    WHERE delivery_id = '$delivery_id' 
    AND delivery_status = 'delivered'
    AND DATE(delivered_at) >= '$monthStart'
");
$monthCount = mysqli_fetch_assoc($monthQuery)['count'] ?? 0;
$monthEarnings = $monthCount * 50;

// Total orders (all time)
$totalQuery = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM orders 
    WHERE delivery_id = '$delivery_id' 
    AND delivery_status = 'delivered'
");
$totalOrders = mysqli_fetch_assoc($totalQuery)['count'] ?? 0;

// Average per order
$avgPerOrder = $totalOrders > 0 ? 50 : 0;

// Rating (placeholder - could be from a ratings table)
$rating = 4.9;

// Weekly data (last 7 days by day of week)
$weeklyData = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

foreach ($days as $dayName) {
    $dayDate = date('Y-m-d', strtotime("$dayName this week"));
    
    $dayQuery = mysqli_query($conn, "
        SELECT COUNT(*) as count FROM orders 
        WHERE delivery_id = '$delivery_id' 
        AND delivery_status = 'delivered'
        AND DATE(delivered_at) = '$dayDate'
    ");
    $dayCount = mysqli_fetch_assoc($dayQuery)['count'] ?? 0;
    
    $weeklyData[] = [
        "day" => substr($dayName, 0, 3),
        "amount" => $dayCount * 50,
        "orders" => (int)$dayCount
    ];
}

// Recent transactions (last 10 delivered orders)
$transactionsQuery = mysqli_query($conn, "
    SELECT order_id, delivered_at, total_amount 
    FROM orders 
    WHERE delivery_id = '$delivery_id' 
    AND delivery_status = 'delivered'
    ORDER BY delivered_at DESC 
    LIMIT 10
");

$transactions = [];
while ($row = mysqli_fetch_assoc($transactionsQuery)) {
    $deliveredAt = strtotime($row['delivered_at']);
    $dateDisplay = "";
    
    if (date('Y-m-d', $deliveredAt) == $today) {
        $dateDisplay = "Today, " . date('g:i A', $deliveredAt);
    } elseif (date('Y-m-d', $deliveredAt) == date('Y-m-d', strtotime('-1 day'))) {
        $dateDisplay = "Yesterday, " . date('g:i A', $deliveredAt);
    } else {
        $dateDisplay = date('M d, g:i A', $deliveredAt);
    }
    
    $transactions[] = [
        "order_id" => "ORD-" . $row['order_id'],
        "date_time" => $dateDisplay,
        "amount" => 50.0,
        "type" => "delivery"
    ];
}

echo json_encode([
    "status" => "success",
    "today_earnings" => (float)$todayEarnings,
    "week_earnings" => (float)$weekEarnings,
    "month_earnings" => (float)$monthEarnings,
    "total_orders" => (int)$totalOrders,
    "avg_per_order" => (float)$avgPerOrder,
    "rating" => (float)$rating,
    "weekly_data" => $weeklyData,
    "transactions" => $transactions
]);

mysqli_close($conn);
