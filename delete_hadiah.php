<?php
require 'config.php';header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['success'=>false,'message'=>'Metode request tidak valid.']);exit;}
$gameId=filter_input(INPUT_GET,'id_permainan',FILTER_VALIDATE_INT);$no=filter_input(INPUT_POST,'no',FILTER_VALIDATE_INT);
if(!$gameId||$gameId<1||!$no||$no<1){http_response_code(400);echo json_encode(['success'=>false,'message'=>'ID permainan atau hadiah tidak valid.']);exit;}
$stmt=$conn->prepare('DELETE FROM tb_hadiah WHERE no=? AND id_permainan=?');$stmt->bind_param('ii',$no,$gameId);$stmt->execute();
if($stmt->affected_rows>0)echo json_encode(['success'=>true,'message'=>'Hadiah berhasil dihapus.']);
else{http_response_code(404);echo json_encode(['success'=>false,'message'=>'Hadiah tidak ditemukan pada permainan ini.']);}
