<!DOCTYPE html>
<html>
<head>
	<title>Message</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Bootstrap -->
	<link href="../../css/bootstrap.min.css" rel="stylesheet" media="screen">
	<style type="text/css">
	
	body {
		margin-top: 20px;
	}
	
	</style>
</head>
<body>
	<script src="../../js/jquery.js"></script>
	<script src="../../js/bootstrap.min.js"></script>
	<script type="text/javascript">
	
	</script>
	
	<div class="container">
    
<?php
require_once(__DIR__.'/../../lib/common.php');
require_once('app_info.php');

if ($_POST["submit"]) {
	$apiUrl = 'http://localhost:'.$_SERVER["SERVER_PORT"].'/api/users/id/'.$_POST["user_id"];
	$data = getJsonFromUrl($apiUrl);
	if ($data["success"] == false) {
?>
	<em><strong>Error:</strong> Invalid User ID</em>
<?php
	}
	else {
		$user = $data["data"];
		$apiUrl = "http://localhost:{$_SERVER['SERVER_PORT']}/api/users/me";
		$me = getJsonFromUrl($apiUrl);
		$me = $me["data"];
		
		$apiUrl = "http://{$user['user_ip']}:{$_SERVER['SERVER_PORT']}/api/apps/message";
		//echo $apiUrl."<br/>";
		$json = array('app_id' => $app_id, 'author_id' => $me['user_id'], 'message' => $_POST['message']);
		
		//echo "<p>".urlencode(json_encode($json))."</p>";
		$result = postJsonToUrl($apiUrl, $json);
		
		if (!$result["success"]) {
?>
	<em><strong>Error:</strong> Unable to send message to <?php echo $user["user_name"]; ?></em>
<?php
		}
		else {
?>
	<em>Successfully send message to <?php echo $user["user_name"]; ?></em>
<?php
		}
	}
}
?>
	<form class="form-horizontal" method="post">
		<legend>Send message</legend>
		<div class="form-group">
			<label class="control-label col-lg-2" for="user_id">To</label>
			<div class="col-lg-4">
				<input type="text" class="form-control" id="user_id" name="user_id" <?php if ($_GET['user_id']) { echo "value=\"{$_GET['user_id']}\" "; } ?>/>
			</div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-lg-2" for="message">Message</label>
			<div class="col-lg-4">
				<textarea class="form-control" id="message" name="message"></textarea>
			</div>
		</div>
		
		<div class="form-group">
			<div class="col-offset-2 col-lg-4">
				<button type="submit" class="btn btn-default" name="submit" value="submit">Send</button>
			</div>
		</div>
	</form>
	
	<a href="index.php">< Back to Home</a>
	
	</div> <!-- container -->
</body>
</html>