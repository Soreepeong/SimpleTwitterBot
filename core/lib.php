<?php
date_default_timezone_set('Asia/Seoul');
mb_internal_encoding("UTF-8");
require_once(__DIR__."/config.php");
require_once(__DIR__."/TwitterUser.class.php");
function getPdoConnection(){
	global $db_host, $db_name, $db_id, $db_pw;
	try {
		$conn = new PDO('mysql:host='.$db_host.';dbname='.$db_name, $db_id, $db_pw);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch(PDOException $e) {
		die('ERROR: ' . $e->getMessage());
	}
	return $conn;
}
$conn=getPdoConnection();
if($db_init){
	$dat=explode("---", str_replace("%BOT_TBL_PREFIX%", $db_prefix, file_get_contents(__DIR__."/schema.sql")));
	foreach($dat as $query){
		try{
			$conn->query($query);
		}catch(PDOException $e){
		die('ERROR: ' . $e->getMessage());
		}
	}
}
session_start();

function processSpecialTweet($txt, $ruser){
	if(isset($ruser['screen_name'])){
		$txt=str_replace("{randomuser:id}", $ruser['screen_name'], $txt);
		$txt=str_replace("{randomuser:name}", $ruser['name'], $txt);
	}
	if(preg_match_all('/\\{{3}(datetime|year|month|date|hour|minute|weekday|ampm)(?::(.*?))?\\}{3}/iu', $txt, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)){
		array_reverse($matches);
		foreach($matches as $sets){
			$repTo="";
			switch(strtolower($sets[1][0])){
				case 'datetime': $repTo=date(isset($sets[2][0])?$sets[2][0]:"Y-m-d H:i:s"); break;
				case 'year': $repTo=date("Y"); break;
				case 'month':{
					if(isset($sets[2][0])){
						$param=explode("|", $sets[2][0]);
						if(count($param)==12)
						$repTo=$param[date("n")-1];
						else
						$repTo="(ERROR: Bad parameter!)";
					}else
						$repTo=date("n");
					break;
				}
				case 'date': $repTo=date(isset($sets[2][0])?"d":"j"); break; // parameter set - leading zero yes
				case 'hour':{
					if(isset($sets[2][0])){
						$param=explode("|", $sets[2][0]);
						foreach($param as $key=>$val) $param[$key]=strtolower($val);
						array_flip($param);
						if(isset($param['format12']))
							$repTo=date(isset($param['start0'])?"h":"g");
						else
							$repTo=date(isset($param['start0'])?"H":"G");
					}else{
						$repTo=date("G");
					}
					break;
				}
				case 'minute':{
					$repTo=date("i");
					if(isset($sets[2][0])) if(substr($repTo,0,1)=="0") $repTo=substr($repTo, 1);
					break;
				}
				case 'weekday':{
					if(isset($sets[2][0])){
						$param=explode("|", $sets[2][0]);
						}else{
						$param=array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
					}
					if(count($param)==7)
						$repTo=$param[date("N")-1];
					else
						$repTo="(ERROR: Bad parameter!)";
					break;
				}
				case 'ampm':{
					if(isset($sets[2][0])){
						$param=explode("|", $sets[2][0]);
						}else{
						$param=array("AM", "PM");
					}
					if(count($param)==2)
						$repTo=$param[(date("a")=='am')?0:1];
					else
						$repTo="(ERROR: Bad parameter!)";
					break;
					}
			}
			$txt=substr($txt, 0, $sets[0][1]) . $repTo . substr($txt,$sets[0][1] + strlen($sets[0][0]));
		}
	}
	return $txt;
}