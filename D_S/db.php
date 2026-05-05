<?php
session_start(); // Make sure sessions are started

header('Content-Type: application/json');

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
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

// Check if user is logged in
if (!isset($_SESSION['resident_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

$resident_id = $_SESSION['resident_id'];

// Fetch resident data
$sql = "SELECT first_name, middle_name, last_name, birthdate, contact_number, email, temp_purok_id, street_address, civil_status
        FROM residents
        WHERE resident_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $resident_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode(["success" => true, "data" => $data]);
} else {
    echo json_encode(["success" => false, "message" => "Resident not found"]);
}

$stmt->close();
$conn->close();
?>
