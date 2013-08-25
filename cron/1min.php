<?php
set_time_limit(-1);
require_once(__DIR__.'/../core/lib.php');

$t=date("i");
if(substr($t,0,1)=="0") $t=substr($t,1);
$now_time=date("G")*60+$t;

$mems=$conn->prepare("SELECT * FROM {$db_prefix}_user_list WHERE n_working=1 ORDER BY rand()");
$mems->execute();
$threads[]=array();
$sid=session_id();
echo("Running...\r\n");
echo date("Y-m-d H:i:s")."\r\n";
session_write_close();
while($m=$mems->fetch(PDO::FETCH_BOTH)){
	echo "Request for ".$m['s_description']."... ";
	if(false===($sock=socket_create(AF_INET, SOCK_STREAM,  SOL_TCP))){ echo "Fail: ".socket_strerror(socket_last_error())."\r\n"; continue; }
	if(false===socket_connect($sock, "127.0.0.1", 80)){ echo "Fail: ".socket_strerror(socket_last_error())."\r\n"; socket_close($sock); continue; }
	$sockets[]=array($m, $sock);
	$dat=	"GET /internal/process.php?now_time=$now_time&n_index={$m['n_index']} HTTP/1.1\r\n".
				"Host: twitterbot.blastsound.com\r\n".
				"Cookie: PHPSESSID=".$sid."\r\n".
				"Connection: close\r\n\r\n";
	socket_send($sock, $dat, strlen($dat), MSG_EOF);
	echo "Sent!\r\n";
}
foreach($sockets as $v){
	echo "\r\nWaiting for " . $v[0]['s_description'] . "...";
	$dat='';
	while(($k=socket_read($v[1],1024))!==FALSE){
		if(strlen($k)==0) 
			break;
		else
			$dat.=$k;
	}
	echo " OK\r\n";
	echo "|----- DATA START ------\r\n";
	echo "| " . str_replace("\r\n", "\r\n| ", substr($dat,strpos($dat,"\r\n\r\n")+4))."\r\n";
	echo "|----- DATA END ------\r\n";
}