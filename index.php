<?php
require 'config.php';
$games = [];
$migrationReady = true;
try {
    $result = $conn->query("
        SELECT p.id_permainan,p.judul,p.nama_cabang,p.slug,p.status,p.created_at,
               COUNT(h.no) AS jumlah_jenis_hadiah,COALESCE(SUM(h.qty),0) AS total_stok
        FROM tb_permainan p
        LEFT JOIN tb_hadiah h ON h.id_permainan=p.id_permainan
        GROUP BY p.id_permainan,p.judul,p.nama_cabang,p.slug,p.status,p.created_at
        ORDER BY p.created_at DESC,p.id_permainan DESC
    ");
    while ($row=$result->fetch_assoc()) $games[]=$row;
} catch (mysqli_sql_exception $e) {
    $migrationReady=false;
    error_log('Dashboard multi-cabang belum siap: '.$e->getMessage());
}
function e($value) { return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hi! Cayo | Kelola Permainan</title>
<style>
:root{color-scheme:dark;--bg:#10131b;--line:#30394b;--text:#f5f7fb;--muted:#9ca8ba;--accent:#ffc247}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 10% 0%,#222b3e 0,transparent 34%),var(--bg);color:var(--text);font:15px/1.5 Inter,Segoe UI,Arial,sans-serif;min-height:100vh}
.shell{width:min(1180px,calc(100% - 36px));margin:auto;padding:34px 0 60px}.top{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:34px}
.brand{display:flex;align-items:center;gap:12px;font-weight:800}.brand-mark{width:42px;height:42px;border-radius:13px;background:linear-gradient(135deg,#ffe18e,#f5a623);display:grid;place-items:center;color:#222;font-size:21px}
.eyebrow{color:var(--accent);font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.14em}h1{font-size:clamp(27px,4vw,40px);line-height:1.1;margin:8px 0}h2{font-size:19px;margin:0 0 5px}p{color:var(--muted);margin:0}
.layout{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,1.3fr);gap:22px;align-items:start}.panel{background:linear-gradient(145deg,#1c2330,#171c27);border:1px solid var(--line);border-radius:20px;padding:23px;box-shadow:0 18px 50px #0002}
.panel-head{display:flex;justify-content:space-between;gap:12px;margin-bottom:20px}.field{display:grid;gap:7px;margin:15px 0}label{font-size:13px;font-weight:700;color:#dce3ef}
input,select{width:100%;min-width:0;padding:12px 13px;border-radius:10px;border:1px solid #394457;background:#101621;color:var(--text);font:inherit;outline:none}
input:focus,select:focus{border-color:var(--accent);box-shadow:0 0 0 3px #ffc24720}.gift-list{display:grid;gap:10px;margin-top:12px}
.gift-row{display:grid;grid-template-columns:minmax(0,1.5fr) 90px 115px 38px;gap:8px;align-items:center}.gift-row input,.gift-row select{padding:10px 9px}
.icon-btn{height:42px;border:1px solid #4b3540;background:#30202a;color:#ff9a9a;border-radius:10px;font-size:20px;cursor:pointer}
.btn{border:0;border-radius:11px;padding:12px 16px;font:inherit;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;text-decoration:none}.btn-primary{background:linear-gradient(135deg,#ffdb82,#ffb42e);color:#251b08}.btn-secondary{border:1px solid var(--line);background:#222b3b;color:var(--text)}
.btn-block{width:100%;margin-top:18px}.add-gift{margin-top:12px;width:100%}.game-list{display:grid;gap:12px;margin-top:18px}
.game-card{border:1px solid var(--line);border-radius:15px;padding:16px;background:#171d28}.game-top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}
.game-title{font-weight:800;font-size:16px}.game-branch{color:var(--muted);font-size:13px;margin-top:2px}.stats{display:flex;gap:17px;flex-wrap:wrap;color:var(--muted);font-size:12px;margin:14px 0}.stats b{color:#fff;font-size:14px;margin-right:4px}
.actions{display:flex;gap:8px;flex-wrap:wrap}.status{font-size:11px;border-radius:999px;padding:5px 9px;background:#163d32;color:#78e6b9;font-weight:800}
.empty{padding:34px 16px;text-align:center;border:1px dashed #465167;border-radius:14px;color:var(--muted)}.notice{padding:14px 16px;border-radius:12px;background:#392d18;border:1px solid #755b2c;color:#ffe1a1;margin:18px 0}.count{font-size:12px;color:var(--muted)}
@media(max-width:850px){.layout{grid-template-columns:1fr}.shell{padding-top:22px}.gift-row{grid-template-columns:minmax(0,1fr) 76px 100px 36px}}
@media(max-width:460px){.gift-row{grid-template-columns:1fr 75px 90px 34px}.gift-row input,.gift-row select{font-size:12px;padding:10px 6px}.panel{padding:17px}.shell{width:calc(100% - 22px)}}
</style></head><body><div class="shell">
<header class="top"><div class="brand"><div class="brand-mark">✦</div><div>HI! CAYO<div class="count">CLAW MACHINE MANAGER</div></div></div><div class="eyebrow">Multi-Cabang</div></header>
<section style="margin-bottom:25px"><div class="eyebrow">Dashboard permainan</div><h1>Kelola mesin capit<br>semua cabang.</h1><p>Buat konfigurasi hadiah per cabang, lalu jalankan mesin dengan satu sistem yang sama.</p></section>
<?php if (!$migrationReady): ?>
<div class="notice">Database multi-cabang belum disiapkan. Backup database, lalu jalankan <strong>database-multi-cabang.sql</strong> melalui phpMyAdmin. Setelah itu muat ulang halaman ini.</div>
<?php else: ?><div class="layout">
<section class="panel"><div class="panel-head"><div><h2>Buat permainan baru</h2><p>Isi identitas cabang dan daftar hadiah.</p></div><span class="eyebrow">01 / Setup</span></div>
<form action="save_game.php" method="POST">
<div class="field"><label for="judul">Judul permainan</label><input id="judul" name="judul" maxlength="120" placeholder="Contoh: Hi! Cayo" required></div>
<div class="field"><label for="nama_cabang">Nama cabang</label><input id="nama_cabang" name="nama_cabang" maxlength="160" placeholder="Contoh: Yomart Cibaduyut" required></div>
<div style="display:flex;justify-content:space-between;align-items:end;gap:10px;margin-top:22px"><div><h2>Daftar hadiah</h2><p class="count">Stok dan kategori setiap hadiah.</p></div><span class="eyebrow">02 / Rewards</span></div>
<div id="giftList" class="gift-list"><div class="gift-row">
<input name="hadiah[0][nama_hadiah]" maxlength="255" placeholder="Nama hadiah" required>
<input name="hadiah[0][qty]" type="number" min="0" value="1" aria-label="Jumlah stok" required>
<select name="hadiah[0][kategori]" aria-label="Kategori hadiah"><option value="umum">Umum</option><option value="utama">Utama</option></select>
<button class="icon-btn" type="button" onclick="removeGift(this)" aria-label="Hapus hadiah">×</button>
</div></div>
<button class="btn btn-secondary add-gift" type="button" onclick="addGift()">＋ Tambah hadiah</button>
<button class="btn btn-primary btn-block" type="submit">Generate permainan →</button></form></section>
<section class="panel"><div class="panel-head"><div><h2>Permainan terdaftar</h2><p>Setiap cabang memiliki stok terpisah.</p></div><span class="eyebrow">03 / Manage</span></div>
<?php if (!$games): ?><div class="empty">Belum ada permainan. Buat konfigurasi cabang pertama di formulir sebelah.</div>
<?php else: ?><div class="game-list"><?php foreach($games as $game): ?>
<article class="game-card"><div class="game-top"><div><div class="game-title"><?= e($game['judul']) ?></div><div class="game-branch"><?= e($game['nama_cabang']) ?></div></div><span class="status"><?= $game['status']==='aktif'?'AKTIF':'NONAKTIF' ?></span></div>
<div class="stats"><span><b><?= (int)$game['jumlah_jenis_hadiah'] ?></b> jenis hadiah</span><span><b><?= (int)$game['total_stok'] ?></b> total stok</span><span><?= e($game['slug']) ?></span></div>
<div class="actions"><a class="btn btn-primary" href="game.php?id=<?= (int)$game['id_permainan'] ?>">▶ Mainkan</a><a class="btn btn-secondary" href="game.php?id=<?= (int)$game['id_permainan'] ?>#editModal">Atur hadiah</a></div></article>
<?php endforeach; ?></div><?php endif; ?></section></div><?php endif; ?>
</div><script>
let giftIndex=1;
function addGift(){const row=document.createElement('div');row.className='gift-row';row.innerHTML=
'<input name="hadiah['+giftIndex+'][nama_hadiah]" maxlength="255" placeholder="Nama hadiah" required>'+
'<input name="hadiah['+giftIndex+'][qty]" type="number" min="0" value="1" aria-label="Jumlah stok" required>'+
'<select name="hadiah['+giftIndex+'][kategori]" aria-label="Kategori hadiah"><option value="umum">Umum</option><option value="utama">Utama</option></select>'+
'<button class="icon-btn" type="button" onclick="removeGift(this)" aria-label="Hapus hadiah">×</button>';
document.getElementById('giftList').appendChild(row);giftIndex++;}
function removeGift(button){const rows=document.querySelectorAll('#giftList .gift-row');if(rows.length<=1){rows[0].querySelector('input').value='';rows[0].querySelectorAll('input')[1].value='1';rows[0].querySelector('select').value='umum';return;}button.closest('.gift-row').remove();}
</script></body></html>
