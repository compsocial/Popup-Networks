
	
<?php
require_once(__DIR__.'/../../lib/common.php');
require_once('app_info.php');

function echo_error($message) {
	$echo = array('success' => false, 'message' => $message);
	die(json_encode($echo, JSON_PRETTY_PRINT));
}

function echo_success($message) {
	$echo = array('success' => true, 'message' => $message);
	echo json_encode($echo, JSON_PRETTY_PRINT);
}

if ($_POST['button'] == "submit") {
	$localBaseApi = "http://localhost:{$_SERVER['SERVER_PORT']}/api";
	
	$apiUrl = "$localBaseApi/users";
	if (!isset($_POST['user_id'])) {
		echo_error("missing required parameter \"user_id\"");
	}
	
	if (!isset($_POST['vouch_status'])) {
		echo_error("missing required parameter \"vouch_status\"");
	}
	else if ($_POST['vouch_status'] != "notvouch" &&
		$_POST['vouch_status'] != "waiting" &&
		$_POST['vouch_status'] != "vouched") {
		echo_error("invalid value of \"vouch_status\"");
	}
	else if ($_POST['vouch_status'] == "waiting" && !isset($_POST['code'])) {
		echo_error("missing required parameter \"code\"");
	}
	
	$apiUrl = "$localBaseApi/users/me";
	$me = getJsonFromUrl($apiUrl);
	if ($me == null || !$me['success']) {
		echo_error("Error connecting to local api");
	}
	else {
		$me = $me['data'];
	}
	
	$apiUrl = "$localBaseApi/users/id/{$_POST['user_id']}";
	$user = getJsonFromUrl($apiUrl);
	if ($user == null || !$user['success']) {
		echo_error("user is unknown");
	}
	else {
		$user = $user['data'];
	}
	
	$data = array("user_id" => $me['user_id'], "vouch_status" => $_POST['vouch_status']);
	
	if ($_POST['vouch_status'] == "waiting") {
		$data['code'] = $_POST['code'];
	}
	
	$log = array("message" => "vouch", "data" => $data);
	$log_data = array("app_id" => $app_id, "log" => json_encode($log));
	$apiUrl = "$localBaseApi/log";
	postJsonToUrl($apiUrl, $log_data);
	
	/*
	echo $apiUrl."\n";
	var_dump($data);
	echo "\n";
	*/
	
	
	$apiUrl = "http://{$user['user_ip']}:{$_SERVER['SERVER_PORT']}/api/vouchfor";
	$result = postJsonToUrl($apiUrl, $data);
	
	//echo $apiUrl;
	//var_dump($result);
	
	if ($result['success']) {
		if ($_POST['vouch_status'] == 'notvouch' || $_POST['vouch_status'] == 'waiting') {
			$apiUrl = "$localBaseApi/vouchfor/status/update";
			$data = array('user_id' => $_POST['user_id']);
			
			if ($_POST['vouch_status'] == 'notvouch') {
				$data['new_vouch_status'] = 'waiting';
			}
			else if ($_POST['vouch_status'] == 'waiting') {
				$data['new_vouch_status'] = 'vouched';
			}
			
			$result = postJsonToUrl($apiUrl, $data);
			if ($result['success']) {
				//echo "<h1>Successfully updating the vouch process</h1>";
				echo_success('Successfully updating the vouch process');
			}
			else {
				echo_error("error in updating vouch status ({$result['message']})");
			}
		}
	}
	else {
		if ($result['message'] == 'invalid confirmation code') {
			echo_error('invalid confirmation code');
		}
		else {
			echo_error("error in initiating the vouch process ({$result['message']})");
		}
	}
}
else {
?>
<!DOCTYPE html>
<html>
<head>
	<title>Vouch</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Bootstrap -->
	<link href="../../css/bootstrap.min.css" rel="stylesheet" media="screen">
</head>
<body>
	<script src="../../js/jquery.js"></script>
	<script src="../../js/bootstrap.min.js"></script>
	<script type="text/javascript">
		
	</script>
  
	<div class="container">
		<h1>Unauthorized Access</h1>
	</div> <!-- container -->
</body>
</html>
<?php
}
?>