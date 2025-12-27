<?php
require_once '../config/database.php';
require_once '../models/Category.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Restrict API access to allowed pages/origins
requireAllowedRequestSource();

// Check if categories table exists, create if not
try {
    $db = getDB();
    $stmt = $db->query("SHOW TABLES LIKE 'categories'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        $db->exec("
            CREATE TABLE categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(100) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database setup failed: ' . $e->getMessage()]);
    exit;
}

$category = new Category($db);
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $categories = $category->getAll();
            echo json_encode($categories);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Không thể tải danh mục']);
        }
        break;

    case 'POST':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $name = $data['name'] ?? $data['type'] ?? '';
            if (!$data || empty(trim($name))) {
                http_response_code(400);
                echo json_encode(['error' => 'Tên danh mục không được để trống']);
                break;
            }

            if ($category->create(trim($name))) {
                echo json_encode(['success' => true, 'id' => $category->getLastInsertId()]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Không thể tạo danh mục']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi server']);
        }
        break;

    case 'PUT':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $name = $data['name'] ?? $data['type'] ?? '';
            if (!$data || !isset($data['id']) || empty(trim($name))) {
                http_response_code(400);
                echo json_encode(['error' => 'Dữ liệu không hợp lệ']);
                break;
            }

            if ($category->update($data['id'], trim($name))) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Không thể cập nhật danh mục']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi server']);
        }
        break;

    case 'DELETE':
        try {
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID không hợp lệ']);
                break;
            }

            if ($category->delete($_GET['id'])) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Không thể xóa danh mục']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi server']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method không được hỗ trợ']);
        break;
}
?>