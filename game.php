<?php
require 'config.php';
$permainanId=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$permainanId || $permainanId<1){header('Location: index.php');exit;}
$gameStmt=$conn->prepare("SELECT id_permainan,judul,nama_cabang,slug,status FROM tb_permainan WHERE id_permainan=? AND status='aktif' LIMIT 1");
$gameStmt->bind_param('i',$permainanId);$gameStmt->execute();$permainan=$gameStmt->get_result()->fetch_assoc();$gameStmt->close();
if(!$permainan){http_response_code(404);exit('Permainan tidak ditemukan atau sedang nonaktif.');}
$hadiahStmt=$conn->prepare('SELECT no,nama_hadiah,qty,kategori FROM tb_hadiah WHERE id_permainan=? ORDER BY kategori DESC,no ASC');
$hadiahStmt->bind_param('i',$permainanId);$hadiahStmt->execute();$query=$hadiahStmt->get_result();
$hadiahUtama=[];$hadiahUmum=[];while($row=$query->fetch_assoc()){if($row['kategori']==='utama')$hadiahUtama[]=$row;else$hadiahUmum[]=$row;}$hadiahStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style-v30.css">
    <script src="sweetalert2@11.js"></script>
    <title><?= htmlspecialchars($permainan['judul'],ENT_QUOTES,'UTF-8') ?> | <?= htmlspecialchars($permainan['nama_cabang'],ENT_QUOTES,'UTF-8') ?></title>
  </head>
  <body>
    <button id="openEdit" class="btn-edit">Edit Hadiah</button>
      <div class="top-icons">
        <img src="gambar/icon.png" class="top-icon">
        <img src="keranjang/claw-icon.png" class="top-icon">
      </div>
    <div class="wrapper">
      <div class="collection-box pix"></div>
      <div class="claw-machine">
        <div class="box pix">
          <div class="machine-top pix">
            <div class="arm-joint pix">
              <div class="arm pix">
                <div class="claws pix"></div>
              </div>
            </div>
            <div class="rail hori pix"></div>
            <div class="rail vert pix"></div>
          </div>
          <div class="machine-bottom pix">
            <div class="floor-hole">
              <span class="floor-hole-label">HADIAH</span>
            </div>
          </div>
        </div>
        <div class="control pix">
          <div class="cover left"></div>
          <button class="horizon pix"></button>
          <button class="vertic pix"></button>
          <div class="cover right">
            <div class="instruction pix"></div>
          </div>
          <div class="cover bottom"></div>
          <div class="cover top">
            <div class="collection-arrow pix"></div>
          </div>
          <div class="collection-point pix"></div>
        </div>
      </div>
    </div>
    <div class="sign">
      <?= htmlspecialchars($permainan['judul'],ENT_QUOTES,'UTF-8') ?> · <?= htmlspecialchars($permainan['nama_cabang'],ENT_QUOTES,'UTF-8') ?>
    </div>
    <div class="running-text-container">
    <div class="running-text">
        <img src="gambar/icon.png" style="width:40px"> <?= htmlspecialchars($permainan['nama_cabang'],ENT_QUOTES,'UTF-8') ?> · Selamat bermain Hi! Cayo <img src="gambar/icon.png" style="width:40px">
    </div>
    <div class="running-text">
        <img src="gambar/icon.png" style="width: 40px"> <?= htmlspecialchars($permainan['nama_cabang'],ENT_QUOTES,'UTF-8') ?> · Selamat bermain Hi! Cayo <img src="gambar/icon.png" style="width:40px">
    </div>
</div>
<!-- Tambahkan ini di body -->
<div id="custom-alert" style="display:none;">
  <div class="overlay"></div>
  <div class="alert-box">
    <h2 id="alert-title"></h2>
    <p id="alert-text"></p>
    <button id="alert-btn">Tutup</button>
  </div>
</div>

<style>
#custom-alert .overlay {
  position: fixed;
  top:0; left:0; right:0; bottom:0;
  background: rgba(0,0,0,0.5);
  z-index: 999;
}

#custom-alert .alert-box {
  position: fixed;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  background: #fff;
  padding: 20px 30px;
  border-radius: 8px;
  text-align: center;
  z-index: 1000;
  min-width: 250px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

#custom-alert button {
  margin-top: 15px;
  padding: 8px 16px;
  background: #3085d6;
  color: #fff;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}
#custom-alert button:hover {
  background: #2573c6;
}
.btn-edit{
  position: fixed;
  top: 15px;
  right: 15px;
  padding: 10px 15px;
  background: #ff9800;
  border: none;
  color: white;
  border-radius: 6px;
  cursor: pointer;
  z-index: 2000;
}

