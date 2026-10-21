<?php
// login.php - checks email + password, then goes straight to the dashboard
declare(strict_types=1);
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

if ($email === '' || $pass === '') {
    flash('Email and password are required.', 'error');
    redirect('index.php');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($pass, $user['password'])) {
    flash('Invalid email or password.', 'error');
    redirect('index.php');
}
if ($user['role'] === 'DOCTOR' && $user['status'] !== 'APPROVED') {
    flash('Your doctor account is pending administrator approval.', 'error');
    redirect('index.php');
}

session_regenerate_id(true);
$_SESSION['user_id']   = (int)$user['id'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['role']      = $user['role'];

redirect('dashboard.php');
