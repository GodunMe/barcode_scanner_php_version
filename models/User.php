<?php
/**
 * User Model
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class User {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    // Get user by username
    public function getByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    // Get user by ID
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Create new user
    public function create($username, $password) {
        $id = generateId('user-');
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $this->db->prepare("
            INSERT INTO users (id, username, password_hash) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$id, $username, $passwordHash]);
        
        return $this->getById($id);
    }

    // Verify password
    public function verifyPassword($user, $password) {
        if (!$user || empty($user['password_hash'])) {
            return false;
        }
        return password_verify($password, $user['password_hash']);
    }
}
