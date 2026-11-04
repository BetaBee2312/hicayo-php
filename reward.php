<?php
require "../config.php";
session_start();

if (!isset($_SESSION['utama_streak'])) {
    $_SESSION['utama_streak'] = 0;
}

$MAX_UTAMA_STREAK = 5;
$forceNonUtama = $_SESSION['utama_streak'] >= $MAX_UTAMA_STREAK;

header('Content-Type: application/json');

/*
  Sistem bobot hadiah:
  - Voucher: bobot 5x (paling sering muncul)
  - Hadiah utama lainnya: bobot 3x
  - Hadiah umum: bobot 1x
  
  Jika utama_streak >= 5, paksa non-utama sekali.
*/

if ($forceNonUtama) {
    $stmt = $db->prepare("
        SELECT no, nama_hadiah, kategori
        FROM (
            SELECT no, nama_hadiah, kategori,
                CASE
                    WHEN nama_hadiah LIKE '%voucher%' THEN 5
                    ELSE 1
                END AS bobot
            FROM tb_hadiah
            WHERE qty > 0
              AND kategori != 'utama'
        ) AS w
        JOIN (SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) m
          ON m.n <= w.bobot
        ORDER BY RAND()
        LIMIT 1
    ");
    $stmt->execute();
} else {
    $stmt = $db->prepare("
        SELECT no, nama_hadiah, kategori
        FROM (
            SELECT no, nama_hadiah, kategori,
                CASE
                    WHEN nama_hadiah LIKE '%voucher%' OR nama_hadiah LIKE '%Voucher%' THEN 5
                    WHEN kategori = 'utama' THEN 3
                    ELSE 1
                END AS bobot
            FROM tb_hadiah
            WHERE qty > 0
        ) AS w
        JOIN (SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) m
          ON m.n <= w.bobot
        ORDER BY RAND()
        LIMIT 1
    ");
    $stmt->execute();
}

$reward = $stmt->fetch(PDO::FETCH_ASSOC);

// Fallback
if (!$reward) {
    $stmt = $db->prepare("
        SELECT no, nama_hadiah, kategori
        FROM tb_hadiah
        WHERE qty > 0
        ORDER BY RAND()
        LIMIT 1
    ");
    $stmt->execute();
    $reward = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($reward) {
    $update = $db->prepare("UPDATE tb_hadiah SET qty = qty - 1 WHERE no = ? AND qty > 0");
    $update->execute([$reward['no']]);

    if ($update->rowCount() === 0) {
        echo json_encode(['error' => 'Hadiah habis']);
        exit;
    }

    // Update streak
    if ($reward['kategori'] === 'utama' && stripos($reward['nama_hadiah'], 'voucher') === false) {
        $_SESSION['utama_streak']++;
    } else {
        $_SESSION['utama_streak'] = 0;
    }

    echo json_encode([
        'no'          => $reward['no'],
        'nama_hadiah' => $reward['nama_hadiah'],
        'kategori'    => $reward['kategori'],
    ]);
} else {
    echo json_encode(['error' => 'Hadiah habis']);
}
?>