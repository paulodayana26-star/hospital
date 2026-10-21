<?php
// privileges.php - ADMIN ONLY: choose what doctors and patients are allowed to do
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';
require_role(['ADMIN']);
ensure_privileges($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $up = $pdo->prepare("INSERT INTO role_privileges (role, permission, allowed) VALUES (?,?,?)
                         ON DUPLICATE KEY UPDATE allowed = VALUES(allowed)");
    foreach (array_keys(PRIVILEGE_DEFAULTS) as $r) {
        foreach (array_keys(PRIVILEGES) as $p) {
            $up->execute([$r, $p, isset($_POST['priv'][$r][$p]) ? 1 : 0]);
        }
    }
    flash('Privileges saved.');
    redirect('privileges.php');
}

$current = [];
foreach ($pdo->query("SELECT role, permission, allowed FROM role_privileges") as $row) {
    $current[$row['role']][$row['permission']] = (int)$row['allowed'];
}

page_start('privileges', 'Privileges', 'Control what each role is allowed to do');
?>
<form method="POST">
  <?= csrf_field() ?>
  <div class="role-grid">
    <div class="role-card">
      <h3>Administrator</h3>
      <p>Full access. Cannot be changed.</p>
      <?php foreach (PRIVILEGES as $key => $label): ?>
        <label><input type="checkbox" checked disabled> <?= e($label) ?></label>
      <?php endforeach; ?>
      <label><input type="checkbox" checked disabled> Manage users, doctors and bills</label>
    </div>

    <?php foreach (['DOCTOR' => 'Doctor', 'PATIENT' => 'Patient'] as $r => $title): ?>
    <div class="role-card">
      <h3><?= e($title) ?></h3>
      <p>Tick what a <?= e(strtolower($title)) ?> may do.</p>
      <?php foreach (PRIVILEGES as $key => $label): ?>
        <label>
          <input type="checkbox" name="priv[<?= e($r) ?>][<?= e($key) ?>]" value="1" <?= !empty($current[$r][$key]) ? 'checked' : '' ?>>
          <?= e($label) ?>
        </label>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <p style="margin-top:18px"><button class="primary">Save privileges</button></p>
</form>
<?php page_end(); ?>
