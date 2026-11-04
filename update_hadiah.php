<?php
require 'config.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: index.php');exit;}
$gameId=filter_input(INPUT_GET,'id_permainan',FILTER_VALIDATE_INT);
$no=filter_input(INPUT_POST,'no',FILTER_VALIDATE_INT);
$nama=trim($_POST['nama_hadiah']??'');
$qty=filter_input(INPUT_POST,'qty',FILTER_VALIDATE_INT);
$kategori=$_POST['kategori']??'';
if(!$gameId||$gameId<1||!$no||$no<1||$nama===''||$qty===false||$qty===null||$qty<0||!in_array($kategori,['umum','utama'],true)){http_response_code(422);exit('Data hadiah tidak valid.');}
$stmt=$conn->prepare('UPDATE tb_hadiah SET nama_hadiah=?,qty=?,kategori=? WHERE no=? AND id_permainan=?');
$stmt->bind_param('sisii',$nama,$qty,$kategori,$no,$gameId);$stmt->execute();
header('Location: game.php?id='.$gameId);exit;
