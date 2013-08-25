<?php
$user=$_SESSION['users'][$cur_user];
$userc=new TwitterUser($conn, $db_prefix, $user['id']);
$title="Time Tweets of ".$user['screen_name']." - $title";
while(isset($_POST['act'])){
	if($_POST['act']=="add"){
		$n_month=$_POST['n_month'];
		$n_date=$_POST['n_date'];
		$n_hour=$_POST['n_hour'];
		$n_minute=$_POST['n_minute'];
		if(isset($_POST['one_tweet_per_one_line'])){
			$dat=explode("\n", $_POST['s_tweet']);
			foreach($dat as $tweet){
				$tweet=trim($tweet);
				if(strlen($tweet)==0) continue;
				$userc->addTimeTweet($tweet, $n_month, $n_date, $n_hour, $n_minute);
			}
		}else{
			$tweet=trim($_POST['s_tweet']);
			if(strlen($tweet)>0)
				$userc->addTimeTweet($tweet, $n_month, $n_date, $n_hour, $n_minute);
		}
	}else if($_POST['act']=="clear"){
		$userc->clearTimeTweets();
	}else if($_POST['act']=="delete"){
		$userc->deleteTimeTweet($_POST['id']);
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
	$stmt = $conn->prepare("SELECT count(*) FROM {$db_prefix}_time_tweet WHERE n_user=:n_user");
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
		<img style="width:32px;height:32px;vertical-align:middle;" src="<?php echo $user['screen_image']?>" /> <?php echo $user['screen_name']; ?>: Total <?php echo $row[0]?> Time Tweets
	</div>
	<fieldset style="width:480px;float:left;">
		<legend>Add</legend>
		<form method="post" action="/user/<?php echo $user['screen_name']."/".$id?>/time-tweets">
			<input type="hidden" name="act" value="add" />
			<div style="padding:5px;">
				<textarea id="s_tweet" name="s_tweet" style="width:100%;height:48px;"></textarea>
				<div id="s_tweet_test_result"></div>
				<div style="display:block;height:32px;">
					<b>Time: </b>
					<select name="n_month">
						<option value="-1">Any</option>
						<?php for($i=1;$i<=12;$i++) echo "<option value='$i'>$i</option>"; ?>
					</select> month
					<select name="n_date">
						<option value="-1">Any</option>
						<?php for($i=1;$i<=31;$i++) echo "<option value='$i'>$i</option>"; ?>
					</select> date
					<select name="n_hour">
						<option value="-1">Any</option>
						<?php for($i=0;$i<24;$i++) echo "<option value='$i'>$i</option>"; ?>
					</select> hour
					<select name="n_minute">
						<?php for($i=0;$i<60;$i++) echo "<option value='$i'>$i</option>"; ?>
					</select> minute
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
		<form method="post" action="/user/<?php echo $user['screen_name']."/".$id?>/time-tweets" onsubmit="return confirm('Do you really want to clear this list?');">
			<input type="hidden" name="act" value="clear" />
			<input type="submit" value="Clear" style="height:32px;width:80px;float:left;" />
		</form>
		<form method="post" action="/user/<?php echo $user['screen_name']."/".$id?>/time-tweets">
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
					<th style="width:200px;">Time</th>
					<th style="width:60px;">Tweeted</th>
					<th style="width:40px;"></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$stmt = $conn->prepare("SELECT * FROM {$db_prefix}_time_tweet WHERE n_user=:id");
				$stmt->execute(array("id"=>$userc->getUserIndex()));
				$i=0;
				while($row = $stmt->fetch(PDO::FETCH_BOTH)){
					?>
					<tr style="height:24px;vertical-align:middle;background:<?php echo (($i++)%2==0)?"#EEEEEE":"#FFFFFF"?>">
						<td style="text-align:center"><?php echo $row['n_index']?></td>
						<td style="<?php echo mb_strlen($row['s_data'])<=140?"":"background:#FDD";?>"><?php echo nl2br(htmlspecialchars($row['s_data'])); ?> <span style="color:gray">(<?php echo mb_strlen($row['s_data']) ?>)</span></td>
						<td style="text-align:center">
							<?php
							echo (($row['n_month']==-1)?"*":$row['n_month']) . "m ";
							echo (($row['n_date']==-1)?"*":$row['n_date']) . "d ";
							echo (($row['n_hour']==-1)?"**":$row['n_hour']) . ":";
							echo $row['n_minute'] . " min";
							?>
						</td>
						<td style="text-align:center"><?php echo $row['n_runcount'] ?></td>
						<td>
							<form method="post" action="/user/<?php echo $user['screen_name']."/".$id?>/time-tweets">
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