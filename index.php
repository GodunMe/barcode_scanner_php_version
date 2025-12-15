<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Quét mã vạch - Cửa hàng</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div class="container">
    <header>
      <div>
        <h1>Quét mã vạch</h1>
        <p class="lead">Quét mã vạch để tra cứu sản phẩm</p>
      </div>
      <a href="admin/" class="btn ghost">Quản trị</a>
    </header>

    <div class="card">
      <div id="videoWrap">
        <video id="video" autoplay playsinline></video>
      </div>
      
      <div class="controls">
        <select id="videoSelect"></select>
        <button id="startBtn" class="btn">Bắt đầu quét</button>
        <button id="stopBtn" class="btn ghost">Dừng</button>
      </div>

      <div class="modes">
        <label><input type="radio" name="mode" id="modePrice" checked> Tra giá</label>
        <label><input type="radio" name="mode" id="modeCart"> Giỏ hàng</label>
      </div>

      <div style="margin-top:12px">
        <input type="text" id="manualBarcode" placeholder="Nhập mã vạch thủ công..." style="width:70%;padding:8px">
        <button id="manualLookup" class="btn">Tra cứu</button>
      </div>

      <div id="log" style="margin-top:10px">
        <p>Mã vạch: <span id="barcodeValue">(chờ quét...)</span></p>
      </div>

      <!-- Product display for price check mode -->
      <div id="product" style="display:none">
        <img id="productImage" src="" alt="Product">
        <div id="info">
          <h3 id="productName"></h3>
          <p id="productPrice"></p>
          <p id="productBarcode"></p>
        </div>
      </div>
    </div>

    <!-- Cart area -->
    <div id="cartArea" class="card" style="display:none;margin-top:12px">
      <h3>Giỏ hàng</h3>
      <table id="cartTable">
        <thead>
          <tr>
            <th>Sản phẩm</th>
            <th>Giá</th>
            <th>Số lượng</th>
            <th>Thành tiền</th>
            <th>Hành động</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
      <div style="margin-top:12px;text-align:right">
        <strong>Tổng cộng: <span id="cartTotal">0</span> ₫</strong>
      </div>
      <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
        <button id="clearCart" class="btn ghost">Xóa giỏ</button>
        <button id="checkout" class="btn">Thanh toán</button>
      </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal" style="display:none">
      <div class="modal-content">
        <h3>Thanh toán</h3>
        <img id="paymentQRCode" src="" alt="QR Code" style="max-width:300px">
        <p><strong>Tổng tiền: <span id="paymentAmount">0</span></strong></p>
        <button id="paymentCloseBtn" class="btn">Đóng</button>
      </div>
    </div>

    <footer>
      <p>© 2024 Cửa hàng Thúy Dưỡng</p>
    </footer>
  </div>

  <script src="https://unpkg.com/@zxing/library@0.18.6/umd/index.min.js"></script>
  <script src="js/app.js"></script>
</body>
</html>
