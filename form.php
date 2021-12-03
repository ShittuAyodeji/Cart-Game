<?php
include 'database/connect.php';
if(isset($_POST['name'])){
$name=$_POST['name'];
$code=$_POST['code'];
$mobile=$_POST['mobile'];
$sql = $dbc->prepare("SELECT * FROM codes WHERE code=? AND code_status='NO'");
$sql->bind_param("s",$code);
$sql->execute();
$result=$sql->get_result();
$num_rows =$result->num_rows;
$verified = $num_rows>0 ? 1 : 0;
echo $verified;
}
?>