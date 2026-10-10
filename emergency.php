<?php
require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_method('POST');
    $user = require_login();
    $in = body();
    $type     = (string)($in['type'] ?? '');
    $units    = (int)($in['units'] ?? 0);
    $hospital = trim((string)($in['hospital'] ?? ''));
    $urgency  = ($in['urgency'] ?? '') === 'critical' ? 'critical' : 'urgent';

    if (!valid_blood_type($type))                      fail('Select a blood type.');
    if ($units < 1 || $units > 50)                     fail('Units must be between 1 and 50.');
    if ($hospital === '' || mb_strlen($hospital) > 160) fail('Enter a hospital or requester name.');

    // Basic anti-spam: max 5 open requests per user per day
    $st = db()->prepare("SELECT COUNT(*) FROM emergency_requests WHERE requester_id = ? AND created_at > NOW() - INTERVAL 1 DAY");
    $st->execute([$user['id']]);
    if ((int)$st->fetchColumn() >= 5) fail('Request limit reached for today.', 429);

    db()->prepare('INSERT INTO emergency_requests (requester_id, requester_name, blood_type, units_needed, urgency) VALUES (?, ?, ?, ?, ?)')
        ->execute([$user['id'], $hospital, $type, $units, $urgency]);
    respond(['ok' => true, 'message' => 'Emergency request posted to nearby donors.'], 201);
}

require_method('GET');
$rows = db()->query("SELECT id, blood_type AS type, requester_name AS hospital, units_needed AS units, urgency,
                            TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS postedMinsAgo
                     FROM emergency_requests
                     WHERE status = 'open' AND created_at > NOW() - INTERVAL 1 DAY
                     ORDER BY created_at DESC LIMIT 50")->fetchAll();
foreach ($rows as &$r) { $r['id'] = 'er-' . $r['id']; $r['units'] = (int)$r['units']; $r['postedMinsAgo'] = (int)$r['postedMinsAgo']; }
respond($rows);
