<?php
session_start();
$pdo = require __DIR__ . '/db.php';
$user = $_SESSION['user'] ?? null;
$flash = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

$products = $pdo->query('SELECT * FROM products ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

function formatPrice($value) {
    return 'Rp' . number_format($value, 0, ',', '.');
}

$shortDescriptions = [
    'Matcha Latte 200ml' => 'Hijau yang menenangkan',
    'Matcha Latte 500ml' => 'Hijau yang menenangkan',
    'Coffee Latte 200ml' => 'Cokelat yang menguatkan',
    'Coffee Latte 500ml' => 'Cokelat yang menguatkan',
];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>jade & bean — Kopi & Minuman Estetik</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <h1 class="brand">jade & bean</h1>
      <nav class="nav">
        <a href="#products">Produk</a>
        <a href="#about">Tentang</a>
        <a href="#contact">Kontak</a>
      </nav>
      <div class="header-actions">
        <?php if ($user && $user['role'] === 'customer'): ?>
          <button id="cartToggle" class="cart-button" type="button">Keranjang (<span id="cartCount">0</span>)</button>
        <?php endif; ?>
        <?php if ($user && $user['role'] === 'admin'): ?>
          <a class="auth-toggle" href="admin.php">Admin Dashboard</a>
        <?php endif; ?>
        <?php if ($user): ?>
          <span class="user-label">Hi, <?php echo htmlspecialchars($user['name']); ?></span>
          <a class="logout-link" href="logout.php">Keluar</a>
        <?php else: ?>
          <button class="auth-toggle" aria-label="Masuk atau registrasi">Masuk</button>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="hero-content container">
        <h2>Premium Coffee dalam Botol Elegan</h2>
        <p>Kami menyajikan kopi premium dengan rasa kaya, aroma hangat, dan kemasan yang mencerminkan gaya Anda.</p>
        <a class="btn" href="#products">Lihat Koleksi Premium</a>
      </div>
    </section>

    <section class="gallery container">
      <h3 class="section-title">Galeri Premium</h3>
      <p class="subtitle">Tampilkan beberapa sudut kopi premium kami dalam kemasan eksklusif.</p>
      <div class="photo-grid">
        <img src="image/image copy 4.png" alt="Premium Coffee Bottle">
        <img src="image/image copy 2.png" alt="Premium Green Latte">
        <img src="image/image copy 3.png" alt="Premium Coffee Latte">
      </div>
    </section>

    <section id="products" class="container products">
      <div class="section-header">
        <h3 class="section-title">Minuman Kami</h3>
        <p class="subtitle">Siap setiap hari • Ready stock</p>
      </div>
      <div class="grid">
        <?php foreach ($products as $product): ?>
          <?php $categoryClass = strtolower($product['category']) === 'matcha' ? 'matcha-card' : 'coffee-card'; ?>
          <article class="card <?php echo $categoryClass; ?>" data-id="<?php echo $product['id']; ?>">
            <div class="card-badge"><?php echo htmlspecialchars($product['category']); ?></div>
            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <div class="card-body">
              <h4><?php echo htmlspecialchars($product['name']); ?></h4>
              <p class="desc"><?php echo htmlspecialchars($shortDescriptions[$product['name']] ?? $product['description']); ?></p>
              <p class="price"><?php echo formatPrice($product['price']); ?></p>
              <div class="card-actions">
                <button class="detail-btn">Detail Produk</button>
                <button class="cart-add-btn" type="button" data-id="<?php echo $product['id']; ?>">Tambah ke Keranjang</button>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section id="about" class="container about">
      <h3 class="section-title">Tentang jade & bean</h3>
      <p class="lead">Di jade & bean, setiap botol adalah perayaan rasa. Kami meracik kopi premium dalam kemasan minimalis agar setiap tegukan terasa istimewa, tanpa harus menunggu di kedai kopi.</p>
      <div class="about-grid">
        <article class="feature-card">
          <h4>Sourcing Terbaik</h4>
          <p>Biji kopi pilihan dari petani berpengalaman, diseleksi untuk menghasilkan aroma lembut dan kedalaman rasa yang konsisten.</p>
        </article>
        <article class="feature-card">
          <h4>Botol yang Menawan</h4>
          <p>Kemasan elegan kami dirancang supaya cocok dinikmati sendiri atau dikirim sebagai hadiah premium.</p>
        </article>
        <article class="feature-card">
          <h4>Pengalaman Mudah</h4>
          <p>Minuman siap saji tanpa kompromi rasa. Cukup buka, nikmati, dan rasakan suasana kafe di rumah.</p>
        </article>
      </div>
    </section>

    <section id="contact" class="container contact">
      <h3 class="section-title">Kontak & Pesan</h3>
      <p class="lead">Jika tertarik, Anda dapat langsung menghubungi WhatsApp. Order online akan diproses setelah registrasi atau login customer.</p>
      <div class="contact-grid">
        <a class="contact-card contact-whatsapp" href="https://wa.me/6288214630079" target="_blank" rel="noopener">
          <span>WhatsApp</span>
          <strong>088 2146 30079</strong>
          <small>Chat cepat untuk order dan diskusi produk</small>
        </a>
        <a class="contact-card contact-instagram" href="https://instagram.com/jade_andbean" target="_blank">
          <span>Instagram</span>
          <strong>@jade_andbean</strong>
          <small>Temukan inspirasi kopi, testimoni, dan promo</small>
        </a>
      </div>
    </section>
  </main>

  <div id="toast" class="toast" aria-live="polite"></div>

  <div id="productModal" class="modal" aria-hidden="true">
    <div class="modal-panel" role="dialog" aria-modal="true" aria-label="Detail produk">
      <button class="modal-close" aria-label="Tutup detail produk">×</button>
      <span class="modal-tag">Detail Produk</span>
      <div class="modal-product">
        <img id="modalImage" class="modal-image" src="" alt="Produk">
        <div class="modal-product-info">
          <h3 id="modalTitle">-</h3>
          <p id="modalDescription" class="modal-desc"></p>
          <p id="modalPrice" class="modal-price"></p>
          <button id="modalAddCart" class="modal-action" type="button">Tambah ke Keranjang</button>
        </div>
      </div>
      <div class="modal-note">Untuk memesan, silakan login/registrasi dahulu atau hubungi WhatsApp.</div>
      <div class="modal-icon-row">
        <a class="modal-icon-btn whatsapp-icon" href="https://wa.me/6288214630079" target="_blank" rel="noopener">
          <span>📲</span>
          <small>Order WhatsApp</small>
        </a>
        <button class="modal-icon-btn login-detail-btn" type="button">
          <span>🔐</span>
          <small>Login / Register</small>
        </button>
      </div>
      <button class="modal-close-btn">Tutup</button>
    </div>
  </div>

  <div id="loginModal" class="modal" aria-hidden="true">
    <div class="modal-panel" role="dialog" aria-modal="true" aria-label="Login">
      <button class="modal-close" aria-label="Tutup login">×</button>
      <span class="modal-tag">Masuk</span>
      <h3>Login</h3>
      <form class="modal-form" method="post" action="auth.php">
        <input type="hidden" name="action" value="login">
        <label for="loginType">Masuk sebagai</label>
        <select id="loginType" name="login_type">
          <option value="customer">Customer</option>
          <option value="admin">Admin</option>
        </select>
        <label for="loginUsername">Username</label>
        <input id="loginUsername" name="username" type="text" placeholder="Masukkan username" required />
        <label for="loginPassword">Password</label>
        <input id="loginPassword" name="password" type="password" placeholder="Masukkan password" required />
        <button class="modal-action" type="submit">Masuk</button>
      </form>
      <button class="modal-action register-open-btn" type="button">Mendaftar sebagai Customer</button>
    </div>
  </div>

  <div id="registerModal" class="modal" aria-hidden="true">
    <div class="modal-panel" role="dialog" aria-modal="true" aria-label="Registrasi">
      <button class="modal-close" aria-label="Tutup registrasi">×</button>
      <span class="modal-tag">Registrasi</span>
      <h3>Registrasi Customer</h3>
      <form class="modal-form" method="post" action="auth.php">
        <input type="hidden" name="action" value="register">
        <label for="registerName">Nama</label>
        <input id="registerName" name="register_name" type="text" placeholder="Nama lengkap" required />
        <label for="registerUsername">Username</label>
        <input id="registerUsername" name="register_username" type="text" placeholder="Username untuk login" required />
        <label for="registerPhone">Nomor HP</label>
        <input id="registerPhone" name="register_phone" type="tel" placeholder="08xxxxxxxxxx" required />
        <label for="registerPassword">Password</label>
        <input id="registerPassword" name="register_password" type="password" placeholder="Password baru" required />
        <button class="modal-action" type="submit">Daftar</button>
      </form>
    </div>
  </div>

  <div id="cartModal" class="modal" aria-hidden="true">
    <div class="modal-panel" role="dialog" aria-modal="true" aria-label="Keranjang">
      <button class="modal-close" aria-label="Tutup keranjang">×</button>
      <span class="modal-tag">Keranjang</span>
      <h3>Keranjang Saya</h3>
      <div id="cartItems" class="cart-items"></div>
      <div class="cart-summary">
        <p>Total: <strong id="cartTotal">Rp0</strong></p>
      </div>
      <form id="checkoutForm" class="modal-form" method="post" action="order.php">
        <input type="hidden" name="cart_json" id="cartJson" />
        <label for="checkoutPhone">Nomor HP untuk order</label>
        <input id="checkoutPhone" name="customer_phone" type="tel" placeholder="08xxxxxxxxxx" required value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" />
        <label for="paymentMethod">Pilih metode pembayaran</label>
        <select id="paymentMethod" name="payment_method" required>
          <option value="transfer">Transfer Bank</option>
          <option value="cod">Bayar di Tempat</option>
        </select>
        <button class="modal-action" type="submit">Checkout</button>
      </form>
    </div>
  </div>

  <script>
    window.flashMessage = <?php echo json_encode($flash); ?>;
    window.appData = {
      userRole: <?php echo json_encode($user['role'] ?? null); ?>,
      products: <?php echo json_encode($products, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>
    };
  </script>
  <script src="script.js"></script>
</body>
</html>
