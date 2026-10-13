<?php
require __DIR__ . '/bootstrap.php';
require_method('POST');
$user = require_login('donor');
$in = body();E

$bankId = (int)($in['bankId'] ?? 0);
$date   = (string)($in['date'] ?? '');
$time   = slot_to_time((string)($in['time'] ?? ''));
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$bankId)                                    fail('Pick a blood bank first.');
if (!$d || $d->format('Y-m-d') !== $date || !$time) fail('Invalid date or time slot.');

$start = new DateTime("$date $time");
if ($start <= new DateTime())                    fail('That slot has already passed.');
if ($start > new DateTime('+' . BOOKING_WINDOW_DAYS . ' days')) fail('Bookings open up to ' . BOOKING_WINDOW_DAYS . ' days ahead.');

// Donation-gap eligibility (based on last completed donation)
if ($user['last_donation_date']) {
    $eligible = (new DateTime($user['last_donation_date']))->modify('+' . DONATION_GAP_DAYS . ' days');
    if ($start < $eligible) fail('You can donate again from ' . $eligible->format('j M Y') . '.');
}

$pdo = db();
$pdo->beginTransaction();
try {
    // Lock the bank row so two donors can't take the last place in the same slot
    $st = $pdo->prepare('SELECT name, slot_capacity FROM blood_banks WHERE id = ? FOR UPDATE');
    $st->execute([$bankId]);
    $bank = $st->fetch();
    if (!$bank) { $pdo->rollBack(); fail('Blood bank not found.', 404); }

    $st = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE bank_id = ? AND slot_start = ? AND status = 'booked'");
    $st->execute([$bankId, $start->format('Y-m-d H:i:s')]);
    if ((int)$st->fetchColumn() >= (int)$bank['slot_capacity']) { $pdo->rollBack(); fail('Sorry, that slot just filled up. Pick another.', 409); }

    $st = $pdo->prepare("SELECT 1 FROM appointments WHERE user_id = ? AND slot_start = ? AND status = 'booked'");
    $st->execute([$user['id'], $start->format('Y-m-d H:i:s')]);
    if ($st->fetch()) { $pdo->rollBack(); fail('You already have an appointment at that time.', 409); }

    $pdo->prepare('INSERT INTO appointments (user_id, bank_id, slot_start) VALUES (?, ?, ?)')
        ->execute([$user['id'], $bankId, $start->format('Y-m-d H:i:s')]);
    $id = (int)$pdo->lastInsertId();
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}
respond(['ok' => true, 'id' => $id, 'message' => 'Booking confirmed at ' . $bank['name'] . ' on ' . $start->format('j M, g:i A') . '.'], 201);
