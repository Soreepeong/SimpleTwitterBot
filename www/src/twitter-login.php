<?php
$title="Sign in with Twitter - $title";
if(isset($_POST['signin-start'])){
	if(isset($_POST['use_default_keys'])){
		$_POST['s_consumer_key']="YOUR CONSUMER KEY HERE";
		$_POST['s_consumer_secret']="YOUR CONSUMER SECRET HERE";
	}
	$tmhOAuth = new tmhOAuth(array('consumer_key' => $_POST['s_consumer_key'], 'consumer_secret' => $_POST['s_consumer_secret']));
	$special_id=md5(serialize($_SERVER).time().uniqid());
	$params=array('oauth_callback' => tmhUtilities::php_self()."?oauth_my_key=$special_id");
	$code=$tmhOAuth->request('POST', $tmhOAuth->url('oauth/request_token', ''), $params);
	if($code==200) {
		$_SESSION[$special_id] = $tmhOAuth->extract_params($tmhOAuth->response['response']);
		$_SESSION[$special_id]['consumer_key']=$_POST['s_consumer_key'];
		$_SESSION[$special_id]['consumer_secret']=$_POST['s_consumer_secret'];
		$method = 'authorize';
		$authurl = $tmhOAuth->url("oauth/authorize", '')."?oauth_token={$_SESSION[$special_id]['oauth_token']}" . (isset($_POST['force_login'])?"&force_login=1":"");
		header("Location: " . $authurl);
		header("Status: 302 Moved");
		header("HTTP/1.1 302 Moved");
		die();
	} else {
		$errorToShow= 'Error: Invalid consumer key provided!';
	}
}else if(isset($_REQUEST['oauth_verifier'])){
	$special_id=$_GET['oauth_my_key'];
	$tmhOAuth = new tmhOAuth(array('consumer_key' => $_SESSION[$special_id]['consumer_key'], 'consumer_secret' => $_SESSION[$special_id]['consumer_secret']));
	$tmhOAuth->config['user_token']=$_SESSION[$special_id]['oauth_token'];
	$tmhOAuth->config['user_secret']=$_SESSION[$special_id]['oauth_token_secret'];
	$code = $tmhOAuth->request('POST', $tmhOAuth->url('oauth/access_token', ''), array('oauth_verifier' => $_REQUEST['oauth_verifier']));
	if($code==200){
		foreach(array_merge($_SESSION[$special_id], $tmhOAuth->extract_params($tmhOAuth->response['response'])) as $key=>$val){
			$_SESSION[$special_id][$key]=$val;;
		}
		header("Location: /twitter-login?final-login-check&oauth_my_key=$special_id");
	}else{
		$errorToShow= 'Error: ' . $tmhOAuth->response['response'];
	}
}else if(isset($_GET['final-login-check'])){
	$special_id=$_GET['oauth_my_key'];
	if(isset($_SESSION[$special_id])){
		$tmhOAuth = new tmhOAuth(array('consumer_key' => $_SESSION[$special_id]['consumer_key'], 'consumer_secret' => $_SESSION[$special_id]['consumer_secret']));
		$tmhOAuth->config['user_token']=$_SESSION[$special_id]['oauth_token'];
		$tmhOAuth->config['user_secret']=$_SESSION[$special_id]['oauth_token_secret'];
		$code = $tmhOAuth->request('GET', $tmhOAuth->url('1.1/account/verify_credentials'));
		if($code==200){
			$resp = json_decode($tmhOAuth->response['response'],true);
			$_SESSION['users'][$resp['id']]=array(
				'id'=>$resp['id'],
				'screen_name'=>$resp['screen_name'],
				'screen_image'=>$resp['profile_image_url_https'],
				'oauth_token'=>$_SESSION[$special_id]['oauth_token'],
				'oauth_token_secret'=>$_SESSION[$special_id]['oauth_token_secret'],
				'consumer_key'=>$_SESSION[$special_id]['consumer_key'],
				'consumer_secret'=>$_SESSION[$special_id]['consumer_secret'],
			);
			$resultToShow="Login succeed: ".$resp['screen_name'];
			try {
				$stmt = $conn->prepare("SELECT * FROM {$db_prefix}_user_list WHERE n_user=:id");
				$stmt->execute(array('id' => $resp['id']));
				$exists=false;
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)) $exists=true;
				if(!$exists){
					$stmt=$conn->prepare("INSERT INTO {$db_prefix}_user_list (n_user, n_working, s_description, s_consumer_key, s_consumer_secret, s_user_token, s_user_token_secret) VALUES (:n_user, :n_working, :s_description, :s_consumer_key, :s_consumer_secret, :s_user_token, :s_user_token_secret)");
					$stmt->execute(array(
						"n_user"=>$resp['id'],
						"n_working"=>1,
						"s_description"=>$resp['screen_name'],
						's_user_token'=>$_SESSION[$special_id]['oauth_token'],
						's_user_token_secret'=>$_SESSION[$special_id]['oauth_token_secret'],
						's_consumer_key'=>$_SESSION[$special_id]['consumer_key'],
						's_consumer_secret'=>$_SESSION[$special_id]['consumer_secret'],
					));
				}
			} catch(PDOException $e) {
				die('ERROR: ' . $e->getMessage());
			}
		}else{
			$errorToShow= 'Error: ' . $tmhOAuth->response['response'];
		}
		unset($_SESSION[$special_id]);
	}
}
function printContent(){
	global $errorToShow, $resultToShow;
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
	<form method="post" action="/twitter-login">
		<input type="hidden" name="signin-start" value="1" />
		<table>
			<tr>
				<th>Consumer Key</th>
				<td style="width:2px;"></td>
				<td><input type="text" name="s_consumer_key" style="width:240px;" /></td>
			</tr>
			<tr>
				<th>Consumer Secret</th>
				<td style="width:2px;"></td>
				<td><input type="text" name="s_consumer_secret" style="width:240px;" /></td>
			</tr>
			<tr>
				<th></th>
				<td style="width:2px;"></td>
				<td>
					<label for="chk_use_default_keys"><input type="checkbox" checked="checked" name="use_default_keys" id="chk_use_default_keys" /> Use Default</label>
					<label for="chk_force_login"><input type="checkbox" checked="checked" name="force_login" id="chk_force_login" /> Force Login</label>
				</td>
			</tr>
			<tr>
				<th></th>
				<td>
					<input type="submit" value="Sign in" style="width:80px; height:32px;" />
				</td>
			</tr>
		</table>
	</form>
	<?php
}