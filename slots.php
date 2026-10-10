<?php
require __DIR__ . '/bootstrap.php';
require_method('GET');

$bankId = (int)($_GET['bank_id'] ?? 0);
$date   = (string)($_GET['date'] ?? '');
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) fail('Invalid date.');

$st = db()->prepare('SELECT slot_capacity FROM blood_banks WHERE id = ?');
$st->execute([$bankId]);
$bank = $st->fetch();
if (!$bank) fail('Blood bank not found.', 404);

$st = db()->prepare("SELECT TIME(slot_start) AS t, COUNT(*) AS n FROM appointments
                     WHERE bank_id = ? AND DATE(slot_start) = ? AND status = 'booked' GROUP BY t");
$st->execute([$bankId, $date]);
$taken = array_column($st->fetchAll(), 'n', 't');

$now = new DateTime();
$out = [];
foreach (SLOT_TIMES as $label) {
    $time = slot_to_time($label);
    $startsAt = new DateTime("$date $time");
    $full = ((int)($taken[$time] ?? 0)) >= (int)$bank['slot_capacity'] || $startsAt <= $now;
    $out[] = ['time' => $label, 'full' => $full];
}
respond($out);
