<?php
require __DIR__ . '/db.php';

?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Login - MedCare</title>
<link rel="stylesheet" href="style.css">
</head><body>
<div class="login-page">
  <div class="login-card">
    <div class="brand">
      <div class="logo">✚</div>
      <div><h1>MedCare</h1><p>Hospital Management System</p></div>
    </div>
    <h2>Welcome Back</h2>
    <p class="muted">Sign in to continue</p>

    <?= render_flash() ?>

    <form action="login.php" method="POST">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="admin@hospital.com" required>
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required>
      <select name="role">
        <option value="admin">Administrator</option>
        <option value="doctor">Doctor</option>
        <option value="patient">Patient</option>
      </select>
      <button class="primary full" type="submit">Sign In</button>
    </form>

    <p class="links">Don’t have an account? <a href="register.php">Create one</a></p>
  </div>
</div>
</body></html>
