<?php
require __DIR__ . '/db.php';
$_SESSION = [];
session_destroy();
redirect('index.php');
