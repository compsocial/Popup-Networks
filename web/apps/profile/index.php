<!DOCTYPE html>
<html>
<head>
	<title>Profile</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Bootstrap -->
	<link href="../../css/bootstrap.min.css" rel="stylesheet" media="screen">
	<style type="text/css">
		body {
			background-color: #EDECE9;
		}
		
		.container {
			width: 875px;
			background-color: #FFFFFF;
			padding: 20px 35px 30px 35px;
			margin-top: 35px;
			border-radius: 6px;
		}
		
		dd {
			margin-bottom: 10px;
		}
		
		.section {
			margin-top: 40px;
			margin-bottom: 47px;
		}
		
		.grid-2 {
			overflow: hidden;
		}
		
		.block {
			height: auto;
			overflow: hidden;
			margin: 20px 0px;
		}
		
		.grid-2 .block {
			float: left;
			width: 50%;
			padding: 10px;
		}
		
		.left {
			width: 120px;
			float: left;
		}
		
		.right {
			float: none;
			width: auto;
			overflow: hidden;
		}
		
		.profilepic {
			position: absolute;
		}
		
		.pic-container {
			position: relative;
			width: 100px;
			height: 100px;
		}
		
		.mid {
			margin-bottom: 10px;
			overflow: hidden;
			font-size: 16px;
			color: #999;
		}
		
		.bottom {
			font-size: 14px;
		}
		
		.block h3 {
			font-size: 33px;
		}
		
		.message-sender {
			font-size: 20px;
			font-weight: 500;
		}
		
		.message {
			font-size: 21px;
			font-weight: lighter;
		}
	</style>
</head>
<body>
	<script src="../../js/jquery.js"></script>
	<script src="../../js/bootstrap.min.js"></script>
	<script type="text/javascript">
		function scaleImage(srcwidth, srcheight, targetwidth, targetheight, fLetterBox) {

			var result = { width: 0, height: 0, fScaleToTargetWidth: true };

			if ((srcwidth <= 0) || (srcheight <= 0) || (targetwidth <= 0) || (targetheight <= 0)) {
				return result;
			}

			// scale to the target width
			var scaleX1 = targetwidth;
			var scaleY1 = (srcheight * targetwidth) / srcwidth;

			// scale to the target height
			var scaleX2 = (srcwidth * targetheight) / srcheight;
			var scaleY2 = targetheight;

			// now figure out which one we should use
			var fScaleOnWidth = (scaleX2 > targetwidth);
			if (fScaleOnWidth) {
				fScaleOnWidth = fLetterBox;
			}
			else {
			   fScaleOnWidth = !fLetterBox;
			}

			if (fScaleOnWidth) {
				result.width = Math.floor(scaleX1);
				result.height = Math.floor(scaleY1);
				result.fScaleToTargetWidth = true;
			}
			else {
				result.width = Math.floor(scaleX2);
				result.height = Math.floor(scaleY2);
				result.fScaleToTargetWidth = false;
			}
			result.targetleft = Math.floor((targetwidth - result.width) / 2);
			result.targettop = Math.floor((targetheight - result.height) / 2);

			return result;
		}
	
		function onProfilePicLoad(event) {
			var img = event.currentTarget;
		
			// what's the size of this image and it's parent
			var w = $(img).width();
			var h = $(img).height();
			var tw = $(img).parent().width();
			var th = $(img).parent().height();

			// compute the new size and offsets
			var result = scaleImage(w, h, tw, th, true);

			// adjust the image coordinates and size
			img.width = result.width;
			img.height = result.height;
			$(img).css("left", result.targetleft);
			$(img).css("top", result.targettop);
		}
	</script>
  
	<div class="container">
	<!-- <h1>Profile</h1> -->
<?php
require_once(__DIR__.'/../../lib/common.php');
require_once('app_info.php');

function revMessageTimestampCmp($a, $b) {
	date_default_timezone_set('America/New_York');
	$d1 = date('Y-m-d H:i:s', strtotime($a['timestamp'] . ' GMT'));
	$d2 = date('Y-m-d H:i:s', strtotime($b['timestamp'] . ' GMT'));
	
	if ($d1 == $d2)
		return 0;
	else if ($d1 < $d2)
		return 1;
	else
		return -1;
}

$systemName = "BlockParty";

$localBaseApi = "http://localhost:{$_SERVER['SERVER_PORT']}/api";

$apiUrl = "$localBaseApi/users/me";
$me = getJsonFromUrl($apiUrl);
if ($me == null || !$me['success']) {
	die("Error connecting to local api");
}
else {
	$me = $me['data'];
}

