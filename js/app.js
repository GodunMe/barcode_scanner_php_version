/**
 * Main App JavaScript - Barcode Scanner
 * For public product lookup and cart
 */

let codeReader = null;
try {
  if (window.ZXing && ZXing.BrowserMultiFormatReader) {
    codeReader = new ZXing.BrowserMultiFormatReader();
  }
} catch (e) {
  console.warn('ZXing init failed:', e);
  codeReader = null;
}
const supportsBarcodeDetection = 'BarcodeDetector' in window;
let barcodeDetector = null;
let lastDetected = { code: null, time: 0 };
let selectedDeviceId = null;
let scanning = false;
let products = {};
let categories = {};
let mode = 'price';
let cart = {};
let currentStream = null;
let scanningStopper = null;
// Throttle ZXing decode attempts to avoid spamming errors
let lastZxingAttempt = 0;
let zxingErrorLastLog = 0;
// Browse state for pagination
let browsePage = 1;
const BROWSE_PER_PAGE = 6;

// Format price with thousand separator
function formatPrice(n) {
  try {
    const num = typeof n === 'number' ? n : (parseFloat((n || '').toString().replace(/[^0-9.-]+/g, '')) || 0);
    return num.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
  } catch (e) {
    return String(n);
  }
}

// Return product image or inline SVG placeholder
function defaultImageDataUrl() {
  // Use a static default product image from uploads if available
  return '/uploads/product.png';
}

function getImageUrl(url) {
  return url && String(url).trim() ? url : defaultImageDataUrl();
}

// Fallback delegated tab handler: ensures tabs switch even if direct listeners fail
document.addEventListener('click', (e) => {
  const tab = e.target.closest && e.target.closest('.tab');
  if (!tab) return;

  const tabs = document.querySelectorAll('.tab');
  const tabContents = document.querySelectorAll('.tab-content');

  // switch active tab
  tabs.forEach(t => t.classList.remove('active'));
  tab.classList.add('active');

  // hide all contents
  tabContents.forEach(content => content.style.display = 'none');

  const tabId = tab.dataset.tab + 'Tab';
  const tabContent = document.getElementById(tabId);
  if (tabContent) {
    tabContent.style.display = 'block';
    tabContent.style.visibility = 'visible';
    tabContent.style.opacity = '1';
    tabContent.removeAttribute('hidden');
    try { tabContent.scrollIntoView({ behavior: 'smooth', block: 'start' }); } catch (e) {}
  }

  if (tab.dataset.tab === 'browse') {
    const loadPromises = [];
    if (Object.keys(products).length === 0) loadPromises.push(loadProducts());
    if (Object.keys(categories).length === 0) loadPromises.push(loadCategories());
    Promise.all(loadPromises).then(() => {
      setTimeout(() => {
        setupBrowseFilters();
        loadBrowseProducts();
      }, 10);
    });
  }
});

// Toast notification
function showToast(message, type = '') {
  const container = document.getElementById('toastContainer');
  if (!container) return;
  
  const toast = document.createElement('div');
  toast.className = 'toast' + (type ? ' ' + type : '');
  toast.textContent = message;
  container.appendChild(toast);
  
  setTimeout(() => toast.remove(), 3000);
}

// DOM Elements
const video = document.getElementById('video');
const startBtn = document.getElementById('startBtn');
const stopBtn = document.getElementById('stopBtn');
const productBox = document.getElementById('product');
const productImage = document.getElementById('productImage');
const productName = document.getElementById('productName');
const productPrice = document.getElementById('productPrice');
const productBarcode = document.getElementById('productBarcode');
const productCategory = document.getElementById('productCategory');
const modePrice = document.getElementById('modePrice');
const modeCart = document.getElementById('modeCart');
const cartArea = document.getElementById('cartArea');
const cartItemsContainer = document.getElementById('cartItems');
const cartTotalEl = document.getElementById('cartTotal');
const cartCountEl = document.getElementById('cartCount');
const clearCartBtn = document.getElementById('clearCart');
const checkoutBtn = document.getElementById('checkout');
const manualBarcodeInput = document.getElementById('manualBarcode');
const manualLookupBtn = document.getElementById('manualLookup');
const placeholder = document.getElementById('placeholder');
const notFoundBox = document.getElementById('notFound');

// Load products from API
async function loadProducts() {
  // Try API endpoint first, fallback to local JSON when no server is present
  try {
    // Prefer the PHP endpoint when available
    let response = await fetch('/api/products.php');
    if (!response.ok) {
      // fallback to /api/products for other deployments
      response = await fetch('/api/products');
    }
    if (response && response.ok) {
      const data = await response.json();
      if (Array.isArray(data)) products = data.reduce((m, p) => { if (p && p.barcode) m[p.barcode] = p; return m; }, {});
      else if (data && typeof data === 'object') products = data;
      else products = {};
      const activeTab = document.querySelector('.tab.active');
      if (activeTab && activeTab.dataset.tab === 'browse') renderProductList();
      return products;
    }
  } catch (e) {
    console.warn('API products failed:', e);
    // If API is not available, keep products empty and allow UI to show "no products" state.
    products = {};
    const activeTab = document.querySelector('.tab.active');
    if (activeTab && activeTab.dataset.tab === 'browse') renderProductList();
    return products;
  }
  // If API returned no ok response above, ensure products is empty
  products = {};
  return products;
}

