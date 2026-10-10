<?php
require __DIR__ . '/bootstrap.php';
require_method('GET');

$lat = isset($_GET['lat']) && is_numeric($_GET['lat']) ? (float)$_GET['lat'] : DEFAULT_LAT;
$lng = isset($_GET['lng']) && is_numeric($_GET['lng']) ? (float)$_GET['lng'] : DEFAULT_LNG;
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) fail('Invalid coordinates.');

// Haversine formula, result in km
$st = db()->prepare(
  'SELECT id, name, area, phone,
          ROUND(6371 * ACOS(LEAST(1, COS(RADIANS(:lat)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(:lng))
                                   + SIN(RADIANS(:lat2)) * SIN(RADIANS(latitude)))), 1) AS distance_km
   FROM blood_banks ORDER BY distance_km');
$st->execute([':lat' => $lat, ':lng' => $lng, ':lat2' => $lat]);
$banks = $st->fetchAll();

$stock = [];
foreach (db()->query('SELECT bank_id, blood_type, units FROM blood_stock ORDER BY blood_type') as $r) {
    $stock[$r['bank_id']][] = ['type' => $r['blood_type'], 'units' => (int)$r['units']];
}
$out = array_map(fn($b) => [
    'id'         => (string)$b['id'],
    'name'       => $b['name'],
    'area'       => $b['area'],
    'phone'      => $b['phone'],
    'distanceKm' => (float)$b['distance_km'],
    'stock'      => $stock[$b['id']] ?? [],
], $banks);
respond($out);
