<?php
require_once(__DIR__.'/../../lib/common.php');
require_once('app_info.php');

if ($_POST["submit"] == "submit") {
	//var_dump($_POST);
	//$message = $_POST["tweetMessage"];
	$result = postToUrl("http://localhost:{$_SERVER['SERVER_PORT']}/apps/$app_id/sendtweet.php", 
		"tweetMessage={$_POST['tweetMessage']}&tweetPrivacy={$_POST['tweetPrivacy']}");
	//echo "<h1>result = $result</h1>";
}

$localBaseApi = "http://localhost:{$_SERVER['SERVER_PORT']}/api";
$apiUrl = "$localBaseApi/apps/message";
$apiUrl .= '?app_id='.$app_id;
$apiUrl .= '&order_by='.urlencode('timestamp desc');
$data = getJsonFromUrl($apiUrl);

$userCache = array();

if ($data["success"] && count($data["data"]) > 0) {
	$tweets = $data["data"];
	
	$apiUrl = "$localBaseApi/log";
	$log = array("message" => "tweets received", "count" => count($tweets));
	$log_data = array("app_id" => $app_id, "log" => json_encode($log));
	postJsonToUrl($apiUrl, $log_data);
}
else {
	$tweets = array();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Chirpy: Hyperlocal Microblogging Service</title>
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
		$("#tweetMessage").bind("input propertychange", function () {
			message = $(this).val();
			if (message.length > 0) {
				$("#sendTweet").removeAttr("disabled");
			}
			else {
				$("#sendTweet").attr("disabled", "disabled");
			}
		});
	
		$("#sendTweetForm").submit(function () {
			$("textarea#tweetMessage").attr("readonly", "readonly");
			$("#sendTweet").attr("disabled", "disabled");
		});
	});
	</script>

	
	<div class="topbar">
		<div class="container">
			<div class="navbar fill">
			  <a class="navbar-brand" href="./">Chirpy</a>
			  <ul class="nav navbar-nav">
				<li class="active"><a href="./">Feed</a></li>
				<li><a href="nearby.php">Nearby</a></li>
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
							<input type="radio" name="tweetPrivacy" id="privacy1" value="private" checked />
							Private Chirp (only publish to followers)
							</label>
						</label>
					</div>
					<div class="radio">
						<label>
							<input type="radio" name="tweetPrivacy" id="privacy2" value="public" />
							Public Chirp (publish to nearby neighbors)
							</label>
						</label>
					</div>
					<input type="hidden" name="submit" value="submit" />
					<button type="submit" id="sendTweet" class="btn btn-primary" disabled="disabled">Tweet</button>			
				</fieldset>
			</form>
		</div>
		
		<div class="content">
			<div class="page-header">
				<h1>Chirpy: Feed<!-- <small>&mdash;<strong> 6 new updates</strong> since you last visited.</small>--></h1>
			</div>
			<div class="row" id="tweets">
				<div class="block request" id="spinner">
					<img src="img/spinner.gif" />
					Loading messages...
				</div>
				<div class="block center" id="load_more">
					<a href="#" id="load_more_link">More messages</a>
				</div>
				
				<script id="tweet_row" type="text/html">
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
					var tweets = <?php echo json_encode($tweets); ?>;
					var no_tweets = tweets.length;
					
					var serverUrl = window.location.origin;
					var serverPort = window.location.port;
					if (serverPort.length == 0) serverPort = "80";
					serverUrl = serverUrl + ":" + serverPort;
					
					var getTweetUrl = serverUrl + "/apps/<?php echo $app_id; ?>/getremotetweet.php";
					
					function stopSpinningAndShowTweets(indexStart, indexEnd) {
						$("#load_more").hide();
						
						if ($("#tweet_" + indexEnd).attr("ready") == "true") {
							$("#spinner").hide();
							for (var i = indexStart; i <= indexEnd; i++) {
								$("#tweet_" + i).show();
							}
						
							if (indexEnd < no_tweets - 1) {
								$("#load_more_link").click(function () {
									stopSpinningAndShowTweets(indexEnd+1, Math.min(indexEnd+5, no_tweets-1));
									return false;
								});
								$("#load_more").show();
							}
						}
						else {
							$("#spinner").insertBefore("#tweet_" + indexStart);
							$("#spinner").show();
							
							setTimeout(function () {
								stopSpinningAndShowTweets(indexStart, indexEnd);
							}, 500);
						}
					}
					
					function populateTweet(index, data) {
						var tweetDate = new Date(data.tweet.timestamp);
						var tweetData = {
							tweet_message: data.tweet.tweet_message,
							by_name: data.author.user_name,
							date: tweetDate.format("ddd mmm d, yyyy 'at' h:MMtt")
						};
						
						var imgSrc = "";
						if (data.author.hasOwnProperty('user_picture_path')) {
							imgSrc = serverUrl + "/api/img/" + encodeURIComponent(
								"http://" + data.author.user_ip + ":" + serverPort + "/" + data.author.user_picture_path
							);
						}
						else {
							imgSrc = "img/blank-photo.jpg";
						}
						tweetData.img_src = imgSrc;
						
						var tweetDiv = ich.tweet_row(tweetData);
						$("#tweet_" + index).hide();
						$(tweetDiv).appendTo("#tweet_" + index);
						$("#tweet_" + index).attr("ready", true);
						
						if ((no_tweets < 5 && index == no_tweets - 1) || index == 4){
							stopSpinningAndShowTweets(0, index);
						}
					}
					
					var getTweetUrl = serverUrl + "/apps/<?php echo $app_id; ?>/getremotetweet.php";
					function getAndPopulateTweet(index) {
						var tweet = tweets[index];
						$.ajax({
							dataType: "json",
							url: getTweetUrl,
							data: "json=" + JSON.stringify(tweet),
							success: function(data) {
								if (data.success) {
									populateTweet(index, data.data);
								}
							}
						}).done(function () {
							if (index < no_tweets-1) {
								getAndPopulateTweet(index+1);
							}
						});
					}
					
					$(document).ready(function() {
						$("#load_more").hide();
						for (var i = 0; i < no_tweets; i++) {
							var $div = $("<div></div>");
							$div.attr("id", "tweet_" + i);
							$div.attr("ready", false);
							//$div.hide();
							//$div.appendTo("#tweets");
							$div.insertBefore("#load_more");
						}
						
						if (no_tweets > 0) {
							getAndPopulateTweet(0);
						}
						else {
							$("#spinner").text("You have no messages.");
						}
					});
				</script>
			</div>
		</div> <!-- content -->
	</div> <!-- containter -->
	
	<div class="container">
		<footer>
			<!--
			<p>&copy; comp.social lab 2013</p>
			-->
		</footer>
	</div>

</body>
</html>
