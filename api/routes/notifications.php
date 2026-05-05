<?php
/**
 * Notifications API Routes
 */

require_once __DIR__ . '/../helpers.php';

$payload = Request::getPayload();
$method = Request::getMethod();

try {
    Response::success([], 'Notifications retrieved');
} catch (Exception $e) {
    Response::internalError($e->getMessage());
}
?>
