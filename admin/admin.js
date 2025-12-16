/**
 * Admin JavaScript - Product Management
 */

let _csrfToken = '';
let _productsCache = [];
let _productsPage = 1;
let _stream = null;
let _capturedImageData = null;

// API base paths (adjusted for PHP)
const API = {
  products: '/api/products.php',
  auth: '/api/auth.php',
  upload: '/api/upload.php'
};

// Check authentication status
async function checkAuth() {
  const r = await fetch(`${API.auth}/status`, { credentials: 'include' });
  return r.json();
}

// Get CSRF token
async function getCSRFToken() {
  const r = await fetch(`${API.auth}/csrf-token`, { credentials: 'include' });
  const data = await r.json();
  _csrfToken = data.csrfToken;
  return _csrfToken;
}

// Load products
async function loadProducts() {
  const r = await fetch(API.products);
  const data = await r.json();
  _productsCache = data;
  renderProducts();
}

// Render products table
function renderProducts() {
  const tbody = document.querySelector('#productsTable tbody');
  tbody.innerHTML = '';
  
  const searchInput = document.getElementById('searchInput');
  const q = searchInput?.value?.trim().toLowerCase() || '';
  
  // Filter and sort
  let items = _productsCache.filter(p => {
    if (!q) return true;
    const b = (p.barcode || '').toLowerCase();
    const n = (p.name || '').toLowerCase();
    return b.includes(q) || n.includes(q);
  });
  
  items.sort((a, b) => {
    const ta = a.updated_at || a.created_at || a.id || 0;
    const tb = b.updated_at || b.created_at || b.id || 0;
    return new Date(tb) - new Date(ta);
  });
  
  // Pagination
  const perPage = 10;
  const total = items.length;
  const totalPages = Math.max(1, Math.ceil(total / perPage));
  if (_productsPage > totalPages) _productsPage = totalPages;
  const start = (_productsPage - 1) * perPage;
  const pageItems = items.slice(start, start + perPage);
  
  pageItems.forEach(p => {
    const tr = document.createElement('tr');
    const imgHtml = p.image ? `<img src="${p.image}" alt="">` : '';
    tr.innerHTML = `
      <td>${imgHtml}</td>
      <td>${escapeHtml(p.barcode)}</td>
      <td class="product-name">${escapeHtml(p.name)}</td>
      <td>${escapeHtml(p.price)}</td>
      <td>
        <button data-id="${p.id}" class="edit">Sửa</button>
        <button data-id="${p.id}" class="del">Xóa</button>
      </td>
    `;
    tbody.appendChild(tr);
  });
  
  // Render pagination
  renderPagination(total, totalPages);
}

function renderPagination(total, totalPages) {
  const pager = document.getElementById('pagination');
  if (!pager) return;
  
  pager.innerHTML = '';
  
  const info = document.createElement('div');
  info.style.cssText = 'color:#666;font-size:13px';
  const start = (_productsPage - 1) * 10;
  info.textContent = `Hiển thị ${start + 1}-${Math.min(start + 10, total)} trên ${total}`;
  pager.appendChild(info);
  
  const controls = document.createElement('div');
  controls.style.cssText = 'display:flex;gap:6px;margin-left:12px;align-items:center';
  
  const prev = document.createElement('button');
  prev.className = 'btn ghost';
  prev.textContent = '‹ Trước';
  prev.disabled = _productsPage <= 1;
  prev.onclick = () => { _productsPage--; renderProducts(); };
  controls.appendChild(prev);
  
  // Page numbers
  const maxPages = 5;
  let startPage = Math.max(1, _productsPage - 2);
  let endPage = Math.min(totalPages, startPage + maxPages - 1);
  if (endPage - startPage < maxPages - 1) startPage = Math.max(1, endPage - maxPages + 1);
  
  for (let i = startPage; i <= endPage; i++) {
    const btn = document.createElement('button');
    btn.className = i === _productsPage ? 'btn' : 'btn ghost';
    btn.textContent = i;
    btn.onclick = () => { _productsPage = i; renderProducts(); };
    controls.appendChild(btn);
  }
  
  const next = document.createElement('button');
  next.className = 'btn ghost';
  next.textContent = 'Sau ›';
  next.disabled = _productsPage >= totalPages;
  next.onclick = () => { _productsPage++; renderProducts(); };
  controls.appendChild(next);
  
  pager.appendChild(controls);
}

