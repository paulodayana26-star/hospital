<?php
// access.php - permissions (managed from privileges.php) + CSRF helpers
declare(strict_types=1);
require_once __DIR__ . '/db.php';

const PRIVILEGES = [
    'appointments.book'   => 'Book appointments',
    'appointments.manage' => 'Approve / complete appointments',
    'records.view'        => 'View medical records',
    'records.create'      => 'Create medical records',
    'bills.view'          => 'View bills',
];

const PRIVILEGE_DEFAULTS = [
    'DOCTOR'  => ['appointments.book' => 0, 'appointments.manage' => 1, 'records.view' => 1, 'records.create' => 1, 'bills.view' => 0],
    'PATIENT' => ['appointments.book' => 1, 'appointments.manage' => 0, 'records.view' => 1, 'records.create' => 0, 'bills.view' => 1],
];

// Creates the role_privileges table the first time and fills in defaults
function ensure_privileges(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS role_privileges (
        role VARCHAR(20) NOT NULL,
        permission VARCHAR(50) NOT NULL,
        allowed TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (role, permission)
    ) ENGINE=InnoDB");
    $ins = $pdo->prepare("INSERT IGNORE INTO role_privileges (role, permission, allowed) VALUES (?,?,?)");
    foreach (PRIVILEGE_DEFAULTS as $r => $perms) {
        foreach ($perms as $p => $v) $ins->execute([$r, $p, $v]);
    }
    $done = true;
}

// ADMIN always has every privilege; other roles follow the privileges page
function has_priv(string $perm): bool {
    global $pdo;
    if (!is_logged_in()) return false;
    $role = $_SESSION['role'];
    if ($role === 'ADMIN') return true;
    static $cache = null;
    if ($cache === null) {
        ensure_privileges($pdo);
        $cache = [];
        foreach ($pdo->query("SELECT role, permission, allowed FROM role_privileges") as $r) {
            $cache[$r['role']][$r['permission']] = (int)$r['allowed'];
        }
    }
    return !empty($cache[$role][$perm]);
}

function require_priv(string $perm): void {
    require_login();
    if (!has_priv($perm)) { http_response_code(403); die('Access denied.'); }
}

// CSRF protection for every POST form
function csrf_field(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        die('Invalid request. Go back, refresh the page and try again.');
    }
}
