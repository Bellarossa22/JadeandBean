<?php
session_start();
$pdo = require __DIR__ . '/db.php';
$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
$flash = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

function setFlash($message) {
    $_SESSION['flash_message'] = $message;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_product' || $action === 'edit_product') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = intval($_POST['price'] ?? 0);
        $image = trim($_POST['image'] ?? '');

        if (!$name || !$category || !$description || !$price || !$image) {
            setFlash('Lengkapi semua field produk sebelum menyimpan.');
            header('Location: admin.php');
            exit;
        }

        if ($action === 'add_product') {
            $insert = $pdo->prepare('INSERT INTO products (name, category, description, price, image) VALUES (?, ?, ?, ?, ?)');
            $insert->execute([$name, $category, $description, $price, $image]);
            setFlash('Produk baru berhasil ditambahkan.');
        } else {
            $id = intval($_POST['product_id'] ?? 0);
            $update = $pdo->prepare('UPDATE products SET name = ?, category = ?, description = ?, price = ?, image = ? WHERE id = ?');
            $update->execute([$name, $category, $description, $price, $image, $id]);
            setFlash('Produk berhasil diperbarui.');
        }

        header('Location: admin.php');
        exit;
    }

    if ($action === 'delete_product') {
        $id = intval($_POST['product_id'] ?? 0);
        $delete = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $delete->execute([$id]);
        setFlash('Produk berhasil dihapus.');
        header('Location: admin.php');
        exit;
    }
}

$editProduct = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $fetchEdit = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $fetchEdit->execute([$editId]);
    $editProduct = $fetchEdit->fetch(PDO::FETCH_ASSOC);
}

