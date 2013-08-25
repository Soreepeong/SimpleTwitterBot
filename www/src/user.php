<?php
$cur_user=$_GET['__user'];
$file_to_use_2=basename($_GET['__user_action']); $last_file_to_use_2="";
if($file_to_use_2=="") $file_to_use_2="stat";
while(strcmp($last_file_to_use_2, $file_to_use_2)){
	if(!file_exists(__DIR__."/user/$file_to_use_2.php")){
		$file_to_use="404";
		break;
	}
	$last_file_to_use_2=$file_to_use_2;
	include(__DIR__."/user/$file_to_use_2.php");
}