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
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hyperlocal Webmail Service</title>

	<link rel="stylesheet" href="css/pure-min.css">
	<link rel="stylesheet" href="css/email.css">
	<style>
	
	.compose {
		margin-top: 2em;
	}
		.compose .buttons {
			margin: 1em auto 0.5em auto;
			width: 77%;
		}
	.body {
	}
	
	div#spinner {
		margin-left: auto;
		margin-right: auto;
		width: 20px;
	}
	
	div.email-item {
		cursor: pointer;
	}
	</style>
</head>
<body>
<script src="js/jquery-2.0.3.min.js"></script>

<div class="pure-g-r content" id="layout">
    <div class="pure-u" id="nav">
        <a href="#" class="nav-menu-button">Menu</a>

        <div class="nav-inner">
            <button id="button-compose" class="pure-button primary-button">Compose</button>

            <div class="pure-menu pure-menu-open">
                <ul>
                    <li><a href="#" onclick="showInbox()">Inbox <span id="unread-email-count" class="email-count"></span></a></li>
                    <li><a href="#" onclick="showSent()">Sent</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="pure-u-1" id="list">
<?php
$apiUrl = "$localBaseApi/apps/message";
$apiUrl .= '?app_id='.$app_id;
$apiUrl .= '&order_by='.urlencode('timestamp desc');
$data = getJsonFromUrl($apiUrl);
if ($data["success"] && count($data["data"]) > 0) {
	$messages = $data["data"];
	$countUnread = 0;
	
	foreach($messages as $message) {
		$inbox = true;
		if (strcmp($message['author_id'], $me['user_id']) == 0) {
			// belong to sent folder
			$inbox = false;
		}
		$apiUrl = "$localBaseApi/db/$app_id/{$message['message']}";
		$data = getJsonFromUrl($apiUrl);
		if ($data['success']) {
			$email = unpackNoSql($data['data']);
			if (!$email['read'] && $inbox) $countUnread++;
?>
		<div id="<?= $message['message'] ?>" class="email-item pure-g <?php if (!$email['read']) echo 'email-item-unread'; ?> <?php if ($inbox) echo 'inbox'; else echo 'sent'; ?>">
            <div class="pure-u">
<?php 
			echo isset($email['sender_picture_path'])?
"				<img class=\"email-avatar\" src=\"http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}/api/img/" . 
						urlencode("http://{$email['sender_ip']}:{$_SERVER['SERVER_PORT']}/{$email['sender_picture_path']}") . 
						"\" alt=\"{$email['sender_name']}\" height=\"64\" width=\"64\" />"
					:
"				<img class=\"email-avatar\" src=\"img/blank-photo.jpg\" height=\"64\" width=\"64\" />"
					;
?>
            </div>

            <div class="pure-u-3-4">
                <h5 class="email-name"><?= $email['sender_name'] ?></h5>
                <h4 class="email-subject"><?= $email['subject'] ?></h4>
                <p class="email-desc">
                    <?php 
                    	$cutoff = 80;
                    	if (strlen($email['body']) > $cutoff-3) {
                    		echo substr($email['body'], 0, $cutoff-4) . " ...";	
                    	}
                    	else {
                    		echo $email['body'];
                    	}
                    ?>
                </p>
            </div>
        </div>
<?php
		}
	}
	
	$log = array("message" => "unread_count", "count" => $countUnread);
	$log_data = array("app_id" => $app_id, "log" => json_encode($log));
	$apiUrl = "$localBaseApi/log";
	postJsonToUrl($apiUrl, $log_data);
?>
		<script type="text/javascript">
			var unreadCount = <?= $countUnread ?>;
			if (unreadCount > 0) {
				$("#unread-email-count").text("(" + unreadCount + ")");
			}
			
			$(".email-item.sent").hide();
		</script>
<?php
}
?>
    </div>

    <div class="pure-u-1" id="main">
        <div class="email-content">
            <div class="email-content-header pure-g">
                <div class="pure-u-2-3">
                    <h1 id="subject" class="email-content-title"></h1>
                    <p class="email-content-subtitle">
                        From <a><span id="sender_name"></span></a> at <span id="sent_date"></span>
                    </p>
                    <p class="email-content-subtitle">
                        To <a><span id="recipient_name"></span></a>
                    </p>
                </div>

                <div class="pure-u-1-3 email-content-controls">
                    <button class="pure-button secondary-button" onclick="reply()">Reply</button>
                    <button class="pure-button secondary-button" onclick="forward()">Forward</button>
                </div>
            </div>

            <div id="body" class="email-content-body">
            </div>
        </div>
        
        <div class="compose">
        	<form class="pure-form pure-form-aligned">
        		<fieldset>
					<div class="pure-control-group">
						<label for="to">To</label>
						<input id="to" name="to" type="text" class="pure-input-2-3">
					</div>

					<div class="pure-control-group" >
						<label for="subject">Subject</label>
						<input id="subject" name="subject" type="text" class="pure-input-2-3">
					</div>
					
					<div class="body">
						<textarea id="body" name="body" style="display: block; width: 77%; height: 20em; margin-left: auto;margin-right: auto;"></textarea>
					</div>
					<div class="buttons">
						<button id="compose_submit" type="submit" class="pure-button pure-button-primary" style="width: 49%;">Send</button>
						<button id="compose_discard" class="pure-button pure-button-error" style="width: 49%;">Discard</button>
					</div>
				</fieldset>
        	</form>
        	<div id="spinner">
        		<img src="img/loading.gif" width="20"/>
        	</div>
        </div>
    </div>
