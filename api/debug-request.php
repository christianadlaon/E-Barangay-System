<?php
/**
 * Debug endpoint - logs all request data
 * Access via: http://localhost:8000/debug-request.php
 */

header('Content-Type: application/json');

$debug = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'NOT SET',
    'url' => $_SERVER['REQUEST_URI'],
    '_POST' => $_POST,
    '_GET' => $_GET,
    '_FILES' => array_keys($_FILES ?? []),
    'php_input_size' => strlen(file_get_contents('php://input')),
    'headers' => getallheaders(),
];

echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
