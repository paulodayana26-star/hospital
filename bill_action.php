<?php
// bill_action.php - ADMIN ONLY: create a bill or mark it paid
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/access.php';
require_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('bills.php');
csrf_check();

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $patientId = (int)($_POST['patient_id'] ?? 0);
    $amount    = (float)($_POST['amount'] ?? 0);
    $date      = $_POST['bill_date'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

    $c = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id=? AND role='PATIENT'");
    $c->execute([$patientId]);
    if (!(int)$c->fetchColumn() || $amount <= 0) {
        flash('Choose a valid patient and an amount greater than zero.', 'error');
        redirect('bills.php');
    }

    $number = 'BILL-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
    $pdo->prepare("INSERT INTO bills (bill_number, patient_id, amount, bill_date, status)
                   VALUES (?, ?, ?, ?, 'UNPAID')")
        ->execute([$number, $patientId, $amount, $date]);

    flash("Bill $number created.");
    redirect('bills.php');
}

if ($action === 'pay') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE bills SET status='PAID' WHERE id=?")->execute([$id]);
    flash('Bill marked as paid.');
    redirect('bills.php');
}

redirect('bills.php');