</div>

<script type="text/javascript">
	var currentEmail;
	
	$(document).ready(function () {
		$("div#spinner").hide();
		$("#main").children().hide();
		var menuButton = $(".nav-menu-button");
		var nav = $("#nav");
	
		// Setting the active class name expands the menu vertically on small screens.
		menuButton.on('click', function (e) {
			nav.toggleClass('active');
		});

		// This just makes sure that the href="#" attached to the <a> elements
		// don't scroll you back up the page.
		$("body").on("click", "a[href='#']", function (e) {
			e.preventDefault();
		});
		
		$(".compose > form").on("submit", function (e) {
			e.preventDefault();
			
			if ($(this).find("#to").val().length == 0) {
				alert("Please specify recipient");
				$(this).find("#to").focus();
				return ;
			}
			
			if ($(this).find("#subject").val().length == 0) {
				var r = confirm("Are you sure you want to send an email with an empty subject?");
				if (r == false) {
					$(this).find("#subject").focus();
					return false;
				}
			}
			
			if ($(this).find("#body").val().length == 0) {
				var r = confirm("Are you sure you want to send an email with an empty body?");
				if (r == false) {
					$(this).find("#body").focus();
					return false;
				}
			}
			
			if ($(this).find("#to").val() === "<?= $_SERVER['SERVER_ADDR'] ?>" || $(this).find("#to").val() === "<?= $me['user_id'] ?>") {
				alert("Sending email to yourself is currently not supported");
				$(this).find("#to").focus();
				return false;
			}
			
			$(".buttons > button").toggleClass("pure-button-disabled");
			$("div#spinner").show();
			
			formData = $(this).serialize() + "&submit=submit";
			//console.log(formData);
			
			$.ajax({
				url: "sendemail.php",
				data: formData, 
				type: "POST"
			}).done(function (data) {
				//console.log(data);
				var result = $.parseJSON(data);
				if (result.success) {
					alert("email has been sent!");
					$(".compose > form").get(0).reset();
				}
				else {
					alert("Invalid recipient information");
					$("#to").focus();
				}
				
				$(".buttons > button").toggleClass("pure-button-disabled");
				$("div#spinner").hide();
			});
			
			return false;
		});
		
		$("#compose_discard").click(function () {
			var r = confirm("Are you sure you want to discard the message?");
			if (r == true) {
				$(".compose > form").get(0).reset();
			}
			
			return false;
		});
		
		$("div.email-item").click(function () {
			$("#main").children().hide();
			$("div.email-item").removeClass("email-item-selected");
			$(this).addClass("email-item-selected");
			
			var emailId = $(this).attr("id");
			$(this).removeClass("email-item-unread");
			
			$.ajax({
				url: "reademail.php",
				type: "POST",
				data: "email_id=" + emailId
			}).done(function (data) {
				//console.log(data);
				data = $.parseJSON(data);
				if (data.success) {
					var email = $.parseJSON(data.data);
					currentEmail = email;
					//console.log(email);
					var $div = $("div.email-content");
					$div.find("#subject").text(email.subject);
					$div.find("#sender_name").text(email.sender_name);
					$div.find("#recipient_name").text(email.recipient_name);
					$div.find("#body").html(email.body.replace(/\n/g, '<br />'));
					
					var sentDate = new Date(email.send_date);
					var min = sentDate.getMinutes();
					if (min < 10) min = "0" + min;
					var hour = sentDate.getHours();
					var ampm = "am";
					if (hour > 12) {
						hour -= 12;
						ampm = "pm";
					}
					else if (hour == 0) {
						hour = 12;
					}
					
					var date = sentDate.getDate();
					var monthNames = [ "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ];
					var month = monthNames[sentDate.getMonth()];
					var year = sentDate.getFullYear();
					
					var sentDateString = "" + hour + ":" + min + ampm + ", " + month + " " + date + ", " + year;
					$div.find("#sent_date").text(sentDateString);
					$div.show();
					
				}
				else {
					alert("Error retrieving email! Please try again later");
				}
			});
		});
		
		$("#button-compose").click(function () {
			showCompose();
		});
	});
	
	function showCompose() {
		$("#main").children().hide();
		$("#main > div.compose").show();
	}
	
	function showInbox() {
		$(".email-item.sent").hide();
		$(".email-item.inbox").show();
	}
	
	function showSent() {
		$(".email-item.inbox").hide();
		$(".email-item.sent").show();
	}
	
    function prependAt(string, beforeChar, preChar) {
        var lines = string.split(beforeChar);
        $.each(lines, function (i, value) {
            lines[i] = preChar + value;
        });
        
        return lines.join(beforeChar);
    }
    
	function reply() {
		$div = $("div.email-content");
		//console.log(currentEmail);
		
		$compose = $(".compose > form");
		$compose.get(0).reset();
		$compose.find("#to").val(currentEmail.sender_ip);
		$compose.find("#subject").val("Re: " + currentEmail.subject);
        
        var body = prependAt(currentEmail.body, "\n", ">");
		$compose.find("#body").val("\n\n" + body);
		
		showCompose();
		$compose.find("#body").focus();
	}
	
	function forward() {
        $div = $("div.email-content");
		
		$compose = $(".compose > form");
		$compose.get(0).reset();
		$compose.find("#subject").val("Fwd: " + currentEmail.subject);
		var body = prependAt(currentEmail.body, "\n", ">");
		$compose.find("#body").val("\n\n" + body);
        
		showCompose();
        $compose.find("#to").focus();
	}
</script>


</body>
</html>
