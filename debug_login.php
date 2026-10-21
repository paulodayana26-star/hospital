<?php
// debug_login.php - TEMPORARY. Tests one email + password and shows why login fails. DELETE AFTER USE.
declare(strict_types=1);
require __DIR__ . '/db.php';

// Only usable from your own computer
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); die('Local use only.'); }

function row(string $label, string $value, string $color = ''): void {
    echo '<tr><td><b>' . htmlspecialchars($label) . '</b></td><td' . ($color ? " style='color:$color'" : '') . '>' . htmlspecialchars($value) . '</td></tr>';
}
$ok = '#1e8449'; $bad = '#c0392b'; $warn = '#c46a00';
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Login debug</title>
<style>body{font-family:Segoe UI,Arial,sans-serif;margin:30px;max-width:900px}td,th{border:1px solid #ccc;padding:7px 10px;font-size:14px;text-align:left}table{border-collapse:collapse;margin:12px 0}input{padding:8px;margin:4px 0;width:300px}</style>
</head><body>
<h2>Login debug</h2>
<form method="POST">
  <div><input type="email" name="email" placeholder="email you registered with" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required></div>
  <div><input type="text" name="password" placeholder="password you typed" value="<?= htmlspecialchars($_POST['password'] ?? '') ?>" required></div>
  <button>Test</button>
</form>

<h3>1. Which database is the site using?</h3>
<table>
<?php
$info = $pdo->query("SELECT DATABASE() db, @@port port, @@sql_mode mode")->fetch();
row('Database', (string)$info['db']);
row('MySQL port', (string)$info['port']);
$col = $pdo->query("SHOW COLUMNS FROM users LIKE 'password'")->fetch();
$type = (string)($col['Type'] ?? '?');
row('users.password column type', $type, preg_match('/\((\d+)\)/', $type, $m) && (int)$m[1] < 60 ? $bad : $ok);
if (preg_match('/\((\d+)\)/', $type, $m) && (int)$m[1] < 60) {
    row('PROBLEM', 'The column is shorter than 60 characters, so every password hash gets cut off and can never match.', $bad);
}
row('Total users', (string)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn());
?>
</table>

<h3>2. Latest 6 accounts</h3>
<table><tr><th>ID</th><th>Email</th><th>Role</th><th>Status</th><th>Hash length (must be 60)</th></tr>
<?php foreach ($pdo->query("SELECT id, email, role, status, password FROM users ORDER BY id DESC LIMIT 6") as $u):
  $len = strlen((string)$u['password']); ?>
<tr><td><?= (int)$u['id'] ?></td><td><?= htmlspecialchars($u['email']) ?></td><td><?= htmlspecialchars($u['role']) ?></td>
<td><?= htmlspecialchars((string)$u['status']) ?></td><td style="color:<?= $len === 60 ? $ok : $bad ?>"><?= $len ?></td></tr>
<?php endforeach; ?>
</table>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'):
  $email = trim($_POST['email'] ?? ''); $pass = $_POST['password'] ?? '';
  $st = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1"); $st->execute([$email]); $u = $st->fetch();
?>
<h3>3. Result for <?= htmlspecialchars($email) ?></h3>
<table>
<?php if (!$u): row('User found', 'NO - this email is not in the users table', $bad);
else:
  row('User found', 'yes (id ' . $u['id'] . ')', $ok);
  row('Role / status', $u['role'] . ' / ' . $u['status']);
  $len = strlen((string)$u['password']);
  row('Stored hash length', (string)$len, $len === 60 ? $ok : $bad);
  $match = password_verify($pass, (string)$u['password']);
  row('Password matches', $match ? 'YES' : 'NO', $match ? $ok : $bad);
  row('Password you typed has', strlen($pass) . ' characters' . ($pass !== trim($pass) ? ' (has a space at start or end!)' : ''), $pass !== trim($pass) ? $warn : '');
  if ($u['role'] === 'DOCTOR' && $u['status'] !== 'APPROVED') row('Blocked', 'Doctor is not APPROVED yet', $warn);
endif; ?>
</table>
<?php endif; ?>
</body></html>
