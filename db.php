<?php
$dbFile = __DIR__ . '/data/users.sqlite';
if (!file_exists(dirname($dbFile))) {
    mkdir(dirname($dbFile), 0755, true);
}

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    name TEXT NOT NULL,
    phone TEXT,
    role TEXT NOT NULL CHECK(role IN ('admin','customer')),
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
)");

$columns = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
$columnNames = array_column($columns, 'name');
if (!in_array('phone', $columnNames, true)) {
    $pdo->exec('ALTER TABLE users ADD COLUMN phone TEXT');
}

$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    category TEXT NOT NULL,
    description TEXT NOT NULL,
    price INTEGER NOT NULL,
    image TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    total_amount INTEGER NOT NULL,
    payment_method TEXT NOT NULL CHECK(payment_method IN ('transfer','cod')),
    status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL,
    unit_price INTEGER NOT NULL,
    subtotal INTEGER NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)");

$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute(['admin']);
if (!$stmt->fetch()) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $insert = $pdo->prepare('INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)');
    $insert->execute(['admin', $hash, 'Administrator', 'admin']);
}

$productCount = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
if ($productCount == 0) {
    $seed = [
        ['Matcha Latte 200ml', 'Matcha', 'Hadir dengan rasa matcha premium yang lembut dan menenangkan. Ukuran 200ml cocok untuk dinikmati sendiri saat santai.', 15000, 'image/image.png'],
        ['Matcha Latte 500ml', 'Matcha', 'Pilihan terbaik untuk pecinta matcha. Ukuran 500ml pas untuk dinikmati lebih lama atau berbagi bersama teman.', 40000, 'image/image copy 5.png'],
        ['Coffee Latte 200ml', 'Coffee', 'Perpaduan kopi dan susu dengan rasa yang creamy dan menguatkan. Ukuran 200ml cocok untuk satu orang.', 13000, 'image/IMG_9334.jpg'],
        ['Coffee Latte 500ml', 'Coffee', 'Ukuran besar untuk momen yang lebih seru. Cocok dinikmati rame-rame atau untuk kamu yang ingin lebih puas.', 35000, 'image/image copy 6.png']
    ];
    $insertProduct = $pdo->prepare('INSERT INTO products (name, category, description, price, image) VALUES (?, ?, ?, ?, ?)');
    foreach ($seed as $item) {
        $insertProduct->execute($item);
    }
}

return $pdo;