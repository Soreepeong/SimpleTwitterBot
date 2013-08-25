<?php
require_once(__DIR__."/../core/lib.php");
$title="Simple Tweeting Bot";
$file_to_use=basename($_GET['__action']); $last_file_to_use="";
if($file_to_use=="") $file_to_use="main";
while(strcmp($last_file_to_use, $file_to_use)){
	if(!file_exists(__DIR__."/src/$file_to_use.php")) $file_to_use="404";
	$last_file_to_use=$file_to_use;
	include(__DIR__."/src/$file_to_use.php");
}
?><!doctype html><html><head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?php echo $title?></title>
	<link rel="stylesheet" media="screen" href="/css/main.css" />
	<script type="text/javascript" src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
	<script type="text/javascript" src="http://code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
	<script type="text/javascript" src="/js/main.js"></script>
</head><body>
	<div id="top">
		<a href="/"><span style="line-height:48px;color:white;">Simple Tweeting Bot</span></a>
	</div>
	<div id="downholder">
		<div id="left"></div>
		<div id="onleft">
			<h5>Bot list</h5>
			<?php if(isset($_SESSION['users'])) foreach($_SESSION['users'] as $id=>$user){ ?>
				<div class="bot">
					<a href="/user/<?php echo $user['screen_name']."/".$id?>/stat" style="font-weight:bold;font-size:10pt;"><img src="<?php echo $user['screen_image']; ?>" style="vertical-align:middle;height:24px; width:24px;" /> <?php echo $user['screen_name']; ?></a><br />
					<div style="font-size:9pt;">
						<a href="/user/<?php echo $user['screen_name']."/".$id?>/tweets">Tweets</a> | <a href="/user/<?php echo $user['screen_name']."/".$id?>/time-tweets">Time Tweets</a> | <a href="/user/<?php echo $user['screen_name']."/".$id?>/logout">Out</a><br />
						<a href="/user/<?php echo $user['screen_name']."/".$id?>/replies">Replies</a> (<a href="/user/<?php echo $user['screen_name']."/".$id?>/random-replies">Random</a> | <a href="/user/<?php echo $user['screen_name']."/".$id?>/timeline-replies">Timeline</a>)
					</div>
				</div>
			<?php } ?>
			<div id="login" style="font-size:9pt">
				<a href="/twitter-login">Add account from twitter</a>
				<a href="/group">Group Management</a><br />
			</div>
		</div>
		<div id="right">
			<?php printContent(); ?>
		</div>
	</div>
</body></html>