async function loadCategories() {
  try {
    const response = await fetch('/api/categories.php');
    if (!response.ok) throw new Error('API error');
    const data = await response.json();
    categories = data.reduce((m, c) => { m[c.id] = c; return m; }, {});
    populateCategoryFilter();
    
    // Trigger render if browse tab is active
    const activeTab = document.querySelector('.tab.active');
    if (activeTab && activeTab.dataset.tab === 'browse') {
      loadBrowseProducts();
    }
    
    return categories; // Return for Promise chaining
  } catch (e) {
    console.error('Failed to load categories:', e);
    categories = {};
    return {};
  }
}

function populateCategoryFilter() {
  const select = document.getElementById('categoryFilter');
  if (!select) return;
  
  select.innerHTML = '<option value="">Tất cả loại</option>';
  Object.values(categories).forEach(cat => {
    select.innerHTML += `<option value="${cat.id}">${cat.type}</option>`;
  });
}

function loadBrowseProducts() {
  // Populate browse category filter
  const THRESHOLD = 12;
  let browseCategoryFilter = document.getElementById('browseCategoryFilter');
  // If many categories, create a custom scrollable chooser
  if (Object.values(categories).length > THRESHOLD) {
    // If already replaced, update list
    if (browseCategoryFilter && browseCategoryFilter.tagName && browseCategoryFilter.tagName.toLowerCase() !== 'select') {
      const hidden = browseCategoryFilter;
      const wrapper = hidden.closest('.custom-select-wrapper');
      const list = wrapper.querySelector('.custom-select-list');
      const toggle = wrapper.querySelector('.custom-select-toggle');
      list.innerHTML = '';
      const def = document.createElement('div');
      def.className = 'custom-option';
      def.dataset.value = '';
      def.textContent = '📂 Tất cả loại';
      def.addEventListener('click', () => { hidden.value = ''; toggle.textContent = def.textContent; list.style.display = 'none'; hidden.dispatchEvent(new Event('change')); });
      list.appendChild(def);
      Object.values(categories).forEach(cat => {
        const opt = document.createElement('div');
        opt.className = 'custom-option';
        opt.dataset.value = cat.id;
        opt.textContent = `📂 ${cat.type}`;
        opt.addEventListener('click', () => { hidden.value = cat.id; toggle.textContent = opt.textContent; list.style.display = 'none'; hidden.dispatchEvent(new Event('change')); });
        list.appendChild(opt);
      });
    } else {
      // replace select with custom wrapper
      if (browseCategoryFilter && browseCategoryFilter.tagName && browseCategoryFilter.tagName.toLowerCase() === 'select') {
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select-wrapper';
        wrapper.style.flex = '1'; wrapper.style.minWidth = '0';

        const hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.id = 'browseCategoryFilter'; hidden.name = 'browseCategoryFilter'; hidden.value = '';

        const toggle = document.createElement('button');
        toggle.type = 'button'; toggle.className = 'custom-select-toggle'; toggle.textContent = '📂 Tất cả loại';

        const list = document.createElement('div'); list.className = 'custom-select-list'; list.style.display = 'none';

        const def = document.createElement('div'); def.className = 'custom-option'; def.dataset.value = ''; def.textContent = '📂 Tất cả loại';
        def.addEventListener('click', () => { hidden.value = ''; toggle.textContent = def.textContent; list.style.display = 'none'; hidden.dispatchEvent(new Event('change')); });
        list.appendChild(def);

        Object.values(categories).forEach(cat => {
          const opt = document.createElement('div');
          opt.className = 'custom-option';
          opt.dataset.value = cat.id;
          opt.textContent = `📂 ${cat.type}`;
          opt.addEventListener('click', () => { hidden.value = cat.id; toggle.textContent = opt.textContent; list.style.display = 'none'; hidden.dispatchEvent(new Event('change')); });
          list.appendChild(opt);
        });

        wrapper.appendChild(hidden);
        wrapper.appendChild(toggle);
        wrapper.appendChild(list);

        hidden.addEventListener('change', () => { browsePage = 1; if (Object.keys(products).length > 0) renderProductList(); });

        browseCategoryFilter.replaceWith(wrapper);

        toggle.addEventListener('click', (e) => { e.stopPropagation(); list.style.display = (list.style.display === 'block') ? 'none' : 'block'; });
        document.addEventListener('click', () => { list.style.display = 'none'; });
      }
    }
  } else {
    // small list: ensure native select exists and populate
    if (browseCategoryFilter && browseCategoryFilter.tagName && browseCategoryFilter.tagName.toLowerCase() !== 'select') {
      const select = document.createElement('select');
      select.id = 'browseCategoryFilter';
      select.className = 'camera-select';
      browseCategoryFilter.replaceWith(select);
      browseCategoryFilter = select;
    }
    browseCategoryFilter = document.getElementById('browseCategoryFilter');
    if (browseCategoryFilter) {
      browseCategoryFilter.innerHTML = '<option value="">📂 Tất cả loại</option>';
      Object.values(categories).forEach(cat => {
        browseCategoryFilter.innerHTML += `<option value="${cat.id}">📂 ${cat.type}</option>`;
      });
    }
  }
  
  // Ensure price filter uses matching custom wrapper so heights align and it can scroll if needed
  const priceOpts = [
    { v: '', t: '💰 Tất cả giá' },
    { v: '0-100000', t: '💰 Dưới 100.000₫' },
    { v: '100000-200000', t: '💰 100.000₫ - 200.000₫' },
    { v: '200000-300000', t: '💰 200.000₫ - 300.000₫' },
    { v: '300000-400000', t: '💰 300.000₫ - 400.000₫' },
    { v: '400000+', t: '💰 Trên 400.000₫' }
  ];

  let browsePriceFilter = document.getElementById('browsePriceFilter');
  // If a custom wrapper already exists (not a select), update or create a wrapper
  if (browsePriceFilter && browsePriceFilter.tagName && browsePriceFilter.tagName.toLowerCase() !== 'select') {
    const hidden = browsePriceFilter;
    const wrapper = hidden.closest('.custom-select-wrapper');
    const list = wrapper.querySelector('.custom-select-list');
    const toggle = wrapper.querySelector('.custom-select-toggle');
    list.innerHTML = '';
    priceOpts.forEach(optData => {
      const opt = document.createElement('div');
      opt.className = 'custom-option';
      opt.dataset.value = optData.v;
      opt.textContent = optData.t;
      opt.addEventListener('click', () => { hidden.value = optData.v; toggle.textContent = optData.t; list.style.display = 'none'; hidden.dispatchEvent(new Event('change')); });
      list.appendChild(opt);
    });
  } else {
    // replace native select with custom wrapper to match category UI
    if (browsePriceFilter && browsePriceFilter.tagName && browsePriceFilter.tagName.toLowerCase() === 'select') {
      const wrapper = document.createElement('div');
      wrapper.className = 'custom-select-wrapper';
      wrapper.style.flex = '1'; wrapper.style.minWidth = '0';

      const hidden = document.createElement('input');
      hidden.type = 'hidden'; hidden.id = 'browsePriceFilter'; hidden.name = 'browsePriceFilter'; hidden.value = '';

      const toggle = document.createElement('button');
      toggle.type = 'button'; toggle.className = 'custom-select-toggle'; toggle.textContent = '💰 Tất cả giá';

      const list = document.createElement('div'); list.className = 'custom-select-list'; list.style.display = 'none';

      priceOpts.forEach(optData => {
        const opt = document.createElement('div');
        opt.className = 'custom-option';
        opt.dataset.value = optData.v;
        opt.textContent = optData.t;
        opt.addEventListener('click', () => { hidden.value = optData.v; toggle.textContent = optData.t; list.style.display = 'none'; hidden.dispatchEvent(new Event('change')); });
        list.appendChild(opt);
      });

      wrapper.appendChild(hidden);
      wrapper.appendChild(toggle);
      wrapper.appendChild(list);

      hidden.addEventListener('change', () => { browsePage = 1; if (Object.keys(products).length > 0) renderProductList(); });

      browsePriceFilter.replaceWith(wrapper);

      toggle.addEventListener('click', (e) => { e.stopPropagation(); list.style.display = (list.style.display === 'block') ? 'none' : 'block'; });
      document.addEventListener('click', () => { list.style.display = 'none'; });
    }
  }

  // Reset to first page and render products
  browsePage = 1;
  renderProductList();
}
function listDevices() {
  // No camera selection UI: default to using the device's back/environment camera.
  // Keep selectedDeviceId null so startScanner requests `facingMode: environment`.
  selectedDeviceId = null;
  return Promise.resolve();
}

