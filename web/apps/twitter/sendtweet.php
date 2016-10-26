<?php
require_once(__DIR__.'/../../lib/common.php');
require_once('app_info.php');
require_once('nosql.php');

$requestFromIp = $_SERVER['REMOTE_ADDR'];
if (strcmp($requestFromIp, "127.0.0.1") != 0) {
	die("only request from localhost is allowed");
}
	
$localBaseApiUrl = "http://localhost:{$_SERVER['SERVER_PORT']}/api";
//echo $localBaseApiUrl;

$tweetMessage = $_POST["tweetMessage"];
if (!isset($tweetMessage)) {
	$tweetMessage = $_GET["tweetMessage"];
}

$tweetPrivacy = $_POST["tweetPrivacy"];
if (!isset($tweetPrivacy)) {
	$tweetPrivacy = $_GET["tweetPrivacy"];
}

$data = array("tweetMessage" => $tweetMessage, "tweetPrivacy" => $tweetPrivacy);
$log = array("action" => "sendtweet", "data" => $data);
$log_data = array("app_id" => $app_id, "log" => json_encode($log));
$apiUrl = "$localBaseApi/log";
postJsonToUrl($apiUrl, $log_data);

if (!isset($tweetPrivacy)) {
	die("no privacy set");
}

if (isset($tweetMessage)) {
	$apiUrl = "$localBaseApiUrl/users/me";
	$me = getJsonFromUrl($apiUrl);
	$me = $me["data"];
	
	$tweet = array('author_id' => $me['user_id'], 
			'tweet_message' => $tweetMessage, 
			'timestamp' => gmdate("M d Y H:i:s e", time()));
	$key = $me['user_id'].time();
	
	// add tweet to database
	$apiUrl = "$localBaseApiUrl/db/$app_id/$key";
	$json = packNoSql($tweet);
	$result = postJsonToUrl($apiUrl, $json);
	//var_dump($result);
	
	if (!$result['success']) {
		die('unable to store chirp message at localhost');
	}
	
	// get followers
	$apiUrl = "$localBaseApiUrl/follower/users?app_id=$app_id";
	$followers = getJsonFromUrl($apiUrl);
	
	if ($followers["success"]) {
		if ($tweetPrivacy == "private") {
			$recipients = $followers["data"];
		}
		else {
			$recipients = array();
		}
		
		// add message to table
		$myMessage = array("app_id" => $app_id, "author_id" => $me["user_id"], 
			"message" => "$key", "recipients" => implode(",", $recipients));
		$apiUrl = "$localBaseApiUrl/apps/message";
		$result = postJsonToUrl($apiUrl, $myMessage);
		//var_dump($result);
	
		if (!$result["success"]) {
			die("unable to insert tweet into message table");
		}
		$message_id = $result['message_id'];
		
		// push tweet to followers
		$sendCount = 0;
		$tweetToFollower = array("app_id" => $app_id, "author_id" => $me["user_id"], 
			"message" => "tweet $key");
			
		//$followers_id = explode(",", $followers["data"]);
		$followers_id = $followers["data"];
		foreach ($followers_id as $follower_id) {
			$tweetToFollower["recipients"] = $follower_id;
			
			$follower = getJsonFromUrl("$localBaseApiUrl/users/id/$follower_id");
			if ($follower["success"]) {
				$follower = $follower["data"];
			}
			else {
				continue;
			}
		
			$apiUrl = "{$follower['user_ip']}:{$_SERVER['SERVER_PORT']}/api/apps/message";
			$result = postJsonToUrl($apiUrl, $tweetToFollower);
			if ($result["success"]) {
				$sendCount++;
			}
		}
		
		/* relational database method
		$db = db_connect_db($app_id);
		$sql = "INSERT INTO twitter.tweet (author_id, tweet_message) VALUES (?, ?)";
		$statement = db_prepare_statement($db, $sql);
		
		if (!$statement) {
			die("unable to prepare sql statement: $sql");
		}
		
		db_statement_bind_parameter($statement, 'ss', 
			$me["user_id"],
			$tweetMessage
		); 
		
		$result = db_execute_statement($statement);
		
		if (!$result) {
			die("unable to insert tweet into tweet table");
		}
		
		$tweet_id = db_insert_id($db);
		
		// add message to table
		$myMessage = array("app_id" => $app_id, "author_id" => $me["user_id"], 
			"message" => "tweet $tweet_id", "recipients" => implode(",", $recipients));
		$apiUrl = "$localBaseApiUrl/apps/message";
		$result = postJsonToUrl($apiUrl, $myMessage);
		
		if (!$result["success"]) {
			die("unable to insert tweet into message table");
		}
		
		$message_id = $result["message_id"];
		$sql = "UPDATE twitter.tweet SET message_id = ? WHERE id = ?";
		
		$statement = db_prepare_statement($db, $sql);
		
		if (!$statement) {
			die("unable to prepare sql statement: $sql");
		}
		
		db_statement_bind_parameter($statement, 'ii', $message_id, $tweet_id);
		$result = db_execute_statement($statement);
		
		if (!$result) {
			die("unable to update tweet in tweet table");
		}
		
		db_close_db($db);
		
		
		// push tweet to followers
		$messageId = $result["message_id"];
		$sendCount = 0;
		$tweet = array("app_id" => $app_id, "author_id" => $me["user_id"], 
			"message" => "tweet $tweet_id");
			
		//$followers_id = explode(",", $followers["data"]);
		$followers_id = $followers["data"];
		foreach ($followers_id as $follower_id) {
			$tweet["recipients"] = $follower_id;
			
			$follower = getJsonFromUrl("$localBaseApiUrl/users/id/$follower_id");
			if ($follower["success"]) {
				$follower = $follower["data"];
			}
			else {
				continue;
			}
		
			$apiUrl = "{$follower['user_ip']}:{$_SERVER['SERVER_PORT']}/api/apps/message";
			$result = postJsonToUrl($apiUrl, $tweet);
			if ($result["success"]) {
				$sendCount++;
			}
		}
		*/
		
		echo "$sendCount";
	}
	else {
		echo "-1";
	}
}
else {
	echo "no message";
}
?>