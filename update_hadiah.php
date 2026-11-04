<?php
require "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$no = filter_input(INPUT_POST, 'no', FILTER_VALIDATE_INT);
$nama = trim($_POST['nama_hadiah'] ?? '');
$qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);
$kategori = $_POST['kategori'] ?? '';

if (
    !$no || $no < 1 ||
    $nama === '' ||
    $qty === false || $qty === null || $qty < 0 ||
    !in_array($kategori, ['umum', 'utama'], true)
) {
    echo "<script>
        alert('Data hadiah tidak valid!');
        window.location.href = 'index.php';
    </script>";
    exit;
}

$stmt = $conn->prepare(
    "UPDATE tb_hadiah
     SET nama_hadiah = ?, qty = ?, kategori = ?
     WHERE no = ?"
);

$stmt->bind_param("sisi", $nama, $qty, $kategori, $no);
$stmt->execute();

if ($stmt->affected_rows >= 0) {
    header("Location: index.php");
    exit;
}

http_response_code(500);
echo "Gagal memperbarui hadiah.";

$stmt->close();
$conn->close();