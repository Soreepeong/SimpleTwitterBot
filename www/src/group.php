<?php
$title="Group Management - $title";
function encrypt($decrypted, $password, $salt='rjetsw5$%t358$#%@I*ergsgsr') { 
	$key = hash('SHA256', $salt . $password, true);
	srand(); $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
	if (strlen($iv_base64 = rtrim(base64_encode($iv), '=')) != 22) return false;
	$encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $decrypted . md5($decrypted), MCRYPT_MODE_CBC, $iv));
	return $iv_base64 . $encrypted;
} 
function decrypt($encrypted, $password, $salt='rjetsw5$%t358$#%@I*ergsgsr') {
	$key = hash('SHA256', $salt . $password, true);
	$iv = base64_decode(substr($encrypted, 0, 22) . '==');
	$encrypted = substr($encrypted, 22);
	$decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($encrypted), MCRYPT_MODE_CBC, $iv), "\0\4");
	$hash = substr($decrypted, -32);
	$decrypted = substr($decrypted, 0, -32);
	if (md5($decrypted) != $hash) return false;
	return $decrypted;
}
function idpwToStr($id, $pw){
	return __DIR__."/../../bot/group/".sha1(urlencode($id)."=".urlencode($pw).'2s4yhrs0gwsnsdfs$WTY4tg3424#@w4tgwsr%^#%$wg2fesf4wgrgs76azzhbpsdfodce96').".data";
}
if(isset($_POST['act'])){
	if($_POST['act']=='export'){
		$d=$_SESSION['users'];
		foreach($d as $key=>$val){
			$stmt=$conn->prepare("SELECT * FROM {$db_prefix}_user_list WHERE n_index=:n_user");
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)) $d[$key]=array_merge($d[$key], $row);
			foreach(array('tweet', 'reply', 'tl_reply', 'randomreply', 'time_tweet') as $table_name){
				$res=array();
				$stmt = $conn->prepare("SELECT * FROM {$db_prefix}_{$table_name} WHERE n_user=:n_user");
				$stmt->execute(array("n_user"=>$key));
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)) $res[]=$row;
				$d[$key]['data'][$table_name]=$res;
			}
		}
		if($_POST['as']==1 || $_POST['as']==2){
			$new=array();
			foreach($d as $key=>$val){
				unset($d[$key]['oauth_token'], $d[$key]['oauth_token_secret'], $d[$key]['consumer_key'], $d[$key]['consumer_secret']);
			}
			if($_POST['as']==1){ $file_data=json_encode($d); $fext="json"; }
			else if($_POST['as']==2){ $file_data=print_r($d,true); $fext="txt"; }
		}else{
			$file_data=encrypt(serialize(array_merge($d,array(-1=>md5(time().rand(1,32767).uniqid().serialize($_SERVER))))), $_POST['s_password']);
			$fext="botinfo";
		}
		header("Content-Type: application/x-force-download");
		header("Content-Length: " . strlen($file_data));
		header("Content-Disposition: attachment; filename*=UTF-8''".rawurlencode($_POST['s_id'].".whole.$fext"));
		die($file_data);
	}else if($_POST['act']=='loaddata'){
		$fn=idpwToStr($_POST['s_id'], $_POST['s_password']);
		if(isset($_FILES["login_file"])){
			$fn=$_FILES["login_file"]["tmp_name"];
			if(file_exists($fn)){
				$f=@unserialize(@decrypt(file_get_contents($fn), $_POST['s_password']));
				if(is_array($f)){
					if(!isset($_SESSION['users'])) $_SESSION['users']=array();
					$c=0;
					foreach($f as $key=>$val){
						if($key==-1) continue;
						if(!isset($val['screen_name'])) continue;
						$c+=isset($_SESSION['users'][$key])?0:1;
						$_SESSION['users'][$key]=$val;
					}
					$resultToShow="Loaded $c user information.";
				}else{
					$errorToShow="Wrongly formatted file, or bad password provided!";
				}
			}
		}else{
			$fn=idpwToStr($_POST['s_id'], $_POST['s_password']);
			if(file_exists($fn)){
				$f=unserialize(decrypt(file_get_contents($fn), $_POST['s_password']));
				if(!isset($_SESSION['users'])) $_SESSION['users']=array();
				$c=0;
				foreach($f as $key=>$val){
					if($key==-1) continue;
					if(!isset($val['screen_name'])) continue;
					$c+=isset($_SESSION['users'][$key])?0:1;
					$_SESSION['users'][$key]=$val;
				}
				$resultToShow="Loaded $c user information.";
			}
		}
	}else if($_POST['act']=='savedata'){
		if(isset($_SESSION['users'])){
			$fn=idpwToStr($_POST['s_id'], $_POST['s_password']);
			file_put_contents($fn, $file_data=encrypt(serialize(array_merge($_SESSION['users'],array(-1=>md5(time().rand(1,32767).uniqid().serialize($_SERVER))))), $_POST['s_password']));
			if(isset($_POST['remove']))
				unlink($fn);
			if(isset($_POST['download'])){
				header("Content-Type: application/x-force-download");
				header("Content-Length: " . strlen($file_data));
				header("Content-Disposition: attachment; filename*=UTF-8''".rawurlencode($_POST['s_id'].".botinfo"));
				die($file_data);
			}
			$resultToShow="Saved " . count($_SESSION['users']) . " user information.";
		}
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
	<div style="font-size:15pt;font-weight:bold;">
		Account Group Management
	</div>
	<fieldset style="float:left;width:260px;">
		<legend>Load/Append Group Credentials</legend>
		<form method="post" action="/group" enctype="multipart/form-data">
			<input type="hidden" name="act" value="loaddata" />
			<table>
				<tr>
					<th>ID</th>
					<td style="width:2px;"></td>
					<td><input type="text" name="s_id" style="width:180px;" /></td>
				</tr>
				<tr>
					<th>Password</th>
					<td style="width:2px;"></td>
					<td><input type="password" name="s_password" style="width:180px;" /></td>
				</tr>
				<tr>
					<th>Passfile</th>
					<td style="width:2px;"></td>
					<td><input type="file" name="login_file" style="width:180px;" /></td>
				</tr>
				<tr>
					<th></th>
					<td style="width:2px;"></td>
					<td><input type="submit" value="Load" style="width:80px;height:32px;" /></td>
				</tr>
			</table>
		</form>
	</fieldset>
	<fieldset style="float:left;width:260px;">
		<legend>Save Group Credentials</legend>
		<form method="post" action="/group">
			<input type="hidden" name="act" value="savedata" />
			<table>
				<tr>
					<th>ID</th>
					<td style="width:2px;"></td>
					<td><input type="text" name="s_id" style="width:180px;" /></td>
				</tr>
				<tr>
					<th>Password</th>
					<td style="width:2px;"></td>
					<td><input type="password" name="s_password" style="width:180px;" /></td>
				</tr>
				<tr>
					<th></th>
					<td style="width:2px;"></td>
					<td><label for="chk_download"><input type="checkbox" id="chk_download" name="download" value="1" />Download as file</label></td>
				</tr>
				<tr>
					<th></th>
					<td style="width:2px;"></td>
					<td><label for="chk_remove"><input type="checkbox" id="chk_remove" name="remove" value="1" />Don't save user info in here</label></td>
				</tr>
				<tr>
					<th></th>
					<td style="width:2px;"></td>
					<td><input type="submit" value="Save" style="width:80px;height:32px;" /></td>
				</tr>
			</table>
		</form>
	</fieldset>
	<fieldset style="float:left;width:260px;">
		<legend>Export Account Group</legend>
		<form method="post" action="/group">
			<input type="hidden" name="act" value="export" />
			<table>
				<tr>
					<th>Filename</th>
					<td style="width:2px;"></td>
					<td><input type="text" name="s_id" style="width:180px;" /></td>
				</tr>
				<tr>
					<th>Password</th>
					<td style="width:2px;"></td>
					<td><input type="password" id="txt_s_password" name="s_password" style="width:180px;" /></td>
				</tr>
				<tr>
					<th></th>
					<td style="width:2px;"></td>
					<td>
						Export as: <select name="as">
							<option value="0">Internal use</option>
							<option value="1">JSON</option>
							<option value="2">Text</option>
						</select>
					</td>
				</tr>
				<tr>
					<th></th>
					<td style="width:2px;"></td>
					<td><input type="submit" value="Export" style="width:80px;height:32px;" /></td>
				</tr>
			</table>
		</form>
	</fieldset>
	<?php
}