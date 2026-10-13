<?php
require 'bootstrap.php';
require_method('POST');
$in = body();

$name  = trim((string)($in['name'] ?? ''));
$email = strtolower(trim((string)($in['email'] ?? '')));
$pass  = (string)($in['password'] ?? '');
$type  = (string)($in['bloodType'] ?? '');
$city  = trim((string)($in['city'] ?? ''));

if (($in['role'] ?? 'donor') !== 'donor') fail('Blood bank admin accounts are created by the LifeFlow team. Register as a donor, or contact us.', 403);

if ($name === '' || mb_strlen($name) > 100)          fail('Enter your full name.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))      fail('Enter a valid email address.');
if (strlen($pass) < MIN_PASSWORD_LEN)                fail('Password must be at least ' . MIN_PASSWORD_LEN . ' characters.');
if (!valid_blood_type($type))                        fail('Select a blood type.');
if ($city === '' || mb_strlen($city) > 120)          fail('Enter your city or area.');

$st = db()->prepare('SELECT 1 FROM users WHERE email = ?');
$st->execute([$email]);
if ($st->fetch()) fail('An account with this email already exists.', 409);

db()->prepare('INSERT INTO users (role, name, email, password_hash, blood_type, city) VALUES (?, ?, ?, ?, ?, ?)')
    ->execute(['donor', $name, $email, password_hash($pass, PASSWORD_DEFAULT), $type, $city]);

respond(['ok' => true, 'message' => 'Account created. You can now log in.'], 201);
