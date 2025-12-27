<?php
/**
 * File Upload API
 * Handles image uploads for products
 */

session_start();

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Restrict API access to allowed pages/origins
requireAllowedRequestSource();

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('method_not_allowed', 405);
}

// Require authentication
requireAuth();

// Validate CSRF
$csrfToken = $_SERVER['HTTP_CSRF_TOKEN'] ?? '';
if (!validateCSRFToken($csrfToken)) {
    errorResponse('invalid_csrf', 403);
}

// Check if file uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    errorResponse('no_file', 400);
}

$file = $_FILES['image'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    errorResponse('invalid_file_type', 400);
}

// Validate file size (max 5MB)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    errorResponse('file_too_large', 400);
}

// Create uploads directory if not exists
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate safe filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$extension = preg_replace('/[^a-zA-Z0-9]/', '', $extension) ?: 'jpg';
$safeName = time() . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
$targetPath = $uploadDir . $safeName;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    errorResponse('upload_failed', 500);
}

// Return public URL
$url = '/uploads/' . $safeName;
jsonResponse(['url' => $url]);