function escapeHtml(s) {
  return (s || '').toString()
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

// Toast notification
function showToast(message, timeout = 3000, type = '') {
  const wrap = document.getElementById('toastWrap');
  if (!wrap) return;
  
  const t = document.createElement('div');
  t.className = 'toast' + (type ? ' ' + type : '');
  t.textContent = message;
  wrap.appendChild(t);
  
  requestAnimationFrame(() => t.classList.add('show'));
  setTimeout(() => {
    t.classList.remove('show');
    setTimeout(() => t.remove(), 300);
  }, timeout);
}

// Field error helpers
function showFieldError(fieldId, message) {
  const el = document.getElementById(fieldId);
  if (el) el.classList.add('input-invalid');
  const err = document.getElementById('err_' + fieldId);
  if (err) { err.textContent = message; err.style.display = 'block'; }
}

function clearFieldError(fieldId) {
  const el = document.getElementById(fieldId);
  if (el) el.classList.remove('input-invalid');
  const err = document.getElementById('err_' + fieldId);
  if (err) { err.textContent = ''; err.style.display = 'none'; }
}

// Reset form
function resetForm() {
  document.getElementById('addForm').reset();
  document.getElementById('editingId').value = '';
  document.getElementById('formTitle').textContent = 'Thêm sản phẩm';
  document.getElementById('submitBtn').textContent = 'Thêm';
  document.getElementById('cancelEdit').style.display = 'none';
  document.getElementById('preview').innerHTML = '';
  _capturedImageData = null;
  
  ['addBarcode', 'addName', 'addCategory', 'addPrice', 'addImage'].forEach(clearFieldError);
}

// Show image preview
function showPreview(url) {
  const preview = document.getElementById('preview');
  if (url) {
    preview.innerHTML = `<img src="${url}" alt="Preview">`;
  } else {
    preview.innerHTML = '';
  }
}

// Upload data URL
async function uploadDataUrl(dataUrl) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = async () => {
      try {
        const maxDim = 800;
        let w = img.width, h = img.height;
        if (w > maxDim || h > maxDim) {
          const ratio = Math.min(maxDim / w, maxDim / h);
          w = Math.round(w * ratio);
          h = Math.round(h * ratio);
        }
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, w, h);
        
        canvas.toBlob(async (blob) => {
          if (!blob) return reject('compress_failed');
          
          const fd = new FormData();
          fd.append('image', blob, 'upload.jpg');
          
          const token = await getCSRFToken();
          const res = await fetch(API.upload, {
            method: 'POST',
            credentials: 'include',
            headers: { 'csrf-token': token },
            body: fd
          });
          
          if (!res.ok) {
            const j = await res.json().catch(() => ({}));
            return reject(j.error || res.status);
          }
          
          const j = await res.json();
          resolve(j.url);
        }, 'image/jpeg', 0.8);
      } catch (e) {
        reject(e);
      }
    };
    img.onerror = () => reject('image_load_failed');
    img.src = dataUrl;
  });
}

// Login form handler
document.getElementById('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const username = document.getElementById('username').value;
  const password = document.getElementById('password').value;
  
  const r = await fetch(`${API.auth}/login`, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password })
  });
  
  if (r.ok) {
    document.getElementById('auth').style.display = 'none';
    document.getElementById('adminPanel').style.display = 'block';
    await loadProducts();
  } else {
    const j = await r.json().catch(() => ({}));
    document.getElementById('loginMsg').textContent = j.error || 'Login failed';
  }
});

// Logout handler
document.getElementById('logoutBtn').addEventListener('click', async () => {
  await fetch(`${API.auth}/logout`, { method: 'POST', credentials: 'include' });
  location.reload();
});

