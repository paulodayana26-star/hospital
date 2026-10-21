<?php
// dashboard.php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';
require_login();

$role = $_SESSION['role'];
$uid  = (int)$_SESSION['user_id'];
$name = $_SESSION['user_name'] ?? 'User';

// Small helper: run a query and return the first column of the first row
function scalar(PDO $pdo, string $sql, array $params = []) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchColumn();
}

$stats = [];
$pending = [];

if ($role === 'ADMIN') {
    $stats = [
        ['♙', 'Patients',       (int)scalar($pdo, "SELECT COUNT(*) FROM users WHERE role='PATIENT'")],
        ['✚', 'Doctors',        (int)scalar($pdo, "SELECT COUNT(*) FROM users WHERE role='DOCTOR' AND status='APPROVED'")],
        ['▣', 'Appointments',   (int)scalar($pdo, "SELECT COUNT(*) FROM appointments")],
        ['$', 'Unpaid bills',   '$' . number_format((float)scalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM bills WHERE status<>'PAID'"), 2)],
    ];
    $pending = $pdo->query("SELECT id, full_name, specialization FROM users
                            WHERE role='DOCTOR' AND status='PENDING'
                            ORDER BY created_at DESC LIMIT 5")->fetchAll();
    $stmt = $pdo->prepare("SELECT a.*, p.full_name patient, d.full_name doctor
                           FROM appointments a
                           JOIN users p ON p.id=a.patient_id
                           JOIN users d ON d.id=a.doctor_id
                           ORDER BY a.appointment_date DESC LIMIT 6");
    $stmt->execute();
} elseif ($role === 'DOCTOR') {
    $stats = [
        ['▣', 'My appointments', (int)scalar($pdo, "SELECT COUNT(*) FROM appointments WHERE doctor_id=?", [$uid])],
        ['!', 'Pending',         (int)scalar($pdo, "SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND status='PENDING'", [$uid])],
        ['♙', 'My patients',     (int)scalar($pdo, "SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id=?", [$uid])],
        ['▤', 'Records written', (int)scalar($pdo, "SELECT COUNT(*) FROM medical_records WHERE doctor_id=?", [$uid])],
    ];
    $stmt = $pdo->prepare("SELECT a.*, p.full_name patient, d.full_name doctor
                           FROM appointments a
                           JOIN users p ON p.id=a.patient_id
                           JOIN users d ON d.id=a.doctor_id
                           WHERE a.doctor_id=?
                           ORDER BY a.appointment_date DESC LIMIT 6");
    $stmt->execute([$uid]);
} else { // PATIENT
    $stats = [
        ['▣', 'My appointments', (int)scalar($pdo, "SELECT COUNT(*) FROM appointments WHERE patient_id=?", [$uid])],
        ['!', 'Pending',         (int)scalar($pdo, "SELECT COUNT(*) FROM appointments WHERE patient_id=? AND status='PENDING'", [$uid])],
        ['▤', 'My records',      (int)scalar($pdo, "SELECT COUNT(*) FROM medical_records WHERE patient_id=?", [$uid])],
        ['$', 'Unpaid bills',    '$' . number_format((float)scalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM bills WHERE patient_id=? AND status<>'PAID'", [$uid]), 2)],
    ];
    $stmt = $pdo->prepare("SELECT a.*, p.full_name patient, d.full_name doctor
                           FROM appointments a
                           JOIN users p ON p.id=a.patient_id
                           JOIN users d ON d.id=a.doctor_id
                           WHERE a.patient_id=?
                           ORDER BY a.appointment_date DESC LIMIT 6");
    $stmt->execute([$uid]);
}
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard - MedCare</title>
<link rel="stylesheet" href="style.css">
</head><body><div class="app">
<aside class="sidebar">
  <div class="side-brand"><span class="logo small">✚</span><span>MedCare</span></div>
  <nav><?= nav_html('dashboard') ?></nav>
  <a class="logout" href="logout.php">↪ <span>Logout</span></a>
</aside>

<main class="main">
<header class="topbar">
  <div><h2>Dashboard</h2><p class="muted">Overview of your activity</p></div>
  <a class="profile" href="profile.php">👤 <?= e($name) ?> · <?= e(ucfirst(strtolower($role))) ?> · ID #<?= $uid ?></a>
</header>

<?= render_flash() ?>

<section class="hero">
  <div>
    <h1>Welcome back, <?= e($name) ?></h1>
    <p>Here is what is happening at MedCare today.</p>
  </div>
  <a class="primary" href="appointments.php">View appointments</a>
</section>

<div class="stats">
<?php foreach ($stats as [$icon, $label, $value]): ?>
  <div class="stat"><span><?= e($icon) ?></span><div><p><?= e($label) ?></p><h2><?= e((string)$value) ?></h2></div></div>
<?php endforeach; ?>
</div>

<div class="<?= $role === 'ADMIN' ? 'grid-2' : '' ?>">
  <div class="panel">
    <div class="panel-head"><h3>Recent Appointments</h3><a href="appointments.php">View all</a></div>
    <table>
      <thead><tr><th>Patient</th><th>Doctor</th><th>Date</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (!$appointments): ?>
        <tr><td colspan="4" class="muted">No appointments yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($appointments as $a):
        $st = strtoupper($a['status']);
        $badge = $st === 'CONFIRMED' ? 'blue' : ($st === 'COMPLETED' ? 'green' : ($st === 'PENDING' ? 'orange' : 'red'));
      ?>
        <tr>
          <td><?= e($a['patient']) ?></td>
          <td><?= e($a['doctor']) ?></td>
          <td><?= date('d M Y, H:i', strtotime($a['appointment_date'])) ?></td>
          <td><span class="badge <?= $badge ?>"><?= e(ucfirst(strtolower($st))) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($role === 'ADMIN'): ?>
  <div class="panel">
    <div class="panel-head"><h3>Doctor Approvals</h3><a href="users.php?filter=DOCTOR">View all</a></div>
    <?php if (!$pending): ?><p class="muted">No pending doctor accounts.</p><?php endif; ?>
    <?php foreach ($pending as $d): ?>
      <div class="approval">
        <div class="avatar"><?= e(strtoupper(substr($d['full_name'], 0, 2))) ?></div>
        <div><b><?= e($d['full_name']) ?></b><p><?= e($d['specialization'] ?? '—') ?></p></div>
        <form method="POST" action="user_action.php" style="margin-left:auto"><?= csrf_field() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="back" value="dashboard.php"><input type="hidden" name="id" value="<?= (int)$d['id'] ?>"><button class="approve">Approve</button></form>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

</main></div></body></html>
