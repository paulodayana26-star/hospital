<?php
// layout.php - shared sidebar + page header so every page looks the same
declare(strict_types=1);
require_once __DIR__ . '/access.php';

function nav_html(string $active): string {
    $role = $_SESSION['role'] ?? '';
    $items = [
        ['dashboard',    'dashboard.php',    '▦', 'Dashboard',       true],
        ['users',        'users.php',        '♙', 'Users',           $role === 'ADMIN'],
        ['appointments', 'appointments.php', '▣', 'Appointments',    true],
        ['records',      'records.php',      '▤', 'Medical Records', has_priv('records.view')],
        ['bills',        'bills.php',        '▤', 'Bills',           has_priv('bills.view')],
        ['privileges',   'privileges.php',   '⚿', 'Privileges',      $role === 'ADMIN'],
        ['profile',      'profile.php',      '♙', 'My Profile',      true],
    ];
    $html = '';
    foreach ($items as [$key, $href, $icon, $label, $show]) {
        if (!$show) continue;
        $cls = $key === $active ? ' class="active"' : '';
        $html .= "<a$cls href=\"$href\">$icon <span>$label</span></a>";
    }
    return $html;
}

function page_start(string $active, string $title, string $sub): void {
    $role = $_SESSION['role'] ?? '';
    $name = $_SESSION['user_name'] ?? 'User';
    $uid  = (int)($_SESSION['user_id'] ?? 0);
    ?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= e($title) ?> - MedCare</title>
<link rel="stylesheet" href="style.css">
</head><body><div class="app">
<aside class="sidebar">
  <div class="side-brand"><span class="logo small">✚</span><span>MedCare</span></div>
  <nav><?= nav_html($active) ?></nav>
  <a class="logout" href="logout.php">↪ <span>Logout</span></a>
</aside>
<main class="main">
<header class="topbar">
  <div><h2><?= e($title) ?></h2><p class="muted"><?= e($sub) ?></p></div>
  <a class="profile" href="profile.php">👤 <?= e($name) ?> · <?= e(ucfirst(strtolower($role))) ?> · ID #<?= $uid ?></a>
</header>
<?= render_flash() ?>
<?php
}

function page_end(): void {
    echo '</main></div></body></html>';
}
