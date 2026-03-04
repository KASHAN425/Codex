const apiBase = '/api';
let products = [];
let cart = JSON.parse(localStorage.getItem('cart') || '[]');
let token = localStorage.getItem('token') || '';
let currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');

function toast(selector, msg, type = 'info') {
  const color = type === 'error' ? 'danger' : type === 'success' ? 'success' : 'secondary';
  const el = $(`<div class="alert alert-${color} mt-2 mb-0">${msg}</div>`);
  $(selector).html(el);
}

function authHeaders() {
  return token ? { Authorization: `Bearer ${token}` } : {};
}

function isAdmin() {
  return currentUser && currentUser.role === 'admin';
}

function updateRoleUI() {
  if (!currentUser) {
    $('#roleBanner').removeClass('d-none alert-success alert-warning').addClass('alert-secondary').text('Not logged in.');
    $('#adminPanel').addClass('d-none');
    return;
  }

  if (isAdmin()) {
    $('#roleBanner').removeClass('d-none alert-secondary alert-success').addClass('alert-warning').text(`Admin logged in: ${currentUser.name}`);
    $('#adminPanel').removeClass('d-none');
  } else {
    $('#roleBanner').removeClass('d-none alert-secondary alert-warning').addClass('alert-success').text(`User logged in: ${currentUser.name}`);
    $('#adminPanel').addClass('d-none');
  }
}

function saveCart() {
  localStorage.setItem('cart', JSON.stringify(cart));
  updateCartUI();
}

function updateCartUI() {
  $('#cartCount').text(cart.reduce((acc, item) => acc + item.quantity, 0));
  if (!cart.length) {
    $('#cartItems').html('<p class="text-muted">Cart is empty.</p>');
    $('#cartTotal').text('0.00');
    return;
  }

  let total = 0;
  const rows = cart.map(item => {
    total += item.price * item.quantity;
    return `
      <div class="border rounded p-2 mb-2">
        <div class="fw-semibold">${item.name}</div>
        <div class="small text-muted">$${item.price.toFixed(2)} × ${item.quantity}</div>
        <div class="d-flex gap-2 mt-2">
          <button class="btn btn-sm btn-outline-secondary qty-btn" data-id="${item.id}" data-op="minus">-</button>
          <button class="btn btn-sm btn-outline-secondary qty-btn" data-id="${item.id}" data-op="plus">+</button>
          <button class="btn btn-sm btn-outline-danger remove-btn" data-id="${item.id}">Remove</button>
        </div>
      </div>
    `;
  }).join('');

  $('#cartItems').html(rows);
  $('#cartTotal').text(total.toFixed(2));
}

function renderProducts() {
  if (!products.length) {
    $('#productGrid').html('<p class="text-muted">No Loomi crochet products found.</p>');
    return;
  }

  const cards = products.map(p => `
    <div class="col-md-4 col-lg-3">
      <div class="card h-100 shadow-sm">
        <img src="${p.image_url || 'https://images.unsplash.com/photo-1617038220319-276d3cfab638?auto=format&fit=crop&w=900&q=60'}" class="card-img-top" alt="${p.name}">
        <div class="card-body d-flex flex-column">
          <h6 class="card-title">${p.name}</h6>
          <p class="text-muted small mb-1">${p.category_name || 'Uncategorized'}</p>
          <p class="small flex-grow-1">${p.description || ''}</p>
          <div class="small mb-2">Stock: ${p.stock}</div>
          <div class="d-flex justify-content-between align-items-center mt-2">
            <span class="price-tag">$${Number(p.price).toFixed(2)}</span>
            <button class="btn btn-sm btn-primary add-cart" data-id="${p.id}">Add</button>
          </div>
        </div>
      </div>
    </div>
  `).join('');

  $('#productGrid').html(cards);
}

function loadProducts() {
  const search = $('#searchInput').val().trim();
  const category = $('#categorySelect').val();
  $.getJSON(`${apiBase}/products.php`, { search, category_id: category })
    .done(res => {
      products = res.products || [];
      renderProducts();
    })
    .fail(() => {
      $('#productGrid').html('<p class="text-danger">Unable to load products.</p>');
    });
}

function loadCategories() {
  $.getJSON(`${apiBase}/categories.php`).done(res => {
    $('#categorySelect').html('<option value="">All Categories</option>');
    (res.categories || []).forEach(c => {
      $('#categorySelect').append(`<option value="${c.id}">${c.name}</option>`);
    });
  });
}

function addToCart(productId) {
  const p = products.find(x => Number(x.id) === Number(productId));
  if (!p) return;

  const existing = cart.find(x => Number(x.id) === Number(productId));
  if (existing) existing.quantity += 1;
  else cart.push({ id: Number(p.id), name: p.name, price: Number(p.price), quantity: 1 });

  saveCart();
}

function setAuthFromResponse(res, statusSelector) {
  token = res.token;
  currentUser = res.user;
  localStorage.setItem('token', token);
  localStorage.setItem('currentUser', JSON.stringify(currentUser));
  updateRoleUI();
  toast(statusSelector, `${res.message}. Role: ${res.user.role}`, 'success');
}

