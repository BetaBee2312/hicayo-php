<?php
require 'config.php';header('Content-Type: application/json; charset=utf-8');
$gameId=filter_input(INPUT_GET,'id_permainan',FILTER_VALIDATE_INT);
if(!$gameId||$gameId<1){http_response_code(400);echo json_encode(['success'=>false,'message'=>'ID permainan tidak valid.']);exit;}
try{
 $stmt=$conn->prepare("SELECT no,nama_hadiah,kategori FROM (SELECT no,nama_hadiah,kategori,CASE WHEN LOWER(nama_hadiah) LIKE '%voucher%' THEN 5 WHEN LOWER(kategori)='utama' THEN 3 ELSE 1 END AS bobot FROM tb_hadiah WHERE id_permainan=? AND qty>0) hadiah JOIN (SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5) multiplier ON multiplier.n<=hadiah.bobot ORDER BY RAND() LIMIT 1");
 $stmt->bind_param('i',$gameId);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();
 if(!$row){echo json_encode(['success'=>false,'message'=>'Stok hadiah cabang ini sudah habis.']);exit;}
 echo json_encode(['success'=>true,'data'=>$row]);
}catch(Throwable $e){error_log('Gagal mengambil hadiah: '.$e->getMessage());http_response_code(500);echo json_encode(['success'=>false,'message'=>'Terjadi kesalahan saat mengambil hadiah.']);}
