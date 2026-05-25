<?php
session_start();
$pdo = require __DIR__ . '/db.php';

function setFlash($message) {
    $_SESSION['flash_message'] = $message;
}

$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'customer') {
    setFlash('Silakan login sebagai customer untuk melakukan checkout.');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$cartJson = $_POST['cart_json'] ?? '[]';
$paymentMethod = $_POST['payment_method'] ?? '';
$cart = json_decode($cartJson, true);
$customerPhone = trim($_POST['customer_phone'] ?? ($user['phone'] ?? ''));

if (!$customerPhone) {
    setFlash('Silakan masukkan nomor HP sebelum checkout.');
    header('Location: index.php');
    exit;
}

if (!is_array($cart) || empty($cart)) {
    setFlash('Keranjang kosong. Tambahkan produk terlebih dahulu.');
    header('Location: index.php');
    exit;
}

if (!in_array($paymentMethod, ['transfer', 'cod'], true)) {
    setFlash('Pilih metode pembayaran yang valid.');
    header('Location: index.php');
    exit;
}

$totalAmount = 0;
$items = [];
$fetchProduct = $pdo->prepare('SELECT * FROM products WHERE id = ?');
foreach ($cart as $entry) {
    $productId = intval($entry['id'] ?? 0);
    $quantity = intval($entry['quantity'] ?? 0);
    if ($productId <= 0 || $quantity <= 0) {
        continue;
    }
    $fetchProduct->execute([$productId]);
    $product = $fetchProduct->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        continue;
    }
    $subtotal = $product['price'] * $quantity;
    $items[] = [
        'product_id' => $productId,
        'quantity' => $quantity,
        'unit_price' => $product['price'],
        'subtotal' => $subtotal
    ];
    $totalAmount += $subtotal;
}

if (empty($items)) {
    setFlash('Tidak ada produk valid di keranjang.');
    header('Location: index.php');
    exit;
}

if (empty($user['phone']) || $user['phone'] !== $customerPhone) {
    $updatePhone = $pdo->prepare('UPDATE users SET phone = ? WHERE id = ?');
    $updatePhone->execute([$customerPhone, $user['id']]);
    $_SESSION['user']['phone'] = $customerPhone;
}

$pdo->beginTransaction();
$orderInsert = $pdo->prepare('INSERT INTO orders (user_id, total_amount, payment_method, status) VALUES (?, ?, ?, ?)');
$orderInsert->execute([$user['id'], $totalAmount, $paymentMethod, 'pending']);
$orderId = $pdo->lastInsertId();
$itemInsert = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)');
foreach ($items as $item) {
    $itemInsert->execute([$orderId, $item['product_id'], $item['quantity'], $item['unit_price'], $item['subtotal']]);
}
$pdo->commit();

setFlash('Checkout berhasil. Silakan lanjutkan dengan metode pembayaran yang dipilih.');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Order Diproses — jade & bean</title>
</head>
<body>
  <p>Pesanan Anda sudah diproses. Anda akan diarahkan kembali ke toko.</p>
  <script>
    localStorage.removeItem('jadeBeanCart');
    window.location.replace('index.php');
  </script>
</body>
</html>
