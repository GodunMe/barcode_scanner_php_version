<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Quét mã vạch - Check Giá</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #6366f1;
      --primary-dark: #4f46e5;
      --secondary: #f97316;
      --success: #10b981;
      --danger: #ef4444;
      --bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --card-bg: rgba(255, 255, 255, 0.95);
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
      max-width: 480px;
      margin: 0 auto;
      padding: 20px;
      min-height: 100vh;
    }

    /* Header */
    .header {
      text-align: center;
      padding: 30px 0 20px;
      color: white;
    }

    .header-icon {
      width: 70px;
      height: 70px;
      background: rgba(255,255,255,0.2);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      font-size: 36px;
      backdrop-filter: blur(10px);
    }

    .header h1 {
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 8px;
      text-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .header p { opacity: 0.9; font-size: 0.95rem; }

    /* Card */
    .card {
      background: var(--card-bg);
      border-radius: 24px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.15);
      padding: 24px;
      margin-bottom: 16px;
      backdrop-filter: blur(10px);
    }

    /* Scanner */
    .scanner-container {
      position: relative;
      border-radius: 16px;
      overflow: hidden;
      background: #000;
      aspect-ratio: 4/3;
    }

    .scanner-container video {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .scanner-overlay {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      pointer-events: none;
    }

    .scan-frame {
      width: 70%;
      height: 50%;
      border: 3px solid rgba(255,255,255,0.8);
      border-radius: 16px;
      box-shadow: 0 0 0 9999px rgba(0,0,0,0.4);
      position: relative;
    }

    .scan-frame::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, transparent, #10b981, transparent);
      animation: scanLine 2s ease-in-out infinite;
    }

    @keyframes scanLine {
      0%, 100% { transform: translateY(-40px); opacity: 0; }
      50% { transform: translateY(40px); opacity: 1; }
    }

    .scanner-placeholder {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
      color: white;
    }

    .scanner-placeholder.hidden { display: none; }

    .scanner-placeholder svg {
      width: 64px;
      height: 64px;
      opacity: 0.5;
      margin-bottom: 12px;
    }

    .scanner-placeholder p { opacity: 0.7; font-size: 0.9rem; }

    /* Controls */
    .controls {
      display: flex;
      gap: 10px;
      margin-top: 16px;
    }

    .btn {
      flex: 1;
      padding: 14px 20px;
      border: none;
      border-radius: 12px;
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
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

    .btn-secondary:hover { background: #e2e8f0; }

    .btn-success { background: var(--success); color: white; }
    .btn-danger { background: var(--danger); color: white; }

    .btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none !important;
    }

    /* Camera Select */
    .camera-select {
      width: 100%;
      padding: 12px 16px;
      padding-right: 44px; /* space for caret */
      border: 2px solid var(--border);
      border-radius: 12px;
      font-size: 0.9rem;
      margin-top: 12px;
      background: white;
      cursor: pointer;
      height: 48px;
      display: inline-flex;
      align-items: center;
      box-sizing: border-box;
      -webkit-appearance: none;
      appearance: none;
    }

    .camera-select:focus {
      outline: none;
      border-color: var(--primary);
    }

    /* Mode Toggle */
    .mode-toggle {
      display: flex;
      background: #f1f5f9;
      border-radius: 12px;
      padding: 4px;
      margin-top: 16px;
    }

    .mode-toggle label {
      flex: 1;
      text-align: center;

    }

    /* Category Filter */
    .category-filter {
      margin-top: 16px;
    }

    /* Browse filters - mobile friendly layout */
    .browse-filters {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-top: 0;
    }

    .filter-selects {
      display: flex;
      gap: 8px;
      align-items: center;
    }

    /* Ensure both filters share same flex sizing and alignment: split 50%/50% */
    .filter-selects > * {
      flex: 0 0 calc(50% - 4px);
      min-width: 0;
    }

    /* Remove extra top margin for selects inside filter row */
    .filter-selects .camera-select { margin-top: 0; }
    
    /* Custom scrollable dropdown for long category lists */
    .custom-select-wrapper { position: relative; width: 100%; }
    .custom-select-toggle {
      width: 100%;
      padding: 12px 16px;
      padding-right: 44px;
      border: 2px solid var(--border);
      border-radius: 12px;
      background: white;
      text-align: left;
      cursor: pointer;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 8px;
      box-sizing: border-box;
      height: 48px;
    }
    .custom-select-toggle::after {
      content: '';
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      width: 0; height: 0;
      border-left: 6px solid transparent;
      border-right: 6px solid transparent;
      border-top: 6px solid #111;
      opacity: 0.7;
      pointer-events: none;
    }
    .custom-select-list {
      position: absolute;
      left: 0; right: 0;
      margin-top: 8px;
      background: white;
      border: 1px solid var(--border);
      border-radius: 10px;
      max-height: 220px;
      overflow-y: auto;
      z-index: 1200;
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }
    .custom-option { padding: 10px 12px; cursor: pointer; }
    .custom-option:hover { background: #f1f5f9; }

    .filter-actions {
      display: flex;
      gap: 8px;
      width: 100%;
    }

    .filter-actions .btn {
      width: 100%;
      padding: 12px 16px;
      border-radius: 12px;
      font-size: 0.95rem;
      box-sizing: border-box;
    }
    }

    /* Make sure selects are full width on very small screens */
    @media (max-width: 360px) {
      .filter-selects {
        flex-direction: column;
      }
      .filter-selects > * { flex: 1 1 100%; }
    }

    /* Manual Input */
    .manual-input {
      display: flex;
      gap: 10px;
      margin-top: 16px;
    }

    .manual-input input {
      flex: 1;
      padding: 14px 16px;
      border: 2px solid var(--border);
      border-radius: 12px;
      font-size: 1rem;
      transition: border-color 0.2s;
    }

    .manual-input input:focus {
      outline: none;
      border-color: var(--primary);
    }

    .manual-input input::placeholder { color: #94a3b8; }

    /* Barcode Display */
    /* Barcode display removed */

    /* Product Result */
    .product-result {
      display: none;
      margin-top: 16px;
      animation: slideUp 0.3s ease;
    }

    .product-result.show { display: block; }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .product-card {
      background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
      border-radius: 16px;
      padding: 20px;
      display: flex;
      gap: 16px;
      align-items: center;
    }

    .product-image {
      width: 100px;
      height: 100px;
      border-radius: 12px;
      object-fit: cover;
      background: white;
      border: 2px solid white;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .product-image.placeholder {
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 40px;
      background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
    }

    .product-info { flex: 1; min-width: 0; }

    .product-name {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 8px;
      color: var(--text);
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-overflow: ellipsis;
      word-break: break-word;
    }

    .product-price {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--secondary);
    }

    .product-category {
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-top: 4px;
      font-weight: 500;
    }

    .product-barcode {
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-top: 4px;
      font-family: monospace;
    }

    .not-found {
      text-align: center;
      padding: 30px;
      color: var(--text-muted);
    }

    .not-found svg {
      width: 48px;
      height: 48px;
      margin-bottom: 12px;
      opacity: 0.5;
    }

    /* Cart Section */
    .cart-section { display: none; }
    .cart-section.show { display: block; }

    .cart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }

    .cart-header h3 {
      font-size: 1.1rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .cart-count {
      background: var(--primary);
      color: white;
      font-size: 0.75rem;
      padding: 2px 8px;
      border-radius: 20px;
    }

    .cart-item {
      display: flex;
      gap: 12px;
      padding: 12px 0;
      border-bottom: 1px solid var(--border);
      align-items: center;
    }

    .cart-item:last-child { border-bottom: none; }

    .cart-item-image {
      width: 50px;
      height: 50px;
      border-radius: 8px;
      object-fit: cover;
      background: #f1f5f9;
    }

    .cart-item-info { flex: 1; min-width: 0; }

    .cart-item-name {
      font-weight: 500;
      font-size: 0.9rem;
      margin-bottom: 4px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-overflow: ellipsis;
      word-break: break-word;
    }

    .cart-item-price {
      color: var(--text-muted);
      font-size: 0.85rem;
    }

    .cart-item-qty {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .cart-item-qty button {
      width: 28px;
      height: 28px;
      border: none;
      border-radius: 6px;
      background: #f1f5f9;
      cursor: pointer;
      font-weight: 600;
    }

    .cart-item-qty span {
      min-width: 24px;
      text-align: center;
      font-weight: 600;
    }

    .cart-item-delete {
      width: 28px;
      height: 28px;
      border: none;
      border-radius: 6px;
      background: #fee2e2;
      color: #dc2626;
      cursor: pointer;
      font-weight: 600;
      font-size: 1.1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-left: 8px;
      transition: all 0.2s;
    }

    .cart-item-delete:hover {
      background: #fecaca;
    }

    .cart-total {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 0;
      border-top: 2px solid var(--border);
      margin-top: 8px;
    }

    .cart-total-label {
      font-weight: 500;
      color: var(--text-muted);
    }

    .cart-total-value {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--secondary);
    }

    .cart-actions {
      display: flex;
      gap: 10px;
      margin-top: 16px;
    }

    .cart-empty {
      text-align: center;
      padding: 40px 20px;
      color: var(--text-muted);
    }

    .cart-empty svg {
      width: 64px;
      height: 64px;
      opacity: 0.3;
      margin-bottom: 12px;
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

    .modal.show { display: flex; }

    .modal-content {
      background: white;
      border-radius: 24px;
      padding: 32px;
      max-width: 360px;
      width: 100%;
      text-align: center;
      animation: modalPop 0.3s ease;
    }

    @keyframes modalPop {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }

    .modal-content h3 { margin-bottom: 20px; font-size: 1.2rem; }
    .modal-content img { max-width: 200px; margin-bottom: 16px; }

    .modal-amount {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--secondary);
      margin-bottom: 20px;
    }

    /* Footer */
    .footer {
      text-align: center;
      padding: 20px;
      color: rgba(255,255,255,0.7);
      font-size: 0.85rem;
    }

    .footer a {
      color: white;
      text-decoration: none;
    }

    /* Toast */
    .toast-container {
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 1001;
    }

    .toast {
      background: #1e293b;
      color: white;
      padding: 12px 24px;
      border-radius: 12px;
      font-size: 0.9rem;
      box-shadow: 0 10px 40px rgba(0,0,0,0.3);
      animation: toastSlide 0.3s ease;
    }

    .toast.success { background: var(--success); }
    .toast.error { background: var(--danger); }

    @keyframes toastSlide {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 400px) {
      .app-container { padding: 12px; }
      .card { padding: 16px; border-radius: 20px; }
      .product-card { flex-direction: column; text-align: center; }
    }

    /* Tabs */
    .tabs {
      display: flex;
      background: #f1f5f9;
      border-radius: 12px;
      padding: 4px;
      margin-bottom: 20px;
      position: relative; /* ensure stacking context */
      z-index: 1102; /* place above overlays/modals */
    }

    .tab {
      flex: 1;
      text-align: center;
      cursor: pointer;
      font-weight: 500;
      font-size: 0.9rem;
      transition: all 0.2s;
      padding: 12px;
      border-radius: 10px;
    }

    .tab.active {
      background: white;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      color: var(--primary);
    }

    /* Product List */
    .product-list {
      display: none;
      margin-top: 16px;
    }

    .product-list.show { display: block; }

    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
      gap: 12px;
      margin-top: 16px;
    }

    .product-item {
      background: white;
      border-radius: 12px;
      padding: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      cursor: pointer;
      transition: all 0.2s;
      text-align: center;
    }

    .product-item .add-to-cart {
      display: inline-block;
      margin-top: 8px;
      padding: 6px 10px;
      border-radius: 8px;
      background: var(--primary);
      color: white;
      font-size: 0.85rem;
      cursor: pointer;
      border: none;
    }

    .pagination {
      display: flex;
      gap: 8px;
      justify-content: center;
      margin-top: 14px;
      align-items: center;
    }

    .pagination button {
      padding: 8px 12px;
      border-radius: 8px;
      border: 1px solid rgba(0,0,0,0.06);
      background: white;
      cursor: pointer;
    }

    .pagination button.active {
      background: var(--primary);
      color: white;
      border-color: rgba(0,0,0,0.08);
    }

    .browse-cart-preview {
      background: rgba(255,255,255,0.9);
      border-radius: 12px;
      padding: 12px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.06);
      font-size: 0.95rem;
      color: var(--text);
    }

    .browse-cart-preview .item { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px dashed rgba(0,0,0,0.04);} 
    .browse-cart-preview .item:last-child { border-bottom: none; }

    .product-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }

    .product-item-image {
      width: 100%;
      height: 80px;
      border-radius: 8px;
      object-fit: cover;
      background: #f1f5f9;
      margin-bottom: 8px;
    }

    .product-item-name {
      font-size: 0.8rem;
      font-weight: 500;
      margin-bottom: 4px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .product-item-price {
      font-size: 0.75rem;
      color: var(--primary);
      font-weight: 600;
    }

    /* Product grid and items styling (restored to normal) */
    #productGrid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
      gap: 12px;
      margin-top: 16px;
    }

    .product-item {
      background: white;
      border-radius: 12px;
      padding: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      cursor: pointer;
      transition: all 0.2s;
      text-align: center;
      margin: 8px;
    }
  </style>
