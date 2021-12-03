<?php
include 'database/connect.php';
$start=0;
$page=100;
$player_score='';
$player_position='';
$player='';
$company_id=$_POST['company_id'];

if(isset($_POST['player'])){
$player=$_POST['player'];
$player_score = player_score($dbc,$company_id,$player);
$player_position = player_position($dbc,$company_id,$player);
}
$data = array();
$query_rows="SELECT lbd1.* FROM leaderboard lbd1 LEFT JOIN leaderboard lbd2 ON (lbd1.player_id = lbd2.player_id AND lbd1.id < lbd2.id) WHERE lbd2.company_id is NULL AND lbd1.company_id='$company_id' ORDER BY total_score DESC";
$sql_rows = $dbc->prepare($query_rows);
$sql_rows->execute();
$res=$sql_rows->get_result();
$all_rows =$res->num_rows;

if($all_rows<=50){
	$query="SELECT lbd1.* FROM leaderboard lbd1 LEFT JOIN leaderboard lbd2 ON (lbd1.player_id = lbd2.player_id AND lbd1.id < lbd2.id) WHERE lbd2.company_id is NULL AND lbd1.company_id='$company_id' ORDER BY total_score DESC LIMIT 50";
	$sql = $dbc->prepare($query) or die(mysqli_error($dbc));
}else{
	$saveAverages=array();
	$result_average=average($dbc,$company_id,"");
	$query="SELECT lbd1.* FROM leaderboard lbd1 LEFT JOIN leaderboard lbd2 ON (lbd1.player_id = lbd2.player_id AND lbd1.id < lbd2.id) WHERE lbd2.company_id is NULL AND (lbd1.company_id='$company_id' AND lbd1.total_score>=$result_average) ORDER BY total_score DESC";
	$sql = $dbc->prepare($query) or die(mysqli_error($dbc));
	$pre_sql=$sql;
	$pre_sql->execute();
	$pre_result = $pre_sql->get_result();
	$new_all_rows=$pre_result->num_rows;
	do{
		$result_average=average($dbc,$company_id,$result_average);
		$query="SELECT lbd1.* FROM leaderboard lbd1 LEFT JOIN leaderboard lbd2 ON (lbd1.player_id = lbd2.player_id AND lbd1.id < lbd2.id) WHERE lbd2.company_id is NULL AND (lbd1.company_id='$company_id' AND lbd1.total_score>=$result_average) ORDER BY total_score DESC";
		$sql = $dbc->prepare($query) or die(mysqli_error($dbc));
		$new_all_rows=rows($dbc, $company_id, $result_average);
		array_push($saveAverages);
	}while($new_all_rows >50 && end($saveAverages)!=$result_average);
}
$sql->execute();
$result=$sql->get_result();
$num_rows =$result->num_rows;
if($num_rows>0){
	$i=1;
	$currentScore=0;
	while($rows= $result->fetch_assoc()){
		$name=$rows['player_name'];
		$latest_score=$rows['latest_score'];
		$total_score=$rows['total_score'];
		$player_id=$rows['unique_id'];
		if($total_score!=$currentScore && $currentScore>0){
			$i++;
		}
		$player_position = $player==$player_id ? $i : $player_position;
		$arr = array(
			'name' => $name,
			'latest_score' => number_format($latest_score),
			'player_score' => empty($player_score) ? '' :  number_format($player_score),
			'player_position' => empty($player_position) ? '' : number_format($player_position),
			'player' => $player,
			'all_players' => $all_rows,
			'total_score' => number_format($total_score),
			'rows' => $num_rows,
			'ranking' => $i,
		);
		array_push($data,$arr);
		$currentScore=$total_score;
	}
}
echo json_encode($data);

function rows($dbc,$company_id,$alter){
	$clause="";
	if($alter!=""){
		$clause=" AND lbd1.total_score>=$alter";
	}
	$query="SELECT lbd1.* FROM leaderboard lbd1 LEFT JOIN leaderboard lbd2 ON (lbd1.player_id = lbd2.player_id AND lbd1.id < lbd2.id) WHERE lbd2.company_id is NULL AND (lbd1.company_id='$company_id' ".$clause.") ORDER BY total_score DESC";
	$sql=$dbc->prepare($query)  or die(mysqli_error($dbc));
	$sql->execute();
	$result=$sql->get_result();
	$num_rows = $result->num_rows;
	return $num_rows;
}
function average($dbc,$company_id,$alter){
	$clause="";
	if($alter!=""){
		$clause=" AND lbd1.total_score>=$alter";
	}
	$num_rows =rows($dbc,$company_id,$alter);
	$query="SELECT lbd1.*,SUM(lbd1.total_score) AS total FROM leaderboard lbd1 LEFT JOIN leaderboard lbd2 ON (lbd1.player_id = lbd2.player_id AND lbd1.id < lbd2.id) WHERE lbd2.company_id is NULL AND (lbd1.company_id='$company_id' ".$clause.") ORDER BY total_score DESC";
	//echo $query;
	$sql=$dbc->prepare($query) or die(mysqli_error($dbc));
	$sql->execute();
	$result=$sql->get_result();
	$num=$result->num_rows;
	$score=0;
	if($num>0){
		$row=$result->fetch_assoc();
		$scores=$row['total']/$num_rows;
	}
	return ceil($scores);
}

function player_score($dbc,$company_id,$id){
	$total_score='';
		$query="SELECT total_score FROM leaderboard WHERE company_id='$company_id' AND unique_id=? ORDER BY total_score DESC LIMIT 1";
		$sql=$dbc->prepare($query);
		$sql->bind_param("s",$id);
		$sql->execute();
		$result=$sql->get_result();
		$num_rows =$result->num_rows;
		if($num_rows>0){
		$rows= $result->fetch_assoc();
		$total_score=$rows['total_score'];
		}
		return $total_score;
}

function player_position($dbc,$company_id,$id){
		$player_score=player_score($dbc,$company_id,$id);
		$position='';
		$query="SELECT total_score FROM leaderboard WHERE company_id=? AND total_score>? GROUP BY player_id ORDER BY total_score DESC";
		$sql=$dbc->prepare($query);
		$sql->bind_param("si",$company_id,$player_score);
		$sql->execute();
		$result=$sql->get_result();
		$num_rows =$result->num_rows;
		if($player_score!=''){
			$position=$num_rows+1;
		}
		return $position;;
}
?>