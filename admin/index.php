<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin - Quản trị sản phẩm</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/admin.css">
  <meta name="referrer" content="no-referrer">
</head>
<body>
  <div class="container">
    <h1>Trang quản trị</h1>

    <!-- Login Form -->
    <div id="auth" class="card">
      <h3>Đăng nhập</h3>
      <form id="loginForm">
        <div class="form-group">
          <label>Username</label>
          <input type="text" id="username" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" id="password" required>
        </div>
        <button type="submit" class="btn">Đăng nhập</button>
      </form>
      <div id="loginMsg" class="error-msg"></div>
    </div>

    <!-- Admin Panel (hidden by default) -->
    <div id="adminPanel" style="display:none">
      <p>
        <button id="logoutBtn" class="btn">Đăng xuất</button>
        <button id="toggleAddBtn" class="btn ghost" style="margin-left:8px">Thêm sản phẩm</button>
        <a href="../" class="btn ghost" style="margin-left:8px">Về trang chính</a>
      </p>

      <!-- Add/Edit Form -->
      <div id="addCard" class="card collapsible">
        <h4 id="formTitle">Thêm sản phẩm</h4>
        
        <!-- Mini camera preview -->
        <div id="miniCap" class="mini-camera" style="display:none">
          <video id="miniVideo" autoplay playsinline muted></video>
          <div class="mini-controls">
            <button id="miniTake" type="button" class="btn btn-sm">Chụp</button>
            <button id="miniStop" type="button" class="btn ghost btn-sm">Dừng</button>
          </div>
        </div>

        <form id="addForm">
          <div class="form-group">
            <div class="input-with-icon">
              <input type="text" id="addBarcode" placeholder="Barcode" required>
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
            <input type="text" id="addName" placeholder="Tên sản phẩm" required>
            <div id="err_addName" class="field-error"></div>
          </div>

          <div class="form-group">
            <input type="text" id="addPrice" placeholder="Giá (VND)" inputmode="numeric" pattern="[0-9]*">
            <div id="err_addPrice" class="field-error"></div>
          </div>

          <div class="form-group">
            <div class="input-with-icon">
              <input type="text" id="addImage" placeholder="URL ảnh hoặc chụp">
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
            <button type="submit" id="submitBtn" class="btn">Thêm</button>
            <button type="button" id="cancelEdit" class="btn ghost" style="display:none">Hủy</button>
          </div>
        </form>
      </div>

      <!-- Search -->
      <div class="search-box">
        <input type="text" id="searchInput" placeholder="Tìm theo barcode hoặc tên...">
        <button type="button" id="searchScanBtn" class="icon-btn" title="Quét mã vạch">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
            <circle cx="12" cy="13" r="4"></circle>
          </svg>
        </button>
      </div>

      <!-- Products Table -->
      <h3>Sản phẩm</h3>
      <div class="table-responsive">
        <table id="productsTable" class="products-table">
          <thead>
            <tr>
              <th>Ảnh</th>
              <th>Barcode</th>
              <th>Tên</th>
              <th>Giá</th>
              <th>Hành động</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div id="pagination"></div>

      <!-- Toast Container -->
      <div id="toastWrap" class="toast-wrap"></div>
    </div>

    <!-- Camera Overlays -->
    <div id="captureArea" class="modal" style="display:none">
      <div class="modal-content">
        <h4>Chụp ảnh sản phẩm</h4>
        <video id="capVideo" autoplay playsinline></video>
        <div class="modal-actions">
          <button id="takePhoto" class="btn">Chụp</button>
          <button id="stopCapture" class="btn ghost">Đóng</button>
        </div>
        <canvas id="capCanvas" style="display:none"></canvas>
      </div>
    </div>

    <div id="scanArea" class="modal" style="display:none">
      <div class="modal-content">
        <h4>Quét mã vạch</h4>
        <video id="scanVideo" autoplay playsinline></video>
        <div class="modal-actions">
          <button id="stopScan" class="btn ghost">Đóng</button>
        </div>
      </div>
    </div>

    <div id="searchScanModal" class="modal" style="display:none">
      <div class="modal-content">
        <h4>Quét mã vạch để tìm kiếm</h4>
        <video id="searchScanVideo" autoplay playsinline muted></video>
        <div class="modal-actions">
          <button id="stopSearchScan" class="btn ghost">Đóng</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://unpkg.com/@zxing/library@0.18.6/umd/index.min.js"></script>
  <script src="admin.js"></script>
</body>
</html>
