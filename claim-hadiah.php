<?php
include("config.php");

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
    "UPDATE tb_hadiah
     SET qty = qty - 1
     WHERE no = ? AND qty > 0"
);

$stmt->bind_param("i", $no);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Gagal mengurangi stok hadiah."
    ]);
    exit;
}

if ($stmt->affected_rows > 0) {
    echo json_encode([
        "success" => true,
        "message" => "Stok hadiah berhasil dikurangi."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Hadiah tidak ditemukan atau stok habis."
    ]);
}

$stmt->close();
$conn->close();