<?php
require "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("Metode request tidak valid.");
}

$nama = trim($_POST['nama_hadiah'] ?? '');
$qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);
$kategori = trim($_POST['kategori'] ?? '');

if ($nama === '' || $qty === false || $qty === null || $qty < 0 || $kategori === '') {
    exit("Data hadiah tidak valid.");
}

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO tb_hadiah (nama_hadiah, qty, kategori)
     VALUES (?, ?, ?)"
);

mysqli_stmt_bind_param($stmt, "sis", $nama, $qty, $kategori);

if (mysqli_stmt_execute($stmt)) {
    echo "success";
} else {
    http_response_code(500);
    echo "Gagal menyimpan hadiah.";
}

mysqli_stmt_close($stmt);
