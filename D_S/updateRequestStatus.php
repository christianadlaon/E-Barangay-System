<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

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
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

$payload = json_decode(file_get_contents('php://input'), true);
$resident_id = intval($payload['resident_id'] ?? 0);
$status = trim($payload['status'] ?? '');

if ($resident_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid resident_id']);
    $conn->close();
    exit;
}

if ($status !== 'Approved' && $status !== 'Not Approved') {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    $conn->close();
    exit;
}

$statusColumnResult = $conn->query("SHOW COLUMNS FROM residents LIKE 'request_status'");
if ($statusColumnResult && $statusColumnResult->num_rows === 0) {
    $conn->query("ALTER TABLE residents ADD COLUMN request_status VARCHAR(30) NOT NULL DEFAULT 'Not Approved'");
}

$updatedAtColumnResult = $conn->query("SHOW COLUMNS FROM residents LIKE 'updated_at'");
$hasUpdatedAt = $updatedAtColumnResult && $updatedAtColumnResult->num_rows > 0;

if ($hasUpdatedAt) {
    $stmt = $conn->prepare("UPDATE residents SET request_status = ?, updated_at = NOW() WHERE id = ?");
} else {
    $stmt = $conn->prepare("UPDATE residents SET request_status = ? WHERE id = ?");
}

$stmt->bind_param("si", $status, $resident_id);
$stmt->execute();

if ($stmt->affected_rows >= 0) {
    $response = [
        'success' => true,
        'resident_id' => $resident_id,
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}

$stmt->close();
$conn->close();
?>