// Add/Edit form handler
document.getElementById('addForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const id = document.getElementById('editingId').value;
  const barcode = document.getElementById('addBarcode').value.trim();
  const name = document.getElementById('addName').value.trim();
  const categoryId = document.getElementById('addCategory').value;
  const price = document.getElementById('addPrice').value.trim();
  let image = document.getElementById('addImage').value.trim();
  
  // Clear previous errors
  ['addBarcode', 'addName', 'addCategory', 'addPrice', 'addImage'].forEach(clearFieldError);
  
  // Validation
  let hasError = false;
  
  if (!barcode) {
    showFieldError('addBarcode', 'Barcode là bắt buộc');
    hasError = true;
  }
  
  // Check duplicate barcode (exclude current product when editing)
  const dup = _productsCache.find(p => p.barcode === barcode && (!id || String(p.id) !== String(id)));
  if (dup) {
    showFieldError('addBarcode', 'Barcode đã tồn tại');
    hasError = true;
  }
  
  if (!name) {
    showFieldError('addName', 'Tên sản phẩm là bắt buộc');
    hasError = true;
  }
  
  if (price && (!/^[0-9]+$/.test(price) || Number(price) <= 0)) {
    showFieldError('addPrice', 'Giá phải là số nguyên dương');
    hasError = true;
  }
  
  if (hasError) return;
  
  // Handle image upload (from camera capture or file upload)
  if (_capturedImageData && (image.startsWith('Đã chụp') || image.startsWith('Đã chọn'))) {
    image = _capturedImageData;
  }
  
  try {
    if (image && image.startsWith('data:')) {
      image = await uploadDataUrl(image);
    }
  } catch (err) {
    console.error('Upload error:', err);
    showFieldError('addImage', 'Không thể upload ảnh');
    return;
  }
  
  const token = await getCSRFToken();
  const payload = { barcode, name, image };
  if (price) payload.price = price;
  if (categoryId) payload.category_id = categoryId;
  
  try {
    let r;
    if (id) {
      // Update
      r = await fetch(`${API.products}/${id}`, {
        method: 'PUT',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'csrf-token': token },
        body: JSON.stringify(payload)
      });
    } else {
      // Create
      r = await fetch(API.products, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'csrf-token': token },
        body: JSON.stringify(payload)
      });
    }
    
    const j = await r.json().catch(() => ({}));
    
    if (r.ok) {
      _productsPage = 1;
      await loadProducts();
      resetForm();
      showToast(id ? 'Cập nhật thành công' : 'Thêm thành công', 3000, 'success');
      
      // Close form after save
      const addCard = document.getElementById('addCard');
      if (addCard) addCard.classList.remove('open');
      document.getElementById('toggleAddBtn').textContent = 'Thêm sản phẩm';
    } else {
      if (j.errors) {
        j.errors.forEach(err => {
          const map = { barcode: 'addBarcode', name: 'addName', price: 'addPrice', image: 'addImage' };
          if (map[err.param]) showFieldError(map[err.param], err.msg);
        });
      }
      showToast(j.error || 'Có lỗi xảy ra', 4000, 'error');
    }
  } catch (err) {
    console.error('Network/API error:', err);
    showToast('Lỗi mạng', 4000, 'error');
  }
});

