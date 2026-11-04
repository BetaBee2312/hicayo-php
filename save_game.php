<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit('Metode request tidak valid.'); }
$judul=trim($_POST['judul']??''); $cabang=trim($_POST['nama_cabang']??''); $hadiah=$_POST['hadiah']??[];
if ($judul==='' || mb_strlen($judul)>120 || $cabang==='' || mb_strlen($cabang)>160) { http_response_code(422); exit('Judul atau nama cabang tidak valid.'); }
if (!is_array($hadiah) || count($hadiah)<1 || count($hadiah)>100) { http_response_code(422); exit('Tambahkan minimal satu dan maksimal 100 hadiah.'); }
$valid=[];
foreach($hadiah as $item){
 if(!is_array($item)) continue;
 $nama=trim((string)($item['nama_hadiah']??'')); $qty=filter_var($item['qty']??null,FILTER_VALIDATE_INT); $kategori=$item['kategori']??'umum';
 if($nama==='' || mb_strlen($nama)>255 || $qty===false || $qty<0 || !in_array($kategori,['umum','utama'],true)){http_response_code(422);exit('Nama hadiah, stok, atau kategori tidak valid.');}
 $valid[]=['nama'=>$nama,'qty'=>$qty,'kategori'=>$kategori];
}
if(!$valid){http_response_code(422);exit('Minimal satu hadiah wajib diisi.');}
$baseSlug=trim(preg_replace('/[^a-z0-9]+/','-',strtolower($cabang)),'-'); if($baseSlug==='')$baseSlug='cabang';
try{
 $conn->begin_transaction();$slug=$baseSlug;$suffix=2;
 $check=$conn->prepare('SELECT id_permainan FROM tb_permainan WHERE slug=? LIMIT 1');
 while(true){$check->bind_param('s',$slug);$check->execute();$check->store_result();$exists=$check->num_rows>0;$check->free_result();if(!$exists)break;$slug=$baseSlug.'-'.$suffix++;}
 $check->close();
 $create=$conn->prepare("INSERT INTO tb_permainan(judul,nama_cabang,slug,status) VALUES(?,?,?,'aktif')");
 $create->bind_param('sss',$judul,$cabang,$slug);$create->execute();$gameId=(int)$conn->insert_id;$create->close();
 $insert=$conn->prepare('INSERT INTO tb_hadiah(id_permainan,nama_hadiah,qty,kategori) VALUES(?,?,?,?)');
 foreach($valid as $item){$insert->bind_param('isis',$gameId,$item['nama'],$item['qty'],$item['kategori']);$insert->execute();}
 $insert->close();$conn->commit();header('Location: index.php?created=1');exit;
}catch(Throwable $e){$conn->rollback();error_log('Gagal membuat permainan: '.$e->getMessage());http_response_code(500);exit('Gagal membuat permainan. Periksa skema database dan log server.');}
