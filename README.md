# Barcode Scanner - PHP Version

## Yêu cầu
- Laragon (hoặc XAMPP/WAMP với PHP 7.4+ và MySQL)
- PHP 7.4 trở lên với extension PDO MySQL

## Cài đặt

### 1. Copy thư mục vào Laragon
Copy toàn bộ thư mục `php_version` vào thư mục `www` của Laragon:
```
C:\laragon\www\barcode_scanner\
```

### 2. Tạo Database
1. Mở phpMyAdmin (http://localhost/phpmyadmin)
2. Chạy file SQL: `config/setup.sql`

Hoặc chạy từ terminal:
```bash
mysql -u root < config/setup.sql
```

### 3. Cấu hình Database
Nếu cần, chỉnh sửa file `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'barcode_scanner');
define('DB_USER', 'root');
define('DB_PASS', ''); // Mật khẩu MySQL nếu có
```

### 4. Tạo thư mục uploads
```bash
mkdir uploads
chmod 755 uploads
```

### 5. Truy cập ứng dụng
- Trang chính: http://localhost/barcode_scanner/
- Trang admin: http://localhost/barcode_scanner/admin/

### 6. Đăng nhập Admin
- Username: `admin`
- Password: `admin123`

## Cấu trúc thư mục
```
php_version/
├── index.php           # Trang chính (quét mã vạch)
├── admin/
│   ├── index.php       # Trang quản trị
│   └── admin.js        # JavaScript admin
├── api/
│   ├── auth.php        # API xác thực
│   ├── products.php    # API sản phẩm
│   └── upload.php      # API upload ảnh
├── config/
│   ├── database.php    # Cấu hình database
│   └── setup.sql       # Script tạo database
├── includes/
│   └── functions.php   # Các hàm helper
├── models/
│   ├── Product.php     # Model sản phẩm
│   └── User.php        # Model người dùng
├── css/
│   ├── style.css       # CSS chính
│   └── admin.css       # CSS admin
├── js/
│   └── app.js          # JavaScript trang chính
└── uploads/            # Thư mục chứa ảnh upload
```

## Tính năng
- ✅ Quét mã vạch bằng camera
- ✅ Tra cứu giá sản phẩm
- ✅ Giỏ hàng với tính tổng tiền
- ✅ Quản lý sản phẩm (CRUD)
- ✅ Upload ảnh sản phẩm
- ✅ Xác thực admin với session
- ✅ Bảo vệ CSRF
- ✅ Responsive design

## Đổi mật khẩu Admin
Để đổi mật khẩu admin, chạy script sau hoặc update trực tiếp trong database:

```php
<?php
$newPassword = 'your_new_password';
$hash = password_hash($newPassword, PASSWORD_BCRYPT);
echo $hash;
// Copy hash này vào database
```

Hoặc SQL:
```sql
UPDATE users SET password_hash = '$2y$10$...' WHERE username = 'admin';
```