// Products table click handler (edit/delete)
document.getElementById('productsTable').addEventListener('click', async (e) => {
  const id = e.target.dataset.id;
  
  if (e.target.classList.contains('del')) {
    if (!confirm('Xóa sản phẩm này?')) return;
    
    const token = await getCSRFToken();
    const r = await fetch(`${API.products}/${id}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: { 'csrf-token': token }
    });
    
    if (r.ok) {
      await loadProducts();
      showToast('Đã xóa sản phẩm', 3000, 'success');
    } else {
      showToast('Lỗi khi xóa', 3000, 'error');
    }
  }
  
  if (e.target.classList.contains('edit')) {
    const r = await fetch(`${API.products}/id/${id}`, { credentials: 'include' });
    if (!r.ok) {
      showToast('Không lấy được sản phẩm', 3000, 'error');
      return;
    }
    
    const p = await r.json();
    document.getElementById('addBarcode').value = p.barcode || '';
    document.getElementById('addName').value = p.name || '';
    document.getElementById('addCategory').value = p.category_id || '';
    document.getElementById('addPrice').value = p.price || '';
    document.getElementById('addImage').value = p.image || '';
    document.getElementById('editingId').value = p.id;
    document.getElementById('formTitle').textContent = 'Sửa sản phẩm';
    document.getElementById('submitBtn').textContent = 'Lưu';
    document.getElementById('cancelEdit').style.display = 'inline-block';
    showPreview(p.image);
    
    const addCard = document.getElementById('addCard');
    if (addCard) addCard.classList.add('open');
    document.getElementById('toggleAddBtn').textContent = 'Đóng';
    
    setTimeout(() => document.getElementById('addBarcode').focus(), 200);
  }
});

// Cancel edit
document.getElementById('cancelEdit').addEventListener('click', () => {
  resetForm();
});

// Toggle add form
document.getElementById('toggleAddBtn').addEventListener('click', () => {
  const addCard = document.getElementById('addCard');
  const isOpen = addCard.classList.contains('open');
  
  if (isOpen) {
    addCard.classList.remove('open');
    document.getElementById('toggleAddBtn').textContent = 'Thêm sản phẩm';
  } else {
    resetForm();
    addCard.classList.add('open');
    document.getElementById('toggleAddBtn').textContent = 'Đóng';
    setTimeout(() => document.getElementById('addBarcode').focus(), 200);
  }
});

// Search input
document.getElementById('searchInput').addEventListener('input', () => {
  _productsPage = 1;
  renderProducts();
});

// Clear errors on input
['addBarcode', 'addName', 'addImage'].forEach(id => {
  const el = document.getElementById(id);
  if (el) el.addEventListener('input', () => clearFieldError(id));
});

const priceEl = document.getElementById('addPrice');
if (priceEl) {
  priceEl.addEventListener('input', () => {
    priceEl.value = priceEl.value.replace(/[^0-9]/g, '');
    clearFieldError('addPrice');
  });
}

// File upload
document.getElementById('addImageUploadBtn')?.addEventListener('click', () => {
  document.getElementById('fileInput').click();
});

document.getElementById('fileInput')?.addEventListener('change', (e) => {
  const file = e.target.files[0];
  if (!file) return;
  
  const reader = new FileReader();
  reader.onload = (ev) => {
    _capturedImageData = ev.target.result;
    document.getElementById('addImage').value = 'Đã chọn ảnh';
    showPreview(_capturedImageData);
  };
  reader.readAsDataURL(file);
});

// Camera capture for image
let capStream = null;
const captureArea = document.getElementById('captureArea');
const capVideo = document.getElementById('capVideo');
const capCanvas = document.getElementById('capCanvas');

document.getElementById('addImageCameraBtn')?.addEventListener('click', async () => {
  try {
    capStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'environment' }
    });
    capVideo.srcObject = capStream;
    captureArea.style.display = 'flex';
  } catch (err) {
    alert('Không thể mở camera: ' + err.message);
  }
});

document.getElementById('takePhoto')?.addEventListener('click', () => {
  capCanvas.width = capVideo.videoWidth;
  capCanvas.height = capVideo.videoHeight;
  capCanvas.getContext('2d').drawImage(capVideo, 0, 0);
  _capturedImageData = capCanvas.toDataURL('image/jpeg', 0.8);
  document.getElementById('addImage').value = 'Đã chụp ảnh';
  showPreview(_capturedImageData);
  
  // Stop camera
  if (capStream) {
    capStream.getTracks().forEach(t => t.stop());
    capStream = null;
  }
  captureArea.style.display = 'none';
});

document.getElementById('stopCapture')?.addEventListener('click', () => {
  if (capStream) {
    capStream.getTracks().forEach(t => t.stop());
    capStream = null;
  }
  captureArea.style.display = 'none';
});

// Barcode scanning for add form
const codeReader = new ZXing.BrowserMultiFormatReader();
let scanStream = null;
const scanArea = document.getElementById('scanArea');
const scanVideo = document.getElementById('scanVideo');

document.getElementById('addBarcodeCameraBtn')?.addEventListener('click', async () => {
  try {
    const devices = await codeReader.listVideoInputDevices();
    const rear = devices.find(d => /back|rear|environment/i.test(d.label)) || devices[0];
    
    codeReader.decodeFromVideoDevice(rear?.deviceId, scanVideo, (result, err) => {
      if (result) {
        document.getElementById('addBarcode').value = result.getText();
        clearFieldError('addBarcode');
        
        // Stop scanning
        codeReader.reset();
        if (scanStream) {
          scanStream.getTracks().forEach(t => t.stop());
          scanStream = null;
        }
        scanArea.style.display = 'none';
      }
    });
    
    scanStream = scanVideo.srcObject;
    scanArea.style.display = 'flex';
  } catch (err) {
    alert('Không thể mở camera: ' + err.message);
  }
});

document.getElementById('stopScan')?.addEventListener('click', () => {
  codeReader.reset();
  if (scanStream) {
    scanStream.getTracks().forEach(t => t.stop());
    scanStream = null;
  }
  scanArea.style.display = 'none';
});

// Search barcode scanning
let searchScanStream = null;
const searchScanModal = document.getElementById('searchScanModal');
const searchScanVideo = document.getElementById('searchScanVideo');

document.getElementById('searchScanBtn')?.addEventListener('click', async () => {
  try {
    const devices = await codeReader.listVideoInputDevices();
    const rear = devices.find(d => /back|rear|environment/i.test(d.label)) || devices[0];
    
    codeReader.decodeFromVideoDevice(rear?.deviceId, searchScanVideo, (result, err) => {
      if (result) {
        document.getElementById('searchInput').value = result.getText();
        _productsPage = 1;
        renderProducts();
        
        // Stop scanning
        codeReader.reset();
        if (searchScanStream) {
          searchScanStream.getTracks().forEach(t => t.stop());
          searchScanStream = null;
        }
        searchScanModal.style.display = 'none';
      }
    });
    
    searchScanStream = searchScanVideo.srcObject;
    searchScanModal.style.display = 'flex';
  } catch (err) {
    alert('Không thể mở camera: ' + err.message);
  }
});

document.getElementById('stopSearchScan')?.addEventListener('click', () => {
  codeReader.reset();
  if (searchScanStream) {
    searchScanStream.getTracks().forEach(t => t.stop());
    searchScanStream = null;
  }
  searchScanModal.style.display = 'none';
});

// Mini camera stop
document.getElementById('miniStop')?.addEventListener('click', () => {
  if (_stream) {
    _stream.getTracks().forEach(t => t.stop());
    _stream = null;
  }
  const miniCap = document.getElementById('miniCap');
  if (miniCap) miniCap.style.display = 'none';
});

// Categories management
let _categoriesCache = [];

// Load categories
async function loadCategories() {
  try {
    const r = await fetch('../api/categories.php');
    const categories = await r.json();
    _categoriesCache = categories;
    renderCategories();
    populateCategoryDropdown();
  } catch (error) {
    console.error('Error loading categories:', error);
  }
}

// Render categories list
function renderCategories() {
  const list = document.getElementById('categoriesList');
  if (!list) return;

  list.innerHTML = _categoriesCache.map(cat => `
    <div class="category-item">
      <span class="category-name">${cat.type}</span>
      <div class="category-actions">
        <button class="edit-btn" onclick="editCategory(${cat.id}, '${cat.type}')">Sửa</button>
        <button class="delete-btn" onclick="deleteCategory(${cat.id})">Xóa</button>
      </div>
    </div>
  `).join('');
}

// Populate category dropdown
function populateCategoryDropdown() {
  const select = document.getElementById('addCategory');
  if (!select) return;

  select.innerHTML = '<option value="">Chọn loại...</option>';
  _categoriesCache.forEach(cat => {
    select.innerHTML += `<option value="${cat.id}">${cat.type}</option>`;
  });
}

// Add category
async function addCategory() {
  const input = document.getElementById('categoryName');
  const name = input.value.trim();
  if (!name) {
    showToast('Vui lòng nhập loại danh mục', 'error');
    return;
  }

  try {
    const r = await fetch('../api/categories.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ type: name })
    });

    const result = await r.json();
    if (result.success) {
      input.value = '';
      await loadCategories();
      showToast('Đã thêm loại danh mục', 'success');
    } else {
      showToast(result.error || 'Không thể thêm loại danh mục', 'error');
    }
  } catch (error) {
    console.error('Error adding category:', error);
    showToast('Lỗi khi thêm loại danh mục', 'error');
  }
}

// Edit category
function editCategory(id, currentType) {
  const newType = prompt('Nhập loại mới cho danh mục:', currentType);
  if (!newType || newType.trim() === currentType) return;

  updateCategory(id, newType.trim());
}

// Update category
async function updateCategory(id, type) {
  try {
    const r = await fetch('../api/categories.php', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, type })
    });

    const result = await r.json();
    if (result.success) {
      await loadCategories();
      showToast('Đã cập nhật loại danh mục', 'success');
    } else {
      showToast(result.error || 'Không thể cập nhật loại danh mục', 'error');
    }
  } catch (error) {
    console.error('Error updating category:', error);
    showToast('Lỗi khi cập nhật loại danh mục', 'error');
  }
}

// Delete category
async function deleteCategory(id) {
  if (!confirm('Bạn có chắc muốn xóa loại danh mục này?')) return;

  try {
    const r = await fetch(`../api/categories.php?id=${id}`, {
      method: 'DELETE'
    });

    const result = await r.json();
    if (result.success) {
      await loadCategories();
      showToast('Đã xóa loại danh mục', 'success');
    } else {
      showToast(result.error || 'Không thể xóa loại danh mục', 'error');
    }
  } catch (error) {
    console.error('Error deleting category:', error);
    showToast('Lỗi khi xóa loại danh mục', 'error');
  }
}

// Event listeners for categories
document.getElementById('addCategoryBtn')?.addEventListener('click', addCategory);
document.getElementById('categoryName')?.addEventListener('keypress', (e) => {
  if (e.key === 'Enter') addCategory();
});

// Initialize - check auth status
(async () => {
  const status = await checkAuth();
  if (status.authenticated) {
    document.getElementById('auth').style.display = 'none';
    document.getElementById('adminPanel').style.display = 'block';
    await loadProducts();
    await loadCategories();
  }
})();
