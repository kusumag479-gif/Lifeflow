<?php
declare(strict_types=1);
require __DIR__. '/config.php';

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
        $opts = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        if (DB_SSL) {
            $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, $opts);
        $pdo->exec("SET time_zone = '+05:30'"); // keep NOW() in line with APP_TZ
    }
    return $pdo;
    function require_method(string
    $method):void{
        $actual =
    strtoupper($_SERVER['REQUEST_METHOD']
    ?? '');
        if ($actual !==
    strtoupper($method)) {
            header('Allow: ' .
    strtoupper($method));
            http_response_code(405);
            echo json_encode([
               'error' => 'Method not
            allowed.'
                ]);
                exit;
        }
    }
        
}
