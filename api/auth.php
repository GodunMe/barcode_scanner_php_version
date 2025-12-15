<?php
/**
 * Authentication API
 * Handles login, logout, status check, and CSRF token
 */

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/User.php';

header('Content-Type: application/json; charset=utf-8');

$user = new User();
$method = $_SERVER['REQUEST_METHOD'];

// Parse action from URL
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$authIndex = array_search('auth.php', $pathParts);
$action = $pathParts[$authIndex + 1] ?? '';

// Route: POST /api/auth.php/login - Login
if ($method === 'POST' && $action === 'login') {
    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!checkRateLimit('login_' . $ip, 5, 60)) {
        errorResponse('Too many login attempts, try later.', 429);
    }
    
    $data = getJsonBody();
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        errorResponse('invalid', 401);
    }
    
    $userData = $user->getByUsername($username);
    
    if (!$userData) {
        errorResponse('invalid', 401);
    }
    
    if (!$user->verifyPassword($userData, $password)) {
        errorResponse('invalid', 401);
    }
    
    // Set session
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['username'] = $userData['username'];
    
    jsonResponse(['ok' => true]);
}

// Route: POST /api/auth.php/logout - Logout
if ($method === 'POST' && $action === 'logout') {
    session_destroy();
    jsonResponse(['ok' => true]);
}

// Route: GET /api/auth.php/status - Check authentication status
if ($method === 'GET' && $action === 'status') {
    jsonResponse(['authenticated' => isAuthenticated()]);
}

// Route: GET /api/auth.php/csrf-token - Get CSRF token
if ($method === 'GET' && $action === 'csrf-token') {
    jsonResponse(['csrfToken' => generateCSRFToken()]);
}

// Method/action not found
errorResponse('not_found', 404);
