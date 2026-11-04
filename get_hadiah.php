<?php
require("config.php");

header('Content-Type: application/json; charset=utf-8');

$sql = "
    SELECT no, nama_hadiah, kategori
    FROM (
        SELECT
            no,
            nama_hadiah,
            kategori,
            CASE
                WHEN LOWER(nama_hadiah) LIKE '%voucher%' THEN 5
                WHEN LOWER(kategori) = 'utama' THEN 3
                ELSE 1
            END AS bobot
        FROM tb_hadiah
        WHERE qty > 0
    ) AS hadiah
    JOIN (
        SELECT 1 AS n
        UNION ALL SELECT 2
        UNION ALL SELECT 3
        UNION ALL SELECT 4
        UNION ALL SELECT 5
    ) AS multiplier
        ON multiplier.n <= hadiah.bobot
    ORDER BY RAND()
    LIMIT 1
";

try {
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    if (!$row) {
        echo json_encode([
            "success" => false,
            "message" => "Tidak ada hadiah dengan stok tersedia."
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "data" => $row
    ]);

} catch (mysqli_sql_exception $e) {
    error_log("Undian hadiah gagal: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Terjadi kesalahan saat mengambil hadiah."
    ]);
}
?>