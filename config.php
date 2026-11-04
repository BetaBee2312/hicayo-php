<?php
$db_host     = "localhost";
$db_user     = "marketing";
$db_password = "marketing2025";
$db_name     = "db_claw";

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
date_default_timezone_set('Asia/Jakarta');
?>
