<?php
require __DIR__ . '/bootstrap.php';
require_method('GET');
$u = current_user();
respond(['user' => $u ? ['name' => $u['name'], 'role' => $u['role'], 'bloodType' => $u['blood_type'], 'city' => $u['city']] : null]);
