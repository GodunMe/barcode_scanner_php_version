<?php
/**
 * Helper functions
 */

// Generate unique ID
function generateId($prefix = '') {
    return $prefix . bin2hex(random_bytes(16));
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Error response
function errorResponse($message, $statusCode = 400) {
    jsonResponse(['error' => $message], $statusCode);
}

// Check if request is AJAX/API
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Get JSON body from POST request
function getJsonBody() {
    $json = file_get_contents('php://input');
    return json_decode($json, true) ?? [];
}

// CSRF Token functions
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Check authentication
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireAuth() {
    if (!isAuthenticated()) {
        errorResponse('unauthorized', 401);
    }
}

// Rate limiting (simple implementation)
function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 60) {
    $cacheKey = 'rate_limit_' . $key;
    
    if (!isset($_SESSION[$cacheKey])) {
        $_SESSION[$cacheKey] = ['count' => 0, 'time' => time()];
    }
    
    $data = &$_SESSION[$cacheKey];
    
    // Reset if time window passed
    if (time() - $data['time'] > $timeWindow) {
        $data = ['count' => 0, 'time' => time()];
    }
    
    $data['count']++;
    
    return $data['count'] <= $maxAttempts;
}

// ----- Request source enforcement -----
function getBaseOrigin() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
    return rtrim($scheme . '://' . $host, '/');
}

function isAllowedRequestSource() {
    // Only allow when Referer path explicitly ends with one of the allowed entry pages.
    // Do NOT permit a generic same-origin Origin header to avoid broader access.
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $refPath = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) ?: '/';
        // Accept both explicit index.php and directory-style referers like / or /admin/
        $allowed = ['/', '/index.php', '/admin', '/admin/', '/admin/index.php'];
        foreach ($allowed as $a) {
            $len = strlen($a);
            if ($len === 0) continue;
            if (substr($refPath, -$len) === $a) return true;
        }
    }

    // As a last resort, allow direct requests to the two allowed pages themselves
    $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    if ($reqPath === '/index.php' || $reqPath === '/admin/index.php') return true;

    return false;
}

function requireAllowedRequestSource() {
    if (!isAllowedRequestSource()) {
        errorResponse('forbidden_origin', 403);
    }
}
