<?php
class SingleUser{
	public $usr, $pdo, $db_prefix, $start_time;
	public $random_users=false, $mentions=false, $home_timeline=false, $processed_tweets=array();
	
	public $debugmode=0;
	
	public function __construct($pdo, $db_prefix, $usr, $start_time){
		$this->usr=$usr;
		$this->pdo=$pdo;
		$this->db_prefix=$db_prefix;
		$this->start_time=$start_time;
	}
	private function postProcessTweet($txt){
		if((strpos($txt, "{randomuser:id}")!==false) || (strpos($txt, "{randomuser:name}")!==false)){
			$ruser=$this->pickRandomUser();
			if($ruser===false) return false;
			unset($this->random_users[$this->usr->getTwitterId()]);
			unset($this->random_users[323283810]);
			for($i=0;$i<25;$i++){
				$ruser=$this->pickRandomUser();
				if($ruser['id']==$this->usr->getTwitterId()) continue;
				if($ruser===false) return false;
				break;
			}
		}else{
			$ruser=array();
		}
		return processSpecialTweet($txt, $ruser);
	}
	private function fillMentions(){
		if($this->mentions===false){
			$code=$this->usr->getTmh()->request('GET', $this->usr->getTmh()->url('1.1/statuses/mentions_timeline'), array("count"=>200));
			if($code==200)
				$this->mentions = json_decode($this->usr->getTmh()->response['response'],true);
			else{
				$this->mentions=array();
				$err=json_decode($this->usr->getTmh()->response['response'],true);
				print_r($err);
			}
		}else{
		}
	}
	private function pickRandomUser(){
		if($this->debugmode){
			return array(
				'name'=>'(이름)',
				'screen_name'=>'(ID)',
				'id'=>0
				);
		}
		if($this->random_users===false){
			$code=$this->usr->getTmh()->request('GET', $this->usr->getTmh()->url('1.1/statuses/home_timeline'), array("count"=>200));
			if($code==200){
				$this->home_timeline = json_decode($this->usr->getTmh()->response['response'],true);
				$this->random_users=array();
				foreach($this->home_timeline as $val) $this->random_users[]=$val['user'];
			}else{
				$err=json_decode($this->usr->getTmh()->response['response'],true);
				print_r($err);
				$random_users=true;
			}
		}
		if($this->random_users===true) return false;
		if(count($this->random_users)==0) return false;
		for($i=mt_rand(2,8);$i>0;$i--)
			$res=$this->random_users[array_rand($this->random_users)];
		return $res;
	}
	private $tweeted_items=array(), $limited=false, $duplicated=false;
	private function doTweet($str, $replyto=0){
		if(isset($this->tweeted_items[$str])) return false;
		if($this->limited) return false;
		$this->tweeted_items[$str]=true;
		if(preg_match_all('/\\{{3}set (name|url|location|description)\\s+(.*?)\\}{3}/iu', $str, $matches, PREG_SET_ORDER)){
			$updates=array();
			foreach($matches as $sets){
				$updates[$sets[1]]=$sets[2];
			}
			if(count($updates)>0){
				$res=$this->usr->getTmh()->request('POST', $this->usr->getTmh()->url('1.1/account/update_profile'), $updates);
			}
			$str=preg_replace('/\\{{3}set (name|url|location|description)\\s+(.*?)\\}{3}/ui', '', $str);
			$str=trim($str);
			if(strlen($str)==0)
				return $res==200;
		}
		if(strlen($str)==0)
			return true;
		$res=$this->usr->tweet($str, $replyto);
		if(!$res){
			$err=json_decode($this->usr->getTmh()->response['response'],true);
			echo $err['errors'][0]['code'];
			if($err['errors'][0]['code']==185){ // User is over daily status update limit
				echo $err['errors'][0]['message'];
				$this->limited=true;
			}else if($err['errors'][0]['code']==187){ // Duplicate
				echo $err['errors'][0]['message'];
				$this->duplicated=true;
			}else{
				echo $err['errors'][0]['message'];
			}
			echo "\r\n";
		}
		return $res;
	}
	private function processAutoTweet(){
		$intervals=array(1=>"1 minute", 2=>"2 minutes", 3=>"3 minutes", 4=>"4 minutes", 5=>"5 minutes", 6=>"6 minutes", 7=>"7 minutes", 8=>"8 minutes", 9=>"9 minutes", 10=>"10 minutes",  15=>"15 minutes", 20=>"20 minutes", 25=>"25 minutes", 30=>"30 minutes", 40=>"40 minutes", 50=>"50 minutes", 60=>"1 hour", 120=>"2 hours", 180=>"3 hours", 240=>"4 hours", 480=>"8 hours", 720=>"12 hours", 1440=>"1 day");
		$cond=array();
		foreach($intervals as $in=>$v){
			if($this->start_time % $in==0)
				$cond[]="n_interval=$in";
		}
		$cond=implode(" OR ",$cond);
		$stmt = $this->pdo->prepare("SELECT * FROM {$this->db_prefix}_tweet WHERE(((n_time_start>=n_time_end) AND (n_time_start<={$this->start_time} OR {$this->start_time}<n_time_end)) OR (n_time_start<={$this->start_time} AND {$this->start_time}<n_time_end)) AND n_user={$this->usr->getUserIndex()} AND ($cond) ORDER BY n_runcount ASC limit 3");
		$stmt->execute();
		$res=array();
		while($row = $stmt->fetch(PDO::FETCH_BOTH)) $res[]=$row;
		if(count($res)==0) return;
		foreach($res as $val){
			$txt=$val['s_data'];
			$txt=$this->postProcessTweet($val['s_data']);
			if($txt===false) continue;
			if($this->doTweet($txt)){
				$this->pdo->query("UPDATE {$this->db_prefix}_tweet SET n_runcount=n_runcount+1 WHERE n_index=".$val['n_index']);
				break;
			}
		}
	}
	private function processAutoTimeTweet(){
		$min=date("i");
		if(substr($min,0,1)=="0") $min=substr($min,1);
		$cond=array(
			"(n_month=-1 OR n_month=".date("n").")",
			"(n_date=-1 OR n_month=".date("j").")",
			"(n_hour=-1 OR n_hour=".date("G").")",
			"(n_minute=-1 OR n_minute=$min)"
		);
		$cond=implode(" AND ",$cond);
		$stmt = $this->pdo->prepare("SELECT * FROM {$this->db_prefix}_time_tweet WHERE n_user={$this->usr->getUserIndex()} AND ($cond) ORDER BY n_runcount ASC limit 3");
		$stmt->execute();
		$res=array();
		while($row = $stmt->fetch(PDO::FETCH_BOTH)) $res[]=$row;
		if(count($res)==0) return;
		foreach($res as $val){
			$txt=$val['s_data'];
			$txt=$this->postProcessTweet($val['s_data']);
			if($txt===false) continue;
			if($this->doTweet($txt)){
				$this->pdo->query("UPDATE {$this->db_prefix}_time_tweet SET n_runcount=n_runcount+1 WHERE n_index=".$val['n_index']);
				break;
			}
		}
	}
	private function processAutoTimelineReply(){
		$newTime=0;
		$saveNewTime=false;
		$lastAvailable=$this->usr->getHomeLastTime();
		$stmt = $this->pdo->prepare("SELECT * FROM {$this->db_prefix}_tl_reply WHERE(((n_time_start>=n_time_end) AND (n_time_start<={$this->start_time} OR {$this->start_time}<n_time_end)) OR (n_time_start<={$this->start_time} AND {$this->start_time}<n_time_end)) AND n_user={$this->usr->getUserIndex()} ORDER BY rand(), n_runcount ASC");
		$stmt->execute();
		$this->duplicated=false;
		while($row = $stmt->fetch(PDO::FETCH_BOTH)){
			$this->pickRandomUser();
			foreach($this->home_timeline as $aa=>$twt){
				if(isset($this->processed_tweets[$twt['id']])) continue;
				if(isset($twt['retweeted_status'])) continue;
				$tm=strtotime($twt['created_at']);
				if($tm>$newTime) $newTime=$tm;
				if($tm>$lastAvailable){
					if(!preg_match($row['s_trigger_user'], $twt['user']['screen_name'])) continue;
					$txt=htmlspecialchars_decode($twt['text']);
					// echo $row['s_trigger_text'] . " / " . $txt . "\r\n";
					if(!preg_match($row['s_trigger_text'], $txt)) continue;
					// echo "PASS\r\n";
					$row['s_data']=str_replace('{writer.id}', $twt['user']['screen_name'], $row['s_data']);
					$row['s_data']=str_replace('{writer.name}', $twt['user']['name'], $row['s_data']);
					$row['s_data']=$this->postProcessTweet($row['s_data']);
					$res=preg_replace($row['s_trigger_text'], $row['s_data'], $txt);
					if($this->doTweet($res, $twt['id_str'])){
						$this->pdo->query("UPDATE {$this->db_prefix}_tl_reply SET n_runcount=n_runcount+1 WHERE n_index=".$row['n_index']);
						$this->processed_tweets[$twt['id']]=1;
						$saveNewTime=true;
						continue;
					}
				}
			}
		}
		if($saveNewTime || $this->limited || $this->duplicated)
			$this->usr->setHomeLastTime($newTime);
	}
	private function processAutoReply(){
		echo "Auto Reply...\r\n";
		$newTime=0;
		$saveNewTime=false;
		$lastAvailable=$this->usr->getMentionLastTime();
		$stmt = $this->pdo->prepare("SELECT * FROM {$this->db_prefix}_reply WHERE(((n_time_start>=n_time_end) AND (n_time_start<={$this->start_time} OR {$this->start_time}<n_time_end)) OR (n_time_start<={$this->start_time} AND {$this->start_time}<n_time_end)) AND n_user={$this->usr->getUserIndex()} ORDER BY rand(), n_runcount ASC");
		$stmt->execute();
		$this->duplicated=false;
		while($row = $stmt->fetch(PDO::FETCH_BOTH)){
			$this->fillMentions();
			foreach($this->mentions as $aa=>$twt){
				if(isset($this->processed_tweets[$twt['id']])) continue;
				if(isset($twt['retweeted_status'])) continue;
				$tm=strtotime($twt['created_at']);
				if($tm>$newTime) $newTime=$tm;
				if($tm>$lastAvailable){
					echo "Check: @" . $twt['user']['screen_name'] . ": ". $twt['text'] . "\r\n";
					if(!preg_match($row['s_trigger_user'], $twt['user']['screen_name'])) continue;
					echo "User OK!\r\n";
					$txt=htmlspecialchars_decode($twt['text']);
					if(!preg_match($row['s_trigger_text'], $txt)) continue;
					echo "Process!\r\n";
					$row['s_data']=str_replace('{mentioner.id}', $twt['user']['screen_name'], $row['s_data']);
					$row['s_data']=str_replace('{mentioner.name}', $twt['user']['name'], $row['s_data']);
					$row['s_data']=$this->postProcessTweet($row['s_data']);
					if($row['s_data']===false) continue;
					$res=preg_replace($row['s_trigger_text'], $row['s_data'], $txt);
					if($this->doTweet($res, $twt['id_str'])){
						$this->pdo->query("UPDATE {$this->db_prefix}_reply SET n_runcount=n_runcount+1 WHERE n_index=".$row['n_index']);
						$this->processed_tweets[$twt['id']]=1;
						$saveNewTime=true;
						continue;
					}
				}
			}
		}
		
		echo "Auto Timeline Reply...\r\n";
		$this->processAutoTimelineReply();
		
		echo "Auto Random Reply...\r\n";
		$stmt = $this->pdo->prepare("SELECT * FROM {$this->db_prefix}_randomreply WHERE(((n_time_start>=n_time_end) AND (n_time_start<={$this->start_time} OR {$this->start_time}<n_time_end)) OR (n_time_start<={$this->start_time} AND {$this->start_time}<n_time_end)) AND n_user={$this->usr->getUserIndex()} ORDER BY rand(), n_runcount ASC");
		$stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_BOTH)){
			$this->fillMentions();
			foreach($this->mentions as $aa=>$twt){
				echo $twt['user']['screen_name'] . ": " . $twt['text']."\r\n";
				if(isset($this->processed_tweets[$twt['id']])) continue;
				if(isset($twt['retweeted_status'])) continue;
				$tm=strtotime($twt['created_at']);
				if($tm>$lastAvailable){ // Random Reply
					echo "Not replied: @" . $twt['user']['screen_name'] . ": ". $twt['text'] . "\r\n";
					if(!preg_match($row['s_trigger_user'], $twt['user']['screen_name'])) continue;
					echo "Process!\r\n";
					$txt=htmlspecialchars_decode($twt['text']);
					$row['s_data']=str_replace('{mentioner.id}', $twt['user']['screen_name'], $row['s_data']);
					$row['s_data']=str_replace('{mentioner.name}', $twt['user']['name'], $row['s_data']);
					echo "Result: " . $row['s_data']."\r\n";
					$row['s_data']=$this->postProcessTweet($row['s_data']);
					if($row['s_data']===false) continue;
					echo "Tweeting!\r\n";
					$res=$row['s_data'];
					if($this->doTweet($res, $twt['id_str'])){
						$this->pdo->query("UPDATE {$this->db_prefix}_randomreply SET n_runcount=n_runcount+1 WHERE n_index=".$row['n_index']);
						$this->processed_tweets[$twt['id']]=1;
						$saveNewTime=true;
						continue;
					}
				}
			}
		}
		if($saveNewTime || $this->limited || $this->duplicated)
		$this->usr->setMentionLastTime($newTime);
	}
	public function run(){
		date_default_timezone_set('Asia/Seoul');
		echo "Auto Tweet...\r\n";
		$this->processAutoTweet();
		echo "Auto Time Tweet...\r\n";
		$this->processAutoTimeTweet();
		$this->processAutoReply();
	}
}
