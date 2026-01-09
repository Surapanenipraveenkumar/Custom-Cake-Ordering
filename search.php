<?php
header("Content-Type: application/json");
include "db.php";

// ... (rest of your code)

$host = "localhost";
$user = "root";
$pass = "";
// THIS LINE MUST BE CORRECT
$db   = "custom-cake"; 

$conn = new mysqli($host, $user, $pass, $db);

// ... (rest of your code)
if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit;
}

/* ---------- READ JSON ---------- */
$data = json_decode(file_get_contents("php://input"), true);

/* ---------- FILTER INPUTS ---------- */
$keyword   = trim($data['keyword'] ?? '');
$shape     = trim($data['shape'] ?? '');
$colour    = trim($data['colour'] ?? '');
$flavour   = trim($data['flavour'] ?? '');
$topping   = trim($data['topping'] ?? '');
$min_price = $data['min_price'] ?? '';
$max_price = $data['max_price'] ?? '';

/* ---------- BASE QUERY ---------- */
$sql = "
    SELECT DISTINCT c.*
    FROM cakes c
    LEFT JOIN cake_shapes cs   ON c.cake_id = cs.cake_id
    LEFT JOIN cake_colours cc  ON c.cake_id = cc.cake_id
    LEFT JOIN cake_flavours cf ON c.cake_id = cf.cake_id
    LEFT JOIN cake_toppings ct ON c.cake_id = ct.cake_id
    WHERE c.availability = 1
";

$params = [];
$types  = "";

/* ---------- SEARCH CONDITIONS ---------- */
if ($keyword !== '') {
    $sql .= " AND (c.cake_name LIKE ? OR c.description LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $types .= "ss";
}

if ($shape !== '') {
    $sql .= " AND cs.shape = ?";
    $params[] = $shape;
    $types .= "s";
}

if ($colour !== '') {
    $sql .= " AND cc.colour = ?";
    $params[] = $colour;
    $types .= "s";
}

if ($flavour !== '') {
    $sql .= " AND cf.flavour = ?";
    $params[] = $flavour;
    $types .= "s";
}

if ($topping !== '') {
    $sql .= " AND ct.topping = ?";
    $params[] = $topping;
    $types .= "s";
}

if ($min_price !== '') {
    $sql .= " AND c.price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price !== '') {
    $sql .= " AND c.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

$sql .= " ORDER BY c.cake_id DESC";

/* ---------- EXECUTE ---------- */
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

/* ---------- FETCH RESULTS ---------- */
$cakes = [];
while ($row = $result->fetch_assoc()) {
    $cakes[] = $row;
}

/* ---------- RESPONSE ---------- */
echo json_encode([
    "status" => "success",
    "count" => count($cakes),
    "cakes" => $cakes
]);
exit;
?>