</head>
<body>
  <div class="app-container">
    <!-- Header -->
    <header class="header">
      <div class="header-icon">📷</div>
      <h1>Quét mã vạch</h1>
    </header>

    <!-- Tabs -->
    <div class="tabs">
      <div class="tab active" data-tab="scan">📷 Quét mã</div>
      <div class="tab" data-tab="browse">🛍️ Duyệt sản phẩm</div>
    </div>

    <!-- Scan Tab -->
    <div class="tab-content" id="scanTab">
    <!-- Main Card -->
    <div class="card">
      <!-- Scanner -->
      <div class="scanner-container">
        <video id="video" autoplay playsinline muted></video>
        <div class="scanner-overlay">
          <div class="scan-frame"></div>
        </div>
        <div class="scanner-placeholder" id="placeholder">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
            <circle cx="12" cy="13" r="4"></circle>
          </svg>
          <p>Nhấn "Bắt đầu" để quét mã vạch</p>
        </div>
      </div>

      <!-- Controls -->
      <div class="controls">
        <button id="startBtn" class="btn btn-primary">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="5 3 19 12 5 21 5 3"></polygon>
          </svg>
          Bắt đầu
        </button>
        <button id="stopBtn" class="btn btn-secondary" disabled>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="6" y="6" width="12" height="12"></rect>
          </svg>
          Dừng
        </button>
      </div>

      <!-- Camera Select removed: default to back camera only -->

      <!-- Mode Toggle -->
      <div class="mode-toggle">
        <label>
          <input type="radio" name="mode" id="modePrice" checked>
          <span>💰 Tra giá</span>
        </label>
        <label>
          <input type="radio" name="mode" id="modeCart">
          <span>🛒 Giỏ hàng</span>
        </label>
      </div>

      

      <!-- Manual Input -->
      <div class="manual-input">
        <input type="text" id="manualBarcode" placeholder="Nhập mã vạch thủ công...">
        <button id="manualLookup" class="btn btn-primary" style="flex: 0 0 auto;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
        </button>
      </div>

      <!-- Barcode display removed -->

      <!-- Product Result -->
      <div class="product-result" id="product">
        <div class="product-card">
          <img id="productImage" src="" alt="" class="product-image">
          <div class="product-info">
            <div class="product-name" id="productName"></div>
            <div class="product-category" id="productCategory"></div>
            <div class="product-price" id="productPrice"></div>
          </div>
        </div>
      </div>

      <!-- Not Found -->
      <div class="product-result" id="notFound">
        <div class="not-found">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="m15 9-6 6"></path>
            <path d="m9 9 6 6"></path>
          </svg>
          <p>Không tìm thấy sản phẩm</p>
        </div>
      </div>
      
      <!-- Hidden canvas used by scanner preprocessing -->
      <canvas id="canvas" style="display:none"></canvas>
    </div>
    </div> <!-- Close scanTab -->

    <!-- Browse Tab -->
    <div class="tab-content" id="browseTab" style="display: none; position: relative;">
        <div class="card">
        <h3 style="margin-bottom: 16px; color: var(--text);">🛍️ Duyệt sản phẩm</h3>

        <!-- Search Bar -->
        <div class="search-bar" style="margin-bottom: 16px; position: relative;">
          <input type="text" id="browseNameFilter" class="camera-select" style="width: 100%; padding-right: 40px;" placeholder="Tìm theo tên sản phẩm...">
          <button id="searchBtn" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer; height: 24px; width: 24px; display: flex; align-items: center; justify-content: center;">🔍</button>
        </div>

        <!-- Debug: shows current search query and match count (mobile troubleshooting) -->
        <div id="browseDebug" style="font-size:12px; color:var(--text-muted); margin-bottom:8px; display:none;"></div>

        <!-- Filters -->
        <div class="browse-filters">
          <div class="filter-selects">
            <select id="browseCategoryFilter" class="camera-select" style="flex: 1; min-width: 0;">
              <option value="">📂 Tất cả loại</option>
            </select>

            <select id="browsePriceFilter" class="camera-select" style="flex: 1; min-width: 0;">
              <option value="">💰 Tất cả giá</option>
              <option value="0-100000">💰 Dưới 100.000₫</option>
              <option value="100000-200000">💰 100.000₫ - 200.000₫</option>
              <option value="200000-300000">💰 200.000₫ - 300.000₫</option>
              <option value="300000-400000">💰 300.000₫ - 400.000₫</option>
              <option value="400000+">💰 Trên 400.000₫</option>
            </select>
          </div>

          <div class="filter-actions">
            <button id="refreshProducts" class="btn btn-primary" style="flex: 1;">
              🔄 Làm mới
            </button>
          </div>
        </div>

        <!-- Product List -->
        <div class="product-list show" id="productList">
          <div class="product-grid" id="productGrid">
            <!-- Products will be populated here -->
          </div>
            <div id="productPagination" class="pagination" aria-label="Pagination"></div>
            <!-- Browse cart preview removed -->
        </div>
      </div>
    </div>

    <!-- Cart Section -->
    <div class="card cart-section show" id="cartArea">
      <div class="cart-header">
        <h3>🛒 Giỏ hàng <span class="cart-count" id="cartCount">0</span></h3>
        <button id="clearCart" class="btn btn-secondary" style="padding: 8px 12px; font-size: 0.85rem;">
          Xóa tất cả
        </button>
      </div>

      <div id="cartItems">
        <div class="cart-empty" id="cartEmpty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
          </svg>
          <p>Giỏ hàng trống</p>
        </div>
      </div>

      <div class="cart-total">
        <span class="cart-total-label">Tổng cộng</span>
        <span class="cart-total-value"><span id="cartTotal">0</span> ₫</span>
      </div>

      <div class="cart-actions">
        <button id="checkout" class="btn btn-success" style="flex: 1;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
            <line x1="1" y1="10" x2="23" y2="10"></line>
          </svg>
          Thanh toán
        </button>
      </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
      <p>© 2025 Cửa hàng Thúy Dưỡng</p>
      <div>Made with ❤️ by Godun</div>
      <p><a href="admin/">Quản trị viên →</a></p>
    </footer>
  </div>

  <!-- Payment Modal -->
  <div id="paymentModal" class="modal">
    <div class="modal-content">
      <h3>💳 Thanh toán</h3>
      <img id="paymentQRCode" src="" alt="QR Code" style="max-width: 250px; border-radius: 12px;">
      <div class="modal-amount">
        <span id="paymentAmount">0</span> ₫
      </div>
      <button id="paymentCloseBtn" class="btn btn-primary" style="width: 100%;">Đóng</button>
    </div>
  </div>

  <!-- Generic Confirm Modal -->
  <div id="confirmModal" class="modal">
    <div class="modal-content" style="max-width:360px;">
      <h3 id="confirmTitle">Xác nhận</h3>
      <p id="confirmMessage" style="margin-bottom:20px; color:var(--text-muted);"></p>
      <div style="display:flex; gap:8px;">
        <button id="confirmCancel" class="btn btn-secondary" style="flex:1">Hủy</button>
        <button id="confirmOk" class="btn btn-primary" style="flex:1">Xác nhận</button>
      </div>
    </div>
  </div>

  <!-- Toast Container -->
  <div class="toast-container" id="toastContainer"></div>

  <script src="https://unpkg.com/@zxing/library@0.18.6/umd/index.min.js"></script>
  <script src="js/app.js"></script>
</body>
</html>
