<?php
require 'config.php';header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['success'=>false,'message'=>'Metode request tidak valid.']);exit;}
$gameId=filter_input(INPUT_GET,'id_permainan',FILTER_VALIDATE_INT);$data=json_decode(file_get_contents('php://input'),true);
if(!$gameId||$gameId<1||!is_array($data)){http_response_code(400);echo json_encode(['success'=>false,'message'=>'ID permainan atau format data tidak valid.']);exit;}
try{
 $conn->begin_transaction();
 $insert=$conn->prepare('INSERT INTO tb_hadiah(id_permainan,nama_hadiah,qty,kategori) VALUES(?,?,?,?)');
 $update=$conn->prepare('UPDATE tb_hadiah SET nama_hadiah=?,qty=?,kategori=? WHERE no=? AND id_permainan=?');
 foreach($data as $item){
  if(!is_array($item)||!isset($item['no'],$item['nama'],$item['qty'],$item['kategori']))throw new RuntimeException('Data hadiah tidak lengkap.');
  $nama=trim((string)$item['nama']);$qty=filter_var($item['qty'],FILTER_VALIDATE_INT);$kategori=$item['kategori'];
  if($nama===''||mb_strlen($nama)>255||$qty===false||$qty<0||!in_array($kategori,['umum','utama'],true))throw new RuntimeException('Nama, stok, atau kategori hadiah tidak valid.');
  if($item['no']==='new'){$insert->bind_param('isis',$gameId,$nama,$qty,$kategori);$insert->execute();}
  else{
   $no=filter_var($item['no'],FILTER_VALIDATE_INT);if($no===false||$no<1)throw new RuntimeException('Nomor hadiah tidak valid.');
   $update->bind_param('sisii',$nama,$qty,$kategori,$no,$gameId);$update->execute();
   if($update->affected_rows===0){
    $check=$conn->prepare('SELECT no FROM tb_hadiah WHERE no=? AND id_permainan=?');
    $check->bind_param('ii',$no,$gameId);$check->execute();$check->store_result();$exists=$check->num_rows>0;$check->close();
    if(!$exists)throw new RuntimeException('Hadiah tidak ditemukan pada cabang ini.');
   }
  }
 }
 $insert->close();$update->close();$conn->commit();echo json_encode(['success'=>true,'message'=>'Semua hadiah berhasil disimpan.']);
}catch(Throwable $e){$conn->rollback();error_log('Update hadiah gagal: '.$e->getMessage());http_response_code(500);echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}
