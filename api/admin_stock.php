<?php
// Blood bank admin: set units for one blood type at their own bank. Body: { bloodType, units }
require __DIR__. '/bootstrap.php';
require_method('POST');
$admin = require_login('admin');
$in = body();
$type  = (string)($in['bloodType'] ?? '');
$units = $in['units'] ?? null;
if (!valid_blood_type($type))                          fail('Invalid blood type.');
if (!is_int($units) || $units < 0 || $units > 9999)    fail('Units must be a whole number from 0 to 9999.');

$st = db()->prepare('SELECT id FROM blood_banks WHERE admin_user_id = ?');
$st->execute([$admin['id']]);
$bankId = $st->fetchColumn();
if (!$bankId) fail('Your account is not linked to a blood bank.', 403);

db()->prepare('INSERT INTO blood_stock (bank_id, blood_type, units) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE units = VALUES(units)')
    ->execute([$bankId, $type, $units]);
respond(['ok' => true]);
