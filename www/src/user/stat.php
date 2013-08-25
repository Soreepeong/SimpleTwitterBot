<?php
$user=$_SESSION['users'][$cur_user];
$userc=new TwitterUser($conn, $db_prefix, $user['id']);
$title="Status of ".$user['screen_name']." - $title";
if(isset($_POST['act'])){
	if($_POST['act']=="pause")	$userc->stop();
	else if($_POST['act']=="resume") $userc->run();
	else if($_POST['act']=="remove") $userc->remove();
}

function printContent(){
	global $conn, $db_prefix, $userc, $intervals, $cur_user;
	global $errorToShow, $resultToShow;
	$user=$_SESSION['users'][$cur_user];
	$id=$user['id'];
	$stmt = $conn->prepare("SELECT count(*),(SELECT count(*) FROM {$db_prefix}_tweet WHERE n_user=:n_user),(SELECT count(*) FROM {$db_prefix}_reply WHERE n_user=:n_user),(SELECT count(*) FROM {$db_prefix}_randomreply WHERE n_user=:n_user),(SELECT count(*) FROM {$db_prefix}_tl_reply WHERE n_user=:n_user),(SELECT count(*) FROM {$db_prefix}_time_tweet WHERE n_user=:n_user) FROM {$db_prefix}_tweet WHERE n_user=:n_user");
	$stmt->execute(array("n_user"=>$userc->getUserIndex()));
	while($row = $stmt->fetch(PDO::FETCH_BOTH)) break;
	?>
	<div style="font-size:15pt;font-weight:bold;">
		<img style="width:32px;height:32px;vertical-align:middle;" src="<?php echo $user['screen_image']?>" /> <?php echo $user['screen_name']; ?>: <?php echo $userc->isRunning()?"Running":"Paused"; ?>
	</div>
	<fieldset style="width:240px;height:100px;float:left;">
		<legend>Statistics</legend>
		<a href="/user/<?php echo $user['screen_name']."/".$id?>/tweets"><div style="width:180px;display:inline-block;text-align:right;margin-right:5px;">Registered Tweets: </div><?php echo $row[1]; ?></a><br />
		<a href="/user/<?php echo $user['screen_name']."/".$id?>/replies"><div style="width:180px;display:inline-block;text-align:right;margin-right:5px;">Registered Replies: </div><?php echo $row[2]; ?></a><br />
		<a href="/user/<?php echo $user['screen_name']."/".$id?>/random-replies"><div style="width:180px;display:inline-block;text-align:right;margin-right:5px;">Registered Random Replies: </div><?php echo $row[3]; ?></a><br />
		<a href="/user/<?php echo $user['screen_name']."/".$id?>/timeline-replies"><div style="width:180px;display:inline-block;text-align:right;margin-right:5px;">Registered Timeline Replies: </div><?php echo $row[4]; ?></a><br />
		<a href="/user/<?php echo $user['screen_name']."/".$id?>/time-tweets"><div style="width:180px;display:inline-block;text-align:right;margin-right:5px;">Registered Time Tweets: </div><?php echo $row[5]; ?></a><br />
	</fieldset>
	<fieldset style="width:240px;height:100px;float:left;">
		<legend>Control</legend>
		<?php if($userc->isRunning()){ ?>
			<form method="post" action="/user/<?php echo $user['screen_name']."/".$id?>/stat">
				<input type="hidden" name="act" value="pause" />
				<input type="submit" value="Pause" style="height:32px;width:80px;float:left;" />
			</form>
		<?php }else{ ?>
			<form method="post" action="/user/<?php echo $user['screen_name']."/".$id?>/stat">
				<input type="hidden" name="act" value="resume" />
				<input type="submit" value="Resume" style="height:32px;width:80px;float:left;" />
			</form>
	<?php } ?>
		<form method="post" action="/user/<?php echo $user['screen_name']."/".$id?>/stat" onsubmit="return confirm('Do you really want to remove this bot?');">
			<input type="hidden" name="act" value="remove" />
			<input type="submit" value="Remove" style="height:32px;width:80px;float:left;" />
		</form>
	</fieldset>
	<?php
}