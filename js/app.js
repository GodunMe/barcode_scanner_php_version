/**
 * Main App JavaScript - Barcode Scanner
 * For public product lookup and cart
 */

const codeReader = new ZXing.BrowserMultiFormatReader();
let selectedDeviceId = null;
let scanning = false;
let products = {};
let mode = 'price';
let cart = {};
let currentStream = null;
let scanningStopper = null;

// Format price with thousand separator
function formatPrice(n) {
  try {
    const num = typeof n === 'number' ? n : (parseFloat((n || '').toString().replace(/[^0-9.-]+/g, '')) || 0);
    return num.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
  } catch (e) {
    return String(n);
  }
}

// DOM Elements
const video = document.getElementById('video');
const videoSelect = document.getElementById('videoSelect');
const startBtn = document.getElementById('startBtn');
const stopBtn = document.getElementById('stopBtn');
const barcodeValue = document.getElementById('barcodeValue');
const productBox = document.getElementById('product');
const productImage = document.getElementById('productImage');
const productName = document.getElementById('productName');
const productPrice = document.getElementById('productPrice');
const productBarcode = document.getElementById('productBarcode');
const modePrice = document.getElementById('modePrice');
const modeCart = document.getElementById('modeCart');
const cartArea = document.getElementById('cartArea');
const cartTableBody = document.querySelector('#cartTable tbody');
const cartTotalEl = document.getElementById('cartTotal');
const clearCartBtn = document.getElementById('clearCart');
const checkoutBtn = document.getElementById('checkout');
const manualBarcodeInput = document.getElementById('manualBarcode');
const manualLookupBtn = document.getElementById('manualLookup');

// Load products from API
async function loadProducts() {
  try {
    const response = await fetch('/api/products.php');
    if (!response.ok) throw new Error('API error');
    const data = await response.json();
    products = data.reduce((m, p) => { m[p.barcode] = p; return m; }, {});
  } catch (e) {
    console.error('Failed to load products:', e);
    products = {};
  }
}

// List available cameras
function listDevices() {
  codeReader.listVideoInputDevices().then(devices => {
    videoSelect.innerHTML = '';
    devices.forEach(device => {
      const opt = document.createElement('option');
      opt.value = device.deviceId;
      opt.text = device.label || `Camera ${videoSelect.length + 1}`;
      videoSelect.appendChild(opt);
    });
    
    // Try to select rear camera
    const rear = devices.find(d => /back|rear|environment/i.test(d.label));
    if (rear) selectedDeviceId = rear.deviceId;
    else if (devices.length) selectedDeviceId = devices[0].deviceId;
  }).catch(err => {
    const opt = document.createElement('option');
    opt.text = 'No camera found';
    videoSelect.appendChild(opt);
  });
}

// Start scanner
async function startScanner() {
  if (!selectedDeviceId) {
    alert('Không tìm thấy camera');
    return null;
  }
  
  try {
    const controls = await codeReader.decodeFromVideoDevice(selectedDeviceId, video, (result, err) => {
      if (result) {
        const code = result.getText();
        barcodeValue.textContent = code;
        
        if (mode === 'price') {
          showProductForBarcode(code);
        } else {
          addToCartByBarcode(code);
        }
      }
    });
    
    currentStream = video.srcObject;
    
    return () => {
      controls.stop();
      if (currentStream) {
        currentStream.getTracks().forEach(t => t.stop());
        currentStream = null;
      }
    };
  } catch (e) {
    throw e;
  }
}

// Show product for price check mode
function showProductForBarcode(code) {
  const p = products[code];
  if (p) {
    productImage.src = p.image || '';
    productName.textContent = p.name;
    productPrice.textContent = 'Giá: ' + formatPrice(p.price) + ' ₫';
    productBarcode.textContent = 'Barcode: ' + code;
    productBox.style.display = 'flex';
  } else {
    productBox.style.display = 'none';
    alert('Không tìm thấy sản phẩm: ' + code);
  }
}

// Add to cart
function addToCartByBarcode(code) {
  const p = products[code];
  if (!p) {
    alert('Sản phẩm không có trong cơ sở dữ liệu: ' + code);
    return;
  }
  
  if (!cart[code]) {
    cart[code] = { product: p, qty: 1 };
    renderCart();
  } else {
    // Highlight existing item
    const row = cartTableBody.querySelector(`tr[data-code="${code}"]`);
    if (row) {
      row.style.transition = 'background-color 0.15s';
      row.style.backgroundColor = '#fffbcc';
      setTimeout(() => { row.style.backgroundColor = ''; }, 350);
    }
  }
}

