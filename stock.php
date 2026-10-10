<?php
require __DIR__. '/bootstrap.php';
require_method('GET');
$totals = array_fill_keys(BLOOD_TYPES, 0);
foreach (db()->query('SELECT blood_type, SUM(units) AS u FROM blood_stock GROUP BY blood_type') as $r) {
    $totals[$r['blood_type']] = (int)$r['u'];
}
$out = [];
foreach ($totals as $type => $units) $out[] = ['type' => $type, 'units' => $units];
respond($out);
