<?php
// profile.php  - every logged-in user sees ONLY their own details
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';
require_login();

$uid  = (int)$_SESSION['user_id'];   // always from the session, never from the URL
$role = $_SESSION['role'];

// ---------- Handle form submissions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name  = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name === '' || $phone === '') {
            flash('Name and phone are required.', 'error');
        } else {
            $pdo->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?")
                ->execute([$name, $phone, $uid]);
            $_SESSION['user_name'] = $name;
            flash('Profile updated.');
        }
        redirect('profile.php');
    }

    if ($action === 'change_password') {
        $cur = $_POST['current'] ?? '';
        $new = $_POST['new'] ?? '';
        $con = $_POST['confirm'] ?? '';

        $st = $pdo->prepare("SELECT password FROM users WHERE id=?");
        $st->execute([$uid]);
        $hash = (string)$st->fetchColumn();

        if (!password_verify($cur, $hash))      flash('Current password is wrong.', 'error');
        elseif (strlen($new) < 6)               flash('New password must be at least 6 characters.', 'error');
        elseif ($new !== $con)                  flash('New passwords do not match.', 'error');
        else {
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")
                ->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            flash('Password changed.');
        }
        redirect('profile.php');
    }
}

// ---------- Load own record ----------
$st = $pdo->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
$st->execute([$uid]);
$me = $st->fetch();
if (!$me) { redirect('logout.php'); }
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Profile - MedCare</title>
<link rel="stylesheet" href="style.css">
</head><body><div class="app">
<aside class="sidebar">
  <div class="side-brand"><span class="logo small">✚</span><span>MedCare</span></div>
  <nav><?= nav_html('profile') ?></nav>
  <a class="logout" href="logout.php">↪ <span>Logout</span></a>
</aside>

<main class="main">
<header class="topbar"><div><h2>My Profile</h2><p class="muted">Your personal details</p></div></header>

<?= render_flash() ?>

<div class="grid-2">
  <div class="panel">
    <h3>Personal Details</h3>
    <table>
      <tr><td class="muted">ID</td><td>#<?= (int)$me['id'] ?></td></tr>
      <tr><td class="muted">Email</td><td><?= e($me['email']) ?></td></tr>
      <tr><td class="muted">Role</td><td><?= e(ucfirst(strtolower($me['role']))) ?></td></tr>
      <?php if ($me['role'] === 'DOCTOR'): ?>
      <tr><td class="muted">Specialization</td><td><?= e($me['specialization'] ?? '—') ?></td></tr>
      <?php endif; ?>
      <tr><td class="muted">Status</td><td><span class="badge green"><?= e(ucfirst(strtolower($me['status']))) ?></span></td></tr>
      <tr><td class="muted">Member since</td><td><?= !empty($me['created_at']) ? date('d M Y', strtotime($me['created_at'])) : '—' ?></td></tr>
    </table>

    <h3 style="margin-top:22px">Edit Details</h3>
    <form method="POST" class="inline-form" style="grid-template-columns:1fr">
      <input type="hidden" name="action" value="update_profile">
      <div><label>Full name</label><input name="full_name" value="<?= e($me['full_name']) ?>" required></div>
      <div><label>Phone</label><input name="phone" value="<?= e($me['phone']) ?>" required></div>
      <button class="primary">Save</button>
    </form>
  </div>

  <div class="panel">
    <h3>Change Password</h3>
    <form method="POST" class="inline-form" style="grid-template-columns:1fr">
      <input type="hidden" name="action" value="change_password">
      <div><label>Current password</label><input type="password" name="current" required></div>
      <div><label>New password</label><input type="password" name="new" minlength="6" required></div>
      <div><label>Confirm new password</label><input type="password" name="confirm" minlength="6" required></div>
      <button class="primary">Change password</button>
    </form>
  </div>
</div>
</main></div></body></html>
