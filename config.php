<?php
    // ENV
    $env = 'development';

    // CORS
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false || preg_match('/^http:\/\/192\.168\.\d+\.\d+$/', $origin)) {
        header("Access-Control-Allow-Origin: $origin");
    }

    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

    // Preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

    // Error handling
    if ($env === 'development') { ini_set('display_errors', 1); error_reporting(E_ALL); }
    else { ini_set('display_errors', 0); }

    // DB Config
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'parkori');
    define('DB_USER', 'root');
    define('DB_PASS', '');

    function getDB() {
        static $pdo = null;

        if ($pdo === null) {
            try {
                $pdo = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
                );
            } catch (PDOException $e) { error_log($e->getMessage()); die("Database connection failed"); }
        }

        return $pdo;
    }

    // Common response
    function respond($data) { header('Content-Type: application/json'); echo json_encode($data); exit; }

    // Timezone
    date_default_timezone_set('Asia/Kolkata');

    $now       = new DateTime();
    $today     = $now->format('Y-m-d');
    $timestamp = $now->format('Y-m-d H:i:s');
    $tomorrow  = (clone $now)->modify('+1 day')->format('Y-m-d');
    $yesterday = (clone $now)->modify('-1 day')->format('Y-m-d');
?>