// Preprocess canvas: grayscale + contrast stretch
function preprocessCanvas(ctx, w, h) {
  try {
    const imgd = ctx.getImageData(0, 0, w, h);
    const data = imgd.data;
    let min = 255, max = 0;
    for (let i = 0; i < data.length; i += 4) {
      const g = Math.round(0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2]);
      data[i] = data[i + 1] = data[i + 2] = g;
      if (g < min) min = g;
      if (g > max) max = g;
    }
    const range = Math.max(1, max - min);
    for (let i = 0; i < data.length; i += 4) {
      let v = data[i];
      v = Math.round((v - min) * 255 / range);
      data[i] = data[i + 1] = data[i + 2] = v;
    }
    ctx.putImageData(imgd, 0, 0);
  } catch (e) {
    // ignore
  }
}

function getBarcodeDetector() {
  if (typeof BarcodeDetector === 'undefined') return null;
  try {
    if (!barcodeDetector) barcodeDetector = new BarcodeDetector({ formats: ['ean_13','ean_8','upc_e','upc_a','code_128','code_39','qr_code'] });
    return barcodeDetector;
  } catch (e) { return null; }
}

async function detectWithBarcodeDetector(source) {
  const detector = getBarcodeDetector();
  if (!detector) return null;
  try {
    // Debug: log that BarcodeDetector is being used
    // Only log when a result is found to avoid flooding the console
    const result = await detector.detect(source);
    if (result && result.length) {
      return result[0].rawValue || (result[0].raw && result[0].raw.value) || null;
    }
    return null;
  } catch (e) { console.warn('detectWithBarcodeDetector: error', e); return null; }
}

