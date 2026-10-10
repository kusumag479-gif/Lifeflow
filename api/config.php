<?php
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$name = getenv('DB_NAME') ?: 'lifeflow';
define('DB_DSN',  "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4");
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_SSL',  getenv('DB_SSL') === '1');
const APP_TZ  = 'Asia/Kolkata';

const MIN_PASSWORD_LEN = 6;
const DONATION_GAP_DAYS = 90;
const BOOKING_WINDOW_DAYS = 60;
const SLOT_TIMES = ['9:00 AM','9:30 AM','10:00 AM','10:30 AM','11:00 AM','11:30 AM','2:00 PM','2:30 PM','3:00 PM','3:30 PM'];
const BLOOD_TYPES = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
const DEFAULT_LAT = 12.9716;
const DEFAULT_LNG = 77.5946;
