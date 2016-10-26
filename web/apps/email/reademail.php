<?php
require_once(__DIR__.'/../../lib/common.php');
require_once('app_info.php');
require_once('nosql.php');

$localBaseApi = "http://localhost:{$_SERVER['SERVER_PORT']}/api";

if ($_POST["email_id"]) {
	$apiUrl = "$localBaseApi/db/$app_id/{$_POST['email_id']}";
	$result = getJsonFromUrl($apiUrl);
	
	if ($result['success']) {
		$email = unpackNoSql($result['data']);
		echo json_encode(array('success' => true, 'data' => json_encode($email)));
		
		if (!$email['read']) {
			$email['read'] = true;
			
			$apiUrl = "http://localhost:{$_SERVER['SERVER_PORT']}/api/db/$app_id/{$_POST['email_id']}";
			$json = packNoSql($email);
			$result = postJsonToUrl($apiUrl, $json);
		}
	}
	else {
		die(json_encode(array('success' => false, 'message' => 'unable to retrieve email')));
	}
}
?>