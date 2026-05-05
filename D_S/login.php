<?php
session_start(); // Start session

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
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
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

$data = json_decode(file_get_contents("php://input"), true);
$user = $data['username'] ?? '';
$pass = $data['password'] ?? '';

if (!$user || !$pass) {
    echo json_encode(["success" => false, "message" => "Username and password required"]);
    exit;
}

$stmt = $conn->prepare("SELECT resident_id, username, role FROM resident_accounts WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $user, $pass);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    $_SESSION['resident_id'] = $row['resident_id'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['role'] = $row['role'];

    echo json_encode([
        "success" => true,
        "resident_id" => $row['resident_id'],
        "role" => $row['role'],
        "username" => $row['username']
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid username or password"]);
}

$stmt->close();
$conn->close();
?>
