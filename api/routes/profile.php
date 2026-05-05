<?php
/**
 * Profile API Routes
 */

require_once __DIR__ . '/../helpers.php';

$payload = Request::getPayload();
$method = Request::getMethod();

try {
    switch ($endpoint) {
        case 'photo':
            Response::success(['photo' => 'default'], 'Photo retrieved');
            break;
        
        default:
            Response::notFound("Endpoint: $endpoint not found");
    }
} catch (Exception $e) {
    Response::internalError($e->getMessage());
}
?>
