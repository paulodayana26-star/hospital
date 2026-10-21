<?php
// bills.php - admin creates and marks paid; patients see only their own bills
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';
require_priv('bills.view');

$role = $_SESSION['role'];
$uid  = (int)$_SESSION['user_id'];

$wRows = ''; $wTot = ''; $params = [];
if ($role === 'PATIENT') { $wRows = 'WHERE b.patient_id = ?'; $wTot = 'WHERE patient_id = ?'; $params[] = $uid; }

$t = $pdo->prepare("SELECT COALESCE(SUM(amount),0) total,
        COALESCE(SUM(CASE WHEN status='PAID' THEN amount ELSE 0 END),0) paid,
        COALESCE(SUM(CASE WHEN status<>'PAID' THEN amount ELSE 0 END),0) unpaid
        FROM bills $wTot");
$t->execute($params);
$totals = $t->fetch();

$s = $pdo->prepare("SELECT b.*, p.full_name patient FROM bills b
                    JOIN users p ON p.id = b.patient_id
                    $wRows
                    ORDER BY b.bill_date DESC, b.id DESC");
$s->execute($params);
$rows = $s->fetchAll();

if ($role === 'ADMIN') {
    $patients = $pdo->query("SELECT id, full_name FROM users WHERE role='PATIENT' ORDER BY full_name")->fetchAll();
}

page_start('bills', 'Bills & Payments', $role === 'ADMIN' ? 'Manage patient billing' : 'Your bills');
?>
<div class="stats mini">
  <div class="stat"><span>$</span><div><p>Total billed</p><h2>$<?= number_format((float)$totals['total'], 2) ?></h2></div></div>
  <div class="stat"><span>✓</span><div><p>Paid</p><h2>$<?= number_format((float)$totals['paid'], 2) ?></h2></div></div>
  <div class="stat"><span>!</span><div><p>Unpaid</p><h2>$<?= number_format((float)$totals['unpaid'], 2) ?></h2></div></div>
</div>

<?php if ($role === 'ADMIN'): ?>
<div class="panel">
  <h3>Create Bill</h3>
  <form action="bill_action.php" method="POST" class="inline-form" style="grid-template-columns:2fr 1fr 1fr auto">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <div><label>Patient</label><select name="patient_id" required>
      <?php foreach ($patients as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['full_name']) ?></option><?php endforeach; ?>
    </select></div>
    <div><label>Amount ($)</label><input type="number" step="0.01" name="amount" min="0.01" required></div>
    <div><label>Date</label><input type="date" name="bill_date" value="<?= date('Y-m-d') ?>"></div>
    <button class="primary">Create</button>
  </form>
</div>
<?php endif; ?>

<div class="panel">
<table>
<thead><tr><th>Bill #</th><th>Patient</th><th>Amount</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="6" class="muted">No bills found.</td></tr><?php endif; ?>
<?php foreach ($rows as $r):
  $st = strtolower((string)$r['status']);
  $badge = $st === 'paid' ? 'green' : ($st === 'partial' ? 'orange' : 'red');
?>
<tr>
  <td><?= e($r['bill_number'] ?? ('#' . $r['id'])) ?></td>
  <td><?= e($r['patient']) ?></td>
  <td>$<?= number_format((float)$r['amount'], 2) ?></td>
  <td><?= date('d M Y', strtotime($r['bill_date'])) ?></td>
  <td><span class="badge <?= $badge ?>"><?= e(ucfirst($st)) ?></span></td>
  <td>
    <?php if ($role === 'ADMIN' && strtoupper((string)$r['status']) !== 'PAID'): ?>
      <form method="POST" action="bill_action.php" style="display:inline">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="pay">
        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <button class="approve">Mark Paid</button>
      </form>
    <?php else: ?><span class="muted">—</span><?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<?php page_end(); ?>
