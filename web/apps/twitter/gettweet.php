<?php
require_once(__DIR__.'/../../lib/common.php');
require_once('app_info.php');
require_once('nosql.php');

$localBaseApi = "http://localhost:{$_SERVER['SERVER_PORT']}/api";

function getTweetById($tweetId) {
	global $localBaseApi, $app_id;
	
	$apiUrl = "$localBaseApi/db/$app_id/$tweetId";
	$result = getJsonFromUrl($apiUrl);
	
	if ($result['success']) {
		return $result['data'];
	}
	else {
		return false;
	}
}

$tweet_id = $_POST["tweet_id"];
if (!isset($tweet_id)) {
	$tweet_id = $_GET["tweet_id"];
}

if (isset($tweet_id)) {
	// get specific tweet
	
	/* relational database version
	*
	$db = db_connect_db($app_id);
	
	$sql = "SELECT t.author_id, t.tweet_message, t.timestamp FROM tweet t WHERE id=$tweet_id";
	
	$result = db_query_result($db, $sql);
	
	if (count($result) < 1) {
		die(json_encode(array("success" => false, "message" => "no tweet with tweet id $tweet_id"), JSON_PRETTY_PRINT));
	}
	
	$tweet = $result[0];
	
	$apiUrl = "$localBaseApiUrl/api/apps/message?app_id=$app_id&message_id={$tweet['message_id']}&author_id={$tweet['author_id']}";
	$message = getJsonFromUrl($apiUrl);
	
	if (!$message["success"] || !isset($message["data"])) {
		die(json_encode(array("success" => false, "message" => "tweet id $tweet_id and message mismatch error")));
	}
	
	$message = $message["data"];
	echo json_encode(array("success" => true, "data" => $tweet), JSON_PRETTY_PRINT);
	*/
	
	$tweet = getTweetById($tweet_id);
	if ($tweet) {
		$tweet = unpackNoSql($tweet);
		echo json_encode(array("success" => true, "data" => $tweet), JSON_PRETTY_PRINT);
	}
	else {
		die(json_encode(array("success" => false, "message" => "no tweet with tweet id $tweet_id"), JSON_PRETTY_PRINT));
	}
}
else {
	//die(json_encode(array("success" => false, "message" => "missing required parameter 'tweet_id'")));
	
	// get public tweet
	$apiUrl = "$localBaseApi/apps/publicmessage?app_id=$app_id&order_by=timestamp%20DESC";
	$result = getJsonFromUrl($apiUrl);
	
	if ($result['success']) {
		$result = $result['data'];
		if (count($result) < 1) {
			echo json_encode(array("success" => false, "message" => "no public tweet"), JSON_PRETTY_PRINT);
		}
		else {
			$public = array();
			foreach ($result as $message) {
				$key = $message['message'];
				$tweet = getTweetById($key);
				if ($tweet) {
					array_push($public, unpackNoSql($tweet));
				}
			}
			echo json_encode(array("success" => true, "data" => $public), JSON_PRETTY_PRINT);
		}
	}
	else {
		echo json_encode(array("success" => false, "message" => "could not retrieve public messages"), JSON_PRETTY_PRINT);
	}
	
	/* relational database version
	*
	$db = db_connect_db($app_id);
	
	$sql = "SELECT t.author_id, t.tweet_message, t.timestamp FROM twitter.tweet t LEFT JOIN api.apps_message_recipient r " .
		"ON t.message_id=r.message_id WHERE r.message_id IS NULL";
	
	$result = db_query_result($db, $sql);
	
	if (count($result) < 1) {
		echo json_encode(array("success" => false, "message" => "no public tweet"), JSON_PRETTY_PRINT);
	}
	else {
		echo json_encode(array("success" => true, "data" => $result), JSON_PRETTY_PRINT);
	}
	*/
}
?>