# Barcode Scanner - PHP Version

## Yêu cầu
- Laragon (hoặc XAMPP/WAMP với PHP 7.4+ và MySQL)
- PHP 7.4 trở lên với extension PDO MySQL

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
- ✅ Phân loại sản phẩm theo type
- ✅ Tìm kiếm theo type