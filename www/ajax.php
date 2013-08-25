<?php
require_once(__DIR__."/../core/lib.php");
switch($_GET['what']){
	case 'test-string':{
		if($_GET['page']=='tweets')
			die(processSpecialTweet($_GET['data'], array("screen_name"=>"(ID".rand(0,32767).")", "name"=>"(Name".rand(0,32767).")")));
		else if($_GET['page']=='random-replies')
			die(
				str_replace('{mentioner.name}','(MentionerName'.rand(1,32767).')',
					str_replace('{mentioner.id}','(MentionerID'.rand(1,32767).')',
						processSpecialTweet(
							$_GET['data'], array("screen_name"=>"(ID".rand(0,32767).")", "name"=>"(Name".rand(0,32767).")")
						)
					)
				)
			);
		else if($_GET['page']=='replies'){
			$dat=str_replace('{writer.id}', "(WriterID".rand(1,32767).")", $_GET['repto']);
			$dat=str_replace('{writer.name}', "(WriterName".rand(1,32767).")", $dat);
			$dat=processSpecialTweet(
				$dat, array("screen_name"=>"(ID".rand(0,32767).")", "name"=>"(Name".rand(0,32767).")")
			);
			$res=@preg_match($_GET['repfrom'], $_GET['test']);
			if($res===FALSE){
				$a=error_get_last();
				die('bad:' . $a['message']); //bad expression
			}else if($res===0)
				die('nomatch'); // no match
			else
				die('1:'.preg_replace($_GET['repfrom'], $dat, $_GET['test']));
		}
	}
}