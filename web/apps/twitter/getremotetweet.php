<?php
require_once(__DIR__.'/../../lib/common.php');
require_once('app_info.php');

function die_error($message) {
	$output = array("success" => false, "message" => $message);
	die(json_encode($output, JSON_PRETTY_PRINT));
}
function echo_success($data) {
	$output = array("success" => true, "data" => $data);
	echo json_encode($output, JSON_PRETTY_PRINT);
}

$localBaseApi = "http://localhost:{$_SERVER['SERVER_PORT']}/api";

if (isset($_GET['json'])) {
	$json = $_GET['json'];
	$tweet = json_decode($json, true);
	if ($tweet == null) {
		die_error("invalid input");
	}
}
else {
	die_error("invalid input");
}

$tweet_id = $tweet['message'];
$apiUrl = "$localBaseApi/users/id/{$tweet['author_id']}";
$author = getJsonFromUrl($apiUrl);
if (!$author["success"]) {
	// this is weird!
	echo "There's something wrong! {$tweet['author_id']} is unknown<br/>";
	continue;
}

$author = $author["data"];

$tweetUrl = "{$author['user_ip']}:{$_SERVER['SERVER_PORT']}/apps/$app_id/gettweet.php?tweet_id=$tweet_id";
$tweet = getJsonFromUrl($tweetUrl);
if ($tweet["success"]) {
	$tweet = $tweet["data"];
	echo_success(array("tweet" => $tweet, "author" => $author));
}
else {
	die_error("There's something wrong! tweet id $tweet_id can't be retrieved");
}
		
?>