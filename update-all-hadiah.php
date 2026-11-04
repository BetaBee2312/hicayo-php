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

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Format data tidak valid."
    ]);
    exit;
}

try {
    $conn->begin_transaction();

    $stmtInsert = $conn->prepare(
        "INSERT INTO tb_hadiah (nama_hadiah, qty, kategori)
         VALUES (?, ?, ?)"
    );

    $stmtUpdate = $conn->prepare(
        "UPDATE tb_hadiah
         SET nama_hadiah = ?, qty = ?, kategori = ?
         WHERE no = ?"
    );

    foreach ($data as $item) {
        if (
            !is_array($item) ||
            !isset($item['no'], $item['nama'], $item['qty'], $item['kategori'])
        ) {
            throw new Exception("Struktur data hadiah tidak lengkap.");
        }

        $nama = trim((string) $item['nama']);
        $qty = filter_var($item['qty'], FILTER_VALIDATE_INT);
        $kategori = $item['kategori'];
        $no = $item['no'];

        if ($nama === '' || mb_strlen($nama) > 255) {
            throw new Exception("Nama hadiah tidak valid.");
        }

        if ($qty === false || $qty < 0) {
            throw new Exception("Jumlah hadiah harus berupa bilangan bulat nonnegatif.");
        }

        if (!in_array($kategori, ['umum', 'utama'], true)) {
            throw new Exception("Kategori hadiah tidak valid.");
        }

        if ($no === 'new') {
            $stmtInsert->bind_param(
                "sis",
                $nama,
                $qty,
                $kategori
            );

            $stmtInsert->execute();
        } else {
            $no = filter_var($no, FILTER_VALIDATE_INT);

            if ($no === false || $no < 1) {
                throw new Exception("Nomor hadiah tidak valid.");
            }

            $stmtUpdate->bind_param(
                "sisi",
                $nama,
                $qty,
                $kategori,
                $no
            );

            $stmtUpdate->execute();

            if ($stmtUpdate->affected_rows === 0) {
                $check = $conn->prepare(
                    "SELECT no FROM tb_hadiah WHERE no = ?"
                );
                $check->bind_param("i", $no);
                $check->execute();
                $check->store_result();

                if ($check->num_rows === 0) {
                    $check->close();
                    throw new Exception(
                        "Hadiah dengan nomor {$no} tidak ditemukan."
                    );
                }

                $check->close();
            }
        }
    }

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Semua perubahan hadiah berhasil disimpan."
    ]);

} catch (Throwable $e) {
    $conn->rollback();

    error_log("Update hadiah gagal: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Gagal menyimpan perubahan hadiah."
    ]);
}
?>