<?php
/**
 * Delete uploaded file API
 * Accepts POST JSON { url: '/uploads/xxx.jpg' }
 */

session_start();
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Restrict API access to allowed pages/origins
requireAllowedRequestSource();

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

// Require authentication
requireAuth();

// Validate CSRF
$csrfToken = $_SERVER['HTTP_CSRF_TOKEN'] ?? '';
$body = json_decode(file_get_contents('php://input'), true) ?: [];
if (!validateCSRFToken($csrfToken) || empty($body['url'])) {
    http_response_code(403);
    echo json_encode(['error' => 'invalid_csrf_or_params']);
    exit;
}

$url = $body['url'];
// Only allow deletion from /uploads/ directory
if (strpos($url, '/uploads/') !== 0) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_path']);
    exit;
}

$uploadsDir = realpath(__DIR__ . '/../uploads');
$target = realpath(__DIR__ . '/..' . $url);

// Ensure file is under uploads dir and exists
if (!$target || strpos($target, $uploadsDir) !== 0 || !is_file($target)) {
    http_response_code(404);
    echo json_encode(['error' => 'file_not_found']);
    exit;
}

// Attempt to unlink
if (!@unlink($target)) {
    http_response_code(500);
    echo json_encode(['error' => 'delete_failed']);
    exit;
}

echo json_encode(['ok' => true]);
