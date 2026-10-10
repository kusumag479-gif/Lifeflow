<?php
require __DIR__ . '/bootstrap.php';
require_method('POST');
$_SESSION = [];
session_destroy();
respond(['ok' => true]);
