<?php
class Category {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY type");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($type) {
        try {
            $stmt = $this->db->prepare("INSERT INTO categories (type) VALUES (?)");
            return $stmt->execute([$type]);
        } catch (Exception $e) {
            error_log("Category create error: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $type) {
        try {
            $stmt = $this->db->prepare("UPDATE categories SET type = ? WHERE id = ?");
            return $stmt->execute([$type, $id]);
        } catch (Exception $e) {
            error_log("Category update error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Category delete error: " . $e->getMessage());
            return false;
        }
    }

    public function getLastInsertId() {
        return $this->db->lastInsertId();
    }
}
?>