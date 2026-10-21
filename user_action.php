<?php
// user_action.php - ADMIN ONLY: create / approve / reject / delete a user
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/access.php';
require_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('users.php');
csrf_check();

$action = $_POST['action'] ?? '';
$back   = in_array($_POST['back'] ?? '', ['dashboard.php', 'users.php'], true) ? $_POST['back'] : 'users.php';
$me     = (int)$_SESSION['user_id'];

if ($action === 'create') {
    $name  = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $role  = strtoupper($_POST['role'] ?? '');
    $spec  = trim($_POST['specialization'] ?? '');

    if ($name === '' || $email === '' || $phone === '' || $pass === '') {
        flash('All fields are required.', 'error'); redirect('users.php');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Enter a valid email address.', 'error'); redirect('users.php');
    }
    if (!in_array($role, ['ADMIN', 'DOCTOR', 'PATIENT'], true)) {
        flash('Invalid role.', 'error'); redirect('users.php');
    }
    if (strlen($pass) < 6) {
        flash('Password must be at least 6 characters.', 'error'); redirect('users.php');
    }
    if ($role === 'DOCTOR' && $spec === '') {
        flash('Specialization is required for doctors.', 'error'); redirect('users.php');
    }

    $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $chk->execute([$email]);
    if ($chk->fetch()) { flash('That email is already registered.', 'error'); redirect('users.php'); }

    $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role, specialization, status)
                   VALUES (?, ?, ?, ?, ?, ?, 'APPROVED')")
        ->execute([$name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT), $role,
                   $role === 'DOCTOR' ? $spec : null]);

    flash("User created. {$email} can log in now.");
    redirect('users.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('Invalid user.', 'error'); redirect($back); }

if ($action === 'approve') {
    $pdo->prepare("UPDATE users SET status='APPROVED' WHERE id=? AND role='DOCTOR'")->execute([$id]);
    flash('Doctor approved.');
    redirect($back);
}

if ($action === 'reject') {
    $pdo->prepare("UPDATE users SET status='REJECTED' WHERE id=? AND role='DOCTOR'")->execute([$id]);
    flash('Doctor rejected.');
    redirect($back);
}

if ($action === 'delete') {
    if ($id === $me) { flash('You cannot delete your own account.', 'error'); redirect($back); }
    try {
        $pdo->prepare("DELETE FROM users WHERE id=? AND role <> 'ADMIN'")->execute([$id]);
        flash('User deleted.');
    } catch (PDOException $e) {
        flash('Cannot delete: this user still has appointments, records or bills.', 'error');
    }
    redirect($back);
}

redirect('users.php');
