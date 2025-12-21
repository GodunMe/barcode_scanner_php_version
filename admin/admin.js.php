<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

// Only allow when request comes from allowed entry pages (e.g., /admin/index.php)
requireAllowedRequestSource();

// Serve the JS file
$jsPath = __DIR__ . '/admin.js';
if (!file_exists($jsPath)) {
    // respond with 404 in JavaScript-friendly way
    header('Content-Type: application/javascript; charset=utf-8');
    http_response_code(404);
    echo "console.error('admin.js not found');";
    exit;
}

header('Content-Type: application/javascript; charset=utf-8');
// Optional: prevent caching for development; you can adjust/remove later
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($jsPath);
exit;
