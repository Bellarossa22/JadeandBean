<?php
session_start();
$pdo = require __DIR__ . '/db.php';

function setFlash($message) {
    $_SESSION['flash_message'] = $message;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $type = $_POST['login_type'] ?? 'customer';
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$username || !$password) {
            setFlash('Lengkapi username dan password terlebih dahulu.');
            header('Location: index.php');
            exit;
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND role = ?');
        $stmt->execute([$username, $type]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'],
                'phone' => $user['phone'] ?? '',
                'role' => $user['role']
            ];
            setFlash('Login berhasil. Selamat datang, ' . $user['name'] . '!');
            header('Location: index.php');
            exit;
        }

        setFlash('Username atau password tidak cocok.');
        header('Location: index.php');
        exit;
    }

    if ($action === 'register') {
        $name = trim($_POST['register_name'] ?? '');
        $username = trim($_POST['register_username'] ?? '');
        $password = trim($_POST['register_password'] ?? '');

        $phone = trim($_POST['register_phone'] ?? '');
        if (!$name || !$username || !$password || !$phone) {
            setFlash('Lengkapi semua kolom registrasi.');
            header('Location: index.php');
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            setFlash('Username sudah terdaftar. Silakan gunakan username lain.');
            header('Location: index.php');
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare('INSERT INTO users (username, password, name, phone, role, created_at) VALUES (?, ?, ?, ?, ?, datetime("now"))');
        $insert->execute([$username, $hash, $name, $phone, 'customer']);

        setFlash('Registrasi berhasil. Silakan login.');
        header('Location: index.php');
        exit;
    }
}

header('Location: index.php');
exit;
