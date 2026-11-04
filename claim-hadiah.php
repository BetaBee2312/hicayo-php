<?php
require 'config.php';header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['success'=>false,'message'=>'Metode request tidak valid.']);exit;}
$gameId=filter_input(INPUT_POST,'id_permainan',FILTER_VALIDATE_INT);
if(!$gameId||$gameId<1){http_response_code(400);echo json_encode(['success'=>false,'message'=>'ID permainan tidak valid.']);exit;}
try{
 $conn->begin_transaction();
 $stmt=$conn->prepare('SELECT no,nama_hadiah,kategori FROM tb_hadiah WHERE id_permainan=? AND qty>0 FOR UPDATE');
 $stmt->bind_param('i',$gameId);$stmt->execute();$result=$stmt->get_result();$pool=[];$totalWeight=0;
 while($row=$result->fetch_assoc()){$name=strtolower($row['nama_hadiah']);$weight=(strpos($name,'voucher')!==false)?5:(($row['kategori']==='utama')?3:1);$pool[]=['row'=>$row,'weight'=>$weight];$totalWeight+=$weight;}
 $stmt->close();
 if(!$pool){$conn->rollback();echo json_encode(['success'=>false,'message'=>'Stok hadiah cabang ini sudah habis.']);exit;}
 $ticket=random_int(1,$totalWeight);$selected=null;
 foreach($pool as $candidate){$ticket-=$candidate['weight'];if($ticket<=0){$selected=$candidate['row'];break;}}
 if(!$selected)throw new RuntimeException('Gagal memilih hadiah.');
 $rewardNo=(int)$selected['no'];
 $update=$conn->prepare('UPDATE tb_hadiah SET qty=qty-1 WHERE no=? AND id_permainan=? AND qty>0');
 $update->bind_param('ii',$rewardNo,$gameId);$update->execute();if($update->affected_rows!==1)throw new RuntimeException('Stok berubah saat proses klaim.');$update->close();
 $history=$conn->prepare('INSERT INTO tb_riwayat_hadiah(id_permainan,id_hadiah,nama_hadiah,kategori) VALUES(?,?,?,?)');
 $history->bind_param('iiss',$gameId,$rewardNo,$selected['nama_hadiah'],$selected['kategori']);$history->execute();$history->close();
 $conn->commit();echo json_encode(['success'=>true,'data'=>['no'=>$rewardNo,'nama_hadiah'=>$selected['nama_hadiah'],'kategori'=>$selected['kategori']]]);
}catch(Throwable $e){$conn->rollback();error_log('Klaim hadiah gagal: '.$e->getMessage());http_response_code(500);echo json_encode(['success'=>false,'message'=>'Hadiah gagal diklaim. Coba sekali lagi.']);}
