<?php
require_once(__DIR__.'/../../lib/common.php');
require_once(__DIR__.'/../../lib/db-mysqli.php');
require_once('app_info.php');

function cmpTweets($a, $b) {
	$timeA = $a["tweet"]["timestamp"];
	$timeB = $b["tweet"]["timestamp"];
	
	date_default_timezone_set('America/New_York');
	$d1 = date('Y-m-d H:i:s', strtotime($timeA . ' GMT'));
	$d2 = date('Y-m-d H:i:s', strtotime($timeB . ' GMT'));
	
	if ($d1 == $d2)
		return 0;
	else if ($d1 < $d2)
		return 1;
	else
		return -1;
}

$localBaseApiUrl = "http://localhost:{$_SERVER['SERVER_PORT']}/api";

if (isset($_POST["follow"])) {
	$to_follow_ip = $_POST["follow"];
	
	$apiUrl = "$localBaseApiUrl/users/me";
	$me = getJsonFromUrl($apiUrl);
	if (!$me["success"]) {
		die("cannot retrieve users/me");
	}
	
	$me = $me["data"];
	
	$apiUrl = "http://$to_follow_ip:{$_SERVER['SERVER_PORT']}/api/users/me";
	$follow = getJsonFromUrl($apiUrl);
	if (!$follow["success"]) {
		die("cannot retrieve users/me from $to_follow_ip");
	}
	
	$follow = $follow["data"];
	
	// check if "follow" is already in our database
	$apiUrl = "$localBaseApiUrl/users/id/{$follow['user_id']}";
	$result = getJsonFromUrl($apiUrl);
	if (!$result["success"]) {
		// the user is not yet in our database, add them
		$apiUrl = "$localBaseApiUrl/users";
		$follow["user_ip"] = $to_follow_ip;
		$result = postJsonToUrl($apiUrl, $follow);
		if (!$result["success"]) {
			die("cannot add a new user to users table");
		}
	}
	
	// add "follow" to following table
	$apiUrl = "http://localhost:{$_SERVER['SERVER_PORT']}/api/following/users";
	$data = array("app_id" => $app_id, "following_id" => $follow["user_id"]);
	$result = postJsonToUrl($apiUrl, $data);
	if (!$result["success"]) {
		var_dump($result);
		die();
	}
	
	$apiUrl = "http://$to_follow_ip:{$_SERVER['SERVER_PORT']}/api/follower/users";
	$data = array("app_id" => $app_id, "follower_id" => $me["user_id"]);
	$result = postJsonToUrl($apiUrl, $data);
	if (!$result["success"]) {
		var_dump($result);
		die();
	}
}
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Chirpy: Nearby</title>
	
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/my.css">
</head>
<body>
	<script src="../../js/jquery.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="../../js/ICanHaz.min.js"></script>
	<script src="../../js/date.format.js"></script>
	
	<script type="text/javascript">
	$(document).ready(function () {
	});
	</script>
	
	<div class="topbar">
		<div class="container">
			<div class="navbar fill">
			  <a class="navbar-brand" href="./">Chirpy</a>
			  <ul class="nav navbar-nav">
				<li><a href="./">Feed</a></li>
				<li class="active"><a href="nearby.php">Nearby</a></li>
			  </ul>
			</div>
		</div>
	</div>
	
	<div class="container">
		<div class="tweetbar">
			<form id="sendTweetForm" method="post" action="">
				<fieldset>
					<textarea class="form-control" id="tweetMessage" name="tweetMessage" placeholder="What's going on?" rows=5 cols=80 maxlength=400></textarea>
					<div class="radio">
						<label>
							<input type="radio" name="tweetPrivacy" id="privacy1" value="private" />
							Private Chirp (only publish to followers)
							</label>
						</label>
					</div>
					<div class="radio">
						<label>
							<input type="radio" name="tweetPrivacy" id="privacy2" value="public" checked />
							Public Chirp (publish to nearby neighbors)
							</label>
						</label>
					</div>
					<input type="hidden" name="submit" value="submit" />
					<button type="submit" id="sendTweet" class="btn btn-primary" disabled="disabled">Tweet</button>			
				</fieldset>
			</form>
		</div>
		
		<div class="content"> <!-- tk-ff-meta-web-pro -->
			<div class="page-header">
				<h1>Chirpy: Nearby<!-- <small>&mdash;<strong> 3 new updates</strong> since you last visited.</small>--></h1>
			</div>
			<div class="row">
				<div class="block request" id="spinner">
					<img src="img/spinner.gif" />
					Loading messages...
				</div>
		  		<div class="span12">
		  			<script type="text/html" id="tweet_row">
		  				<div class="block request">
							<div class="left">
								<div class="pic-container">
									<img class="profilepic" src="{{ img_src }}" alt="{{ by_name }}" />
								</div>
							</div>
					
							<div class="right">
								<div>
									{{ tweet_message }}
								</div>
								<div class="byline">
									&mdash; {{ by_name }} on {{ date }}
								</div>
							</div>
					
						</div>
		  			</script>
		  			<script type="text/javascript">
		  				var serverUrl = window.location.origin;
						var serverPort = window.location.port;
						if (serverPort.length == 0) serverPort = "80";
						serverUrl = serverUrl + ":" + serverPort;
						
						function stopSpinningAndShowTweets() {
							$("#spinner").hide();
							$(".row > .span12").show();
						}
						
		  				function populateNearbyTweets(data) {
		  					if (data.length > 0) {
								for (var i = 0; i < data.length; i++) {
									var tweet = data[i];
									var tweetData = {
										tweet_message: tweet.tweet.tweet_message, 
										by_name: tweet.author.user_name
									};
								
									var tweetDate = new Date(tweet.tweet.timestamp);
									tweetData.date = tweetDate.format("ddd mmm d, yyyy 'at' h:MMtt");
								
									var imgSrc = "";
									if (tweet.author.hasOwnProperty('user_picture_path')) {
										imgSrc = serverUrl + "/api/img/" + encodeURIComponent(
											"http://" + tweet.author.user_ip + ":" + serverPort + "/" + tweet.author.user_picture_path
										);
									}
									else {
										imgSrc = "img/blank-photo.jpg";
									}
									tweetData.img_src = imgSrc;
								
									var tweetDiv = ich.tweet_row(tweetData);
									//$(tweetDiv).hide();
									$(tweetDiv).appendTo(".row > .span12");
								}
								
							}
							else {
								$("<div></div>").addClass("block request").text("No nearby public chirp").appendTo(".row > .span12");
							}
							
							stopSpinningAndShowTweets();
		  				}
		  				
		  				$(document).ready(function () {
		  					$(".row > .span12").hide();
		  					var nearbyApi = serverUrl + "/apps/<?php echo $app_id; ?>/getnearbytweets.php";
		  					$.ajax({
		  						url: nearbyApi,
		  						dataType: "json"
		  					}).done(function (data) {
		  						populateNearbyTweets(data);
		  					});
		  				});
		  			</script>