// Start scanner - custom loop using canvas preprocessing, native BarcodeDetector first, then ZXing fallback
async function startScanner() {
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) throw new Error('no_media');
  let stream;
  const baseVideoConstraints = { width: { ideal: 1280 }, height: { ideal: 720 } };
  try {
    if (selectedDeviceId) {
      stream = await navigator.mediaDevices.getUserMedia({ video: Object.assign({ deviceId: { exact: selectedDeviceId } }, baseVideoConstraints) });
    } else {
      try { stream = await navigator.mediaDevices.getUserMedia({ video: Object.assign({ facingMode: { exact: 'environment' } }, baseVideoConstraints) }); }
      catch (e) { stream = await navigator.mediaDevices.getUserMedia({ video: Object.assign({ facingMode: { ideal: 'environment' } }, baseVideoConstraints) }); }
    }
  } catch (err) {
    throw err;
  }

  currentStream = stream;
  video.srcObject = stream;
  await video.play();

  const canvas = document.getElementById('canvas') || document.createElement('canvas');
  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  let stopped = false;

  async function loop() {
    if (stopped) return;
    try {
      const w = video.videoWidth || 1280;
      const h = video.videoHeight || 720;
      canvas.width = w; canvas.height = h;
      ctx.drawImage(video, 0, 0, w, h);

      // try native detector first
      const nativeResult = await detectWithBarcodeDetector(canvas);
      if (nativeResult) {
        const now = Date.now();
        if (!(lastDetected.code === nativeResult && (now - lastDetected.time) < 800)) {
          lastDetected.code = nativeResult; lastDetected.time = now;
          handleDetected(nativeResult);
        }
      }

      // preprocess and try ZXing
      preprocessCanvas(ctx, w, h);
      try {
        if (codeReader) {
          // Throttle ZXing attempts to reduce CPU and error spam
          const nowAttempt = Date.now();
          if (nowAttempt - lastZxingAttempt < 600) {
            // skip this ZXing decode for throttling
          } else {
            lastZxingAttempt = nowAttempt;
          // ZXing expects an HTMLImageElement (with .complete). Canvas doesn't have that property,
          // so convert the canvas to an image first to avoid "reading 'complete'" errors.
          try {
            const img = new Image();
            img.src = canvas.toDataURL('image/png');
            await new Promise((res, rej) => { img.onload = res; img.onerror = rej; });
            // Prefer decodeFromImageElement if available, fallback to decodeFromImage
            let zres = null;
            if (typeof codeReader.decodeFromImageElement === 'function') {
              zres = await codeReader.decodeFromImageElement(img);
            } else if (typeof codeReader.decodeFromImage === 'function') {
              zres = await codeReader.decodeFromImage(img);
            } else if (typeof codeReader.decode === 'function') {
              // some builds expose a generic decode API
              zres = await codeReader.decode(img);
            }
            
            if (zres && (zres.text || zres.result)) {
              const text = zres.text || zres.result || (zres.rawValue || null) || null;
              if (text) {
                const now = Date.now();
                if (!(lastDetected.code === text && (now - lastDetected.time) < 800)) {
                  lastDetected.code = text; lastDetected.time = now;
                  handleDetected(text);
                }
              }
            }
            } catch (innerErr) {
              // image conversion or decode failed for this frame — continue loop
              const msg = innerErr && (innerErr.message || innerErr.toString()) || '';
              // Suppress frequent expected 'No MultiFormat Readers' errors; log only occasionally
              if (/No MultiFormat/.test(msg) || /Not Found/.test(msg)) {
                if (Date.now() - zxingErrorLastLog > 5000) {
                  console.debug('startScanner.loop: suppressed zxing decode error:', msg);
                  zxingErrorLastLog = Date.now();
                }
              } else {
                console.warn('startScanner.loop: zxing decode inner error', innerErr);
              }
            }
          }
        }
      } catch (e) { console.warn('startScanner.loop: zxing decode error', e); }
    } catch (e) { /* loop error */ }
    setTimeout(loop, 350);
  }
  loop();

  return () => { stopped = true; try { stream.getTracks().forEach(t => t.stop()); } catch (e) {} currentStream = null; scanning = false; scanningStopper = null; };
}

