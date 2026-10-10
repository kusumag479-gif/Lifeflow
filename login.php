<?php
require __DIR__ . '/bootstrap.php';
require_method('POST');
$in = body();
$email = strtolower(trim((string)($in['email'] ?? '')));
$pass  = (string)($in['password'] ?? '');
$role  = ($in['role'] ?? 'donor') === 'admin' ? 'admin' : 'donor';

$st = db()->prepare('SELECT * FROM users WHERE email = ?');
$st->execute([$email]);
$u = $st->fetch();

if (!$u || $u['role'] !== $role || !password_verify($pass, $u['password_hash'])) {
    usleep(300000); // small delay slows brute force; add real rate limiting before launch
    fail('Incorrect email, password, or account type.', 401);
}
if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
}
session_regenerate_id(true);
$_SESSION['uid'] = (int)$u['id'];
respond(['ok' => true, 'message' => 'Logged in successfully.', 'user' => ['name' => $u['name'], 'role' => $u['role']]]);
