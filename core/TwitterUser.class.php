<?php
require_once(__DIR__."/tmhOAuth.class.php");
require_once(__DIR__."/tmhUtilities.class.php");
class TwitterUser{
	private $pdo, $db_prefix, $tmh;
	private $userIndex, $s_consumer_key, $s_consumer_secret, $s_user_token, $s_user_token_secret, $s_description;
	private $n_working, $n_last_check_mention, $n_last_check_tweet, $n_user;
	
	public function __construct($pdo, $db_prefix, $userIndexOrResArray){
		$this->pdo=$pdo;
		$this->db_prefix=$db_prefix;
		if(!is_array($userIndexOrResArray)){
			try {
				$stmt = $this->pdo->prepare("SELECT * FROM {$this->db_prefix}_user_list WHERE n_user=:id");
				$stmt->execute(array('id' => $userIndexOrResArray));
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
					$userIndexOrResArray=$row;
				}
			} catch(PDOException $e) {
				die('ERROR: ' . $e->getMessage());
			}
		}
		$this->userIndex=$userIndexOrResArray['n_index'];
		$this->s_description=$userIndexOrResArray['s_description'];
		$this->s_consumer_key=$userIndexOrResArray['s_consumer_key'];
		$this->s_consumer_secret=$userIndexOrResArray['s_consumer_secret'];
		$this->s_user_token=$userIndexOrResArray['s_user_token'];
		$this->s_user_token_secret=$userIndexOrResArray['s_user_token_secret'];
		$this->n_working=$userIndexOrResArray['n_working'];
		$this->n_user=$userIndexOrResArray['n_user'];
		$this->tmh = new tmhOAuth(array('consumer_key' => $this->s_consumer_key, 'consumer_secret' => $this->s_consumer_secret));
		$this->tmh->config['user_token']=$userIndexOrResArray['s_user_token'];
		$this->tmh->config['user_secret']=$userIndexOrResArray['s_user_token_secret'];
		$this->n_last_check_mention=$userIndexOrResArray['n_last_check_mention'];
		$this->n_last_check_tweet=$userIndexOrResArray['n_last_check_tweet'];
	}
	public function getTmh(){ return $this->tmh; }
	public function getUserIndex(){
		return $this->userIndex;
	}
	public function tweet($status, $replyto=0){
		if($replyto!=0)
			$res=$this->tmh->request('POST', $this->tmh->url('1.1/statuses/update'), array('status' => $status, 'in_reply_to_status_id'=>$replyto));
		else
			$res=$this->tmh->request('POST', $this->tmh->url('1.1/statuses/update'), array('status' => $status));
		echo "$res: $status\r\n";
		return $res==200;
	}
	public function isRunning(){
		return $this->n_working;
	}
	public function getTwitterId(){ return $this->n_user; }
	public function run(){
		$this->n_working=1;
		$stmt=$this->pdo->prepare("UPDATE {$this->db_prefix}_user_list SET n_working=1 WHERE n_index=:id");
		$stmt->execute(array("id"=>$this->userIndex));
	}
	public function stop(){
		$this->n_working=0;
		$stmt=$this->pdo->prepare("UPDATE {$this->db_prefix}_user_list SET n_working=0 WHERE n_index=:id");
		$stmt->execute(array("id"=>$this->userIndex));
	}
	public function remove(){
	}
	public function addTweet($str, $from, $to, $interval){
		$stmt=$this->pdo->prepare("INSERT INTO {$this->db_prefix}_tweet(n_user, s_data, n_time_start, n_time_end, n_interval) VALUES (:n_user, :s_data, :n_time_start, :n_time_end, :n_interval)");
		$stmt->execute(array(
			"n_user"=>$this->userIndex,
			"s_data"=>$str,
			"n_time_start"=>$from,
			"n_time_end"=>$to,
			"n_interval"=>$interval,
		));
	}
	public function clearTweets(){
		$stmt=$this->pdo->prepare("DELETE FROM {$this->db_prefix}_tweet WHERE n_user=:n_user");
		$stmt->execute(array(
			"n_user"=>$this->userIndex
		));
	}
	public function deleteTweet($id){
		$stmt=$this->pdo->prepare("DELETE FROM {$this->db_prefix}_tweet WHERE n_user=:n_user AND n_index=:n_index");
		$stmt->execute(array(
			"n_user"=>$this->userIndex,
			"n_index"=>$id,
		));
	}
	public function addTimeTweet($str, $n_month, $n_date, $n_hour, $n_minute){
		$stmt=$this->pdo->prepare("INSERT INTO {$this->db_prefix}_time_tweet(n_user, s_data, n_month, n_date, n_hour, n_minute) VALUES (:n_user, :s_data, :n_month, :n_date, :n_hour, :n_minute)");
		$stmt->execute(array(
		"n_user"=>$this->userIndex,
		"s_data"=>$str,
		"n_month"=>$n_month,
		"n_date"=>$n_date,
		"n_hour"=>$n_hour,
		"n_minute"=>$n_minute,
		));
	}
	public function clearTimeTweets(){
		$stmt=$this->pdo->prepare("DELETE FROM {$this->db_prefix}_time_tweet WHERE n_user=:n_user");
		$stmt->execute(array(
		"n_user"=>$this->userIndex
		));
	}
	public function deleteTimeTweet($id){
		$stmt=$this->pdo->prepare("DELETE FROM {$this->db_prefix}_time_tweet WHERE n_user=:n_user AND n_index=:n_index");
		$stmt->execute(array(
		"n_user"=>$this->userIndex,
		"n_index"=>$id,
		));
	}
	public function getDescription(){
		return $this->s_description;
	}
	public function getMentionLastTime(){
		return $this->n_last_check_mention;
	}
	public function setMentionLastTime($lastTime){
		$this->n_last_check_mention=$lastTime;
		$stmt=$this->pdo->prepare("UPDATE {$this->db_prefix}_user_list SET n_last_check_mention=$lastTime WHERE n_index=:id");
		$stmt->execute(array("id"=>$this->userIndex));
	}
	public function getHomeLastTime(){
		return $this->n_last_check_tweet;
	}
	public function setHomeLastTime($lastTime){
		$this->n_last_check_tweet=$lastTime;
		$stmt=$this->pdo->prepare("UPDATE {$this->db_prefix}_user_list SET n_last_check_tweet=$lastTime WHERE n_index=:id");
		$stmt->execute(array("id"=>$this->userIndex));
	}
	public function addRandomReply($repfromusr, $repto, $from, $to){
		$stmt=$this->pdo->prepare("INSERT INTO {$this->db_prefix}_randomreply(n_user, s_trigger_user, s_data, n_time_start, n_time_end) VALUES (:n_user, :s_trigger_user, :s_data, :n_time_start, :n_time_end)");
		$stmt->execute(array(
		"n_user"=>$this->userIndex,
		"s_data"=>$repto,
		"n_time_start"=>$from,
		"n_time_end"=>$to,
		"s_trigger_user"=>$repfromusr,
		));
	}
	public function clearRandomReplies(){
		$stmt=$this->pdo->prepare("DELETE FROM {$this->db_prefix}_randomreply WHERE n_user=:n_user");
		$stmt->execute(array(
		"n_user"=>$this->userIndex
		));
	}
	public function deleteRandomReply($id){
		$stmt=$this->pdo->prepare("DELETE FROM {$this->db_prefix}_randomreply WHERE n_user=:n_user AND n_index=:n_index");
		$stmt->execute(array(
		"n_user"=>$this->userIndex,
		"n_index"=>$id,
		));
	}
	public function addReply($repfrom, $repfromusr, $repto, $from, $to){
		$stmt=$this->pdo->prepare("INSERT INTO {$this->db_prefix}_reply(n_user, s_trigger_user, s_trigger_text, s_data, n_time_start, n_time_end) VALUES (:n_user, :s_trigger_user, :s_trigger_text, :s_data, :n_time_start, :n_time_end)");
		$stmt->execute(array(
			"n_user"=>$this->userIndex,
			"s_data"=>$repto,
			"n_time_start"=>$from,
			"n_time_end"=>$to,
			"s_trigger_text"=>$repfrom,
			"s_trigger_user"=>$repfromusr,
		));
	}
	public function clearReplies(){
		$stmt=$this->pdo->prepare("DELETE FROM {$this->db_prefix}_reply WHERE n_user=:n_user");
		$stmt->execute(array(
			"n_user"=>$this->userIndex
		));
	}
	public function deleteReply($id){
		$stmt=$this->pdo->prepare("DELETE FROM {$this->db_prefix}_reply WHERE n_user=:n_user AND n_index=:n_index");
		$stmt->execute(array(
			"n_user"=>$this->userIndex,
			"n_index"=>$id,
		));
	}
	public function addTimelineReply($repfrom, $repfromusr, $repto, $from, $to){
		$stmt=$this->pdo->prepare("INSERT INTO {$this->db_prefix}_tl_reply(n_user, s_trigger_user, s_trigger_text, s_data, n_time_start, n_time_end) VALUES (:n_user, :s_trigger_user, :s_trigger_text, :s_data, :n_time_start, :n_time_end)");
		$stmt->execute(array(
			"n_user"=>$this->userIndex,
			"s_data"=>$repto,
			"n_time_start"=>$from,
			"n_time_end"=>$to,
			"s_trigger_text"=>$repfrom,
			"s_trigger_user"=>$repfromusr,
		));
	}
	public function clearTimelineReplies(){
		$stmt=$this->pdo->prepare("DELETE FROM {$this->db_prefix}_tl_reply WHERE n_user=:n_user");
		$stmt->execute(array(
			"n_user"=>$this->userIndex
		));
	}
	public function deleteTimelineReply($id){
		$stmt=$this->pdo->prepare("DELETE FROM {$this->db_prefix}_tl_reply WHERE n_user=:n_user AND n_index=:n_index");
		$stmt->execute(array(
			"n_user"=>$this->userIndex,
			"n_index"=>$id,
		));
	}
}
