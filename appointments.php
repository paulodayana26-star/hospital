<?php
// appointments.php - everyone sees only what belongs to them; admin sees all
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';
require_login();

$role = $_SESSION['role'];
$uid  = (int)$_SESSION['user_id'];

$where = ''; $params = [];
if ($role === 'PATIENT') { $where = 'WHERE a.patient_id = ?'; $params[] = $uid; }
if ($role === 'DOCTOR')  { $where = 'WHERE a.doctor_id = ?';  $params[] = $uid; }

$stmt = $pdo->prepare("SELECT a.*, p.full_name patient, d.full_name doctor
                       FROM appointments a
                       JOIN users p ON p.id = a.patient_id
                       JOIN users d ON d.id = a.doctor_id
                       $where
                       ORDER BY a.appointment_date DESC, a.id DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$canBook = $role === 'ADMIN' || ($role === 'PATIENT' && has_priv('appointments.book'));
if ($canBook) {
    $doctors = $pdo->query("SELECT id, full_name, specialization FROM users WHERE role='DOCTOR' AND status='APPROVED' ORDER BY full_name")->fetchAll();
    if ($role === 'ADMIN') {
        $patients = $pdo->query("SELECT id, full_name FROM users WHERE role='PATIENT' ORDER BY full_name")->fetchAll();
    }
}

$danger = 'background:#fdecea;color:#c0392b';
function appt_btn(int $id, string $action, string $label, string $style = '', string $confirm = ''): void {
    $onsub = $confirm !== '' ? ' onsubmit="return confirm(\'' . e($confirm) . '\')"' : '';
    echo '<form method="POST" action="appointment_action.php" style="display:inline"' . $onsub . '>'
       . csrf_field()
       . '<input type="hidden" name="id" value="' . $id . '">'
       . '<input type="hidden" name="action" value="' . e($action) . '">'
       . '<button class="approve" style="' . e($style) . '">' . e($label) . '</button></form> ';
}

page_start('appointments', 'Appointments', $role === 'ADMIN' ? 'All appointments' : 'Your appointments');
?>
<?php if ($canBook): ?>
<div class="panel">
  <h3>Book Appointment</h3>
  <form action="appointment_action.php" method="POST" class="inline-form"
        style="grid-template-columns:<?= $role === 'ADMIN' ? '1fr 1fr 1fr 1.5fr auto' : '1.2fr 1fr 2fr auto' ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <?php if ($role === 'ADMIN'): ?>
      <div><label>Patient</label><select name="patient_id" required>
        <?php foreach ($patients as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['full_name']) ?></option><?php endforeach; ?>
      </select></div>
    <?php endif; ?>
    <div><label>Doctor</label><select name="doctor_id" required>
      <?php foreach ($doctors as $d): ?>
        <option value="<?= (int)$d['id'] ?>"><?= e($d['full_name']) ?><?= $d['specialization'] ? ' – ' . e($d['specialization']) : '' ?></option>
      <?php endforeach; ?>
    </select></div>
    <div><label>Date &amp; time</label><input type="datetime-local" name="appointment_date" min="<?= date('Y-m-d\TH:i') ?>" required></div>
    <div><label>Notes</label><input name="notes" placeholder="Reason for visit"></div>
    <button class="primary">Book</button>
  </form>
</div>
<?php endif; ?>

<div class="panel">
<table>
<thead><tr><th>#</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Notes</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="7" class="muted">No appointments found.</td></tr><?php endif; ?>
<?php foreach ($rows as $a):
  $st = strtoupper((string)$a['status']);
  $badge = $st === 'CONFIRMED' ? 'blue' : ($st === 'COMPLETED' ? 'green' : ($st === 'PENDING' ? 'orange' : 'red'));
  $mineDoctor  = $role === 'DOCTOR'  && (int)$a['doctor_id']  === $uid;
  $minePatient = $role === 'PATIENT' && (int)$a['patient_id'] === $uid;
  $canManage = $role === 'ADMIN' || ($mineDoctor && has_priv('appointments.manage'));
  $canCancel = in_array($st, ['PENDING', 'CONFIRMED'], true) && ($role === 'ADMIN' || $mineDoctor || $minePatient);
  $id = (int)$a['id'];
?>
  <tr>
    <td>#<?= $id ?></td>
    <td><?= e($a['patient']) ?></td>
    <td><?= e($a['doctor']) ?></td>
    <td><?= date('d M Y, H:i', strtotime($a['appointment_date'])) ?></td>
    <td><?= e($a['notes'] ?? '—') ?></td>
    <td><span class="badge <?= $badge ?>"><?= e(ucfirst(strtolower($st))) ?></span></td>
    <td>
      <?php
        $any = false;
        if ($canManage && $st === 'PENDING')   { appt_btn($id, 'approve', 'Approve'); $any = true; }
        if ($canManage && $st === 'CONFIRMED') { appt_btn($id, 'complete', 'Complete'); $any = true; }
        if ($canCancel) { appt_btn($id, 'cancel', 'Cancel', $danger, 'Cancel this appointment?'); $any = true; }
        if (!$any) echo '<span class="muted">—</span>';
      ?>
    </td>
  </tr>
<?php endforeach; ?>
</tbody></table>
</div>
<?php page_end(); ?>
