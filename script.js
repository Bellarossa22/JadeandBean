const productModal = document.getElementById('productModal');
const loginModal = document.getElementById('loginModal');
const registerModal = document.getElementById('registerModal');
const cartModal = document.getElementById('cartModal');
const modalTitle = document.getElementById('modalTitle');
const modalDescription = document.getElementById('modalDescription');
const modalPrice = document.getElementById('modalPrice');
const modalImage = document.getElementById('modalImage');
const modalAddCart = document.getElementById('modalAddCart');
const cartItemsContainer = document.getElementById('cartItems');
const cartTotalElement = document.getElementById('cartTotal');
const cartJson = document.getElementById('cartJson');
const paymentMethod = document.getElementById('paymentMethod');
const cartCountElement = document.getElementById('cartCount');
const loginOpenButtons = document.querySelectorAll('.login-detail-btn, .auth-toggle:not([href])');
const registerOpenButtons = document.querySelectorAll('.register-open-btn');
const closeButtons = document.querySelectorAll('.modal-close, .modal-close-btn');
const cartToggle = document.getElementById('cartToggle');

const products = window.appData?.products || [];
const userRole = window.appData?.userRole || null;
const cartKey = 'jadeBeanCart';
let cart = JSON.parse(localStorage.getItem(cartKey) || '[]');

const productDetails = {
  'Matcha Latte 200ml': 'Hadir dengan rasa matcha premium yang lembut dan menenangkan 🌿 Ukuran 200ml cocok untuk dinikmati sendiri saat santai, belajar, atau menemani kerja agar lebih fokus. Nikmat diminum dingin dengan es batu maupun langsung dari kulkas.',
  'Matcha Latte 500ml': 'Pilihan terbaik untuk pecinta matcha 💚 Ukuran 500ml pas untuk dinikmati lebih lama atau berbagi bersama teman dan keluarga. Tetap creamy dan nikmat meski ditambah es batu, cocok untuk menemani aktivitas seharian.',
  'Coffee Latte 200ml': 'Perpaduan kopi dan susu dengan rasa yang creamy dan menguatkan ☕ Ukuran 200ml cocok untuk satu orang, praktis sebagai teman kerja, belajar, atau perjalanan. Nikmat diminum dingin tanpa mengurangi cita rasa kopinya.',
  'Coffee Latte 500ml': 'Ukuran besar untuk momen yang lebih seru 🤎 Cocok dinikmati rame-rame, sharing bersama teman, atau untuk kamu yang ingin lebih puas menikmati kopi favorit. Tetap lezat dengan ataupun tanpa es batu, memberikan sensasi kopi yang smooth dan menyegarkan.',
};

function formatPrice(value) {
  return 'Rp' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function showToast(message) {
  if (!message) return;
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.classList.add('show');
  clearTimeout(toast._timeout);
  toast._timeout = setTimeout(() => toast.classList.remove('show'), 2600);
}

function saveCart() {
  localStorage.setItem(cartKey, JSON.stringify(cart));
  renderCart();
}

function getProductById(id) {
  return products.find(item => item.id === Number(id));
}

function updateCartCount() {
  if (!cartCountElement) return;
  const count = cart.reduce((sum, item) => sum + item.quantity, 0);
  cartCountElement.textContent = count;
}

function renderCart() {
  if (!cartItemsContainer) return;

  if (cart.length === 0) {
    cartItemsContainer.innerHTML = '<p>Keranjang kosong. Tambahkan produk untuk checkout.</p>';
    cartTotalElement.textContent = 'Rp0';
    return;
  }

  const rows = cart.map(item => {
    const product = getProductById(item.id);
    if (!product) return '';
    return `
      <div class="cart-item" data-id="${item.id}">
        <div>
          <strong>${product.name}</strong>
          <p>${product.category} • ${formatPrice(product.price)} x ${item.quantity}</p>
        </div>
        <div class="cart-item-actions">
          <button class="cart-remove-btn" type="button" data-id="${item.id}">Hapus</button>
        </div>
      </div>
    `;
  }).join('');

  cartItemsContainer.innerHTML = rows;
  const total = cart.reduce((sum, item) => {
    const product = getProductById(item.id);
    return product ? sum + product.price * item.quantity : sum;
  }, 0);
  cartTotalElement.textContent = formatPrice(total);
}

function openModal(modal) {
  modal.classList.add('show');
  modal.setAttribute('aria-hidden', 'false');
}

function closeModal(modal) {
  modal.classList.remove('show');
  modal.setAttribute('aria-hidden', 'true');
}

function requireCustomer(action) {
  if (userRole !== 'customer') {
    showToast('Silakan login sebagai customer untuk menggunakan keranjang.');
    openModal(loginModal);
    return false;
  }
  return true;
}

function addToCart(productId) {
  if (!requireCustomer()) return;
  const product = getProductById(productId);
  if (!product) return;
  const item = cart.find(entry => entry.id === Number(productId));
  if (item) {
    item.quantity += 1;
  } else {
    cart.push({ id: Number(productId), quantity: 1 });
  }
  saveCart();
  showToast(`${product.name} berhasil ditambahkan ke keranjang.`);
}

function removeFromCart(productId) {
  cart = cart.filter(entry => entry.id !== Number(productId));
  saveCart();
}

document.addEventListener('click', (event) => {
  if (event.target.matches('.detail-btn')) {
    const card = event.target.closest('.card');
    const id = card.dataset.id;
    const product = getProductById(id);
    if (!product) return;
    modalTitle.textContent = product.name;
    modalDescription.textContent = productDetails[product.name] || product.description;
    modalPrice.textContent = formatPrice(product.price);
    modalImage.src = product.image;
    modalImage.alt = product.name;
    modalAddCart.dataset.id = product.id;
    openModal(productModal);
  }

  if (event.target.matches('.modal-action') && event.target.id === 'modalAddCart') {
    const id = event.target.dataset.id;
    addToCart(id);
  }

  if (event.target.matches('.cart-remove-btn')) {
    const id = event.target.dataset.id;
    removeFromCart(id);
  }

  if (event.target.matches('.cart-button') || event.target.matches('#cartToggle')) {
    if (!requireCustomer()) return;
    renderCart();
    openModal(cartModal);
  }

  if (event.target.matches('.cart-add-btn')) {
    const id = event.target.dataset.id;
    addToCart(id);
  }

  if (event.target.matches('.modal-close') || event.target.matches('.modal-close-btn')) {
    const parentModal = event.target.closest('.modal');
    if (parentModal) closeModal(parentModal);
  }

  if (event.target.matches('.auth-toggle') || event.target.matches('.login-detail-btn')) {
    closeModal(productModal);
    openModal(loginModal);
  }

  if (event.target.matches('.register-open-btn')) {
    closeModal(loginModal);
    openModal(registerModal);
  }
});

document.addEventListener('submit', (event) => {
  if (event.target.id === 'checkoutForm') {
    if (cart.length === 0) {
      event.preventDefault();
      showToast('Keranjang kosong. Tambahkan produk terlebih dahulu.');
      return;
    }
    cartJson.value = JSON.stringify(cart);
  }
});

document.querySelectorAll('.modal').forEach((modal) => {
  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeModal(modal);
    }
  });
  modal.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeModal(modal);
  });
});

if (window.flashMessage) {
  showToast(window.flashMessage);
}

updateCartCount();
renderCart();
