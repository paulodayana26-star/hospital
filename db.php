<?php
declare(strict_types=1);
ini_set('display_errors', '1'); error_reporting(E_ALL);   // development only - remove on a live server
if (session_status() === PHP_SESSION_NONE) session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'medcare_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Tries your MySQL port 3307 first, then the XAMPP default 3306
$pdo = null;
$lastError = '';
foreach ([3307, 3306] as $port) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=$port;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        break;
    } catch (PDOException $e) {
        $lastError = $e->getMessage();
    }
}
if (!$pdo) die("DB error: " . htmlspecialchars($lastError));

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $u): void { header("Location: $u"); exit; }
function is_logged_in(): bool { return isset($_SESSION['user_id']); }
function require_login(): void { if (!is_logged_in()) redirect('index.php'); }
function require_role(array $roles): void {
    require_login();
    if (!in_array($_SESSION['role'], $roles, true)) { http_response_code(403); die("Access denied."); }
}
function flash(string $m, string $t = 'success'): void { $_SESSION['flash'] = ['msg' => $m, 'type' => $t]; }
function get_flash(): ?array {
    if (empty($_SESSION['flash'])) return null;
    $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f;
}
function render_flash(): string {
    $f = get_flash(); if (!$f) return '';
    return '<div class="flash ' . e($f['type']) . '">' . e($f['msg']) . '</div>';
}
