-- HICAYO PHP: migrasi satu kali ke sistem multi-cabang
-- Backup db_claw sebelum menjalankan file ini melalui phpMyAdmin.
-- Jika kolom id_permainan sudah pernah ditambahkan, lewati ALTER TABLE pertama.
CREATE TABLE IF NOT EXISTS tb_permainan (
    id_permainan INT UNSIGNED NOT NULL AUTO_INCREMENT,
    judul VARCHAR(120) NOT NULL,
    nama_cabang VARCHAR(160) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_permainan),
    UNIQUE KEY uq_permainan_slug (slug),
    KEY idx_permainan_cabang (nama_cabang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jalankan hanya satu kali pada database lama.
ALTER TABLE tb_hadiah ADD COLUMN id_permainan INT UNSIGNED NULL AFTER no;

-- Hadiah lama dipindahkan ke permainan awal agar stok tidak hilang.
INSERT INTO tb_permainan (judul,nama_cabang,slug,status)
SELECT 'Hi! Cayo','Cabang Lama','cabang-lama','aktif'
WHERE NOT EXISTS (SELECT 1 FROM tb_permainan WHERE slug='cabang-lama');
SET @legacy_game_id = (SELECT id_permainan FROM tb_permainan WHERE slug='cabang-lama' LIMIT 1);
UPDATE tb_hadiah SET id_permainan=@legacy_game_id WHERE id_permainan IS NULL;

ALTER TABLE tb_hadiah
    ENGINE=InnoDB,
    MODIFY COLUMN id_permainan INT UNSIGNED NOT NULL,
    ADD KEY idx_hadiah_game_stock (id_permainan,qty),
    ADD CONSTRAINT fk_hadiah_permainan FOREIGN KEY (id_permainan)
        REFERENCES tb_permainan(id_permainan) ON UPDATE CASCADE ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS tb_riwayat_hadiah (
    id_riwayat BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_permainan INT UNSIGNED NOT NULL,
    id_hadiah INT NULL,
    nama_hadiah VARCHAR(255) NOT NULL,
    kategori ENUM('umum','utama') NOT NULL DEFAULT 'umum',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_riwayat),
    KEY idx_riwayat_game_date (id_permainan,created_at),
    CONSTRAINT fk_riwayat_permainan FOREIGN KEY (id_permainan)
        REFERENCES tb_permainan(id_permainan) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
