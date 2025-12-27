<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin - Quản trị sản phẩm</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- removed no-referrer so browser sends Referer to allow guarded admin.js loader -->
  <style>
    :root {
      --primary: #6366f1;
      --primary-dark: #4f46e5;
      --secondary: #f97316;
      --success: #10b981;
      --danger: #ef4444;
      --warning: #f59e0b;
      --bg: #f1f5f9;
      --card-bg: #ffffff;
      --text: #1e293b;
      --text-muted: #64748b;
      --border: #e2e8f0;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg);
      min-height: 100vh;
      color: var(--text);
    }

    .app-container {
      max-width: 900px;
      margin: 0 auto;
      padding: 20px;
    }

    /* Header */
    .admin-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 24px;
      border-radius: 20px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
    }

    .admin-header h1 {
      font-size: 1.5rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .admin-header-icon {
      width: 48px;
      height: 48px;
      background: rgba(255,255,255,0.2);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
    }

    .header-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    /* Card */
    .card {
      background: var(--card-bg);
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      padding: 24px;
      margin-bottom: 20px;
    }

    .card h3, .card h4 {
      margin-bottom: 20px;
      font-weight: 600;
      color: var(--text);
    }

    /* Login Form */
    .login-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 20px;
    }

    .login-card {
      background: white;
      border-radius: 24px;
      padding: 40px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }

    .login-header {
      text-align: center;
      margin-bottom: 32px;
    }

    .login-icon {
      width: 70px;
      height: 70px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      font-size: 32px;
    }

    .login-header h2 {
      font-size: 1.5rem;
      color: var(--text);
      margin-bottom: 8px;
    }

    .login-header p {
      color: var(--text-muted);
      font-size: 0.9rem;
    }

    /* Forms */
    .form-group {
      margin-bottom: 16px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--text);
      font-size: 0.9rem;
    }

    input[type="text"],
    input[type="password"],
    input[type="email"],
    input[type="number"],
    select {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid var(--border);
      border-radius: 12px;
      font-size: 1rem;
      transition: all 0.2s;
      background: white;
    }

    input:focus, select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    input::placeholder { color: #94a3b8; }

    .input-with-icon {
      position: relative;
    }

    .input-with-icon input {
      padding-right: 100px;
    }

    .input-with-icon .icon-btn {
      position: absolute;
      right: 8px;
      top: 50%;
      transform: translateY(-50%);
    }

    .input-with-icon .icon-btn + .icon-btn {
      right: 48px;
    }

    .icon-btn {
      background: transparent;
      border: none;
      cursor: pointer;
      padding: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text-muted);
      border-radius: 8px;
      transition: all 0.2s;
    }

    .icon-btn:hover {
      color: var(--primary);
      background: rgba(99, 102, 241, 0.1);
    }

    .field-error {
      color: var(--danger);
      font-size: 0.85rem;
      margin-top: 6px;
      display: none;
    }

    .input-invalid {
      border-color: var(--danger) !important;
    }

    .error-msg {
      color: var(--danger);
      text-align: center;
      margin-top: 16px;
      font-size: 0.9rem;
    }

    /* Buttons */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px 20px;
      border: none;
      border-radius: 12px;
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
    }

    .btn-primary {
      background: var(--primary);
      color: white;
    }

    .btn-primary:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
    }

    .btn-secondary {
      background: #f1f5f9;
      color: var(--text);
    }

    .btn-secondary:hover {
      background: #e2e8f0;
    }

    .btn-success {
      background: var(--success);
      color: white;
    }

    .btn-danger {
      background: var(--danger);
      color: white;
    }

    .btn-warning {
      background: var(--warning);
      color: white;
    }

    .btn-sm {
      padding: 8px 12px;
      font-size: 0.85rem;
    }

    .btn-block {
      width: 100%;
    }

    .btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    /* Form Actions */
    .form-actions {
      display: flex;
      gap: 10px;
      margin-top: 20px;
    }

    /* Search Box */
    .search-box {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .search-box input {
      flex: 1;
    }

    /* Preview */
    #preview {
      margin: 16px 0;
    }

    #preview img {
      max-width: 150px;
      border-radius: 12px;
      border: 2px solid var(--border);
    }

    /* Table */
    .table-responsive {
      overflow-x: auto;
      border-radius: 12px;
      border: 1px solid var(--border);
    }

    .products-table {
      width: 100%;
      border-collapse: collapse;
    }

    .products-table th,
    .products-table td {
      padding: 14px 16px;
      text-align: left;
      border-bottom: 1px solid var(--border);
    }

    .products-table th {
      background: #f8fafc;
      font-weight: 600;
      color: var(--text-muted);
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .products-table tr:hover {
      background: #f8fafc;
    }

    .products-table tr:last-child td {
      border-bottom: none;
    }

    .products-table img {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 8px;
    }

    .products-table .product-name {
      font-weight: 500;
      max-width: 200px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .products-table .edit,
    .products-table .del {
      padding: 6px 12px;
      border: none;
      border-radius: 8px;
      font-size: 0.85rem;
      font-weight: 500;
      cursor: pointer;
      margin-right: 6px;
    }

    .products-table .edit {
      background: #dbeafe;
      color: #2563eb;
    }

    .products-table .del {
      background: #fee2e2;
      color: #dc2626;
    }

    .products-table .edit:hover { background: #bfdbfe; }
    .products-table .del:hover { background: #fecaca; }

    /* Pagination */
    #pagination {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 16px;
      padding: 12px 0;
      flex-wrap: wrap;
      gap: 12px;
    }

    /* Toast */
    .toast-wrap {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1001;
    }

    .toast {
      background: var(--text);
      color: white;
      padding: 14px 20px;
      border-radius: 12px;
      margin-top: 10px;
      font-size: 0.9rem;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
      animation: slideIn 0.3s ease;
    }

    .toast.success { background: var(--success); }
    .toast.error { background: var(--danger); }

    @keyframes slideIn {
      from { opacity: 0; transform: translateX(20px); }
      to { opacity: 1; transform: translateX(0); }
    }

    /* Modal */
    .modal {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.6);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
      z-index: 1000;
      backdrop-filter: blur(4px);
    }

    .modal[style*="flex"] {
      display: flex;
    }

    .modal-content {
      background: white;
      border-radius: 20px;
      padding: 24px;
      max-width: 500px;
      width: 100%;
      animation: modalPop 0.3s ease;
    }

    .modal-content h4 {
      margin-bottom: 16px;
    }

    .modal-content video {
      width: 100%;
      border-radius: 12px;
      background: #000;
    }

    .modal-actions {
      display: flex;
      gap: 10px;
      margin-top: 16px;
    }

    @keyframes modalPop {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }

    /* Collapsible */
    .collapsible {
      max-height: 0;
      overflow: hidden;
      padding: 0 24px;
      transition: all 0.3s ease;
    }

    .collapsible.open {
      max-height: 1000px;
      padding: 24px;
    }

    /* Categories Management */
    .category-form {
      display: flex;
      gap: 10px;
      margin-bottom: 16px;
    }

    .category-form input {
      flex: 1;
      padding: 12px 16px;
      border: 2px solid var(--border);
      border-radius: 12px;
      font-size: 0.9rem;
    }

    .category-form input:focus {
      outline: none;
      border-color: var(--primary);
    }

    .categories-list {
      display: grid;
      gap: 8px;
      max-height: 360px; /* limit height and enable scrolling when long */
      overflow-y: auto;
      padding-right: 6px; /* give room for scrollbar */
      scroll-behavior: smooth;
    }

    .category-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      background: #f8fafc;
      border-radius: 12px;
      border: 1px solid var(--border);
    }

    .category-item .category-name {
      font-weight: 500;
      cursor: pointer;
      flex: 1;
    }

    .category-item .category-name:hover {
      color: var(--primary);
    }

    .category-edit-input {
      flex: 1;
      padding: 8px 12px;
      border: 2px solid var(--primary);
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 500;
      outline: none;
    }

    .category-item .category-actions {
      display: flex;
      gap: 8px;
    }

    .category-item .edit-btn,
    .category-item .delete-btn,
    .category-item .save-btn,
    .category-item .cancel-btn {
      padding: 6px 12px;
      border: none;
      border-radius: 8px;
      font-size: 0.85rem;
      font-weight: 500;
      cursor: pointer;
    }

    .category-item .edit-btn {
      background: #dbeafe;
      color: #2563eb;
    }

    .category-item .edit-btn:hover {
      background: #bfdbfe;
    }

    .category-item .delete-btn {
      background: #fee2e2;
      color: #dc2626;
    }

    .category-item .delete-btn:hover {
      background: #fecaca;
    }

    .category-item .save-btn {
      background: #d1fae5;
      color: #065f46;
    }

    .category-item .save-btn:hover {
      background: #a7f3d0;
    }

    .category-item .cancel-btn {
      background: #f3f4f6;
      color: #374151;
    }

    .category-item .cancel-btn:hover {
      background: #e5e7eb;
    }

    /* Preview image wrapper and remove button */
    #preview { margin-top: 12px; position: relative; }
    .preview-wrap { position: relative; display: inline-block; }
    .preview-img { max-width: 150px; border-radius: 12px; border: 2px solid var(--border); display: block; }
    .remove-img {
      position: absolute;
      right: -8px;
      top: -8px;
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: #fee2e2;
      color: #b91c1c;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      border: none;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      cursor: pointer;
    }
    .remove-img:hover { background: #fecaca; }

    /* Scrollbar styling for categories list */
    .categories-list::-webkit-scrollbar { width: 10px; }
    .categories-list::-webkit-scrollbar-track { background: transparent; }
    .categories-list::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.08); border-radius: 8px; }
    .categories-list::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.12); }

    /* Mini Camera */
    .mini-camera {
      margin-bottom: 16px;
      border-radius: 12px;
      overflow: hidden;
      background: #000;
    }

    .mini-camera video {
      width: 100%;
      display: block;
    }

    .mini-controls {
      display: flex;
      gap: 10px;
      padding: 10px;
      background: #1e293b;
    }

    /* Mobile Products (hidden by default) */
    .mobile-products {
      display: none;
    }

    /* Responsive */
    @media (max-width: 600px) {
      .admin-header {
        flex-direction: column;
        text-align: center;
      }

      .header-actions {
        width: 100%;
        justify-content: center;
      }

      .products-table th:nth-child(2),
      .products-table td:nth-child(2) {
        display: none;
      }

      /* Mobile Product Cards */
      .table-responsive {
        display: none;
      }

      .mobile-products {
        display: block;
      }

      .product-card {
        background: var(--card-bg);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        border: 1px solid var(--border);
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        display: flex;
        flex-direction: column;
        gap: 12px;
      }

      .product-card-row {
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .product-card-row-1 {
        /* Hình ảnh + Tên sản phẩm */
      }

      .product-card-row-1 img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
        flex-shrink: 0;
      }

      .product-card-name {
        font-weight: 600;
        font-size: 1rem;
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .product-card-row-2 {
        /* Barcode + Danh mục */
        justify-content: space-between;
      }

      .product-info-item {
        flex: 1;
        text-align: center;
        background: #f8fafc;
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
      }

      .product-info-label {
        font-weight: 600;
        color: var(--text-muted);
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
        display: block;
      }

      .product-info-value {
        color: var(--text);
        font-weight: 500;
        font-size: 0.85rem;
      }

      .product-card-row-3 {
        /* Giá tiền */
        justify-content: center;
      }

      .product-price-block {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1rem;
        text-align: center;
        width: 100%;
        max-width: 200px;
      }

      .product-card-actions {
        display: flex;
        gap: 8px;
        justify-content: center;
      }

      .product-card .edit,
      .product-card .del {
        flex: 1;
        max-width: 120px;
        padding: 8px 12px;
        border: none;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
      }

      .product-card .edit {
        background: #dbeafe;
        color: #2563eb;
      }

      .product-card .del {
        background: #fee2e2;
        color: #dc2626;
      }

      .product-card .edit:hover { background: #bfdbfe; }
      .product-card .del:hover { background: #fecaca; }
    }
  </style>
</head>
<body>
  <!-- Login Form -->
  <div id="auth" class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="login-icon">🔐</div>
        <h2>Đăng nhập Admin</h2>
        <p>Quản lý sản phẩm của bạn</p>
      </div>
      <form id="loginForm">
        <div class="form-group">
          <label>Tên đăng nhập</label>
          <input type="text" id="username" placeholder="Nhập username" required>
        </div>
        <div class="form-group">
          <label>Mật khẩu</label>
          <div class="input-with-icon">
            <input type="password" id="password" placeholder="Nhập password" required>
            <button type="button" id="togglePassword" class="icon-btn" title="Hiện mật khẩu">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
            <polyline points="10 17 15 12 10 7"></polyline>
            <line x1="15" y1="12" x2="3" y2="12"></line>
          </svg>
          Đăng nhập
        </button>
      </form>
      <div id="loginMsg" class="error-msg"></div>
      <div style="text-align: center; margin-top: 20px;">
        <a href="../" style="color: var(--primary); text-decoration: none; font-size: 0.9rem;">← Quay lại trang chủ</a>
      </div>
    </div>
  </div>

  <!-- Admin Panel (hidden by default) -->
  <div id="adminPanel" class="app-container" style="display:none">
    <!-- Header -->
    <header class="admin-header">
      <h1>
        <span class="admin-header-icon">⚙️</span>
        Quản trị sản phẩm
      </h1>
      <div class="header-actions">
        <button id="toggleAddBtn" class="btn btn-secondary">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Thêm sản phẩm
        </button>
        <button id="toggleCategoriesBtn" class="btn btn-secondary">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
            <polyline points="14,2 14,8 20,8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10,9 9,9 8,9"></polyline>
          </svg>
          Quản lý danh mục
        </button>
        <a href="../" class="btn btn-secondary">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
          </svg>
          Trang chủ
        </a>
        <button id="changePassBtn" class="btn btn-secondary">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="10" rx="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
          Đổi mật khẩu
        </button>
        <button id="logoutBtn" class="btn btn-danger">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
          Đăng xuất
        </button>
      </div>
    </header>

    <!-- Change Password Modal -->
    <div id="changePassModal" class="modal" aria-hidden="true" style="display:none">
      <div class="modal-content">
        <h4>Đổi mật khẩu</h4>
        <div id="changePassMsg" class="error-msg" style="display:none;margin-bottom:8px"></div>
        <form id="changePassForm">
          <div class="form-group">
            <label for="oldPassword">Mật khẩu hiện tại</label>
            <input type="password" id="oldPassword" autocomplete="current-password">
            <div id="err_oldPassword" class="field-error"></div>
          </div>
          <div class="form-group">
            <label for="newPassword">Mật khẩu mới</label>
            <input type="password" id="newPassword" autocomplete="new-password">
            <div id="err_newPassword" class="field-error"></div>
          </div>
          <div class="modal-actions">
            <button type="button" id="cancelChangePass" class="btn btn-secondary">Hủy</button>
            <button type="submit" id="submitChangePass" class="btn btn-primary">Lưu</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Categories Management -->
    <div id="categoriesCard" class="card collapsible">
      <h4>📂 Quản lý danh mục</h4>
      <div class="category-form">
        <input type="text" id="categoryName" placeholder="Loại danh mục mới">
        <button id="addCategoryBtn" class="btn btn-primary">Thêm danh mục</button>
      </div>
      <div id="categoriesList" class="categories-list"></div>
    </div>

    <!-- Add/Edit Form -->
    <div id="addCard" class="card collapsible">
      <h4 id="formTitle">➕ Thêm sản phẩm mới</h4>
      
      <!-- Mini camera preview -->
      <div id="miniCap" class="mini-camera" style="display:none">
        <video id="miniVideo" autoplay playsinline muted></video>
        <div class="mini-controls">
          <button id="miniTake" type="button" class="btn btn-primary btn-sm">📸 Chụp</button>
          <button id="miniStop" type="button" class="btn btn-secondary btn-sm">Dừng</button>
        </div>
      </div>

      <form id="addForm">
        <div class="form-group">
          <label>Mã vạch (Barcode)</label>
          <div class="input-with-icon">
            <input type="text" id="addBarcode" placeholder="Nhập hoặc quét mã vạch" required>
            <button type="button" id="addBarcodeCameraBtn" class="icon-btn" title="Quét mã vạch">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                <circle cx="12" cy="13" r="4"></circle>
              </svg>
            </button>
          </div>
          <div id="err_addBarcode" class="field-error"></div>
        </div>

        <div class="form-group">
          <label>Tên sản phẩm</label>
          <input type="text" id="addName" placeholder="Nhập tên sản phẩm" required>
          <div id="err_addName" class="field-error"></div>
        </div>

        <div class="form-group">
          <label>Loại</label>
          <select id="addCategory">
            <option value="">Chọn loại...</option>
          </select>
          <div id="err_addCategory" class="field-error"></div>
        </div>

        <div class="form-group">
          <label>Giá (VNĐ)</label>
          <input type="text" id="addPrice" placeholder="Ví dụ: 50000" inputmode="numeric" pattern="[0-9]*">
          <div id="err_addPrice" class="field-error"></div>
        </div>

        <div class="form-group">
          <label>Hình ảnh</label>
          <div class="input-with-icon">
            <input type="text" id="addImage" placeholder="URL ảnh hoặc chụp/tải lên">
            <button type="button" id="addImageCameraBtn" class="icon-btn" title="Chụp ảnh">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                <circle cx="12" cy="13" r="4"></circle>
              </svg>
            </button>
            <button type="button" id="addImageUploadBtn" class="icon-btn" title="Tải ảnh lên">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
              </svg>
            </button>
          </div>
          <div id="err_addImage" class="field-error"></div>
        </div>

        <input type="file" id="fileInput" accept="image/*" style="display:none">
        <div id="preview"></div>
        <input type="hidden" id="editingId">

        <div class="form-actions">
          <button type="submit" id="submitBtn" class="btn btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
              <polyline points="17 21 17 13 7 13 7 21"></polyline>
              <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            Lưu sản phẩm
          </button>
          <button type="button" id="cancelEdit" class="btn btn-secondary" style="display:none">Hủy</button>
        </div>
      </form>
    </div>

    <!-- Search & Products -->
    <div class="card">
      <h4>📦 Danh sách sản phẩm</h4>
      
      <div class="search-box">
        <input type="text" id="searchInput" placeholder="🔍 Tìm theo barcode hoặc tên sản phẩm...">
        <button type="button" id="searchScanBtn" class="btn btn-secondary">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
            <circle cx="12" cy="13" r="4"></circle>
          </svg>
        </button>
      </div>

      <div class="table-responsive">
        <table id="productsTable" class="products-table">
          <thead>
            <tr>
              <th>Ảnh</th>
              <th>Barcode</th>
              <th>Tên sản phẩm</th>
              <th>Danh mục</th>
              <th>Giá</th>
              <th>Hành động</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div id="mobileProducts" class="mobile-products"></div>
      <div id="pagination"></div>
    </div>

    <!-- Toast Container -->
    <div id="toastWrap" class="toast-wrap"></div>
  </div>

  <!-- Camera Overlays -->
  <div id="captureArea" class="modal" style="display:none">
    <div class="modal-content">
      <h4>📸 Chụp ảnh sản phẩm</h4>
      <video id="capVideo" autoplay playsinline></video>
      <div class="modal-actions">
        <button id="takePhoto" class="btn btn-primary">Chụp</button>
        <button id="stopCapture" class="btn btn-secondary">Đóng</button>
      </div>
      <canvas id="capCanvas" style="display:none"></canvas>
    </div>
  </div>

  <div id="scanArea" class="modal" style="display:none">
    <div class="modal-content">
      <h4>📷 Quét mã vạch</h4>
      <video id="scanVideo" autoplay playsinline></video>
      <div class="modal-actions">
        <button id="stopScan" class="btn btn-secondary">Đóng</button>
      </div>
    </div>
  </div>

  <div id="searchScanModal" class="modal" style="display:none">
    <div class="modal-content">
      <h4>🔍 Quét mã vạch để tìm kiếm</h4>
      <video id="searchScanVideo" autoplay playsinline muted></video>
      <div class="modal-actions">
        <button id="stopSearchScan" class="btn btn-secondary">Đóng</button>
      </div>
    </div>
  </div>

  <script src="https://unpkg.com/@zxing/library@0.18.6/umd/index.min.js"></script>
  <script src="admin.js.php"></script>
</body>
</html>
