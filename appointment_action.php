<?php
// appointment_action.php - create / approve / complete / cancel (with ownership checks)
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/access.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('appointments.php');
csrf_check();

$role   = $_SESSION['role'];
$uid    = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    if (!($role === 'ADMIN' || ($role === 'PATIENT' && has_priv('appointments.book')))) {
        http_response_code(403); die('Access denied.');
    }
    $patientId = $role === 'PATIENT' ? $uid : (int)($_POST['patient_id'] ?? 0);
    $doctorId  = (int)($_POST['doctor_id'] ?? 0);
    $notes     = trim($_POST['notes'] ?? '');
    $ts        = strtotime(str_replace('T', ' ', (string)($_POST['appointment_date'] ?? '')));

    if (!$patientId || !$doctorId || !$ts) {
        flash('Patient, doctor and date are required.', 'error'); redirect('appointments.php');
    }
    if ($ts < time() - 60) {
        flash('Please choose a future date and time.', 'error'); redirect('appointments.php');
    }

    $c = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id=? AND role='PATIENT'");
    $c->execute([$patientId]);
    if (!(int)$c->fetchColumn()) { flash('Invalid patient.', 'error'); redirect('appointments.php'); }

    $c = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id=? AND role='DOCTOR' AND status='APPROVED'");
    $c->execute([$doctorId]);
    if (!(int)$c->fetchColumn()) { flash('Invalid doctor.', 'error'); redirect('appointments.php'); }

    $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, notes, status)
                   VALUES (?, ?, ?, ?, 'PENDING')")
        ->execute([$patientId, $doctorId, date('Y-m-d H:i:s', $ts), $notes]);

    flash('Appointment booked. It will show as Pending until a doctor approves it.');
    redirect('appointments.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('Invalid appointment.', 'error'); redirect('appointments.php'); }

if ($action === 'approve' || $action === 'complete') {
    require_priv('appointments.manage');           // admin always passes
    $from = $action === 'approve' ? 'PENDING' : 'CONFIRMED';
    $to   = $action === 'approve' ? 'CONFIRMED' : 'COMPLETED';
    $sql  = "UPDATE appointments SET status=? WHERE id=? AND status=?";
    $args = [$to, $id, $from];
    if ($role === 'DOCTOR') { $sql .= " AND doctor_id=?"; $args[] = $uid; }
    elseif ($role !== 'ADMIN') { http_response_code(403); die('Access denied.'); }
    $pdo->prepare($sql)->execute($args);
    flash($action === 'approve' ? 'Appointment approved.' : 'Appointment completed.');
    redirect('appointments.php');
}

if ($action === 'cancel') {
    $sql  = "UPDATE appointments SET status='CANCELLED' WHERE id=? AND status IN ('PENDING','CONFIRMED')";
    $args = [$id];
    if ($role === 'PATIENT')     { $sql .= " AND patient_id=?"; $args[] = $uid; }
    elseif ($role === 'DOCTOR')  { $sql .= " AND doctor_id=?";  $args[] = $uid; }
    $pdo->prepare($sql)->execute($args);
    flash('Appointment cancelled.');
    redirect('appointments.php');
}

redirect('appointments.php');
