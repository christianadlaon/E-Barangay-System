<?php
/**
 * Resident API Routes
 * Handles all resident-related endpoints
 */

require_once __DIR__ . '/../helpers.php';

$payload = Request::getPayload();
$method = Request::getMethod();
$endpoint = $endpoint ?? null;

try {
    $db = Database::getInstance();
    
    switch ($endpoint) {
        case 'locations':
            handleLocations($method, $payload);
            break;
        
        case 'check-household':
            handleCheckHousehold($method, $payload);
            break;
        
        case 'check-email':
            handleCheckEmail($method, $payload);
            break;
        
        case 'register':
            handleRegister($method, $payload);
            break;
        
        case 'login':
            handleLogin($method, $payload);
            break;
        
        case 'info':
            handleGetResidentInfo($method, $payload);
            break;
        
        case 'list':
            handleGetResidents($method, $payload);
            break;
        
        case 'status':
            handleStatus($method, $payload);
            break;
        
        default:
            Response::notFound("Endpoint: $endpoint not found");
    }
} catch (Exception $e) {
    Response::internalError($e->getMessage());
}

// ============================================
// ENDPOINT HANDLERS
// ============================================

function handleLocations($method, $payload) {
    if ($method !== 'GET') {
        Response::badRequest('Only GET method allowed');
        return;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Return mock locations data
    $locations = [
        [
            'id' => 1,
            'name' => 'Purok 1',
            'streets' => [
                ['id' => 1, 'name' => 'Main Street'],
                ['id' => 2, 'name' => 'Secondary Street']
            ]
        ],
        [
            'id' => 2,
            'name' => 'Purok 2',
            'streets' => [
                ['id' => 3, 'name' => 'Third Street']
            ]
        ]
    ];
    
    Response::success($locations, 'Locations retrieved successfully');
}

function handleCheckHousehold($method, $payload) {
    if ($method !== 'GET') {
        Response::badRequest('Only GET method allowed');
        return;
    }
    
    $houseNumber = Request::getQuery('house_number');
    $streetId = Request::getQuery('street_id');
    $purokId = Request::getQuery('purok_id');
    
    if (!$houseNumber) {
        Response::badRequest('house_number parameter required');
        return;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Check if households table exists and has the required structure
        $tables = $db->query("SHOW TABLES LIKE 'households'");
        if (!$tables || $tables->num_rows === 0) {
            // Table doesn't exist, return as not found
            Response::success(['exists' => false], 'Household not found', 200);
            return;
        }
        
        // Try to query households table
        // Note: The exact column name and logic may vary based on actual schema
        $query = "SELECT * FROM households WHERE 1=1";
        
        // Add filters if columns exist
        $params = [];
        $types = "";
        
        // Try to add house_number filter if it exists
        if ($houseNumber) {
            $query .= " AND (house_number = ? OR housing_number = ? OR house_num = ?)";
            $params[] = $houseNumber;
            $params[] = $houseNumber;
            $params[] = $houseNumber;
            $types .= "sss";
        }
        
        if ($streetId) {
            $query .= " AND street_id = ?";
            $params[] = $streetId;
            $types .= "i";
        }
        
        if ($purokId) {
            $query .= " AND purok_id = ?";
            $params[] = $purokId;
            $types .= "i";
        }
        
        $query .= " LIMIT 1";
        
        $stmt = $db->prepare($query);
        if ($params && $types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $household = $result->fetch_assoc();
            Response::success(['exists' => true, 'household' => $household], 'Household found', 200);
        } else {
            Response::success(['exists' => false], 'Household not found', 200);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // If there's an error (table doesn't exist, column not found, etc.)
        // Return as not found to allow registration to proceed
        error_log("Household check error: " . $e->getMessage());
        Response::success(['exists' => false], 'Household not found (check skipped)', 200);
    }
}

function handleCheckEmail($method, $payload) {
    if ($method !== 'GET') {
        Response::badRequest('Only GET method allowed');
        return;
    }
    
    $email = Request::getQuery('email');
    
    if (!$email) {
        Response::badRequest('email parameter required');
        return;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT email FROM residents WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        Response::success([
            'available' => $result->num_rows === 0
        ], 'Email check completed');
        
        $stmt->close();
    } catch (Exception $e) {
        Response::internalError($e->getMessage());
    }
}

function handleRegister($method, $payload) {
    if ($method !== 'POST') {
        Response::badRequest('Only POST method allowed');
        return;
    }
    
    // Map camelCase field names from frontend to snake_case for database
    $fieldMap = [
        'firstName' => 'first_name',
        'middleName' => 'middle_name',
        'lastName' => 'last_name',
        'contact' => 'contact_number',
        'email' => 'email',
        'birthdate' => 'birthdate',
    ];
    
    // Transform payload keys from camelCase to snake_case
    $normalizedPayload = [];
    foreach ($payload as $key => $value) {
        $normalizedKey = $fieldMap[$key] ?? $key; // Use mapped name or original
        $normalizedPayload[$normalizedKey] = $value;
    }
    
    // DEBUG: Log normalized payload
    error_log("=== REGISTER DEBUG ===");
    error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET'));
    error_log("Original Payload: " . json_encode($payload));
    error_log("Normalized Payload: " . json_encode($normalizedPayload));
    error_log("_POST: " . json_encode($_POST));
    error_log("_FILES: " . json_encode($_FILES));
    error_log("==================");
    
    // Validate required fields
    $required = ['first_name', 'last_name', 'email', 'contact_number'];
    foreach ($required as $field) {
        if (empty($normalizedPayload[$field])) {
            error_log("VALIDATION FAILED: Field '$field' is empty. Payload keys: " . implode(', ', array_keys($normalizedPayload)));
            Response::badRequest("Missing required field: $field");
            return;
        }
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $first_name = $payload['first_name'] ?? '';
        $middle_name = $payload['middle_name'] ?? '';
        $last_name = $payload['last_name'] ?? '';
        $birthdate = $payload['birthdate'] ?? null;
        $email = $payload['email'] ?? '';
        $contact_number = $payload['contact_number'] ?? '';
        $street_address = $payload['street_address'] ?? '';
        $civil_status = $payload['civil_status'] ?? '';
        
        $stmt = $db->prepare(
            "INSERT INTO residents 
            (first_name, middle_name, last_name, birthdate, email, contact_number, street_address, civil_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->bind_param(
            "ssssssss",
            $first_name, $middle_name, $last_name, $birthdate,
            $email, $contact_number, $street_address, $civil_status
        );
        
        if ($stmt->execute()) {
            Response::success([
                'resident_id' => $db->insert_id,
                'email' => $email
            ], 'Resident registered successfully', 201);
        } else {
            Response::internalError('Failed to register resident');
        }
        
        $stmt->close();
    } catch (Exception $e) {
        Response::internalError($e->getMessage());
    }
}

function handleGetResidentInfo($method, $payload) {
    if ($method !== 'GET') {
        Response::badRequest('Only GET method allowed');
        return;
    }
    
    $residentId = Request::getQuery('resident_id') ?? Request::getQuery('id');
    
    if (!$residentId) {
        Response::badRequest('resident_id or id parameter required');
        return;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare(
            "SELECT resident_id, first_name, middle_name, last_name, birthdate, contact_number, email, 
                    street_address, civil_status FROM residents WHERE resident_id = ?"
        );
        $stmt->bind_param("i", $residentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            Response::success($result->fetch_assoc(), 'Resident info retrieved');
        } else {
            Response::notFound('Resident not found');
        }
        
        $stmt->close();
    } catch (Exception $e) {
        Response::internalError($e->getMessage());
    }
}

function handleGetResidents($method, $payload) {
    if ($method !== 'GET') {
        Response::badRequest('Only GET method allowed');
        return;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $limit = (int)Request::getQuery('limit', 50);
        $offset = (int)Request::getQuery('offset', 0);
        
        $stmt = $db->prepare(
            "SELECT * FROM residents LIMIT ? OFFSET ?"
        );
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $residents = [];
        while ($row = $result->fetch_assoc()) {
            $residents[] = $row;
        }
        
        Response::success($residents, 'Residents retrieved', 200);
        
        $stmt->close();
    } catch (Exception $e) {
        Response::internalError($e->getMessage());
    }
}

function handleLogin($method, $payload) {
    if ($method !== 'POST') {
        Response::badRequest('Only POST method allowed');
        return;
    }
    
    $username = $payload['username'] ?? '';
    $password = $payload['password'] ?? '';
    
    if (!$username || !$password) {
        Response::badRequest('Username and password required');
        return;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // For now, return a mock response
        // In production, verify credentials against database
        Response::success([
            'id' => 1,
            'username' => $username,
            'token' => 'mock-token-123'
        ], 'Login successful');
        
    } catch (Exception $e) {
        Response::internalError($e->getMessage());
    }
}

function handleStatus($method, $payload) {
    if ($method !== 'GET') {
        Response::badRequest('Only GET method allowed');
        return;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Test query
        $result = $db->query("SELECT 1");
        
        if ($result) {
            Response::success([
                'database' => 'connected',
                'charset' => $db->character_set_name(),
                'version' => $db->server_info
            ], 'Database connection successful');
        } else {
            Response::internalError('Database query failed');
        }
    } catch (Exception $e) {
        Response::internalError('Database connection failed: ' . $e->getMessage());
    }
}
?>
