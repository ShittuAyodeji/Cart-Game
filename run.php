<?php
include 'database/connect.php';
if(isset($_POST['name'])){
$name=$_POST['name'];
$code=$_POST['code'];
$mobile=$_POST['mobile'];
$score=$_POST['score'];
$unique_id=bin2hex(openssl_random_pseudo_bytes(20));
$company_id=0;
$total_score=0;
$date = date("Y-m-d H:i:s");
$sql = $dbc->prepare("SELECT * FROM codes WHERE code=? AND code_status='NO'");
$mql = $dbc->prepare("SELECT * FROM codes WHERE mobile=? LIMIT 1");
$company_id = getFields($sql,'company_id',$code);
$existing_id = getFields($sql,'unique_id',$mobile);
$unique_id= $existing_id!='' ? $existing_id : $unique_id;
$ldb = $dbc->prepare("SELECT SUM(latest_score) AS total FROM leaderboard WHERE mobile=?");
$total_score = getFields($ldb,'total',$mobile);
$total_score= $total_score!='' ? $total_score+$score : $score;
$insert = $dbc->prepare("INSERT INTO leaderboard (player_id,unique_id,company_id,player_name,code,mobile,latest_score,total_score) 
						VALUES (?,?,?,?,?,?,?,?)");
$insert->bind_param("ssssssii",$mobile,$unique_id,$company_id,$name,$code,$mobile,$score,$total_score);
$response=$insert->execute();
if($response){
$update = $dbc->prepare("UPDATE codes SET code_status='NO', player_id=?, date_used=? WHERE code=?");	
$update->bind_param("sss",$mobile,$date,$code);
$updated=$update->execute();
if($updated){
	echo "set|".$unique_id;
}
}
}


function getFields($query,$request,$param){
$field='';
$query->bind_param("s",$param);
$query->execute();
$result=$query->get_result();
$num_rows =$result->num_rows;
if($num_rows>0){
	$row=$result->fetch_assoc();
	$field=$row[$request];
}
return $field;	
}
?>