$products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$imageFiles = glob(__DIR__ . '/image/*.{png,jpg,jpeg,gif}', GLOB_BRACE) ?: [];
$imageOptions = array_map(function ($path) {
    return 'image/' . basename($path);
}, $imageFiles);
$orders = $pdo->query('SELECT o.*, u.name AS customer_name, u.phone AS customer_phone FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$orderItemsStmt = $pdo->query('SELECT oi.*, p.name AS product_name FROM order_items oi JOIN products p ON p.id = oi.product_id ORDER BY oi.order_id');
$orderItems = [];
while ($row = $orderItemsStmt->fetch(PDO::FETCH_ASSOC)) {
    $orderItems[$row['order_id']][] = $row;
}
$totalRevenue = 0;
foreach ($orders as $order) {
    $totalRevenue += $order['total_amount'];
}
$totalOrders = count($orders);
$todayDate = date('Y-m-d');
$todayOrders = array_filter($orders, function ($order) use ($todayDate) {
    return substr($order['created_at'], 0, 10) === $todayDate;
});
$todayOrdersCount = count($todayOrders);
$latestTodayOrder = $todayOrdersCount ? reset($todayOrders) : null;

function normalizePhoneNumber($phone) {
    $normalized = preg_replace('/[^0-9]/', '', $phone);
    if (strpos($normalized, '0') === 0) {
        $normalized = '62' . substr($normalized, 1);
    }
    return $normalized;
}

function createWhatsAppUrl($phone, $message = '') {
    $normalized = normalizePhoneNumber($phone);
    $url = 'https://wa.me/' . $normalized;
    if (trim($message) !== '') {
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}

function formatCurrency($value) {
    return 'Rp' . number_format($value, 0, ',', '.');
}

function getWhatsAppMessage($order) {
    $name = trim($order['customer_name'] ?? '');
    $parts = preg_split('/\s+/', $name);
    $firstName = $parts[0] ?? 'Customer';
    $firstName = mb_strtolower($firstName, 'UTF-8');
    $total = formatCurrency($order['total_amount']);
    $address = 'Pendongkelan kapuk muara';

    if ($order['payment_method'] === 'transfer') {
        return "Halo {$firstName} 👋\n\nPesanan kamu sedang diproses.\n\n🛒 Pesanan sesuai keranjang\n💰 Total Pembayaran: {$total}\n\nSilakan transfer ke rekening berikut:\n\n🏦 BCA\nNo Rekening: 8040226835\na/n Bella Rossa Amelia\n\nSetelah transfer, kirim bukti pembayaran ya 😊\n📍 Alamat:\n{$address}";
    }

    return "Halo {$firstName} 👋\n\nPesanan kamu sedang diproses.\n\n🛒 Pesanan sesuai keranjang\n💰 Total Pembayaran: {$total}\n\nMetode pembayaran:\n💵 Bayar di Tempat\n\n📍 Alamat:\n{$address}\n\nSilakan datang sesuai lokasi ya 😊";
}

$pendingOrders = array_filter($orders, function ($order) {
    $status = strtolower($order['status']);
    return $status === 'pending';
});
$pendingCount = count($pendingOrders);
$hasPendingOrders = $pendingCount > 0;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin Dashboard — jade & bean</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="site-header admin-header">
    <div class="container header-inner">
      <div>
        <h1 class="brand">jade & bean Admin</h1>
        <p class="admin-header-subtitle">Dashboard khusus untuk tambah/edit produk, pantau penjualan, dan hubungi customer.</p>
      </div>
      <div class="header-actions">
        <a class="auth-toggle" href="index.php">Kembali ke Toko</a>
        <a class="logout-link" href="logout.php">Keluar</a>
      </div>
    </div>
  </header>

  <main class="container admin-panel">
    <?php if ($flash): ?>
      <div class="toast show"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <section class="admin-intro">
      <div>
        <span class="admin-badge">Admin Dashboard</span>
        <h2>Kelola produk, penjualan, dan kontak pelanggan</h2>
        <p>Daftar produk, laporan pesanan, dan akses WhatsApp untuk follow-up customer tersedia di halaman ini.</p>
      </div>
      <div class="admin-actions">
        <a class="admin-action-pill" href="#product-form">Tambah Produk</a>
        <a class="admin-action-pill" href="#product-list">Daftar Produk</a>
        <a class="admin-action-pill" href="#order-report">Laporan Penjualan</a>
      </div>
    </section>

    <section class="admin-overview">
      <div class="admin-summary">
        <div class="summary-card">
          <span>Total Produk</span>
          <strong><?php echo count($products); ?></strong>
        </div>
        <div class="summary-card">
          <span>Pesanan</span>
          <strong><?php echo $totalOrders; ?></strong>
        </div>
        <div class="summary-card">
          <span>Total Penjualan</span>
          <strong>Rp<?php echo number_format($totalRevenue, 0, ',', '.'); ?></strong>
        </div>
      </div>
      <aside class="admin-sidebar">
        <div class="sidebar-card">
          <span class="sidebar-label">Pesanan Baru</span>
          <strong class="sidebar-value"><?php echo $pendingCount; ?></strong>
          <p class="sidebar-text"><?php echo $pendingCount > 0 ? 'Pesanan baru menunggu konfirmasi' : 'Tidak ada pesanan pending saat ini'; ?></p>
          <a class="sidebar-link" href="#order-report">Lihat Laporan Penjualan</a>
        </div>
        <div class="sidebar-card">
          <span class="sidebar-label">Pesanan Terbaru Hari Ini</span>
          <strong class="sidebar-value"><?php echo $todayOrdersCount; ?></strong>
          <p class="sidebar-text"><?php echo $todayOrdersCount > 0 ? 'Pesanan hari ini dari ' . htmlspecialchars($latestTodayOrder['customer_name']) : 'Tidak ada pesanan hari ini'; ?></p>
          <?php if ($todayOrdersCount > 0): ?>
            <p class="sidebar-text">Order #<?php echo $latestTodayOrder['id']; ?> • <?php echo htmlspecialchars($latestTodayOrder['payment_method'] === 'transfer' ? 'Transfer' : 'Bayar di Tempat'); ?></p>
          <?php endif; ?>
          <a class="sidebar-link" href="#order-report">Lihat Detail Hari Ini</a>
        </div>
      </aside>
    </section>
    <?php if ($hasPendingOrders): ?>
      <div class="admin-note">
        <p>Ada <strong><?php echo $pendingCount; ?> pesanan baru</strong> yang sedang <strong>Pending</strong>. Cek laporan penjualan dan gunakan tombol WhatsApp untuk menghubungi customer.</p>
      </div>
    <?php endif; ?>

    <section class="admin-grid">
      <div class="admin-box">
        <h3 id="product-form">Tambah Produk Baru</h3>
        <form method="post" action="admin.php" class="modal-form">
          <input type="hidden" name="action" value="<?php echo $editProduct ? 'edit_product' : 'add_product'; ?>">
          <?php if ($editProduct): ?>
            <input type="hidden" name="product_id" value="<?php echo $editProduct['id']; ?>">
          <?php endif; ?>
          <label for="name">Nama Produk</label>
          <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>" required>
          <label for="category">Kategori</label>
          <input id="category" name="category" type="text" value="<?php echo htmlspecialchars($editProduct['category'] ?? ''); ?>" required>
          <label for="description">Deskripsi</label>
          <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea>
          <label for="price">Harga (angka saja)</label>
          <input id="price" name="price" type="number" min="0" value="<?php echo htmlspecialchars($editProduct['price'] ?? ''); ?>" required>
          <label for="imageSelect">Pilih gambar dari galeri</label>
          <select id="imageSelect" class="modal-form" onchange="document.getElementById('image').value = this.value;">
            <option value="">Pilih gambar...</option>
            <?php foreach ($imageOptions as $option): ?>
              <option value="<?php echo htmlspecialchars($option); ?>" <?php echo (isset($editProduct['image']) && $editProduct['image'] === $option) ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
            <?php endforeach; ?>
          </select>
          <label for="image">Path Gambar</label>
          <input id="image" name="image" type="text" placeholder="Contoh: image/image.png" value="<?php echo htmlspecialchars($editProduct['image'] ?? ''); ?>" required>
          <button class="modal-action" type="submit"><?php echo $editProduct ? 'Update Produk' : 'Simpan Produk'; ?></button>
          <?php if ($editProduct): ?>
            <a class="auth-toggle" href="admin.php">Batal Edit</a>
          <?php endif; ?>
        </form>
      </div>

      <div class="admin-box">
        <h3 id="product-list">Daftar Produk</h3>
        <table class="table">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Deskripsi</th>
              <th>Harga</th>
              <th>Kategori</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $product): ?>
              <tr>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td><?php echo htmlspecialchars($product['description']); ?></td>
                <td>Rp<?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                <td><?php echo htmlspecialchars($product['category']); ?></td>
                <td>
                  <a class="auth-toggle" href="admin.php?edit=<?php echo $product['id']; ?>">Edit</a>
                  <form method="post" action="admin.php" style="display:inline-flex;gap:0.5rem;">
                    <input type="hidden" name="action" value="delete_product">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <button class="delete" type="submit">Hapus</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <div class="admin-box">
      <h3 id="order-report">Laporan Penjualan</h3>
      <table class="table">
        <thead>
          <tr>
            <th>#Order</th>
            <th>Customer</th>
            <th>No HP</th>
            <th>Total</th>
            <th>Pembayaran</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th>Hubungi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td><?php echo $order['id']; ?></td>
              <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
              <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
              <td>Rp<?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
              <td><?php echo $order['payment_method'] === 'transfer' ? 'Transfer' : 'Bayar di Tempat'; ?></td>
              <td><?php echo htmlspecialchars($order['status']); ?></td>
              <td><?php echo htmlspecialchars($order['created_at']); ?></td>
              <td>
                <?php if ($order['customer_phone']): ?>
                  <a class="whatsapp-link" href="<?php echo createWhatsAppUrl($order['customer_phone'], getWhatsAppMessage($order)); ?>" target="_blank" rel="noopener">WhatsApp</a>
                <?php else: ?>
                  <span class="muted">-</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>
