<?php
// show_log.php - TEMPORARY: shows register_log.txt in the browser. DELETE AFTER USE.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); die('Local use only.'); }
header('Content-Type: text/plain; charset=utf-8');
$f = __DIR__ . '/register_log.txt';
echo "Project folder: " . __DIR__ . "\n\n";
echo is_file($f)
    ? file_get_contents($f)
    : "No log yet. Either register_save.php was never reached (the form is not posting to it), or this is the old file.\n";