if ($_POST["submit"] == "submit") {
	$apiUrl = "$localBaseApi/users";
	$user_name = $_POST['user_name'];
	$user_id = $_POST['user_id'];
	$user_ip = $_POST['user_ip'];
	$user_email = $_POST['user_email'];
    $user_address = $_POST['user_address'];
	$user_bio = $_POST['user_bio'];
	$user_picture_path = $_POST['user_picture_path'];
    $movein_date = $_POST['movein_date'];
	$join_date = $_POST['join_date'];
	
	$json = array('user_name' => $user_name, 'user_id' => $user_id, 'user_ip' => $user_ip, 
		'user_email' => $user_email, 'user_bio' => $user_bio, 'user_picture_path' => $user_picture_path, 
        'user_address' => $user_address, 'movein_date' => $movein_date, 
		'join_date' => $join_date);
	$result = postJsonToUrl($apiUrl, $json);
	
	if (!$result["success"]) {
		var_dump($result);
?>
	<h3>Unable to add <?php echo $user_name; ?> to database</h3>
<?php
		die();
	}
}

$userId = $_GET["user_id"];
$userIp = $_GET["user_ip"];
if ($userId != null && strlen($userId) > 0) {
	// known user
	$apiUrl = "$localBaseApi/users/id/$userId";
	$user = getJsonFromUrl($apiUrl);
	
	if ($user["success"]) {
		$user = $user["data"];
?>
	<h2><?php echo "{$user['user_name']} ({$user['user_id']})"; ?></h2>
	
	<div class="alert">
	
<?php
	$apiUrl = "$localBaseApi/whovouchfor/id/{$user['user_id']}";
	$whovouch = getJsonFromUrl($apiUrl);
	if ($whovouch['success']) {
		$whovouch = $whovouch['data'];
	}
	else {
		$whovouch = array();
	}
	
	$countWhovouch = count($whovouch);
	
	$whovouchYouvouch = array();
	foreach ($whovouch as $vouchuser) {
		$apiUrl = "$localBaseApi/vouchfor/id/{$vouchuser['user_id']}";
		$result = getJsonFromUrl($apiUrl);
		if ($result['success'] && $result['data']['vouched']) {
			array_push($whovouchYouvouch, $vouchuser);
		}
	}
	
	$countWhovouchYouvouch = count($whovouchYouvouch);
	
	$apiUrl = "$localBaseApi/vouchfor/status/id/{$user['user_id']}";
	$vouch = getJsonFromUrl($apiUrl);
	if ($vouch["success"]) {
		$vouch = $vouch["data"];
?>
	<script type="text/javascript">
		
		$(document).ready(function () {
			$("div#<?php echo $vouch['status']; ?>").show();
			$("#vouchForm").submit(function () {
				return false;
			});
		});
		
		function vouch () {
			$("div#notvouch").hide();
			$("div#vouching").show();
			
			data = {
				user_id: "<?php echo $user['user_id']; ?>",
				vouch_status: "notvouch",
				button: "submit"
			};
			
			$.ajax({
				url: "vouch.php",
				type: "post",
				data: data
			}).done(function (result) {
				//console.log(result);
				result = JSON.parse(result);
				if (result['success']) {
					$("div#vouching").hide();
					$("div#waiting").show();
				}
				else {
					//console.log(result);
					$("div#vouching > p").text("There's an error. Please try again later.");
				}
			}).fail(function (error) {
				//console.log(error);
				$("div#vouching > p").text("Vouching failed. Please try again later.");
			});
			
			return false;
		}
		
		function confirm () {
			if ($("#confirmcode").val().length == 0) {
				alert("Confirmation code can't be empty.");
				$("#confirmcode").focus();
			}
			else {
				$("div#waiting").hide();
				$("div#vouching").show();
				
				$("confirmbutton").attr("disabled", "disabled");
				data = {
					user_id: "<?php echo $user['user_id']; ?>",
					vouch_status: "waiting",
					code: $("#confirmcode").val(),
					button: "submit"
				};
			
				$.ajax({
					url: "vouch.php",
					type: "post",
					data: data
				}).done(function (result) {
					//console.log(result);
					result = JSON.parse(result);
					if (result['success']) {
						$("div#vouching").hide();
						$("div#vouched").show();
						
						data['vouch_status'] = "vouched";
						delete data['code'];
						$.ajax({
							url: "vouch.php",
							type: "post",
							data: data
						}).done(function (result) {
							//console.log(result);
							return false;
						}).fail(function (error) {
							//console.log(error);
							return false;
						});
					}
					else if (result['message'] == "invalid confirmation code") {
						//console.log(result);
						$("div#vouching").hide();
						$("div#waiting").show();
						alert("Invalid confirmation code. Please re-enter confirmation code.");
						$("#confirmcode").focus();
					}
					else {
						//console.log(result);
						$("div#vouching > p").text("There's an error. Please try again later.");
					}
					
					return false;
				}).fail(function (error) {
					//console.log(error);
					$("div#vouching > p").text("Vouching failed. Please try again later.");
					return false;
				});
			}
			
			return false;
		}
	</script>
	<div id="notvouch" style="display: none;">
		<p>
<?php
		if ($countWhovouch > 0) {
?>
			<?= $countWhovouch ?> users have vouched for <?= $user['user_name'] ?> (<?= $countWhovouchYouvouch ?> of them you've vouched for).<br/>
			<a href="#" onclick="vouch()">Click here to start vouching.</a>
<?php
		}
		else {
?>
			No one has vouched for <?= $user['user_name'] ?> yet.<br/>
			<a href="#" onclick="vouch()">Click here to be the first one!</a>
<?php
		}
?>
		</p>
	</div>
	
	<div id="vouched" style="display: none;">
		<p>
			You and <?php echo "$countWhovouch other user"; if ($countWhovouch != 1) echo "s"; ?> have vouched for <?= $user['user_name'] ?>
		</p>
	</div>
	
	<div id="waiting" style="display: none;">
		<p>
			Enter vouching confirmation code from <?php echo $user['user_name']; ?>
			<form id="vouchForm" class="form-inline" role="form">
				<div class="form-group">
					<label class="sr-only" for="confirmcode">Enter confirmation code from <?php echo $user['user_name']; ?></label>
					<input id="confirmcode" type="text" class="col-md-4 form-control" placeholder="Confirmation Code" />	
				</div>
				<button id="confirmbutton" class="btn btn-default" onclick="confirm()">Submit</button>
			</form>
		</p>
	</div>
	
	<div id="vouching" style="display: none;">
		<p>Vouching for <?php echo $user['user_name']; ?>...</p>
	</div>
<?php
	}
?>
	
	
	</div>
	<dl class="dl-horizontal">
		<dt></dt>
		<dd>
			<?php echo isset($user["user_picture_path"])?
			"<img id=\"image\" width=200 src=\"http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}/api/img/" . 
				urlencode("http://{$user['user_ip']}:{$_SERVER['SERVER_PORT']}/{$user['user_picture_path']}") . 
				"\" />"
			:"Not Available"; ?>
		</dd>
		
		<dt>User ID</dt>
		<dd><?php echo $user["user_id"]; ?></dd>
		
		<dt>Name</dt>
		<dd><?php echo $user["user_name"]; ?></dd>
		
		<dt>Email Address</dt>
		<dd><?php echo isset($user["user_email"])?$user["user_email"]:"Not Available"; ?></dd>
        
        <dt>Address</dt>
        <dd><?php echo isset($user["user_address"])?$user["user_address"]:"Not Available"; ?></dd>
        
        <dt>Movein Date</dt>
        <dd><?php echo isset($user["movein_date"])?$user["movein_date"]:"Not Available"; ?></dd>
		
		<dt>Bio</dt>
		<dd><?php echo isset($user["user_bio"])?$user["user_bio"]:"Not Available"; ?></dd>
		
		<dt>IP Address</dt>
		<dd><?php echo $user["user_ip"]; ?></dd>
		
		<dt>Join Date</dt>
		<dd><?php echo $user["join_date"]; ?></dd>
		
		<dt>Known Since</dt>
		<dd><?php echo $user["known_date"]; ?></dd>
	</dl>
	
	<!--
	<div>
		<p><a href="message.php?user_id=<?php echo $user["user_id"]; ?>">Send a message to <?php echo $user['user_name']; ?></a></p>
	</div>
	-->
	
	<div>
		<p><a href="index.php">< Back to Home</a></p>
	</div>
<?php
	}
	else {
		$apiUrl = "$localBaseApi/log";
		$log_data = array("app_id" => $app_id, "log" => "looking up no user with id $userId");
		postJsonToUrl($apiUrl, $log_data);
?>
	<h3>No users with user_id "<?php echo $userId; ?>"</h3>
<?php
	}
}
else if ($userIp != null && strlen($userIp) > 0) {
	// unknown user
	$apiUrl ="http://$userIp:".$_SERVER["SERVER_PORT"]."/api/users/me";
	$user = getJsonFromUrl($apiUrl);
	
	if ($user["success"]) {
		$user = $user["data"];
		
		$apiUrl = "$localBaseApi/log";
		$log = array("message" => "unknown user", "user" => $user);
		$log_data = array("app_id" => $app_id, "log" => json_encode($log));
		postJsonToUrl($apiUrl, $log_data);
?>
	<h2><?php echo "{$user['user_name']} ({$user['user_id']})"; ?></h2>
	<form method="post" action="index.php">
		<dl class="dl-horizontal">
			<input type="hidden" name="user_picture_path" value="<?php echo $user["user_picture_path"]; ?>" />
			<dt></dt>
			<dd>
				<?php echo isset($user["user_picture_path"])?
				"<img id=\"image\" width=200 src=\"http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}/api/img/" . 
					urlencode("http://$userIp:{$_SERVER['SERVER_PORT']}/{$user['user_picture_path']}") . 
					"\" />"
				:"Not Available"; ?>
			</dd>
			
			<input type="hidden" name="user_id" value="<?php echo $user["user_id"]; ?>" />
			<dt>User ID</dt>
			<dd><?php echo $user["user_id"]; ?></dd>
			
			<input type="hidden" name="user_name" value="<?php echo $user["user_name"]; ?>" />
			<dt>Name</dt>
			<dd><?php echo $user["user_name"]; ?></dd>
			
			<input type="hidden" name="user_email" value="<?php echo $user["user_email"]; ?>" />
			<dt>Email Address</dt>
			<dd><?php echo isset($user["user_email"])?$user["user_email"]:"Not Available"; ?></dd>
            
            <input type="hidden" name="user_address" value="<?php echo $user["user_address"]; ?>" />
            <dt>Address</dt>
            <dd><?php echo isset($user["user_address"])?$user["user_address"]:"Not Available"; ?></dd>
            
            <input type="hidden" name="movein_date" value="<?php echo $user["movein_date"]; ?>" />
            <dt>Movein Date</dt>
            <dd><?php echo isset($user["movein_date"])?$user["movein_date"]:"Not Available"; ?></dd>
			
			<input type="hidden" name="user_bio" value="<?php echo $user["user_bio"]; ?>" />
			<dt>Bio</dt>
			<dd><?php echo isset($user["user_bio"])?$user["user_bio"]:"Not Available"; ?></dd>
	
			<input type="hidden" name="user_ip" value="<?php echo $userIp; ?>" />
			<dt>IP Address</dt>
			<dd><?php echo $userIp; ?></dd>
			
			<input type="hidden" name="join_date" value="<?php echo $user["join_date"]; ?>" />
			<dt>Join Date</dt>
			<dd><?php echo $user["join_date"]; ?></dd>
		</dl>
		<button type="submit" name="submit" value="submit" class="btn btn-primary" style="margin-bottom: 15px;">Add to Known Contacts</button>
	</form>
	
	<p>To vouch for <?php echo $user["user_name"]; ?>, you must first add them to known contacts.</p>
	
	<p><a href="index.php">< Back to Home</a></p>
<?php
	}
}
else {
	/***************
	 * Get pending vouch code
	 ***************/
	 $apiUrl = "$localBaseApi/vouchcode";
	 $codes = getJsonFromUrl($apiUrl);
	 
	 if ($codes['success'] && count($codes['data']) > 0) {
	 	$codes = $codes['data'];
?>
	<div class="alert">
<?php
		foreach ($codes as $code) {
?>
		<div>
			<p><?= $code['user_name'] ?> would like to vouch for you! Give them confirmation code: <strong><?= $code['code'] ?></strong></p>
		</div>
<?php
		}
?>
	</div>
<?php
	 }
	
	/***************
	 * Get known connections
	 ***************/
	$apiUrl = "$localBaseApi/users";
	$users = getJsonFromUrl($apiUrl);
?>
	<div class="section">
		<h2>Your connections</h2>
<?php
	if ($users["success"] && count($users["data"]) > 0) {
		$users = $users["data"];
?>
		<div class="grid-2">
<?php
		foreach ($users as $user) {
			if ($user['user_ip'] == "127.0.0.1") continue;
?>
			<div class="block">
				<div class="left">
					<div class="pic-container">
						<?php echo isset($user["user_picture_path"])?
						"<img class=\"profilepic\" src=\"http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}/api/img/" . 
							urlencode("http://{$user['user_ip']}:{$_SERVER['SERVER_PORT']}/{$user['user_picture_path']}") . 
							"\" alt=\"{$user['user_name']}\" onload=\"onProfilePicLoad(event)\" />"
						:
						"<img class=\"profilepic\" src=\"img/blank-photo.jpg\" onload=\"onProfilePicLoad(event)\" />"
						; ?>
					</div>
				</div>
				<div class="right">
					<div class="top">
						<h3 style="margin-top: 0px;"><a href="index.php?user_id=<?= $user['user_id'] ?>"><?= $user['user_name'] ?></a></h3>
					</div>
					<div class="mid">
						<?= $user['user_bio'] ?>
					</div>
					<div class="bottom">
						Known since <?= date('F j, Y', strtotime($user['known_date'])) ?>
					</div>
				</div>
			</div>
<?php
		}
?>
		</div>
<?php
	}
	else {
?>
		<p class="lead">You don't have any know connections</p>
<?php
	}
?>
	</div>
	
	<div class="section">
		<h2>Other people nearby</h2>
<?php
	/***************
	 * Get nearby users
	 ***************/
	$apiUrl = "$localBaseApi/users/nearby/unknown";
	$data = getJsonFromUrl($apiUrl);
	if ($data["success"] && count($data["data"]) > 0) {
		$nearby = $data["data"];
		
		$apiUrl = "$localBaseApi/log";
		$log = array("message" => "nearby", "nearby" => $nearby);
		$log_data = array("app_id" => $app_id, "log" => json_encode($log));
		postJsonToUrl($apiUrl, $log_data);
?>
		<div class="grid-2">
<?php		
		foreach ($nearby as $user) {
?>
			<div class="block">
				<div class="left">
					<div class="pic-container">
						<?php echo isset($user["user_picture_path"])?
						"<img class=\"profilepic\" src=\"http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}/api/img/" . 
							urlencode("http://{$user['user_ip']}:{$_SERVER['SERVER_PORT']}/{$user['user_picture_path']}") . 
							"\" alt=\"{$user['user_name']}\" onload=\"onProfilePicLoad(event)\" />"
						:
						"<img class=\"profilepic\" src=\"img/blank-photo.jpg\" onload=\"onProfilePicLoad(event)\" />"
						; ?>
					</div>
				</div>
				<div class="right">
					<div class="top">
						<h3 style="margin-top: 0px;"><a href="index.php?user_ip=<?= $user['user_ip'] ?>"><?= $user['user_name'] ?></a></h3>
					</div>
					<div class="mid">
						<?= $user['user_bio'] ?>
					</div>
					<div class="bottom">
						Joined <?= $systemName ?> on <?= date('F j, Y', strtotime($user['join_date'])) ?>
					</div>
				</div>
			</div>
<?php
		}
?>
		</div>
<?php
	}
	else {
?>
		<p class="lead">There are no unknown nearby users</p>
<?php
	}
?>

	</div>
	
	<!--
	<div class="section">	
		<h2>Inbox</h2>
<?php
	/***************
	 * Get message
	 ***************/
	 /* remove message functionality
	 	migrate to email
	 */
	 /*
	 $apiUrl = "$localBaseApi/apps/message";
	 $apiUrl .= '?app_id='.$app_id;
	 $apiUrl .= '&order_by='.urlencode('timestamp desc');
	 $data = getJsonFromUrl($apiUrl);
	 if ($data["success"] && count($data["data"]) > 0) {
	 	$messages = $data["data"];
		foreach($messages as $message) {
			$apiUrl = "$localBaseApi/users/id/{$message['author_id']}";
			$data = getJsonFromUrl($apiUrl);
			if ($data['success']) {
				$user = $data['data'];
?>
		<div class="block">
			<div class="left">
				<?php echo isset($user["user_picture_path"])?
				"<img class=\"profilepic\" src=\"http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}/api/img/" . 
					urlencode("http://{$user['user_ip']}:{$_SERVER['SERVER_PORT']}/{$user['user_picture_path']}") . 
					"\" alt=\"{$user['user_name']}\"/>"
				:
				"<img class=\"profilepic\" src=\"img/blank-photo.jpg\" />"
				; ?>
			</div>
			<div class="right">
				<div class="top">
					From: <span class="message-sender"><?= $user['user_name'] ?></span>
				</div>
				<div class="message">
					<?= $message['message'] ?>
				</div>
			</div>
		</div>
	<!--	<li><?php echo "<b>{$message['author_id']}</b>: {$message['message']}"; ?></li>-->
<?php
			}
		}
?>
<?php
	 }
	 else {
?>
		<p class="lead">There are no messages in your inbox</p>
<?php
	 }
?>
	</div>
	-->
<?php
	*/
}
?>

	</div> <!-- container -->
</body>
</html>