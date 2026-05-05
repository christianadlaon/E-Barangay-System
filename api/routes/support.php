<?php
/**
 * Support API Routes
 */

require_once __DIR__ . '/../helpers.php';

$payload = Request::getPayload();
$method = Request::getMethod();

try {
    Response::success([], 'Support issues retrieved');
} catch (Exception $e) {
    Response::internalError($e->getMessage());
}
?>
