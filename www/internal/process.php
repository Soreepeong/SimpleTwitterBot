<?php
if($_SERVER['REMOTE_ADDR']!="127.0.0.1")
	die("Wrong hostname!");
set_time_limit(-1);
require_once(__DIR__.'/../../core/lib.php');
require_once(__DIR__.'/singleuser.class.php');
session_write_close();
$now_time=$_GET['now_time'];
$mems=$conn->prepare("SELECT * FROM {$db_prefix}_user_list WHERE n_working=1 AND n_index=:n_index");
$mems->execute(array("n_index"=>$_GET['n_index']));
while($row=$mems->fetch(PDO::FETCH_BOTH)){
	echo $row['s_description'] . ": Executing...\r\n";
	$m=new TwitterUser($conn, $db_prefix, $row);
	$t=new SingleUser($conn, $db_prefix, $m, $now_time);
	$t->run();
}
echo "Executed\r\n";
die();