-- SQL Setup for Barcode Scanner App
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS barcode_scanner 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE barcode_scanner;

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id VARCHAR(50) PRIMARY KEY,
    barcode VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    price VARCHAR(50) DEFAULT NULL,
    image VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_barcode (barcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(50) PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
-- Password hash for 'admin123' using PHP password_hash with PASSWORD_BCRYPT
INSERT INTO users (id, username, password_hash) VALUES 
('admin-001', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE username = username;

-- Sample products (optional)
INSERT INTO products (id, barcode, name, price, image) VALUES 
('prod-001', '8938623477379', 'Giấy ăn Sakara', '50000', ''),
('prod-002', '8850769014082', 'Thuốc', '', '')
ON DUPLICATE KEY UPDATE name = VALUES(name);
