<?php
/* Database configuration: environment -> config.local.php -> Laragon defaults. */
$localConfig = [];
$localPath = __DIR__ . '/config.local.php';
if (is_file($localPath)) {
    $loaded = require $localPath;
    if (is_array($loaded)) $localConfig = $loaded;
}
$db_host = getenv('DB_HOST') ?: ($localConfig['host'] ?? '127.0.0.1');
$db_user = getenv('DB_USER') ?: ($localConfig['user'] ?? 'root');
$db_password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : ($localConfig['password'] ?? '');
$db_name = getenv('DB_NAME') ?: ($localConfig['name'] ?? 'db_claw');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($db_host, $db_user, $db_password, $db_name);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi database.');
}
date_default_timezone_set('Asia/Jakarta');
