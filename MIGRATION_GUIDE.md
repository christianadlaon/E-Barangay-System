# Migration Guide: From Multi-Port to Unified API

This guide helps you migrate existing endpoint files to the new unified API structure.

## Summary of Changes

| Old Setup | New Setup |
|-----------|-----------|
| 3 PHP servers (8000, 8001, 8002) | 1 PHP server (8000) |
| Files scattered in `D_S/` folder | Organized in `api/routes/` folder |
| No routing logic | Centralized routing in `api/index.php` |
| Duplicate CORS headers | Centralized CORS in `api/index.php` |
| No shared utilities | Shared helpers in `api/helpers.php` |

---

## Converting Old Endpoints

### Old Structure (D_S folder)
```php
// D_S/insertResident.php
<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$servername = "localhost";
$username = "root";
$password = "killpain0001";
$dbname = "bdg";

$conn = new mysqli($servername, $username, $password, $dbname);
// ... endpoint logic
```

### New Structure (api/routes/residents.php)
```php
<?php
// CORS handled in api/index.php automatically
// No need to repeat

require_once __DIR__ . '/../helpers.php';

function handleRegister($method, $payload) {
    if ($method !== 'POST') {
        Response::badRequest('Only POST allowed');
        return;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        // ... endpoint logic
        Response::success($data, 'Success');
    } catch (Exception $e) {
        Response::internalError($e->getMessage());
    }
}
```

---

## Step-by-Step Migration

### 1. Identify Endpoints in Old Files

**D_S/ folder files:**
- `insertResident.php` → POST `/api/residents/register`
- `getResident.php` → GET `/api/residents/info`
- `getResidentInfo.php` → GET `/api/residents/info`
- `login.php` → POST `/api/residents/login`
- `updateRequestStatus.php` → PUT `/api/residents/update-status`

### 2. Extract Logic

Remove CORS, connection, and extract core logic:

```php
// OLD
$data = json_decode(file_get_contents("php://input"), true);
$first_name = $data['first_name'] ?? null;

// NEW (payload already parsed)
$first_name = $payload['first_name'] ?? null;
```

### 3. Use Helper Classes

```php
// OLD
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => $conn->connect_error]);
    exit;
}

// NEW
try {
    $db = Database::getInstance()->getConnection();
    // ...
    Response::success($data, 'Message');
} catch (Exception $e) {
    Response::internalError($e->getMessage());
}
```

### 4. Add to Route Handler

```php
// In api/routes/residents.php
function handleLogin($method, $payload) {
    if ($method !== 'POST') {
        Response::badRequest('Only POST allowed');
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
        
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            Response::success($result->fetch_assoc(), 'Login successful');
        } else {
            Response::unauthorized('Invalid credentials');
        }
        
        $stmt->close();
    } catch (Exception $e) {
        Response::internalError($e->getMessage());
    }
}

// Register in switch statement
case 'login':
    handleLogin($method, $payload);
    break;
```

---

## Complete Example: Migrating insertResident.php

### Original Code
```php
<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$servername = "localhost";
$username = "root";
$password = "killpain0001";
$dbname = "bdg";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$first_name = $data['first_name'] ?? null;
$middle_name = $data['middle_name'] ?? null;
$last_name = $data['last_name'] ?? null;
$email = $data['email'] ?? null;

if (!$first_name || !$last_name || !$email) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit();
}

$sql = "INSERT INTO residents (first_name, middle_name, last_name, email) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $first_name, $middle_name, $last_name, $email);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "id" => $conn->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Insert failed"]);
}

$stmt->close();
$conn->close();
?>
```

### Migrated Code (api/routes/residents.php)
```php
<?php
function handleInsertResident($method, $payload) {
    if ($method !== 'POST') {
        Response::badRequest('Only POST allowed');
        return;
    }
    
    // Validate required fields
    $required = ['first_name', 'last_name', 'email'];
    foreach ($required as $field) {
        if (empty($payload[$field])) {
            Response::badRequest("Missing required field: $field");
            return;
        }
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $first_name = $payload['first_name'] ?? '';
        $middle_name = $payload['middle_name'] ?? '';
        $last_name = $payload['last_name'] ?? '';
        $email = $payload['email'] ?? '';
        
        $stmt = $db->prepare(
            "INSERT INTO residents (first_name, middle_name, last_name, email) 
             VALUES (?, ?, ?, ?)"
        );
        
        $stmt->bind_param("ssss", $first_name, $middle_name, $last_name, $email);
        
        if ($stmt->execute()) {
            Response::success(
                ['id' => $db->insert_id],
                'Resident created successfully',
                201
            );
        } else {
            Response::internalError('Failed to insert resident');
        }
        
        $stmt->close();
    } catch (Exception $e) {
        Response::internalError($e->getMessage());
    }
}

// Add to switch in residents.php
case 'insert':
    handleInsertResident($method, $payload);
    break;
```

---

## Testing Migrated Endpoints

### Using curl
```bash
# Old way (multiple ports)
curl -X POST http://localhost:8002/api/register \
  -H "Content-Type: application/json" \
  -d '{"first_name":"John","last_name":"Doe","email":"john@example.com"}'

# New way (unified port)
curl -X POST http://localhost:8000/api/residents/register \
  -H "Content-Type: application/json" \
  -d '{"first_name":"John","last_name":"Doe","email":"john@example.com"}'
```

### Using JavaScript/Fetch
```javascript
// New unified endpoint
const response = await fetch('http://localhost:8000/api/residents/register', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    first_name: 'John',
    last_name: 'Doe',
    email: 'john@example.com'
  })
});

const data = await response.json();
```

---

## Remaining Old Files

The following old files can now be **deprecated** or **removed**:
- ❌ `D_S/insertResident.php`
- ❌ `D_S/getResident.php`
- ❌ `D_S/getResidentInfo.php`
- ❌ `D_S/login.php`
- ❌ `D_S/updateRequestStatus.php`

Keep them for reference only, or remove if no longer needed.

---

## Summary

✅ **Single port (8000)** - Simpler management  
✅ **Organized routes** - Easy to find endpoints  
✅ **Shared helpers** - DRY principle  
✅ **Centralized CORS** - Consistent headers  
✅ **Better error handling** - Standard responses  
✅ **Easier to scale** - Add new endpoints quickly  

**All existing functionality preserved - just better organized!**
