# Hi! Cayo PHP

Claw Machine berbasis PHP dan MySQL dengan konfigurasi multi-cabang.

## Fitur
- Dashboard membuat permainan dari judul dan nama cabang.
- Daftar hadiah dinamis dengan stok dan kategori umum/utama.
- Satu mesin Claw Machine digunakan banyak cabang.
- Hadiah dan stok diisolasi berdasarkan id_permainan.
- Klaim hadiah mengurangi stok dan mencatat riwayat dalam satu transaksi database.

## Instalasi lokal (Laragon)
1. Clone repository ke C:\laragon\www\claw.
2. Buat database MySQL bernama db_claw dan pastikan tabel tb_hadiah lama tersedia.
3. Backup database, lalu jalankan database-multi-cabang.sql satu kali melalui phpMyAdmin.
4. Salin config.local.php.example menjadi config.local.php dan sesuaikan kredensial database.
5. Buka http://localhost/claw/.

Default config.php memakai root tanpa password untuk instalasi Laragon baru. Jangan gunakan default tersebut di production.

## Migrasi database
database-multi-cabang.sql membuat tb_permainan, menambahkan id_permainan pada tb_hadiah, menghubungkan hadiah lama ke permainan awal Hi! Cayo / Cabang Lama agar stok lama tetap ada, dan membuat tabel riwayat. Migrasi dijalankan satu kali. Backup database terlebih dahulu. Jika kolom id_permainan sudah ada, lewati perintah ALTER TABLE yang menambah kolom tersebut.

## Tabel
- tb_permainan: identitas dan status permainan/cabang.
- tb_hadiah: hadiah dan stok per permainan.
- tb_riwayat_hadiah: catatan hadiah yang berhasil diklaim.

## Menambah cabang
Buka dashboard, isi judul permainan, nama cabang, dan daftar hadiah, lalu klik Generate permainan.

## Menjalankan game
Halaman game memakai game.php?id=ID_PERMAINAN. Semua endpoint hadiah menerima id_permainan agar stok antar cabang tidak tercampur.

## Konfigurasi production
Gunakan environment variables DB_HOST, DB_USER, DB_PASSWORD, DB_NAME atau file lokal config.local.php. File config.local.php diabaikan Git.

## Keamanan
Kredensial database yang sebelumnya ada di config.php sudah dikeluarkan dari source code. Ganti password database yang pernah terpublikasi dan gunakan akun database production dengan hak akses minimum.