<?php
/***************
 * Get nearby users
 ***************/
$apiUrl = "$localBaseApiUrl/users/nearby/all";
$data = getJsonFromUrl($apiUrl);
if ($data["success"] && count($data["data"]) > 0) {
	$nearby = $data['data'];
?>
				</div>
				<div class="span4" style="font-size: 115%; color: #555;">
					<h4 class="smallcapsheader">Your Chirpy Neighbors</h4>
					<form method="post" action="">
<?php
	$apiUrl = "$localBaseApiUrl/following/users?app_id=$app_id";
	$result = getJsonFromUrl($apiUrl);
	if (!$result["success"]) {
		die("cannot retrieve list of users following");
	}
	
	if (count($result["data"]) == 0) {
		$following = array();
	}
	else {
		$following = $result["data"];
	}
	
	foreach ($nearby as $user) {
		$apiUrl = "{$user['user_ip']}:{$_SERVER['SERVER_PORT']}/api/apps/id/$app_id";
		$result = getJsonFromUrl($apiUrl);
		if ($result["success"]) {
			// the other end has twitter
			$apiUrl = "{$user['user_ip']}:{$_SERVER['SERVER_PORT']}/api/users/me";
			$result = getJsonFromUrl($apiUrl);
			
			if (!$result["success"]) {
				echo "something is wrong: {$user['user_ip']} has twitter but no users/me";
				continue;
			}
			
			$user_info = $result["data"];
?>
					<div class="block neighbor" style="margin-top: 20px !important;">
						<img class="profile" src="img/blank-photo.jpg" />
						<?php echo $user_info['user_name']; ?>
						<span style="float: right; margin-right: 10px; margin-left: 20px;">
<?php
			if (in_array($user_info['user_id'], $following)) {
				// already followed this user
			
?>
							<button class="btn btn-info" disabled>Following</button>
<?php
			}
			else {
?>
							<button type="submit" class="btn btn-info" name="follow" value="<?php echo $user['user_ip']; ?>">Follow</button>
<?php
			}
?>
						</span>
					</div>
<?php
		}
	}
?>
					</form>
<?php
}
else {
?>
					<h4 class="smallcapsheader">No neighbors are online</h4>
<?php
}
?>
					
				</div>
			</div>
		</div>

	</div> <!-- /container -->
	
	<footer class="container">
		<!--
		<p>&copy; comp.social lab 2013</p>
		-->
	</footer>
</body>
</html>