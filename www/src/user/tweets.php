<?php
$user=$_SESSION['users'][$cur_user];
$userc=new TwitterUser($conn, $db_prefix, $user['id']);
$title="Tweets of ".$user['screen_name']." - $title";
$intervals=array(1=>"1 minute", 2=>"2 minutes", 3=>"3 minutes", 4=>"4 minutes", 5=>"5 minutes", 6=>"6 minutes", 7=>"7 minutes", 8=>"8 minutes", 9=>"9 minutes", 10=>"10 minutes",  15=>"15 minutes", 20=>"20 minutes", 25=>"25 minutes", 30=>"30 minutes", 40=>"40 minutes", 50=>"50 minutes", 60=>"1 hour", 120=>"2 hours", 180=>"3 hours", 240=>"4 hours", 480=>"8 hours", 720=>"12 hours", 1440=>"1 day");
while(isset($_POST['act'])){
	if($_POST['act']=="add"){
		$start_time=$_POST['start_hr']*60 + $_POST['start_min'];
		$end_time=$_POST['end_hr']*60 + $_POST['end_min']+1;
		$interval=intval($_POST['interval']);
		if(!isset($interval)){
			$errorToShow="Bad interval selected!";
			break;
		}
		if(isset($_POST['one_tweet_per_one_line'])){
			$dat=explode("\n", $_POST['s_tweet']);
			foreach($dat as $tweet){
				$tweet=trim($tweet);
				if(strlen($tweet)==0) continue;
				$userc->addTweet($tweet, $start_time, $end_time, $interval);
			}
		}else{
			$tweet=trim($_POST['s_tweet']);
			if(strlen($tweet)>0)
				$userc->addTweet($tweet, $start_time, $end_time, $interval);
		}
	}else if($_POST['act']=="clear"){
		$userc->clearTweets();
	}else if($_POST['act']=="delete"){
		$userc->deleteTweet($_POST['id']);
	}
	break;
}
function timeToString($t){
	if($t>=60)
		return intval($t/60) . " hr " . intval($t%60) . " min";
	else
		return $t . " min";
}
function printContent(){
	global $conn, $db_prefix, $userc, $intervals, $cur_user;
	global $errorToShow, $resultToShow;
	$user=$_SESSION['users'][$cur_user];
	$id=$user['id'];
	$stmt = $conn->prepare("SELECT count(*) FROM {$db_prefix}_tweet WHERE n_user=:n_user");
	$stmt->execute(array("n_user"=>$userc->getUserIndex()));
	while($row = $stmt->fetch(PDO::FETCH_BOTH)) break;
	if(isset($errorToShow)){
		?>
		<div style="padding:5px; margin: 5px; background:#FEE; border: 1px solid red;font-size:12pt;">
			<?php echo nl2br(htmlspecialchars($errorToShow)); ?>
		</div>
		<?php
	}
	if(isset($resultToShow)){
		?>
		<div style="padding:5px; margin: 5px; background:#EFE; border: 1px solid green;font-size:12pt;">
			<?php echo nl2br(htmlspecialchars($resultToShow)); ?>
		</div>
		<?php
	}
	?>
	<div style="font-size:15pt;font-weight:bold;">
		<img style="width:32px;height:32px;vertical-align:middle;" src="<?php echo $user['screen_image']?>" /> <?php echo $user['screen_name']; ?>: Total <?php echo $row[0]?> Tweets
	</div>
	<fieldset style="width:480px;float:left;">
		<legend>Add</legend>
		<form method="post" action="/user/<?php echo $user['screen_name']."/".$id?>/tweets">
			<input type="hidden" name="act" value="add" />
			<div style="padding:5px;">
				<textarea id="s_tweet" name="s_tweet" style="width:100%;height:48px;"></textarea>
				<div id="s_tweet_test_result"></div>
				<div style="display:block;height:32px;">
					<b>Duration: </b>
					From
					<select name="start_hr">
						<?php for($i=0;$i<24;$i++) echo "<option value='$i'>$i</option>"; ?>
					</select> hour
					<select name="start_min">
						<?php for($i=0;$i<60;$i++) echo "<option value='$i'>$i</option>"; ?>
					</select> minute
					to
					<select name="end_hr">
						<?php for($i=23;$i>=0;$i--) echo "<option value='$i'>$i</option>"; ?>
					</select> hour
					<select name="end_min">
						<?php for($i=59;$i>=0;$i--) echo "<option value='$i'>$i</option>"; ?>
					</select> minute
				</div>
				<div style="display:block;height:32px;">
					<b>Interval: </b>
					<select name="interval"><?php foreach($intervals as $k=>$v) echo "<option value='$k' ".($k==30?"selected='selected'":"").">$v</option>"; ?></select>
				</div>
				<div style="display:block;height:32px;">
					<input type="submit" value="Add" style="height:32px;width:80px;" />
					<input type="button" value="Test" style="height:32px;width:80px;" onclick="testInputString('#s_tweet', '#s_tweet_test_result', 'tweets');return false;" />
					<label for="chk_one_tweet_per_one_line">
						<input type="checkbox" id="chk_one_tweet_per_one_line" name="one_tweet_per_one_line" value="1" />
						One tweet per one line
					</label>
				</div>
			</div>
		</form>
	</fieldset>
	<fieldset style="width:240px;height:100px;float:left;">
		<legend>Control</legend>
		<form method="post" action="/user/<?php echo $user['screen_name']."/".$id?>/tweets" onsubmit="return confirm('Do you really want to clear this list?');">
			<input type="hidden" name="act" value="clear" />
			<input type="submit" value="Clear" style="height:32px;width:80px;float:left;" />
		</form>
		<form method="post" action="/user/<?php echo $user['screen_name']."/".$id?>/tweets">
			<input type="hidden" name="act" value="export" />
			<input type="submit" value="Export" style="height:32px;width:80px;float:left;" />
		</form>
	</fieldset>
	<fieldset style="clear:both">
		<legend>Tweets</legend>
		<table style="width:100%">
			<thead>
				<tr style="height:24px;vertical-align:middle;">
					<th style="width:40px;">ID</th>
					<th>Tweet</th>
					<th style="width:200px;">Duration</th>
					<th style="width:70px;">Interval</th>
					<th style="width:60px;">Tweeted</th>
					<th style="width:40px;"></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$stmt = $conn->prepare("SELECT * FROM {$db_prefix}_tweet WHERE n_user=:id");
				$stmt->execute(array("id"=>$userc->getUserIndex()));
				$i=0;
				while($row = $stmt->fetch(PDO::FETCH_BOTH)){
					?>
					<tr style="height:24px;vertical-align:middle;background:<?php echo (($i++)%2==0)?"#EEEEEE":"#FFFFFF"?>">
						<td style="text-align:center"><?php echo $row['n_index']?></td>
						<td style="<?php echo mb_strlen($row['s_data'])<=140?"":"background:#FDD";?>"><?php echo nl2br(htmlspecialchars($row['s_data'])); ?> <span style="color:gray">(<?php echo mb_strlen($row['s_data']) ?>)</span></td>
						<td style="text-align:center"><?php echo timeToString($row['n_time_start']); ?> ~ <?php echo timeToString($row['n_time_end']-1); ?></td>
						<td style="text-align:center">
							<?php echo $intervals[$row['n_interval']]?>
						</td>
						<td style="text-align:center"><?php echo $row['n_runcount'] ?></td>
						<td>
							<form method="post" action="/user/<?php echo $user['screen_name']."/".$id?>/tweets">
								<input type="hidden" name="act" value="delete" />
								<input type="hidden" name="id" value="<?php echo $row['n_index']?>" /><input type="submit" value="Delete" />
							</form>
						</td>
					</tr>
					<?php
					} 
				?>
			</tbody>
		</table>
	</fieldset>
	<?php
}