// Render cart
function renderCart() {
  cartTableBody.innerHTML = '';
  let total = 0;
  
  Object.keys(cart).forEach(code => {
    const item = cart[code];
    const p = item.product;
    const priceNum = parseFloat((p.price || '0').toString().replace(/[^0-9.-]+/g, '')) || 0;
    const lineTotal = priceNum * item.qty;
    total += lineTotal;
    
    const tr = document.createElement('tr');
    tr.dataset.code = code;
    
    // Product name cell
    const nameTd = document.createElement('td');
    nameTd.textContent = p.name;
    
    // Price cell
    const priceTd = document.createElement('td');
    priceTd.textContent = formatPrice(priceNum);
    
    // Quantity cell
    const qtyTd = document.createElement('td');
    qtyTd.className = 'qty-controls';
    const dec = document.createElement('button');
    dec.textContent = '-';
    dec.className = 'qty-btn';
    dec.onclick = () => { if (item.qty > 1) { item.qty--; renderCart(); } };
    const qtySpan = document.createElement('span');
    qtySpan.textContent = item.qty;
    const inc = document.createElement('button');
    inc.textContent = '+';
    inc.className = 'qty-btn';
    inc.onclick = () => { item.qty++; renderCart(); };
    qtyTd.append(dec, qtySpan, inc);
    
    // Line total cell
    const lineTd = document.createElement('td');
    lineTd.textContent = formatPrice(lineTotal);
    
    // Action cell
    const actTd = document.createElement('td');
    const delBtn = document.createElement('button');
    delBtn.textContent = 'Xóa';
    delBtn.className = 'btn warn btn-sm';
    delBtn.onclick = () => { delete cart[code]; renderCart(); };
    actTd.appendChild(delBtn);
    
    tr.append(nameTd, priceTd, qtyTd, lineTd, actTd);
    cartTableBody.appendChild(tr);
  });
  
  cartTotalEl.textContent = formatPrice(total);
}

// Get cart total
function getCartTotal() {
  let total = 0;
  Object.keys(cart).forEach(code => {
    const item = cart[code];
    const priceNum = parseFloat((item.product.price || '0').toString().replace(/[^0-9.-]+/g, '')) || 0;
    total += priceNum * item.qty;
  });
  return total;
}

// Event Listeners
videoSelect.addEventListener('change', () => { selectedDeviceId = videoSelect.value; });

startBtn.addEventListener('click', async () => {
  if (scanning) return;
  scanning = true;
  barcodeValue.textContent = '(quét...)';
  try {
    scanningStopper = await startScanner();
  } catch (e) {
    alert('Không thể khởi tạo camera: ' + (e?.message || e));
    scanning = false;
  }
});

stopBtn.addEventListener('click', () => {
  if (typeof scanningStopper === 'function') scanningStopper();
  scanning = false;
  barcodeValue.textContent = '(đã dừng)';
});

modePrice.addEventListener('change', () => {
  if (modePrice.checked) {
    mode = 'price';
    cartArea.style.display = 'none';
    productBox.style.display = 'none';
  }
});

modeCart.addEventListener('change', () => {
  if (modeCart.checked) {
    mode = 'cart';
    cartArea.style.display = 'block';
    productBox.style.display = 'none';
  }
});

clearCartBtn.addEventListener('click', () => {
  if (confirm('Xóa toàn bộ giỏ hàng?')) {
    cart = {};
    renderCart();
  }
});

checkoutBtn.addEventListener('click', () => {
  const total = getCartTotal();
  const formatted = formatPrice(total);
  const paymentModal = document.getElementById('paymentModal');
  const paymentQRCode = document.getElementById('paymentQRCode');
  const paymentAmount = document.getElementById('paymentAmount');
  
  // Generate QR
  const payload = `Cửa hàng Thúy Dưỡng\nTổng: ${formatted} ₫`;
  const qrSrc = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' + encodeURIComponent(payload);
  
  if (paymentQRCode) {
    paymentQRCode.onerror = function() {
      paymentQRCode.onerror = null;
      paymentQRCode.src = qrSrc;
    };
    paymentQRCode.src = '/uploads/QR.jpg';
  }
  
  if (paymentAmount) paymentAmount.textContent = formatted + ' ₫';
  if (paymentModal) paymentModal.style.display = 'flex';
});

// Payment modal close
const paymentModal = document.getElementById('paymentModal');
const paymentCloseBtn = document.getElementById('paymentCloseBtn');
if (paymentCloseBtn) {
  paymentCloseBtn.addEventListener('click', () => {
    if (paymentModal) paymentModal.style.display = 'none';
  });
}
if (paymentModal) {
  paymentModal.addEventListener('click', (e) => {
    if (e.target === paymentModal) paymentModal.style.display = 'none';
  });
}

// Manual barcode lookup
if (manualLookupBtn) {
  manualLookupBtn.addEventListener('click', () => {
    const code = manualBarcodeInput.value.trim();
    if (!code) return;
    
    barcodeValue.textContent = code;
    if (mode === 'price') {
      showProductForBarcode(code);
    } else {
      addToCartByBarcode(code);
    }
  });
}

if (manualBarcodeInput) {
  manualBarcodeInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      manualLookupBtn.click();
    }
  });
}

// Initialize
listDevices();
loadProducts();
window.addEventListener('focus', listDevices);