/* ===== MODAL ===== */
.modal-edit{
  display:none;
  position:fixed;
  z-index:999;
  left:0;
  top:0;
  width:100%;
  height:100%;
  background:rgba(0,0,0,0.6);
  overflow:auto;
}

/* ===== MODAL CONTENT ===== */
.modal-content{
  background:#ffffff;
  margin:5% auto;
  padding:25px;
  width:90%;
  max-width:800px;
  border-radius:12px;
  box-shadow:0 10px 30px rgba(0,0,0,0.3);
  font-family:sans-serif;
  animation:modalFade 0.2s ease;
}

@keyframes modalFade{
  from{
    transform:translateY(-20px);
    opacity:0;
  }
  to{
    transform:translateY(0);
    opacity:1;
  }
}

/* ===== CLOSE BUTTON ===== */
#closeModal{
  float:right;
  font-size:28px;
  font-weight:bold;
  cursor:pointer;
  color:#999;
}

#closeModal:hover{
  color:#000;
}

/* ===== TABLE ===== */
.table-edit{
  width:100%;
  border-collapse:collapse;
  margin-top:10px;
}

.table-edit th{
  background:#f2f2f2;
  padding:10px;
  text-align:left;
  border-bottom:2px solid #ddd;
}

.table-edit td{
  padding:8px;
  border-bottom:1px solid #eee;
}

/* ===== INPUT ===== */
.table-edit input[type="text"],
.table-edit input[type="number"],
.table-edit select{
  width:100%;
  padding:6px;
  border:1px solid #ccc;
  border-radius:6px;
  font-size:14px;
  box-sizing:border-box;
}

/* ===== BUTTONS ===== */
.table-edit button{
  border:none;
  padding:6px 10px;
  border-radius:6px;
  cursor:pointer;
  font-size:14px;
}

.table-edit button:hover{
  opacity:0.85;
}

/* save */
.table-edit button[type="submit"]{
  background:#4CAF50;
  color:white;
}

/* delete */
.table-edit button[onclick^="hapusHadiah"]{
  background:#e74c3c;
  color:white;
}

/* ===== ADD BUTTON ===== */
.btn-add{
  background:#3498db;
  color:white;
  border:none;
  padding:10px 16px;
  border-radius:8px;
  cursor:pointer;
  font-size:14px;
}

.btn-add:hover{
  background:#2980b9;
}

.onlymobilecansee {
  position: absolute;
  bottom: 50px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 20;
  width: 92%;
  max-width: 420px;
}

/* ===== UTAMA ===== */
.hadiah-utama {
  display: flex;
  justify-content: center;
  margin-bottom: 10px;
}

.hadiah-utama .hadiah-box {
  width: 100%;
  max-width: 280px;
}

/* ===== UMUM ===== */
.hadiah-umum {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(85px,1fr));
  gap: 6px;
  padding: 0 4px;
}