function registerUser() {
  $.ajax({
    url: `${apiBase}/register.php`,
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({
      name: $('#registerName').val().trim(),
      email: $('#registerEmail').val().trim(),
      password: $('#registerPassword').val()
    })
  }).done(res => setAuthFromResponse(res, '#authStatus'))
    .fail(xhr => toast('#authStatus', xhr.responseJSON?.error || 'Registration failed', 'error'));
}

function loginUser() {
  $.ajax({
    url: `${apiBase}/login.php`,
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({
      email: $('#loginEmail').val().trim(),
      password: $('#loginPassword').val(),
      login_type: 'user'
    })
  }).done(res => setAuthFromResponse(res, '#authStatus'))
    .fail(xhr => toast('#authStatus', xhr.responseJSON?.error || 'Login failed', 'error'));
}

function loginAdmin() {
  $.ajax({
    url: `${apiBase}/login.php`,
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({
      email: $('#adminLoginEmail').val().trim(),
      password: $('#adminLoginPassword').val(),
      login_type: 'admin'
    })
  }).done(res => setAuthFromResponse(res, '#adminStatus'))
    .fail(xhr => toast('#adminStatus', xhr.responseJSON?.error || 'Admin login failed', 'error'));
}

function checkout() {
  if (!token || !currentUser || currentUser.role !== 'user') {
    alert('Please login as user first.');
    return;
  }
  if (!cart.length) {
    alert('Your cart is empty.');
    return;
  }

  $.ajax({
    url: `${apiBase}/create_order.php`,
    method: 'POST',
    contentType: 'application/json',
    headers: authHeaders(),
    data: JSON.stringify({
      shipping_address: $('#shippingAddress').val().trim(),
      phone: $('#phone').val().trim(),
      items: cart.map(x => ({ product_id: x.id, quantity: x.quantity }))
    })
  }).done(res => {
    alert(`Order #${res.order_id} placed successfully.`);
    cart = [];
    saveCart();
  }).fail(xhr => {
    alert(xhr.responseJSON?.error || 'Checkout failed');
  });
}

function adminPayload() {
  return {
    id: Number($('#adminProductId').val() || 0),
    name: $('#adminName').val().trim(),
    slug: $('#adminSlug').val().trim(),
    description: $('#adminDescription').val().trim(),
    price: Number($('#adminPrice').val() || 0),
    stock: Number($('#adminStock').val() || 0),
    category_id: Number($('#adminCategoryId').val() || 0),
    image_url: $('#adminImageUrl').val().trim(),
    featured: 0,
    is_active: 1
  };
}

function guardAdmin() {
  if (!isAdmin()) {
    alert('Admin login required.');
    return false;
  }
  return true;
}

function createProduct() {
  if (!guardAdmin()) return;
  $.ajax({
    url: `${apiBase}/products.php`,
    method: 'POST',
    contentType: 'application/json',
    headers: authHeaders(),
    data: JSON.stringify(adminPayload())
  }).done(() => {
    alert('Product added');
    loadProducts();
  }).fail(xhr => alert(xhr.responseJSON?.error || 'Add failed'));
}

function updateProduct() {
  if (!guardAdmin()) return;
  $.ajax({
    url: `${apiBase}/products.php`,
    method: 'PUT',
    contentType: 'application/json',
    headers: authHeaders(),
    data: JSON.stringify(adminPayload())
  }).done(() => {
    alert('Product updated');
    loadProducts();
  }).fail(xhr => alert(xhr.responseJSON?.error || 'Update failed'));
}

function deleteProduct() {
  if (!guardAdmin()) return;
  $.ajax({
    url: `${apiBase}/products.php`,
    method: 'DELETE',
    contentType: 'application/json',
    headers: authHeaders(),
    data: JSON.stringify({ id: Number($('#adminProductId').val() || 0) })
  }).done(() => {
    alert('Product deleted');
    loadProducts();
  }).fail(xhr => alert(xhr.responseJSON?.error || 'Delete failed'));
}

$(function () {
  loadCategories();
  loadProducts();
  updateCartUI();
  updateRoleUI();

  $('#filterBtn').on('click', loadProducts);
  $('#searchInput').on('keypress', e => { if (e.which === 13) loadProducts(); });
  $(document).on('click', '.add-cart', function () { addToCart($(this).data('id')); });
  $(document).on('click', '.qty-btn', function () {
    const id = Number($(this).data('id'));
    const op = $(this).data('op');
    const item = cart.find(x => x.id === id);
    if (!item) return;
    item.quantity = op === 'plus' ? item.quantity + 1 : Math.max(1, item.quantity - 1);
    saveCart();
  });
  $(document).on('click', '.remove-btn', function () {
    const id = Number($(this).data('id'));
    cart = cart.filter(x => x.id !== id);
    saveCart();
  });

  $('#registerBtn').on('click', registerUser);
  $('#loginBtn').on('click', loginUser);
  $('#adminLoginBtn').on('click', loginAdmin);
  $('#checkoutBtn').on('click', checkout);
  $('#addProductBtn').on('click', createProduct);
  $('#updateProductBtn').on('click', updateProduct);
  $('#deleteProductBtn').on('click', deleteProduct);
});
