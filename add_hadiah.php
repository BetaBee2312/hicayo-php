<?php
require 'config.php';header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['success'=>false,'message'=>'Metode request tidak valid.']);exit;}
$gameId=filter_input(INPUT_GET,'id_permainan',FILTER_VALIDATE_INT);$nama=trim($_POST['nama_hadiah']??'');$qty=filter_input(INPUT_POST,'qty',FILTER_VALIDATE_INT);$kategori=$_POST['kategori']??'';
if(!$gameId||$gameId<1||$nama===''||mb_strlen($nama)>255||$qty===false||$qty===null||$qty<0||!in_array($kategori,['umum','utama'],true)){http_response_code(422);echo json_encode(['success'=>false,'message'=>'Data hadiah tidak valid.']);exit;}
$stmt=$conn->prepare('INSERT INTO tb_hadiah(id_permainan,nama_hadiah,qty,kategori) VALUES(?,?,?,?)');$stmt->bind_param('isis',$gameId,$nama,$qty,$kategori);$stmt->execute();
echo json_encode(['success'=>true,'message'=>'Hadiah berhasil ditambahkan.']);