// Called when a barcode is detected by either native detector or ZXing
function handleDetected(code) {
  if (!code) return;
  // Debounce/quick guard already handled by loop via lastDetected
  const doAction = async () => {
    try {
      // Fill manual input so user can see the scanned code
      try {
        if (manualBarcodeInput) {
          manualBarcodeInput.value = code;
          manualBarcodeInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
      } catch (e) { console.warn('handleDetected: failed to populate manual input', e); }
      if (Object.keys(products).length === 0) {
        await loadProducts();
        
      }
      if (mode === 'cart') {
        addToCartByBarcode(code);
      } else {
        showProductForBarcode(code);
      }
    } catch (e) {
      console.error('handleDetected: error', e);
    }
  };
  doAction();
}

// Show product for price check mode
function showProductForBarcode(code) {
  const p = products[code];
  if (notFoundBox) notFoundBox.classList.remove('show');
  
  if (p) {
    productImage.src = getImageUrl(p.image);
    productImage.classList.remove('placeholder');
    productName.textContent = p.name;
    productCategory.textContent = p.category_type ? `📂 ${p.category_type}` : '';
    productPrice.textContent = formatPrice(p.price) + ' ₫';
    productBox.classList.add('show');
    showToast('Đã tìm thấy sản phẩm', 'success');
    // If in price lookup mode, stop the scanner so user can inspect the result
    try {
      if (mode === 'price') {
        
        // Prefer using the existing stop handler to keep UI state consistent
        try {
          if (stopBtn && typeof stopBtn.click === 'function') stopBtn.click();
        } catch (e) {
          console.warn('showProductForBarcode: stopBtn.click() failed', e);
        }

        // Additional cleanup to ensure camera is stopped
        try {
          if (typeof scanningStopper === 'function') {
            scanningStopper();
          }
        } catch (e) { /* ignore */ }

        try { if (codeReader && typeof codeReader.reset === 'function') codeReader.reset(); } catch (e) {}
        try { if (currentStream) { currentStream.getTracks().forEach(t => t.stop()); currentStream = null; } } catch (e) {}
        try { if (video && video.srcObject) video.srcObject = null; } catch (e) {}

        scanning = false;
        scanningStopper = null;
        if (startBtn) startBtn.disabled = false;
        if (stopBtn) stopBtn.disabled = true;
        if (placeholder) placeholder.classList.remove('hidden');
      }
    } catch (e) { console.warn('Error stopping scanner after product found', e); }
  } else {
    productBox.classList.remove('show');
    if (notFoundBox) notFoundBox.classList.add('show');
    showToast('Không tìm thấy sản phẩm', 'error');
  }
}

// Add to cart
function addToCartByBarcode(code) {
  const p = products[code];
  if (!p) {
    showToast('Sản phẩm không có trong cơ sở dữ liệu', 'error');
    return;
  }
  
  if (!cart[code]) {
    cart[code] = { product: p, qty: 1 };
    showToast('Đã thêm vào giỏ hàng', 'success');
  } else {
    cart[code].qty++;
    showToast('Đã tăng số lượng', 'success');
  }
  renderCart();
}

// Render cart
function renderCart() {
  if (!cartItemsContainer) return;
  
  const cartEmpty = document.getElementById('cartEmpty');
  const keys = Object.keys(cart);
  let total = 0;
  let count = 0;
  
  // Clear existing items (keep cartEmpty)
  cartItemsContainer.querySelectorAll('.cart-item').forEach(el => el.remove());
  
  if (keys.length === 0) {
    if (cartEmpty) cartEmpty.style.display = 'block';
  } else {
    if (cartEmpty) cartEmpty.style.display = 'none';
    
    keys.forEach(code => {
      const item = cart[code];
      const p = item.product;
      const priceNum = parseFloat((p.price || '0').toString().replace(/[^0-9.-]+/g, '')) || 0;
      const lineTotal = priceNum * item.qty;
      total += lineTotal;
      count += item.qty;
      
      const div = document.createElement('div');
      div.className = 'cart-item';
      div.dataset.code = code;
      
      div.innerHTML = `
        <img src="${getImageUrl(p.image)}" alt="${p.name}" class="cart-item-image" onerror="this.src='${defaultImageDataUrl()}'">
        <div class="cart-item-info">
          <div class="cart-item-name">${p.name}</div>
          <div class="cart-item-price">${formatPrice(priceNum)} ₫</div>
        </div>
        <div class="cart-item-qty">
          <button class="qty-dec">−</button>
          <span>${item.qty}</span>
          <button class="qty-inc">+</button>
        </div>
        <button class="cart-item-delete" title="Xóa sản phẩm">×</button>
      `;
      
      div.querySelector('.qty-dec').onclick = () => {
        if (item.qty > 1) { item.qty--; renderCart(); }
      };
      div.querySelector('.qty-inc').onclick = () => { item.qty++; renderCart(); };
      div.querySelector('.cart-item-delete').onclick = () => { delete cart[code]; renderCart(); };
      
      cartItemsContainer.appendChild(div);
    });
  }
  
  if (cartTotalEl) cartTotalEl.textContent = formatPrice(total);
  if (cartCountEl) cartCountEl.textContent = count;
  // Also update small preview shown in browse tab
  renderCartPreview();
}

function renderCartPreview() {
  const preview = document.getElementById('browseCartPreview');
  if (!preview) return;

  const keys = Object.keys(cart);
  if (keys.length === 0) {
    preview.style.display = 'none';
    return;
  }

  preview.style.display = 'block';
  let html = `<div style="font-weight:600; margin-bottom:8px;">Giỏ hàng (${keys.length} loại)</div>`;
  let total = 0;
  keys.slice(0,5).forEach(code => {
    const item = cart[code];
    const p = item.product;
    const priceNum = parseFloat((p.price || '0').toString().replace(/[^0-9.-]+/g, '')) || 0;
    total += priceNum * item.qty;
    html += `<div class="item"><span>${p.name} x${item.qty}</span><span>${formatPrice(priceNum*item.qty)} ₫</span></div>`;
  });
  if (keys.length > 5) html += `<div style="text-align:center; color:var(--text-muted); margin-top:6px;">... và ${keys.length-5} món khác</div>`;
  html += `<div style="margin-top:8px; font-weight:700; display:flex; justify-content:space-between; align-items:center;"><span>Tổng</span><span>${formatPrice(total)} ₫</span></div>`;
  html += `<div style="margin-top:8px; text-align:right;"><button id="browseViewCart" class="btn btn-secondary">Xem giỏ hàng</button></div>`;
  preview.innerHTML = html;

  const viewBtn = document.getElementById('browseViewCart');
  if (viewBtn) viewBtn.addEventListener('click', () => {
    const ca = document.getElementById('cartArea');
    if (ca) {
      ca.scrollIntoView({ behavior: 'smooth', block: 'start' });
      ca.classList.add('show');
    }
  });
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
// No video select on main UI; selection disabled intentionally.

if (startBtn) startBtn.addEventListener('click', async () => {
  if (scanning) return;
  scanning = true;
  // scanning started
  startBtn.disabled = true;
  stopBtn.disabled = false;
  if (placeholder) placeholder.classList.add('hidden');
  
  try {
    scanningStopper = await startScanner();
  } catch (e) {
    showToast('Không thể khởi tạo camera', 'error');
    scanning = false;
    startBtn.disabled = false;
    stopBtn.disabled = true;
    if (placeholder) placeholder.classList.remove('hidden');
  }
});

if (stopBtn) stopBtn.addEventListener('click', () => {
  if (typeof scanningStopper === 'function') scanningStopper();
  scanning = false;
  // scanning stopped
  if (startBtn) startBtn.disabled = false;
  if (stopBtn) stopBtn.disabled = true;
  if (placeholder) placeholder.classList.remove('hidden');
});

if (modePrice) modePrice.addEventListener('change', () => {
  if (modePrice.checked) {
    mode = 'price';
    // keep cart visible
    if (productBox) productBox.classList.remove('show');
    if (notFoundBox) notFoundBox.classList.remove('show');
  }
});
if (modeCart) modeCart.addEventListener('change', () => {
  if (modeCart.checked) {
    mode = 'cart';
    if (cartArea) cartArea.classList.add('show');
    if (productBox) productBox.classList.remove('show');
    if (notFoundBox) notFoundBox.classList.remove('show');
  }
});

if (clearCartBtn) clearCartBtn.addEventListener('click', async () => {
  const ok = await showConfirm('Xóa toàn bộ giỏ hàng?', 'Xóa giỏ hàng');
  if (ok) {
    cart = {};
    renderCart();
  }
});

// Manual lookup button
if (manualLookupBtn) {
  manualLookupBtn.addEventListener('click', () => {
    const code = (manualBarcodeInput && manualBarcodeInput.value || '').toString().trim();
    if (!code) {
      showToast('Vui lòng nhập mã vạch', 'error');
      return;
    }
    // Ensure products are loaded
    const doLookup = () => {
      const p = products[code];
      if (mode === 'cart') {
        // add to cart mode
        if (p) {
          addToCartByBarcode(code);
        } else {
          showToast('Sản phẩm không có trong cơ sở dữ liệu', 'error');
        }
      } else {
        // price lookup mode
        if (p) showProductForBarcode(code);
        else showToast('Không tìm thấy sản phẩm với mã này', 'error');
      }
    };

    if (Object.keys(products).length === 0) {
      loadProducts().then(doLookup).catch(() => showToast('Lỗi khi tải sản phẩm', 'error'));
      return;
    }

    doLookup();
  });

  // allow Enter key in input
  if (manualBarcodeInput) {
    manualBarcodeInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        manualLookupBtn.click();
      }
    });
  }
}

