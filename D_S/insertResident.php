<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allow React frontend
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Use unified database configuration
require_once __DIR__ . '/../api/config/database.php';

$dbConfig = DatabaseConfig::getConfig();

// Connect
$conn = new mysqli(
    $dbConfig['host'],
    $dbConfig['user'],
    $dbConfig['pass'],
    $dbConfig['name'],
    $dbConfig['port']
);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

// Map form fields to table columns
$first_name = $data['first_name'] ?? null;
$middle_name = $data['middle_name'] ?? null;
$last_name = $data['last_name'] ?? null;
$birthdate = $data['birthdate'] ?? null;
$contact_number = $data['contact_number'] ?? null;
$email = $data['email'] ?? null;
$temp_purok_id = $data['temp_purok_id'] ?? null;
$temp_street_id = $data['temp_street_id'] ?? null;
$marital_status_id = $data['marital_status_id'] ?? null;

// Add other fields as needed, or set to NULL
$suffix = $data['suffix'] ?? null;
$barangay_id = $data['barangay_id'] ?? null;
$tracking_number = $data['tracking_number'] ?? null;

// Prepare insert statement
$sql = "INSERT INTO residents (
    barangay_id, tracking_number, first_name, middle_name, last_name, suffix,
    birthdate, contact_number, email, temp_purok_id, temp_street_id, marital_status_id,
    created_at, updated_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "isssssssssss",
    $barangay_id,
    $tracking_number,
    $first_name,
    $middle_name,
    $last_name,
    $suffix,
    $birthdate,
    $contact_number,
    $email,
    $temp_purok_id,
    $temp_street_id,
    $marital_status_id
);

// Execute
if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "resident_id" => $stmt->insert_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to insert resident", "details" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
