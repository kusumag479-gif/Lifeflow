<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

date_default_timezone_set(APP_TZ);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

set_exception_handler(function (Throwable $e) {
    error_log('[LifeFlow] ' . $e);
    http_response_code(500);
    echo json_encode(['error' => 'Server error. Please try again.']);
});

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '+05:30'"); // keep NOW() in line with APP_TZ
    }
    return $pdo;
}

function respond(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function fail(string $msg, int $code = 400): never { respond(['error' => $msg], $code); }

/** Enforce HTTP method. POSTs must be JSON, which blocks cross-site form posts (basic CSRF defence). */
function require_method(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== $method) fail('Method not allowed', 405);
    if ($method === 'POST' && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
        fail('Content-Type must be application/json', 415);
    }
}
function body(): array {
    $d = json_decode(file_get_contents('php://input') ?: '', true);
    return is_array($d) ? $d : [];
}
function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    $st = db()->prepare('SELECT id, role, name, email, blood_type, city, last_donation_date FROM users WHERE id = ?');
    $st->execute([$_SESSION['uid']]);
    return $st->fetch() ?: null;
}
function require_login(?string $role = null): array {
    $u = current_user();
    if (!$u) fail('Please log in first.', 401);
    if ($role && $u['role'] !== $role) fail('Not allowed.', 403);
    return $u;
}
function valid_blood_type(?string $t): bool { return in_array($t, BLOOD_TYPES, true); }

/** "9:00 AM" -> "09:00:00" or null if not an allowed slot */
function slot_to_time(string $label): ?string {
    if (!in_array($label, SLOT_TIMES, true)) return null;
    return date('H:i:s', strtotime($label));
}