checkoutBtn.addEventListener('click', () => {
  const total = getCartTotal();
  if (total === 0) {
    showToast('Giỏ hàng trống', 'error');
    return;
  }
  
  const formatted = formatPrice(total);
  const paymentModal = document.getElementById('paymentModal');
  const paymentQRCode = document.getElementById('paymentQRCode');
  const paymentAmount = document.getElementById('paymentAmount');
  
  // Use your own QR code image
  if (paymentQRCode) {
    paymentQRCode.src = 'uploads/QR.jpg';
  }
  
  if (paymentAmount) paymentAmount.textContent = formatted;
  if (paymentModal) paymentModal.classList.add('show');
});

// Payment modal close (simple handler)
const paymentModal = document.getElementById('paymentModal');
const paymentCloseBtn = document.getElementById('paymentCloseBtn');
if (paymentCloseBtn) {
  paymentCloseBtn.addEventListener('click', () => {
    if (paymentModal) paymentModal.classList.remove('show');
  });
}

// Generic confirm modal helper (replaces native confirm())
const confirmModal = document.getElementById('confirmModal');
const confirmTitle = document.getElementById('confirmTitle');
const confirmMessage = document.getElementById('confirmMessage');
const confirmCancel = document.getElementById('confirmCancel');
const confirmOk = document.getElementById('confirmOk');

function showConfirm(message, title = 'Xác nhận') {
  return new Promise((resolve) => {
    if (!confirmModal) {
      // fallback to native confirm if modal not present
      try { resolve(window.confirm(message)); } catch (e) { resolve(false); }
      return;
    }
    if (confirmTitle) confirmTitle.textContent = title;
    if (confirmMessage) confirmMessage.textContent = message;
    confirmModal.classList.add('show');

    function cleanup(result) {
      confirmModal.classList.remove('show');
      confirmOk.removeEventListener('click', onOk);
      confirmCancel.removeEventListener('click', onCancel);
      resolve(result);
    }

    function onOk() { cleanup(true); }
    function onCancel() { cleanup(false); }

    confirmOk.addEventListener('click', onOk);
    confirmCancel.addEventListener('click', onCancel);
  });
}

// Render products if data is available, otherwise show loading
if (Object.keys(products).length > 0) {
  renderProductList();
} else {
  const grid = document.getElementById('productGrid');
  if (grid) {
    grid.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--text-muted);">Đang tải sản phẩm...</div>';
  }
}

