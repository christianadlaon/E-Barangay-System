<?php
/**
 * ============================================
 * UNIFIED E-BARANGAY API ROUTER
 * ============================================
 * Centralized entry point for all API requests
 * Consolidates 3 subsystems on a single port (8000)
 * 
 * Routes:
 * - /api/incidents/* → Incident Management
 * - /api/residents/* → Resident Information
 * - /api/documents/* → Document Services
 * - /api/branding/* → Branding Assets
 * - /api/profile/* → Profile Management
 */

// Suppress temp directory notices (PHP internal messages)
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 0);

// ============================================
// CORS & HEADERS
// ============================================
header('Content-Type: application/json; charset=utf-8');

// Handle CORS with credentials
$allowed_origins = [
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
} else {
    // For non-local development, allow all (you can restrict this in production)
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
header('Access-Control-Max-Age: 3600');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================
// ENVIRONMENT & CONFIG
// ============================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/env.php';

// No more hardcoded constants - all from config!
// Define constants for backward compatibility if needed
$dbConfig = DatabaseConfig::getConfig();
define('DB_HOST', $dbConfig['host']);
define('DB_USER', $dbConfig['user']);
define('DB_PASS', $dbConfig['pass']);
define('DB_NAME', $dbConfig['name']);

// API Configuration
define('API_PORT', EnvConfig::get('API_PORT', '8000'));
define('API_VERSION', EnvConfig::get('API_VERSION', '1.0.0'));

// ============================================
// REQUEST ROUTING
// ============================================
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove leading/trailing slashes and split path
$pathSegments = array_filter(explode('/', trim($requestUri, '/')));

// Extract route components
$apiIndex = array_search('api', $pathSegments);
if ($apiIndex === false) {
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found']);
    exit;
}

// Get subsystem and everything after it as endpoint
$subsystem = $pathSegments[$apiIndex + 1] ?? null;
$endpoint = $pathSegments[$apiIndex + 2] ?? null;
$remainingPath = array_slice($pathSegments, $apiIndex + 2);

// Reconstruct remaining path for nested routes
$requestPath = implode('/', $remainingPath);

// ============================================
// ROUTE DISPATCHER
// ============================================

// Map old endpoints to new subsystem structure (backward compatibility)
$compatibilityMap = [
    'register' => ['subsystem' => 'residents', 'endpoint' => 'register'],
    'locations' => ['subsystem' => 'residents', 'endpoint' => 'locations'],
    'check-household' => ['subsystem' => 'residents', 'endpoint' => 'check-household'],
    'check-email' => ['subsystem' => 'residents', 'endpoint' => 'check-email'],
    'info' => ['subsystem' => 'residents', 'endpoint' => 'info'],
    'list' => ['subsystem' => 'residents', 'endpoint' => 'list'],
    'login' => ['subsystem' => 'residents', 'endpoint' => 'login'],
];

// Check if subsystem is actually an old endpoint (backward compatibility)
if (isset($compatibilityMap[$subsystem]) && $subsystem !== null) {
    $compat = $compatibilityMap[$subsystem];
    $subsystem = $compat['subsystem'];
    $endpoint = $compat['endpoint'];
}

try {
    switch ($subsystem) {
        case 'incidents':
            require __DIR__ . '/routes/incidents.php';
            break;
        
        case 'residents':
            require __DIR__ . '/routes/residents.php';
            break;
        
        case 'documents':
            require __DIR__ . '/routes/documents.php';
            break;
        
        case 'branding':
            require __DIR__ . '/routes/branding.php';
            break;
        
        case 'profile':
            require __DIR__ . '/routes/profile.php';
            break;
        
        case 'notifications':
            require __DIR__ . '/routes/notifications.php';
            break;
        
        case 'announcements':
            require __DIR__ . '/routes/announcements.php';
            break;
        
        case 'support':
            require __DIR__ . '/routes/support.php';
            break;
        
        case 'health':
        case 'debug':
            // Diagnostic endpoint to test configuration
            $config = DatabaseConfig::getConfig();
            $envVars = EnvConfig::all();
            
            // Try to connect to database
            $conn = new mysqli(
                $config['host'],
                $config['user'],
                $config['pass'],
                $config['name'],
                $config['port']
            );
            
            $dbStatus = [
                'connected' => $conn->connect_error === null,
                'error' => $conn->connect_error
            ];
            
            if (!$conn->connect_error) {
                // Test a simple query
                $result = $conn->query("SELECT 1 as test");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $dbStatus['query_test'] = 'success';
                    $dbStatus['version'] = $conn->server_info;
                }
                $conn->close();
            }
            
            // Show masked credentials
            $configShow = [
                'DB_HOST' => $config['host'],
                'DB_USER' => $config['user'],
                'DB_PASS' => $config['pass'] ? '***' . substr($config['pass'], -2) : '(EMPTY)',
                'DB_NAME' => $config['name'],
                'DB_PORT' => $config['port'],
            ];
            
            echo json_encode([
                'status' => 'ok',
                'config' => $configShow,
                'database' => $dbStatus,
                'env_file_exists' => file_exists(__DIR__ . '/.env'),
                'env_vars_loaded' => count($envVars),
                'env_sample' => [
                    'DB_HOST' => $envVars['DB_HOST'] ?? '(not set)',
                    'DB_USER' => $envVars['DB_USER'] ?? '(not set)',
                    'DB_PASS' => isset($envVars['DB_PASS']) ? '***' . substr($envVars['DB_PASS'], -2) : '(not set)',
                    'DB_NAME' => $envVars['DB_NAME'] ?? '(not set)',
                ]
            ]);
            break;
        
        case 'test':
            // Simple test endpoint
            echo json_encode([
                'status' => 'ok',
                'message' => 'API is working',
                'timestamp' => date('Y-m-d H:i:s'),
                'request' => [
                    'uri' => $_SERVER['REQUEST_URI'],
                    'method' => $_SERVER['REQUEST_METHOD'],
                    'path_segments' => $pathSegments,
                    'subsystem' => $subsystem,
                    'endpoint' => $endpoint
                ]
            ]);
            break;
        
        default:
            http_response_code(404);
            echo json_encode([
                'error' => 'Subsystem not found',
                'subsystem' => $subsystem,
                'available' => ['incidents', 'residents', 'documents', 'branding', 'profile', 'debug']
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>
