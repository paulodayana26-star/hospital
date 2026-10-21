<?php
// register_save.php - saves a new account from register.php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/access.php';

// Temporary log so we can see exactly what happens (delete register_log.txt and these lines when done)
function reglog(string $msg): void {
    @file_put_contents(__DIR__ . '/register_log.txt', date('Y-m-d H:i:s') . '  ' . $msg . PHP_EOL, FILE_APPEND);
}
reglog('REACHED register_save.php (method ' . ($_SERVER['REQUEST_METHOD'] ?? '?') . ')');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('register.php');

if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    reglog('STOPPED: security token did not match (session/cookie problem or old form)');
}
csrf_check();

$name  = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$role  = strtoupper($_POST['role'] ?? '');
$spec  = trim($_POST['specialization'] ?? '');
$pass  = $_POST['password'] ?? '';
$passC = $_POST['confirm'] ?? '';
reglog("Form received: email=[$email] role=[$role] name=[$name]");

// Keep what they typed (never the password) so they don't retype everything after an error
$_SESSION['old'] = ['name' => $name, 'phone' => $phone, 'email' => $email, 'role' => $role, 'specialization' => $spec];

function fail(string $msg): void { reglog('STOPPED: ' . $msg); flash($msg, 'error'); redirect('register.php'); }

if ($name === '' || $phone === '' || $email === '' || $pass === '') fail('All fields are required.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))                      fail('Enter a valid email address.');
if (!in_array($role, ['PATIENT', 'DOCTOR'], true))                   fail('Invalid role selected.');   // nobody can self-register as ADMIN
if ($pass !== $passC)                                                fail('Passwords do not match.');
if (strlen($pass) < 6)                                               fail('Password must be at least 6 characters.');
if ($role === 'DOCTOR' && $spec === '')                              fail('Specialization is required for doctors.');

$chk = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$chk->execute([$email]);
if ($chk->fetch()) fail('This email is already registered.');

// Patients can sign in immediately; doctors wait for admin approval
$status = $role === 'DOCTOR' ? 'PENDING' : 'APPROVED';

try {
    $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role, specialization, status)
                   VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT), $role,
                   $role === 'DOCTOR' ? $spec : null, $status]);
} catch (PDOException $e) {
    // Shows the real database reason (wrong column name, bad status value, etc.)
    fail('Could not save the account: ' . $e->getMessage());
}

$info = $pdo->query("SELECT DATABASE() db, @@port port")->fetch();
reglog('SAVED OK: new user id=' . $pdo->lastInsertId() . ' in database [' . $info['db'] . '] on port ' . $info['port']);

unset($_SESSION['old']);
flash($role === 'DOCTOR'
    ? 'Registration submitted. Your doctor account is pending administrator approval.'
    : 'Registration successful. You can now sign in.');
redirect('index.php');