function renderProductList() {
  const grid = document.getElementById('productGrid');
  if (!grid) {
    console.error('Product grid not found - check if browseTab is visible');
    return;
  }

  if (Object.keys(products).length === 0) {
    grid.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--text-muted);">Đang tải sản phẩm...</div>';
    return;
  }

  // Apply grid layout
  grid.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin-top: 16px;';

  const browseCategoryFilter = document.getElementById('browseCategoryFilter');
  const browsePriceFilter = document.getElementById('browsePriceFilter');
  const browseNameFilter = document.getElementById('browseNameFilter');
  const categoryId = browseCategoryFilter ? browseCategoryFilter.value : '';
  const priceRange = browsePriceFilter ? browsePriceFilter.value : '';
  const nameQuery = browseNameFilter ? browseNameFilter.value.trim().toLowerCase() : '';
  // Debug info to help trace search behavior
  try { console.debug('[browse] renderProductList', { nameQuery, productsCount: Object.keys(products).length }); } catch (e) {}

  let filteredProducts = Object.values(products);

  // Filter by category
  if (categoryId) {
    filteredProducts = filteredProducts.filter(p => p.category_id == categoryId);
  }

  // Filter by price
  if (priceRange) {
    filteredProducts = filteredProducts.filter(p => {
      const price = parseFloat(p.price) || 0;
      switch (priceRange) {
          case '0-100000': return price < 100000;
          case '100000-200000': return price >= 100000 && price < 200000;
          case '200000-300000': return price >= 200000 && price < 300000;
          case '300000-400000': return price >= 300000 && price < 400000;
          case '400000+': return price >= 400000;
          default: return true;
        }
    });
  }

  // Filter by name (partial match, case insensitive, multiple words)
  if (nameQuery) {
    const searchWords = nameQuery.split(/\s+/).filter(word => word.length > 0);
    filteredProducts = filteredProducts.filter(p => {
      const b = (p.barcode || '').toLowerCase();
      const n = (p.name || '').toLowerCase();
      const c = (p.category_type || '').toLowerCase();
      return searchWords.some(word => 
        b.includes(word) || n.includes(word) || c.includes(word)
      );
    });
  }

  // Update on-screen debug with match count (if present)
  try {
    const dbg = document.getElementById('browseDebug');
    if (dbg) dbg.textContent = `Query: "${nameQuery}" — matches: ${filteredProducts.length}`;
  } catch (e) {}

  grid.innerHTML = '';

  if (filteredProducts.length === 0) {
    grid.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--text-muted); grid-column: 1 / -1;">Không tìm thấy sản phẩm nào phù hợp với bộ lọc đã chọn</div>';
    return;
  }
  // Pagination
  const totalItems = filteredProducts.length;
  const totalPages = Math.max(1, Math.ceil(totalItems / BROWSE_PER_PAGE));
  if (browsePage > totalPages) browsePage = totalPages;
  if (browsePage < 1) browsePage = 1;

  const start = (browsePage - 1) * BROWSE_PER_PAGE;
  const pageItems = filteredProducts.slice(start, start + BROWSE_PER_PAGE);

  let html = '';
  pageItems.forEach((product) => {
    html += `
      <div class="product-item">
        <img src="${getImageUrl(product.image)}" alt="${product.name}" class="product-item-image" onerror="this.src='${defaultImageDataUrl()}'">
        <div class="product-item-name">${product.name}</div>
        <div class="product-item-price">${formatPrice(product.price)} ₫</div>
        <button class="add-to-cart" data-barcode="${product.barcode}">Thêm vào giỏ</button>
      </div>
    `;
  });

  grid.innerHTML = html;

  // Render pagination controls
  const pager = document.getElementById('productPagination');
  if (pager) {
    let phtml = '';
    phtml += `<button data-page="prev">‹</button>`;
    for (let p = 1; p <= totalPages; p++) {
      phtml += `<button data-page="${p}" class="${p === browsePage ? 'active' : ''}">${p}</button>`;
    }
    phtml += `<button data-page="next">›</button>`;
    pager.innerHTML = phtml;

    // Attach handlers
    pager.querySelectorAll('button').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const val = btn.getAttribute('data-page');
        if (val === 'prev') browsePage = Math.max(1, browsePage - 1);
        else if (val === 'next') browsePage = Math.min(totalPages, browsePage + 1);
        else browsePage = parseInt(val, 10) || 1;
        renderProductList();
      });
    });
  }

  // Attach add-to-cart handlers
  grid.querySelectorAll('.add-to-cart').forEach(b => {
    b.addEventListener('click', (e) => {
      const code = b.getAttribute('data-barcode');
      if (code) addToCartByBarcode(code);
      e.stopPropagation();
    });
  });
}

// Initialize
listDevices();
loadProducts();
loadCategories();
window.addEventListener('focus', listDevices);

// Setup event listeners (DOM is ready since script is at end of body)
// event listeners setup

const tabs = document.querySelectorAll('.tab');
const tabContents = document.querySelectorAll('.tab-content');

