<?php
// GET: my upcoming appointments.  POST {id}: cancel one of mine.
require __DIR__ . '/bootstrap.php';
$user = require_login('donor');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_method('POST');
    $id = (int)(body()['id'] ?? 0);
    $st = db()->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'booked' AND slot_start > NOW()");
    $st->execute([$id, $user['id']]);
    if (!$st->rowCount()) fail('Appointment not found or cannot be cancelled.', 404);
    respond(['ok' => true]);
}
require_method('GET');
$st = db()->prepare("SELECT a.id, b.name AS bank, b.area, a.slot_start AS slotStart, a.status
                     FROM appointments a JOIN blood_banks b ON b.id = a.bank_id
                     WHERE a.user_id = ? AND a.status = 'booked' AND a.slot_start > NOW() ORDER BY a.slot_start");
$st->execute([$user['id']]);
respond($st->fetchAll());
