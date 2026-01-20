<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

$results = [
    "timestamp" => date("Y-m-d H:i:s"),
    "tests" => []
];

// Test 1: Database Connection
include 'db.php';

if ($conn && !mysqli_connect_error()) {
    $results["tests"]["database_connection"] = [
        "status" => "success",
        "message" => "Database connected successfully"
    ];
} else {
    $results["tests"]["database_connection"] = [
        "status" => "error",
        "message" => "Database connection failed: " . mysqli_connect_error()
    ];
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}

// Test 2: Check Users table
$users_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
if ($users_check) {
    $count = mysqli_fetch_assoc($users_check)['count'];
    $results["tests"]["users_table"] = [
        "status" => "success",
        "message" => "Users table exists",
        "record_count" => (int)$count
    ];
    
    // Show sample emails
    $sample = mysqli_query($conn, "SELECT email FROM users LIMIT 3");
    $emails = [];
    while ($row = mysqli_fetch_assoc($sample)) {
        $emails[] = $row['email'];
    }
    $results["tests"]["users_table"]["sample_emails"] = $emails;
} else {
    $results["tests"]["users_table"] = [
        "status" => "error",
        "message" => "Users table not found: " . mysqli_error($conn)
    ];
}

// Test 3: Check Bakers table
$bakers_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM bakers");
if ($bakers_check) {
    $count = mysqli_fetch_assoc($bakers_check)['count'];
    $results["tests"]["bakers_table"] = [
        "status" => "success",
        "message" => "Bakers table exists",
        "record_count" => (int)$count
    ];
    
    // Show sample emails
    $sample = mysqli_query($conn, "SELECT email FROM bakers LIMIT 3");
    $emails = [];
    while ($row = mysqli_fetch_assoc($sample)) {
        $emails[] = $row['email'];
    }
    $results["tests"]["bakers_table"]["sample_emails"] = $emails;
} else {
    $results["tests"]["bakers_table"] = [
        "status" => "error",
        "message" => "Bakers table not found: " . mysqli_error($conn)
    ];
}

// Test 4: Check Delivery table
$delivery_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM delivery_partners");
if ($delivery_check) {
    $count = mysqli_fetch_assoc($delivery_check)['count'];
    $results["tests"]["delivery_table"] = [
        "status" => "success",
        "table_name" => "delivery_partners",
        "message" => "Delivery table exists",
        "record_count" => (int)$count
    ];
} else {
    // Try alternate table name
    $delivery_check2 = mysqli_query($conn, "SELECT COUNT(*) as count FROM delivery_persons");
    if ($delivery_check2) {
        $count = mysqli_fetch_assoc($delivery_check2)['count'];
        $results["tests"]["delivery_table"] = [
            "status" => "success",
            "table_name" => "delivery_persons",
            "message" => "Delivery table exists",
            "record_count" => (int)$count
        ];
    } else {
        $results["tests"]["delivery_table"] = [
            "status" => "error",
            "message" => "No delivery table found"
        ];
    }
}

// Test 5: Users table columns
$cols = mysqli_query($conn, "SHOW COLUMNS FROM users");
if ($cols) {
    $columns = [];
    while ($col = mysqli_fetch_assoc($cols)) {
        $columns[] = $col['Field'];
    }
    $results["tests"]["users_columns"] = [
        "status" => "success",
        "columns" => $columns
    ];
}

// Test 6: Check required PHP files exist
$php_files = [
    "forgot-password.php",
    "register.php",
    "login.php",
    "baker-login.php",
    "delivery-login.php",
    "upload-customer-image.php"
];

$files_check = [];
foreach ($php_files as $file) {
    $files_check[$file] = file_exists($file) ? "exists" : "missing";
}
$results["tests"]["php_files"] = $files_check;

// Overall status
$all_pass = true;
foreach ($results["tests"] as $test) {
    if (isset($test["status"]) && $test["status"] === "error") {
        $all_pass = false;
        break;
    }
}

$results["overall_status"] = $all_pass ? "All tests passed" : "Some tests failed";

echo json_encode($results, JSON_PRETTY_PRINT);
mysqli_close($conn);
?>
