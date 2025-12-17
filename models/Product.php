<?php
/**
 * Product Model
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Product {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    // Get all products
    public function getAll($categoryId = null) {
        $sql = "SELECT p.*, c.type as category_type 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id";
        
        if ($categoryId) {
            $sql .= " WHERE p.category_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$categoryId]);
        } else {
            $stmt = $this->db->query($sql);
        }
        
        return $stmt->fetchAll();
    }

    // Get product by ID
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Get product by barcode
    public function getByBarcode($barcode) {
        $stmt = $this->db->prepare("
            SELECT p.*, c.type as category_type 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.barcode = ?
        ");
        $stmt->execute([$barcode]);
        return $stmt->fetch();
    }

    // Create new product
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO products (barcode, name, price, image, category_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['barcode'],
            $data['name'],
            $data['price'] ?? null,
            $data['image'] ?? null,
            $data['category_id'] ?? null
        ]);
        $id = $this->db->lastInsertId();
        return $this->getById($id);
    }

    // Update product
    public function update($id, $data) {
        $fields = [];
        $values = [];

        if (isset($data['barcode'])) {
            $fields[] = 'barcode = ?';
            $values[] = $data['barcode'];
        }
        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $values[] = $data['name'];
        }
        if (array_key_exists('price', $data)) {
            $fields[] = 'price = ?';
            $values[] = $data['price'];
        }
        if (array_key_exists('image', $data)) {
            $fields[] = 'image = ?';
            $values[] = $data['image'];
        }
        if (array_key_exists('category_id', $data)) {
            $fields[] = 'category_id = ?';
            $values[] = $data['category_id'];
        }

        if (empty($fields)) {
            return $this->getById($id);
        }

        $values[] = $id;
        $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        return $this->getById($id);
    }

    // Delete product
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Check if barcode exists (excluding specific ID)
    public function barcodeExists($barcode, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT id FROM products WHERE barcode = ? AND id != ?");
            $stmt->execute([$barcode, $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM products WHERE barcode = ?");
            $stmt->execute([$barcode]);
        }
        return $stmt->fetch() !== false;
    }
}
