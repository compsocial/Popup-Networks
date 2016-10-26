<?php
require_once(__DIR__.'/../../lib/common.php');
require_once(__DIR__.'/../../lib/db-mysqli.php');
require_once('app_info.php');

$localBaseApiUrl = "http://localhost:{$_SERVER['SERVER_PORT']}/api";
$apiUrl = "$localBaseApiUrl/users/nearby/all";
$data = getJsonFromUrl($apiUrl);
if ($data["success"] && count($data["data"]) > 0) {
	$nearby = $data["data"];
	$nearbyTweets = array();
	
	$log = array("message" => "nearby_count", "count" => count($nearby));
	$log_data = array("app_id" => $app_id, "log" => json_encode($log));
	$apiUrl = "$localBaseApi/log";
	postJsonToUrl($apiUrl, $log_data);
	
	array_unshift($nearby, array("user_ip" => "127.0.0.1"));
	foreach ($nearby as $user) {
		$apiUrl = "{$user['user_ip']}:{$_SERVER['SERVER_PORT']}/apps/$app_id/gettweet.php";
		//echo "$apiUrl<br/>";
		$data = getJsonFromUrl($apiUrl);
		//var_dump($data);
		//echo "<br/>";
		
		if (!isset($data) || !$data["success"] || 
			count($data['data']) < 1) {
			// there's nothing to see here
			continue;
		}
		
		$tweets = $data["data"];
		
		$apiUrl = "{$user['user_ip']}:{$_SERVER['SERVER_PORT']}/api/users/me";
		$author = getJsonFromUrl($apiUrl);
		if (!$author["success"]) {
			// this is weird!
			echo "Can't retrieve information at {$user['user_ip']}<br/>";
			continue;
		}

		$author = $author["data"];
		$author['user_ip'] = $user['user_ip'];
		foreach ($tweets as $tweet) {
			$nearbyTweet = array(
				"author" => $author, 
				"tweet" => $tweet
				);
			array_push($nearbyTweets, $nearbyTweet);
		}
	}
	
	$log = array("message" => "nearbyTweet_count", "count" => count($nearbyTweets));
	$log_data = array("app_id" => $app_id, "log" => json_encode($log));
	$apiUrl = "$localBaseApi/log";
	postJsonToUrl($apiUrl, $log_data);
	
	echo json_encode($nearbyTweets, JSON_PRETTY_PRINT);
}
else {
	echo json_encode(array());
}
?>