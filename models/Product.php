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
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM products ORDER BY updated_at DESC, created_at DESC");
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
        $stmt = $this->db->prepare("SELECT * FROM products WHERE barcode = ?");
        $stmt->execute([$barcode]);
        return $stmt->fetch();
    }

    // Create new product
    public function create($data) {
        $id = generateId('prod-');
        $stmt = $this->db->prepare("
            INSERT INTO products (id, barcode, name, price, image) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id,
            $data['barcode'],
            $data['name'],
            $data['price'] ?? null,
            $data['image'] ?? null
        ]);
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
