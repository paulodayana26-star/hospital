<?php
// users.php - ADMIN ONLY: create accounts, approve doctors, delete users
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';
require_role(['ADMIN']);

$uid    = (int)$_SESSION['user_id'];
$filter = strtoupper($_GET['filter'] ?? 'ALL');
$sql    = "SELECT * FROM users";
$params = [];
if (in_array($filter, ['DOCTOR', 'PATIENT', 'ADMIN'], true)) {
    $sql .= " WHERE role = ?";
    $params[] = $filter;
} else {
    $filter = 'ALL';
}
$sql .= " ORDER BY created_at DESC, id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$danger = 'background:#fdecea;color:#c0392b';

function user_btn(int $id, string $action, string $label, string $style = '', string $confirm = ''): void {
    $onsub = $confirm !== '' ? ' onsubmit="return confirm(\'' . e($confirm) . '\')"' : '';
    echo '<form method="POST" action="user_action.php" style="display:inline"' . $onsub . '>'
       . csrf_field()
       . '<input type="hidden" name="id" value="' . $id . '">'
       . '<input type="hidden" name="action" value="' . e($action) . '">'
       . '<button class="approve" style="' . e($style) . '">' . e($label) . '</button></form> ';
}

page_start('users', 'Users', 'Create accounts and manage patients, doctors and admins');
?>
<div class="panel">
  <h3>Add New User</h3>
  <form action="user_action.php" method="POST" class="inline-form" style="grid-template-columns:repeat(3,1fr)">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <div><label>Full name</label><input name="full_name" required></div>
    <div><label>Email</label><input type="email" name="email" required></div>
    <div><label>Phone</label><input name="phone" required></div>
    <div><label>Password (min 6)</label><input type="password" name="password" minlength="6" required></div>
    <div><label>Role</label>
      <select name="role" id="role" required>
        <option value="PATIENT">Patient</option>
        <option value="DOCTOR">Doctor</option>
        <option value="ADMIN">Admin</option>
      </select></div>
    <div><label>Specialization (doctors only)</label><input name="specialization" id="spec" disabled></div>
    <button class="primary">Create user</button>
  </form>
  <p class="muted" style="margin-top:10px">Accounts created here are approved straight away, so the person can log in with this email and password.</p>
</div>

<div class="filter-bar">
  Filter:
  <a href="users.php?filter=ALL" class="<?= $filter === 'ALL' ? 'active' : '' ?>">All</a>
  <a href="users.php?filter=DOCTOR" class="<?= $filter === 'DOCTOR' ? 'active' : '' ?>">Doctors</a>
  <a href="users.php?filter=PATIENT" class="<?= $filter === 'PATIENT' ? 'active' : '' ?>">Patients</a>
  <a href="users.php?filter=ADMIN" class="<?= $filter === 'ADMIN' ? 'active' : '' ?>">Admins</a>
</div>

<div class="panel">
<table>
<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Specialization</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php foreach ($rows as $r):
  $st = strtoupper((string)$r['status']);
  $badge = $st === 'APPROVED' ? 'green' : ($st === 'PENDING' ? 'orange' : 'red');
?>
  <tr>
    <td>#<?= (int)$r['id'] ?></td>
    <td><?= e($r['full_name']) ?></td>
    <td><?= e($r['email']) ?></td>
    <td><?= e($r['phone']) ?></td>
    <td><?= e($r['role']) ?></td>
    <td><?= e($r['specialization'] ?? '—') ?></td>
    <td><span class="badge <?= $badge ?>"><?= e(ucfirst(strtolower($st))) ?></span></td>
    <td>
      <?php if ($r['role'] === 'DOCTOR' && $st === 'PENDING'): ?>
        <?php user_btn((int)$r['id'], 'approve', 'Approve'); ?>
        <?php user_btn((int)$r['id'], 'reject', 'Reject', $danger); ?>
      <?php elseif ($r['role'] !== 'ADMIN' && (int)$r['id'] !== $uid): ?>
        <?php user_btn((int)$r['id'], 'delete', 'Delete', $danger, 'Delete this user?'); ?>
      <?php else: ?><span class="muted">—</span><?php endif; ?>
    </td>
  </tr>
<?php endforeach; ?>
</tbody></table>
</div>

<script>
// Specialization only matters for doctors
const roleSel = document.getElementById('role'), spec = document.getElementById('spec');
function syncSpec(){ const d = roleSel.value === 'DOCTOR'; spec.disabled = !d; spec.required = d; if(!d) spec.value=''; }
roleSel.addEventListener('change', syncSpec); syncSpec();
</script>
<?php page_end(); ?>