/* ===== BOX ===== */
.hadiah-box {
  background: linear-gradient(135deg,#f59e0b,#fde047);
  color: rgb(0, 0, 0);
  border-radius: 8px;
  padding: 6px;
  text-align: center;
  font-size: 11px;
  line-height: 1.2;

  box-shadow: 0 3px 8px rgba(0,0,0,0.25);
}

.hadiah-box b {
  font-size: 15px;
  display: block;
  margin-top: 2px;
}

/* hadiah utama highlight */
.hadiah-box.utama {
  background: linear-gradient(135deg,#f50b0b,#ff3636);
  color: #fafafa;
  font-weight: bold;
}

</style>
<script>window.GAME_ID = <?= (int)$permainanId ?>;</script>
<script src="script-new.js"></script>

<div id="editModal" class="modal-edit">
  <div class="modal-content">

    <span id="closeModal">&times;</span>
    <h2>Edit Hadiah</h2>

    <table class="table-edit" id="hadiahTable">
      <tr>
        <th>No</th>
        <th>Nama</th>
        <th>Qty</th>
        <th>Kategori</th>
        <th>Aksi</th>
      </tr>

      <?php 
      $listStmt=$conn->prepare("SELECT * FROM tb_hadiah WHERE id_permainan=? ORDER BY no ASC");
      $listStmt->bind_param("i",$permainanId);
      $listStmt->execute();
      $listResult=$listStmt->get_result();
      while($row=$listResult->fetch_assoc()){
      ?>
      <!-- Hapus tag <form> dari sini -->
      <tr>
          <td><?= $row['no'] ?></td>
          <td>
              <!-- Tambahkan class 'input-nama' agar mudah diidentifikasi JS -->
              <input type="hidden" class="input-no" value="<?= $row['no'] ?>">
              <input type="text" class="input-nama" value="<?= htmlspecialchars($row['nama_hadiah']) ?>">
          </td>
          <td>
              <input type="number" class="input-qty" value="<?= $row['qty'] ?>" style="width:60px">
          </td>
          <td>
              <select class="input-kategori">
                  <option value="umum" <?= $row['kategori']=="umum"?"selected":"" ?>>Umum</option>
                  <option value="utama" <?= $row['kategori']=="utama"?"selected":"" ?>>Utama</option>
              </select>
          </td>
          <td>
              <!-- Tombol hapus tetap ada -->
              <button type="button" onclick="hapusHadiah(<?= $row['no'] ?>)">🗑</button>
          </td>
      </tr>
      <!-- Hapus penutup </form> -->
      <?php } $listStmt->close(); ?>
    </table>

    <br>
    <button type="button" class="btn-save-all" onclick="simpanSemua()">Simpan Semua Perubahan</button>
    <button onclick="tambahHadiah()" class="btn-add">+ Tambah Hadiah</button>

  </div>
</div>

<script>
const modal = document.getElementById("editModal");
const openBtn = document.getElementById("openEdit");
const closeBtn = document.getElementById("closeModal");

openBtn.onclick = () => modal.style.display = "block";
closeBtn.onclick = () => modal.style.display = "none";

window.onclick = function(e){
  if(e.target == modal){
    modal.style.display = "none";
  }
}

function hapusHadiah(no){

if(confirm("Hapus hadiah ini?")){

fetch("delete_hadiah.php?id_permainan="+GAME_ID,{
method:"POST",
headers:{
"Content-Type":"application/x-www-form-urlencoded"
},
body:"no="+no
})
.then(()=>location.reload())

}

}

function tambahHadiah(){

let table = document.getElementById("hadiahTable");
let row = table.insertRow(-1);

row.innerHTML = `
<td>new</td>

<td>
<input type="text" id="nama_baru" placeholder="Nama hadiah" required>
</td>

<td>
<input type="number" id="qty_baru" value="1" style="width:60px" required>
</td>

<td>
<select id="kategori_baru">
<option value="umum">Umum</option>
<option value="utama">Utama</option>
</select>
</td>

<td>
<button type="button" onclick="simpanHadiah()">✔</button>
<button type="button" onclick="this.closest('tr').remove()">✖</button>
</td>
`;

}

function simpanHadiah(){

let nama = document.getElementById("nama_baru").value;
let qty = document.getElementById("qty_baru").value;
let kategori = document.getElementById("kategori_baru").value;

fetch("add_hadiah.php?id_permainan="+GAME_ID,{
method:"POST",
headers:{
"Content-Type":"application/x-www-form-urlencoded"
},
body:`nama_hadiah=${encodeURIComponent(nama)}&qty=${qty}&kategori=${kategori}`
})
.then(()=>location.reload());

}
function simpanSemua() {
    let rows = document.querySelectorAll("#hadiahTable tr");
    let dataHadiah = [];

    // Loop mulai dari i=1 (skip header)
    for (let i = 1; i < rows.length; i++) {
        let row = rows[i];
        
        // Ambil input-input di dalam baris
        let inputNama = row.querySelector(".input-nama");
        if (inputNama) {
            // Cek apakah ini data baru atau lama
            let tdNo = row.cells[0]; 
            let no = tdNo.getAttribute('data-no') || row.querySelector(".input-no")?.value;

            dataHadiah.push({
                no: no, // Akan bernilai 'new' jika baris baru
                nama: inputNama.value,
                qty: row.querySelector(".input-qty").value,
                kategori: row.querySelector(".input-kategori").value
            });
        }
    }

    fetch("update-all-hadiah.php?id_permainan="+GAME_ID, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(dataHadiah)
    })
    .then(response => response.text())
    .then(() => {
        alert("Berhasil disimpan!");
        location.reload();
    });
}

function tambahHadiah(){
    let table = document.getElementById("hadiahTable");
    let row = table.insertRow(-1);

    // Kita buat struktur yang persis dengan baris lama
    row.innerHTML = `
        <td data-no="new">New</td> 
        <td>
            <input type="text" class="input-nama" placeholder="Nama hadiah">
        </td>
        <td>
            <input type="number" class="input-qty" value="1" style="width:60px">
        </td>
        <td>
            <select class="input-kategori">
                <option value="umum">Umum</option>
                <option value="utama">Utama</option>
            </select>
        </td>
        <td>
            <button type="button" onclick="this.closest('tr').remove()">✖</button>
        </td>
    `;
}
</script>
  </body>
</html>