tabs.forEach(tab => {
  tab.addEventListener('click', () => {
    // tab clicked
    // Remove active class from all tabs
    tabs.forEach(t => t.classList.remove('active'));
    // Add active class to clicked tab
    tab.classList.add('active');
    
    // Hide all tab contents
    tabContents.forEach(content => content.style.display = 'none');
    
    // Show selected tab content
    const tabId = tab.dataset.tab + 'Tab';
    const tabContent = document.getElementById(tabId);
    // switching to tab
    if (tabContent) {
      // Force display with multiple methods
      tabContent.style.display = 'block';
      tabContent.style.visibility = 'visible';
      tabContent.style.opacity = '1';
      tabContent.removeAttribute('hidden');
      
      // tab display set
      
      try {
        // Ensure the browse area is visible to the user (auto-scroll)
        setTimeout(() => {
          tabContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);

        // Also attempt to focus the first interactive element inside the tab
        const firstControl = tabContent.querySelector('select, button, input');
        if (firstControl && typeof firstControl.focus === 'function') {
          setTimeout(() => firstControl.focus({ preventScroll: true }), 200);
        }
      } catch (e) {
        console.warn('Auto-scroll failed:', e);
      }
    } else {
      console.error('Tab content not found:', tabId);
    }
    
    // Load products for browse tab
    if (tab.dataset.tab === 'browse') {
      // browse tab clicked
      // Ensure products and categories are loaded before setting up filters
      const loadPromises = [];
      if (Object.keys(products).length === 0) {
        // products not loaded, loading
        loadPromises.push(loadProducts());
      }
      if (Object.keys(categories).length === 0) {
        // categories not loaded, loading
        loadPromises.push(loadCategories());
      }
      
      if (loadPromises.length > 0) {
        Promise.all(loadPromises).then(() => {
          // data loaded
          setTimeout(() => {
            setupBrowseFilters();
            loadBrowseProducts();
          }, 10);
        });
      } else {
        // data already loaded
        setTimeout(() => {
          setupBrowseFilters();
          loadBrowseProducts();
        }, 10);
      }
    }
  });
});

// Browse filters
// browse filters presence checked

// Browse filters will be set up when browse tab is clicked

function setupBrowseFilters() {
  // Browse filters
  const browseCategoryFilter = document.getElementById('browseCategoryFilter');
  const browsePriceFilter = document.getElementById('browsePriceFilter');
  const browseNameFilter = document.getElementById('browseNameFilter');

  // Event listeners for browse filters
  if (browseCategoryFilter) {
    browseCategoryFilter.addEventListener('change', () => {
      browsePage = 1;
      if (Object.keys(products).length > 0) renderProductList();
    });
  }

  if (browsePriceFilter) {
    browsePriceFilter.addEventListener('change', () => {
      browsePage = 1;
      if (Object.keys(products).length > 0) renderProductList();
    });
  }

  if (browseNameFilter) {
    // Event listeners added globally
  }

  const searchBtn = document.getElementById('searchBtn');
  if (searchBtn) {
    // Event listener added globally
  }

  // Refresh button: reset filters and reload products
  const refreshProductsBtn = document.getElementById('refreshProducts');
  if (refreshProductsBtn) {
    refreshProductsBtn.addEventListener('click', () => {
      const cf = document.getElementById('browseCategoryFilter');
      const pf = document.getElementById('browsePriceFilter');
      const nf = document.getElementById('browseNameFilter');
      if (cf) cf.value = '';
      if (pf) pf.value = '';
      if (nf) nf.value = '';
      loadProducts();
      loadCategories();
      renderProductList();
    });
  }
}

// Attach browse filter change listeners so UI updates immediately
const browseCategoryEl = document.getElementById('browseCategoryFilter');
if (browseCategoryEl) {
  browseCategoryEl.addEventListener('change', () => { browsePage = 1; renderProductList(); });
}

const browsePriceEl = document.getElementById('browsePriceFilter');
if (browsePriceEl) {
  browsePriceEl.addEventListener('change', () => { browsePage = 1; renderProductList(); });
}

const browseNameEl = document.getElementById('browseNameFilter');
let _browseNamePollInterval = null;
if (browseNameEl) {
  // Standard handlers
  browseNameEl.addEventListener('input', () => { browsePage = 1; renderProductList(); });
  browseNameEl.addEventListener('change', () => { browsePage = 1; renderProductList(); });
  browseNameEl.addEventListener('keyup', (e) => { if (e.key === 'Enter') { browsePage = 1; renderProductList(); } });
  // Handle composition events (IME) which may delay input events on mobile
  browseNameEl.addEventListener('compositionend', () => { browsePage = 1; renderProductList(); });

  // Polling fallback for browsers/devices that don't fire input reliably during typing
  try {
    let lastVal = browseNameEl.value || '';
    _browseNamePollInterval = setInterval(() => {
      const v = browseNameEl.value || '';
      if (v !== lastVal) {
        lastVal = v;
        browsePage = 1;
        renderProductList();
      }
    }, 250);
  } catch (e) {
    console.warn('browseName polling setup failed', e);
  }
}

const searchBtn = document.getElementById('searchBtn');
if (searchBtn) {
  searchBtn.addEventListener('click', (e) => { e.preventDefault(); browsePage = 1; renderProductList(); });
}

// Clean up polling interval on unload/pagehide to avoid leaks
function _clearBrowseNamePoll() {
  try {
    if (_browseNamePollInterval) {
      clearInterval(_browseNamePollInterval);
      _browseNamePollInterval = null;
    }
  } catch (e) {}
}
window.addEventListener('beforeunload', _clearBrowseNamePoll);
window.addEventListener('pagehide', _clearBrowseNamePoll);
