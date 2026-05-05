<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); // allow React app to fetch

// Use unified database configuration
require_once __DIR__ . '/../api/config/database.php';

$dbConfig = DatabaseConfig::getConfig();

$conn = new mysqli(
    $dbConfig['host'],
    $dbConfig['user'],
    $dbConfig['pass'],
    $dbConfig['name'],
    $dbConfig['port']
);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Use requested resident_id and fallback to 15
$resident_id = intval($_GET['resident_id'] ?? 15);

if ($resident_id <= 0) {
    echo json_encode(['error' => 'Invalid resident_id']);
    $conn->close();
    exit;
}

// Ensure request_status exists for status persistence
$statusColumnResult = $conn->query("SHOW COLUMNS FROM residents LIKE 'request_status'");
if ($statusColumnResult && $statusColumnResult->num_rows === 0) {
    $conn->query("ALTER TABLE residents ADD COLUMN request_status VARCHAR(30) NOT NULL DEFAULT 'Not Approved'");
}

$requestedColumns = [
    'id',
    'tracking_number',
    'first_name',
    'middle_name',
    'last_name',
    'birthdate',
    'contact_number',
    'email',
    'temp_purok_id',
    'temp_street_id',
    'street_address',
    'civil_status',
    'marital_status_id',
    'request_status',
    'updated_at'
];

$columnsResult = $conn->query("SHOW COLUMNS FROM residents");
$availableColumns = [];
while ($column = $columnsResult->fetch_assoc()) {
    $availableColumns[] = $column['Field'];
}

$selectedColumns = array_values(array_intersect($requestedColumns, $availableColumns));
if (empty($selectedColumns)) {
    echo json_encode(['error' => 'No compatible resident columns found']);
    $conn->close();
    exit;
}

$selectList = implode(', ', $selectedColumns);

$stmt = $conn->prepare("SELECT $selectList FROM residents WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $resident_id);
$stmt->execute();
$result = $stmt->get_result();
$resident = $result->fetch_assoc();

if ($resident) {
    if (!isset($resident['request_status']) || !$resident['request_status']) {
        $resident['request_status'] = 'Not Approved';
    }
    echo json_encode($resident);
} else {
    echo json_encode(['error' => 'Resident not found']);
}

$stmt->close();
$conn->close();
?>
