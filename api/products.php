<?php
/**
 * Products API
 * Handles all product-related API requests
 */

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Product.php';

header('Content-Type: application/json; charset=utf-8');

$product = new Product();
$method = $_SERVER['REQUEST_METHOD'];

// Parse URL path
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Find the position of 'products' in path
$productsIndex = array_search('products.php', $pathParts);
$action = $pathParts[$productsIndex + 1] ?? null;
$param = $pathParts[$productsIndex + 2] ?? null;

// Route: GET /api/products.php - Get all products
if ($method === 'GET' && !$action) {
    $categoryId = $_GET['category'] ?? null;
    $products = $product->getAll($categoryId);
    jsonResponse($products);
}

// Route: GET /api/products.php/id/{id} - Get product by ID
if ($method === 'GET' && $action === 'id' && $param) {
    $result = $product->getById($param);
    if (!$result) {
        errorResponse('not_found', 404);
    }
    jsonResponse($result);
}

// Route: GET /api/products.php/{barcode} - Get product by barcode
if ($method === 'GET' && $action && $action !== 'id') {
    $result = $product->getByBarcode($action);
    if (!$result) {
        errorResponse('not_found', 404);
    }
    jsonResponse($result);
}

// Route: POST /api/products.php - Create new product (requires auth)
if ($method === 'POST' && !$action) {
    requireAuth();
    
    // Validate CSRF
    $csrfToken = $_SERVER['HTTP_CSRF_TOKEN'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        errorResponse('invalid_csrf', 403);
    }
    
    $data = getJsonBody();
    
    // Validation
    $errors = [];
    
    if (empty($data['barcode'])) {
        $errors[] = ['msg' => 'Barcode là bắt buộc', 'param' => 'barcode'];
    }
    
    if (empty($data['name'])) {
        $errors[] = ['msg' => 'Tên sản phẩm là bắt buộc', 'param' => 'name'];
    }
    
    // Price validation: optional, but if provided must be positive integer
    if (!empty($data['price'])) {
        if (!preg_match('/^[0-9]+$/', $data['price']) || intval($data['price']) <= 0) {
            $errors[] = ['msg' => 'Giá phải là số nguyên dương', 'param' => 'price'];
        }
    }
    
    if (!empty($errors)) {
        jsonResponse(['errors' => $errors], 400);
    }
    
    // Check unique barcode
    if ($product->barcodeExists($data['barcode'])) {
        errorResponse('exists', 409);
    }
    
    $result = $product->create([
        'barcode' => sanitize($data['barcode']),
        'name' => sanitize($data['name']),
        'price' => $data['price'] ?? null,
        'image' => $data['image'] ?? null
    ]);
    
    jsonResponse($result);
}

// Route: PUT /api/products.php/{id} - Update product (requires auth)
if ($method === 'PUT' && $action) {
    requireAuth();
    
    // Validate CSRF
    $csrfToken = $_SERVER['HTTP_CSRF_TOKEN'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        errorResponse('invalid_csrf', 403);
    }
    
    $id = $action;
    $data = getJsonBody();
    
    // Check if product exists
    $existing = $product->getById($id);
    if (!$existing) {
        errorResponse('not_found', 404);
    }
    
    // Validation
    $errors = [];
    
    // Price validation for update
    if (isset($data['price']) && $data['price'] !== '') {
        if (!preg_match('/^[0-9]+$/', $data['price']) || intval($data['price']) <= 0) {
            $errors[] = ['msg' => 'Giá phải là số nguyên dương', 'param' => 'price'];
        }
    }
    
    if (!empty($errors)) {
        jsonResponse(['errors' => $errors], 400);
    }
    
    // Check barcode uniqueness if changed
    if (!empty($data['barcode']) && $product->barcodeExists($data['barcode'], $id)) {
        errorResponse('barcode_exists', 409);
    }
    
    $updates = [];
    if (!empty($data['barcode'])) $updates['barcode'] = sanitize($data['barcode']);
    if (!empty($data['name'])) $updates['name'] = sanitize($data['name']);
    if (isset($data['price'])) $updates['price'] = $data['price'] ?: null;
    if (isset($data['image'])) $updates['image'] = $data['image'];
    if (isset($data['category_id'])) $updates['category_id'] = $data['category_id'] ?: null;
    
    $result = $product->update($id, $updates);
    jsonResponse($result);
}

// Route: DELETE /api/products.php/{id} - Delete product (requires auth)
if ($method === 'DELETE' && $action) {
    requireAuth();
    
    // Validate CSRF
    $csrfToken = $_SERVER['HTTP_CSRF_TOKEN'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        errorResponse('invalid_csrf', 403);
    }
    
    $id = $action;
    
    // Check if product exists
    $existing = $product->getById($id);
    if (!$existing) {
        errorResponse('not_found', 404);
    }
    
    // Delete uploaded image if exists
    if (!empty($existing['image'])) {
        $imagePath = $existing['image'];
        // Remove leading slash and 'public' prefix
        $imagePath = preg_replace('/^\/?(public\/)?/', '', $imagePath);
        $fullPath = __DIR__ . '/../' . $imagePath;
        if (file_exists($fullPath) && strpos(realpath($fullPath), realpath(__DIR__ . '/../uploads')) === 0) {
            @unlink($fullPath);
        }
    }
    
    $product->delete($id);
    jsonResponse(['ok' => true]);
}

// Method not allowed
errorResponse('method_not_allowed', 405);
