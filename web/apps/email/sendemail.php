<?php
require_once(__DIR__.'/../../lib/common.php');
require_once('app_info.php');
require_once('nosql.php');

$localBaseApi = "http://localhost:{$_SERVER['SERVER_PORT']}/api";

$apiUrl = "$localBaseApi/users/me";
$me = getJsonFromUrl($apiUrl);
if ($me == null || !$me['success']) {
	die("Error connecting to local api");
}
else {
	$me = $me['data'];
}

if ($_POST["submit"]) {
	$apiUrl = "$localBaseApi/mymeship";
	$data = getJsonFromUrl($apiUrl);
	if ($data == null || !$data['success']) {
		$log = array("action" => "error", "message" => "error retrieve my mesh ip", "dump" => $data);
		$log_data = array("app_id" => $app_id, "log" => json_encode($log));
		$apiUrl = "$localBaseApi/log";
		postJsonToUrl($apiUrl, $log_data);
		
		die("Error retrieve mesh ip address");
	}
	$mymeship = $data['data']['ip'];
	
	$email = array();
	$email['sender_name'] = $me['user_name'];
	$email['sender_id'] = $me['user_id'];
	$email['sender_ip'] = $mymeship;
	$email['sender_picture_path'] = $me['user_picture_path'];
	$email['subject'] = $_POST['subject'];
	$email['body'] = $_POST['body'];
	$email['send_date'] = gmdate("M d Y H:i:s e", time());
	$email['read'] = false;
	
	$to = $_POST['to'];
	
	$log = array("action" => "send_email", "to" => $to, "email" => $email);
	$log_data = array("app_id" => $app_id, "log" => json_encode($log));
	$apiUrl = "$localBaseApi/log";
	postJsonToUrl($apiUrl, $log_data);
	
	try {
		$isvalidip = @inet_pton($to);
	} catch (ErrorException $e) {
		$isavalidip = false;
	}
	
	if ($isvalidip) {
		$ip = $to;
	}
	else {
		$apiUrl = "$localBaseApi/users/id/{$_POST['user_id']}";
		$data = getJsonFromUrl($apiUrl);
		if ($data['success']) {
			$user = $data['data'];
			$ip = $user['user_ip'];
		}
		else {
			die(json_encode(array('success' => false, 'message' => 'cannot find recipient')));
		}
	}
	
	$apiUrl = "http://$ip:{$_SERVER['SERVER_PORT']}/api/users/me";
	$result = getJsonFromUrl($apiUrl);
	
	if ($result['success']) {
		$recipient = $result['data'];
		$email['recipient_ip'] = $ip;
		$email['recipient_name'] = $recipient['user_name'];
		$email['recipient_id'] = $recipient['user_id'];
		$email['recipient_picture_path'] = $recipient['user_picture_path'];
	}
	else {
		die(json_encode(array('success' => false, 'message' => 'unable to retrieve recipient information')));
	}
	
	$key = $email['sender_id'].time();
	
	$apiUrl = "$localBaseApi/db/$app_id/$key";
	$json = packNoSql($email);
	$result = postJsonToUrl($apiUrl, $json);
	
	if (!$result['success']) {
		die(json_encode(array('success' => false, 'message' => 'unable to store email at localhost')));
	}
	
	$apiUrl = "http://$ip:{$_SERVER['SERVER_PORT']}/api/db/$app_id/$key";
	$result = postJsonToUrl($apiUrl, $json);
	
	if (!$result['success']) {
		die(json_encode(array('success' => false, 'message' => 'unable to store email at remote server')));
	}
	
	$apiUrl = "$localBaseApi/apps/message";
	$json = array('app_id' => $app_id, 'author_id' => $me['user_id'], 'message' => $key);
	$result = postJsonToUrl($apiUrl, $json);

	if (!$result['success']) {
		die(json_encode(array('success' => false, 'message' => 'unable to store email header at localhost')));
	}
	
	$apiUrl = "http://$ip:{$_SERVER['SERVER_PORT']}/api/apps/message";
	$result = postJsonToUrl($apiUrl, $json);
	
	if ($result['success']) {
		echo json_encode(array('success' => true, 'message' => 'successfully sent email'));
	}
	else {
		die(json_encode(array('success' => false, 'message' => 'unable to store email heder at remote server')));
	}
}
?>