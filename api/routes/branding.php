<?php
/**
 * Branding API Routes
 * Handles branding/logo management
 */

require_once __DIR__ . '/../helpers.php';

$payload = Request::getPayload();
$method = Request::getMethod();
$storageDir = __DIR__ . '/../storage/branding';

// Ensure storage directory exists
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$logoFile = $storageDir . '/logo.json';

/**
 * Get the stored logo data
 */
function handleGetLogo() {
    global $logoFile;
    
    if (file_exists($logoFile)) {
        $data = json_decode(file_get_contents($logoFile), true);
        if ($data && isset($data['dataUrl'])) {
            Response::success($data, 'Logo retrieved successfully', 200);
            return;
        }
    }
    
    // Return default/empty logo with 200 status
    Response::success(['dataUrl' => null], 'No custom logo found', 200);
}

/**
 * Save logo data
 */
function handleSaveLogo($payload) {
    global $logoFile;
    
    if (!isset($payload['dataUrl'])) {
        Response::badRequest('dataUrl is required');
        return;
    }
    
    $logoData = [
        'dataUrl' => $payload['dataUrl'],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if (file_put_contents($logoFile, json_encode($logoData, JSON_PRETTY_PRINT))) {
        Response::success($logoData, 'Logo saved successfully', 201);
    } else {
        Response::internalError('Failed to save logo');
    }
}

/**
 * Delete/reset logo
 */
function handleDeleteLogo() {
    global $logoFile;
    
    if (file_exists($logoFile)) {
        unlink($logoFile);
    }
    
    Response::success([], 'Logo reset successfully');
}

try {
    // Debug: Log that we reached branding routes
    error_log("Branding route hit. Endpoint: $endpoint, Method: $method");
    
    switch ($endpoint) {
        case 'logo':
            if ($method === 'GET') {
                handleGetLogo();
            } elseif ($method === 'POST') {
                handleSaveLogo($payload);
            } elseif ($method === 'DELETE') {
                handleDeleteLogo();
            } else {
                Response::badRequest('Method not allowed');
            }
            break;
        
        default:
            error_log("Branding endpoint not found. Endpoint: '$endpoint'");
            Response::notFound("Endpoint: $endpoint not found");
    }
} catch (Exception $e) {
    error_log("Branding exception: " . $e->getMessage());
    Response::internalError($e->getMessage());
}
?>
