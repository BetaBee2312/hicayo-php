<?php
require "config.php";

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Metode request tidak valid."
    ]);
    exit;
}

$no = filter_input(INPUT_POST, 'no', FILTER_VALIDATE_INT);

if (!$no || $no < 1) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Nomor hadiah tidak valid."
    ]);
    exit;
}

$stmt = $conn->prepare(
    "DELETE FROM tb_hadiah WHERE no = ?"
);

$stmt->bind_param("i", $no);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode([
        "success" => true,
        "message" => "Hadiah berhasil dihapus."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Hadiah tidak ditemukan."
    ]);
}

$stmt->close();
$conn->close();