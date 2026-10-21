<?php
// register.php - public sign-up form (patients and doctors)
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/access.php';

$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
$oldRole = $old['role'] ?? 'PATIENT';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Create Account - MedCare</title>
<link rel="stylesheet" href="style.css">
</head><body>
<div class="register-page">
  <div class="register-card">
    <div class="brand">
      <div class="logo">✚</div>
      <div><h1>MedCare</h1><p>Hospital Management System</p></div>
    </div>
    <h2>Create Account</h2>
    <p class="muted">Fill in your details to register</p>

    <?= render_flash() ?>
<form action="reg.php" method="POST">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div><label>Full name</label><input name="name" value="<?= e($old['name'] ?? '') ?>" required></div>
        <div><label>Phone</label><input name="phone" value="<?= e($old['phone'] ?? '') ?>" required></div>
        <div class="full-field"><label>Email address</label><input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" required></div>
        <div><label>Register as</label>
          <select name="role" id="role">
            <option value="PATIENT" <?= $oldRole === 'PATIENT' ? 'selected' : '' ?>>Patient</option>
            <option value="DOCTOR"  <?= $oldRole === 'DOCTOR'  ? 'selected' : '' ?>>Doctor</option>
          </select></div>
        <div><label>Specialization (doctors only)</label><input name="specialization" id="spec" value="<?= e($old['specialization'] ?? '') ?>"></div>
        <div><label>Password (min 6)</label><input type="password" name="password" minlength="6" required></div>
        <div><label>Confirm password</label><input type="password" name="confirm" minlength="6" required></div>
      </div>
      <button class="primary full" type="submit" style="margin-top:22px">Create account</button>
    </form>

    <p class="muted" id="doctorNote" style="margin-top:14px;display:none">Doctor accounts must be approved by an administrator before you can sign in.</p>
    <p style="margin-top:18px;text-align:center;font-size:13px">Already have an account? <a href="index.php" style="color:#1677d2;font-weight:700">Sign in</a></p>
  </div>
</div>

<script>
const roleSel = document.getElementById('role'), spec = document.getElementById('spec'), note = document.getElementById('doctorNote');
function sync(){ const d = roleSel.value === 'DOCTOR'; spec.disabled = !d; spec.required = d; note.style.display = d ? 'block' : 'none'; if(!d) spec.value=''; }
roleSel.addEventListener('change', sync); sync();
</script>
</body></html>
