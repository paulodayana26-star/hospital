<?php
// records.php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';
require_login();
require_priv('records.view');

$role = $_SESSION['role'];
$uid  = (int)$_SESSION['user_id'];
$name = $_SESSION['user_name'] ?? 'User';

// Who can see which records
$where = ''; $params = [];
if ($role === 'PATIENT') { $where = 'WHERE m.patient_id = ?'; $params[] = $uid; }
if ($role === 'DOCTOR')  { $where = 'WHERE m.doctor_id = ?';  $params[] = $uid; }

$stmt = $pdo->prepare("SELECT m.*, p.full_name patient, d.full_name doctor
                       FROM medical_records m
                       JOIN users p ON p.id=m.patient_id
                       JOIN users d ON d.id=m.doctor_id
                       $where
                       ORDER BY m.record_date DESC, m.id DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$canCreate = in_array($role, ['ADMIN', 'DOCTOR'], true) && has_priv('records.create');
if ($canCreate) {
    $patients = $pdo->query("SELECT id, full_name FROM users WHERE role='PATIENT' ORDER BY full_name")->fetchAll();
    $doctors  = $pdo->query("SELECT id, full_name FROM users WHERE role='DOCTOR' AND status='APPROVED' ORDER BY full_name")->fetchAll();
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Medical Records - MedCare</title>
<link rel="stylesheet" href="style.css">
</head><body><div class="app">
<aside class="sidebar">
  <div class="side-brand"><span class="logo small">✚</span><span>MedCare</span></div>
  <nav><?= nav_html('records') ?></nav>
  <a class="logout" href="logout.php">↪ <span>Logout</span></a>
</aside>

<main class="main">
<header class="topbar">
  <div><h2>Medical Records</h2><p class="muted">Patient diagnoses and treatments</p></div>
  <a class="profile" href="profile.php">👤 <?= e($name) ?> · <?= e(ucfirst(strtolower($role))) ?> · ID #<?= $uid ?></a>
</header>

<?= render_flash() ?>

<?php if ($canCreate): ?>
<div class="panel">
  <h3>New Medical Record</h3>
  <form action="record_save.php" method="POST" class="inline-form"
        style="grid-template-columns:1fr 1fr 1fr 1fr 1fr auto">
    <div><label>Patient</label><select name="patient_id" required>
      <?php foreach ($patients as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['full_name']) ?></option><?php endforeach; ?>
    </select></div>
    <div><label>Doctor</label>
      <?php if ($role === 'DOCTOR'): ?>
        <input value="<?= e($name) ?>" disabled>
      <?php else: ?>
        <select name="doctor_id" required>
          <?php foreach ($doctors as $d): ?><option value="<?= (int)$d['id'] ?>"><?= e($d['full_name']) ?></option><?php endforeach; ?>
        </select>
      <?php endif; ?>
    </div>
    <div><label>Diagnosis</label><input name="diagnosis" required></div>
    <div><label>Treatment</label><input name="treatment"></div>
    <div><label>Date</label><input type="date" name="record_date" value="<?= date('Y-m-d') ?>"></div>
    <button class="primary">Save</button>
  </form>
</div>
<?php endif; ?>

<div class="panel">
<table>
<thead><tr><th>Patient</th><th>Doctor</th><th>Diagnosis</th><th>Treatment</th><th>Date</th></tr></thead>
<tbody>
<?php if (!$rows): ?>
  <tr><td colspan="5" class="muted">No medical records found.</td></tr>
<?php endif; ?>
<?php foreach ($rows as $r): ?>
  <tr>
    <td><?= e($r['patient']) ?></td>
    <td><?= e($r['doctor']) ?></td>
    <td><?= e($r['diagnosis']) ?></td>
    <td><?= e($r['treatment'] ?? '—') ?></td>
    <td><?= date('d M Y', strtotime($r['record_date'])) ?></td>
  </tr>
<?php endforeach; ?>
</tbody></table>
</div>
</main></div></body></html>
