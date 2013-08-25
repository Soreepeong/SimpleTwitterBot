<?php
$user=$_SESSION['users'][$cur_user];
$userc=new TwitterUser($conn, $db_prefix, $user['id']);
$title="Timeline Replies of ".$user['screen_name']." - $title";
while(isset($_POST['act'])){
	if($_POST['act']=="add"){
		$start_time=$_POST['start_hr']*60 + $_POST['start_min'];
		$end_time=$_POST['end_hr']*60 + $_POST['end_min']+1;
		$rep_from=$_POST['s_trigger'];
		$rep_from_user=$_POST['trigger_user'];
		if(@preg_match($rep_from, "") === false){
			$errorToShow="Bad regex of Replace From!";
			break;
		}
		if(@preg_match($rep_from_user, "") === false){
			$errorToShow="Bad regex of Replace From User!";
			break;
		}
		if(isset($_POST['one_tweet_per_one_line'])){
			foreach(explode("\n", $_POST['s_tweet']) as $tweet){
				$tweet=trim($tweet);
				if(strlen($tweet)>0)
					$userc->addTimelineReply($rep_from, $rep_from_user, $tweet, $start_time, $end_time);
			}
		}else{
			$tweet=trim($_POST['s_tweet']);
			if(strlen($tweet)>0)
				$userc->addTimelineReply($rep_from, $rep_from_user, $tweet, $start_time, $end_time);
		}
		unset($_POST['s_trigger']);
		unset($_POST['trigger_user']);
		unset($_POST['s_tweet']);
	}else if($_POST['act']=="clear"){
		$userc->clearTimelineReplies();
	}else if($_POST['act']=="delete"){
		$userc->deleteTimelineReply($_POST['id']);
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
	$stmt = $conn->prepare("SELECT count(*) FROM {$db_prefix}_tl_reply WHERE n_user=:n_user");
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
		<img style="width:32px;height:32px;vertical-align:middle;" src="<?php echo $user['screen_image']?>" /> <?php echo $user['screen_name']; ?>: Total <?php echo $row[0]?> Timeline Replies
	</div>
	<fieldset style="width:480px;float:left;">
		<legend>Add</legend>
		<form method="post" action="/user/<?php echo $user['screen_name']."/".$id?>/timeline-replies">
			<input type="hidden" name="act" value="add" />
			<div style="padding:5px;">
				<b>Trigger (RegEx (preg)): </b><a href="http://kr1.php.net/manual/en/pcre.pattern.php">(Help)</a><br />
				<textarea id="s_trigger" name="s_trigger" style="width:100%;height:48px;"><?php if(isset($_POST['s_trigger'])) echo htmlspecialchars($_POST['s_trigger']); else{ ?>/^.*YOUR TEXT HERE.*$/iu<?php } ?></textarea>
				<b>Answer (Replace to): </b><br />
				<textarea id="s_tweet" name="s_tweet" style="width:100%;height:48px;"><?php if(isset($_POST['s_tweet'])) echo htmlspecialchars($_POST['s_tweet']); else{ ?>@{writer.id} <?php } ?></textarea>
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
				<div style="display:block;height:48px;">
					<b>Triggered User: </b>
					<input type="text" name="trigger_user" style="width:240px;" value="<?php if(isset($_POST['trigger_user'])) echo htmlspecialchars($_POST['trigger_user']); else{ ?>/^.*$/i<?php } ?>" /><br />
					Use as <b>{writer.id}</b>, <b>{writer.name}</b>
				</div>
				<div style="display:block;height:32px;">
					<input type="submit" value="Add" style="height:32px;width:80px;" />
					<input type="button" value="Test" style="height:32px;width:80px;" onclick="testReplyInputString('#s_trigger', '#s_tweet', '#s_test_tweet', '#s_tweet_test_result', 'replies');return false;" />
					<label for="chk_one_tweet_per_one_line">
						<input type="checkbox" id="chk_one_tweet_per_one_line" name="one_tweet_per_one_line" value="1" />
						1 answer / 1 line
					</label>
					<input type="text" id="s_test_tweet" style="width:160px;" value="Morning!" />
				</div>
			</div>
		</form>
	</fieldset>
	<fieldset style="width:240px;height:100px;float:left;">
		<legend>Control</legend>
		<form method="post" action="/user/<?php echo $user['screen_name']."/".$id?>/timeline-replies" onsubmit="return confirm('Do you really want to clear this list?');">
			<input type="hidden" name="act" value="clear" />
			<input type="submit" value="Clear" style="height:32px;width:80px;float:left;" />
		</form>
		<form method="post" action="/user/<?php echo $user['screen_name']."/".$id?>/timeline-replies">
			<input type="hidden" name="act" value="export" />
			<input type="submit" value="Export" style="height:32px;width:80px;float:left;" />
		</form>
	</fieldset>
	<fieldset style="clear:both">
		<legend>Replies</legend>
		<table style="width:100%">
			<thead>
				<tr style="height:24px;vertical-align:middle;">
					<th style="width:40px;">ID</th>
					<th>Trigger &amp; Reply</th>
					<th style="width:200px;">Duration</th>
					<th style="width:60px;">Tweeted</th>
					<th style="width:40px;"></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$stmt = $conn->prepare("SELECT * FROM {$db_prefix}_tl_reply WHERE n_user=:id");
				$stmt->execute(array("id"=>$userc->getUserIndex()));
				$i=0;
				while($row = $stmt->fetch(PDO::FETCH_BOTH)){
					?>
					<tr style="height:24px;vertical-align:middle;background:<?php echo (($i++)%2==0)?"#EEEEEE":"#FFFFFF"?>">
						<td style="text-align:center"><?php echo $row['n_index']?></td>
						<td>
							<b>Got</b> <?php echo nl2br(htmlspecialchars($row['s_trigger_text'])); ?><br />
							<b>From</b> @<?php echo nl2br(htmlspecialchars($row['s_trigger_user'])); ?><br />
							<b>Reply</b> <?php echo nl2br(htmlspecialchars($row['s_data'])); ?>
						</td>
						<td style="text-align:center"><?php echo timeToString($row['n_time_start']); ?> ~ <?php echo timeToString($row['n_time_end']-1); ?></td>
						<td style="text-align:center"><?php echo $row['n_runcount'] ?></td>
						<td>
							<form method="post" action="/user/<?php echo $user['screen_name']."/".$id?>/timeline-replies">
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