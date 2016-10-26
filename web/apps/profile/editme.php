<!DOCTYPE html>
<html>
  <head>
    <title>Edit Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="../../css/bootstrap.min.css" rel="stylesheet" media="screen">
    <style type="text/css">
    
    .container {
    	margin-top: 50px;
    }
    
    </style>
    <script src="../../js/jquery.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
  </head>
  <body>
    
<?php
require_once(__DIR__.'/../../lib/common.php');

$apiUrl = 'http://localhost:'.$_SERVER["SERVER_PORT"].'/api/users/me';
$displayForm = true;

if ($_POST["submit"] == "submit") {
	$allowedExts = array("gif", "jpeg", "jpg", "png");
	$temp = explode(".", $_FILES["file"]["name"]);
	$extension = strtolower(end($temp));
	
	if (($_FILES["file"]["type"] == "image/gif"
		|| $_FILES["file"]["type"] == "image/jpeg"
		|| $_FILES["file"]["type"] == "image/jpg"
		|| $_FILES["file"]["type"] == "image/pjpeg"
		|| $_FILES["file"]["type"] == "image/x-png"
		|| $_FILES["file"]["type"] == "image/png")
		&& in_array($extension, $allowedExts)) {
		if ($_FILES["file"]["error"] > 0) {
			//echo "Error: " . $_FILES["file"]["error"] . "<br/>";
			echo "<strong>Error:</strong> the image size must be no bigger than 2MB";
			$error = true;
		}
		else {
			move_uploaded_file($_FILES["file"]["tmp_name"], "img/" . $_FILES["file"]["name"]);
		}
	}
	else if ($_FILES["file"]["name"] != null) {
		echo "<strong>Error:</strong> Invalid file type";
		$error = true;
	}
	
	if (!$error) {
		$user_name = $_POST['name'];
		$user_id = $_POST['username'];
		$user_email = $_POST['email'];
		$user_address = $_POST['address'];
		$user_bio = $_POST['bio'];
		$remove_pic = $_POST['removePic'];
		
		/*
		$movein_day = $_POST['movein_day'];
		$movein_month = $_POST['movein_month'];
		$movein_year = $_POST['movein_year'];
		
		$movein_date = date('Y-m-d', mktime(0, 0, 0, $movein_month, $movein_day, $movein_year));
		*/
		$movein_date = $_POST['movein_date'];
		
		if ($remove_pic) {
			$user_picture_path = "";
			unlink($remove_pic);
		}
		else if ($_FILES["file"]["name"] != null && isset($_FILES["file"]["name"])) {
			$user_picture_path = "img/" . $_FILES["file"]["name"];
		}
		else {
			$user_picture_path = null;
		}
	
		$json = array('user_name' => $user_name, 'user_id' => $user_id, 'user_email' => $user_email, 
			'user_address' => $user_address, 'movein_date' => $movein_date, 
			'user_bio' => $user_bio, 'user_picture_path' => $user_picture_path);
			
		$result = postJsonToUrl($apiUrl, $json);
		$displayForm = false;
	
		if ($result["success"]) {
			$displayForm = true;
?>
	<div class="container">
		<p class="text-success">Successfully updated</p>
	</div>
<?php
		}
	}
}

if ($displayForm) {
	$me = getJsonFromUrl($apiUrl);
?>
    <script type="text/javascript">
    	$(document).ready(function() {
    		$("div#image").hide();
<?php
	if ($me != null && $me["success"]) {
		$me = $me["data"];
?>
			$("a#removePic").click(function() {
				$("div#image").hide();
				$("input#removePic").val("<?php echo $me["user_picture_path"]; ?>");
			});
		
			$("#username").val("<?php echo $me["user_id"]; ?>");
			$("#name").val("<?php echo $me["user_name"]; ?>");
			$("#email").val("<?php echo $me["user_email"]; ?>");
			$("#bio").val("<?php echo $me["user_bio"]; ?>");
			$("#address").val("<?php echo str_replace("\r\n", '\n', $me["user_address"]); ?>");
			$("#movein_date").val("<?php echo $me["movein_date"]; ?>");
<?php
		if (isset($me["user_picture_path"])) {
?>
			$("img#image").attr("src", "<?php echo $me["user_picture_path"]; ?>");
			$("div#image").show();
<?php
		}
			
		/*
		if (isset($me["movein_date"])) {
?>
			var date = new Date("<?= $me["movein_date"] ?> GMT-1200");
<?php
		}
		else {
?>
			var date = new Date();
<?php
		}
		*/
?>
			//console.log(date.getMonth(), date.getDate(), date.getFullYear(), date);
			/*
			$("select#movein_month").val(date.getMonth()+1);
			$("select#movein_day").val(date.getDate());
			$("select#movein_year").val(date.getFullYear());
			
			var daysInMonth = function () {
				var month = parseInt($("select#movein_month").val());
				var day = parseInt($("select#movein_day").val());
				var year = parseInt($("select#movein_year").val());
				
				if ($.inArray(month, [1, 3, 5, 7, 8, 10, 12]) >= 0) {
					$("select#movein_day > option[value='29']").show();
					$("select#movein_day > option[value='30']").show();
					$("select#movein_day > option[value='31']").show();
				}
				else if ($.inArray(month, [4, 6, 9, 11]) >= 0) {
					$("select#movein_day > option[value='29']").show();
					$("select#movein_day > option[value='30']").show();
					$("select#movein_day > option[value='31']").hide();
				}
				else {
					// feb
					$("select#movein_day > option[value='30']").hide();
					$("select#movein_day > option[value='31']").hide();
					if (year%400 == 0) {
						$("select#movein_day > option[value='29']").show();
					}
					else if (year%100 == 0) {
						$("select#movein_day > option[value='29']").hide();
					}
					else if (year%4 == 0) {
						$("select#movein_day > option[value='29']").show();
					}
					else {
						$("select#movein_day > option[value='29']").hide();
					}
				}
			}
			
			$("select#movein_month").change(daysInMonth);
			$("select#movein_year").change(daysInMonth);
			*/
<?php
	}
?>
    	});
    	
    	function readURL(input) {
    		if (input.files && input.files[0]) {
    			var reader = new FileReader();
    			reader.onload = function (e) {
    				$("img#image").attr("src", e.target.result).width(200);
    				$("div#image").show();
    			};
    			
    			reader.readAsDataURL(input.files[0]);
    		}
    	}
    </script>
    
    <div class="container">
		<form class="form-horizontal" method="post" enctype="multipart/form-data">
			<legend>Edit Your Profile</legend>
			<div class="form-group">
				<label class="col-lg-2 control-label" for="username">Username</label>
				<div class="col-lg-4">
					<input type="text" class="form-control" name="username" id="username" placeholder="Username" />
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-2 control-label" for="name">Name</label>
				<div class="col-lg-4">
					<input type="text" class="form-control" name="name" id="name" placeholder="Name" />
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-2 control-label" for="email">Email Address</label>
				<div class="col-lg-4">
					<input type="text" class="form-control" name="email" id="email" placeholder="Email Address" />
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-2 control-label" for="address">Address</label>
				<div class="col-lg-4">
					<textarea cols="50" rows="5" class="form-control" name="address" id="address" placeholder="Address"></textarea>
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-2 control-label" for="movein_date">Move-in Date</label>
				<div class="col-lg-4">
					<input type="date" class="form-control" name="movein_date" id="movein_date" placeholder="Move-in Date (YYYY-MM-DD)" />
					<!--
					<div class="col-lg-5">
						<select class="form-control" id="movein_month" name="movein_month">
<?php
	for($i = 1; $i <= 12; $i++) {
?>
							<option value="<?= $i ?>"><?= date('F', mktime(0,0,0,$i,1,2000)) ?></option>
<?php
	}
?>					
						</select>
					</div>
					
					<div class="col-lg-3">
						<select class="form-control" id="movein_day" name="movein_day">
<?php
	for($i = 1; $i <= 31; $i++) {
?>
							<option value="<?= $i ?>"><?= $i ?></option>
<?php
	}
?>
						</select>
					</div>
					
					<div class="col-lg-4">
						<select class="form-control" id="movein_year" name="movein_year">
<?php
	for($i = intval(date("Y")); $i >= 1980; $i--) {
?>
							<option value="<?= $i ?>"><?= $i ?></option>
<?php
	}
?>
						</select>
					</div>
					-->
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-2 control-label" for="bio">About You</label>
				<div class="col-lg-4">
					<textarea cols="50" rows="10" maxlength="500" class="form-control" name="bio" id="bio" placeholder="Bio"></textarea>
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-2 control-label" for="file">Profile Picture</label>
				<div class="col-lg-4">
					<div id="image">
						<img id="image" src="#" />
						<p><a href="#" id="removePic">Remove Profile Picture</a></p>
					</div>
					<input type="file" class="form-control" name="file" id="file" onchange="readURL(this);"/>
					<input type="hidden" name="removePic" id="removePic" />
				</div>
			</div>
			<div class="form-group">
				<div class="col-offset-2 col-lg-4">
					<button type="submit" name="submit" value="submit" class="btn btn-primary">Submit</button>
				</div>
			</div>
<?php
	if ($_GET['redirect']) {
?>
			<input type="hidden" name="redirect" value=<?php echo '"'.$_GET['redirect'].'"'; ?>></input>
<?php
	}
?>
		</form>
    </div>
<?php
}
?>
  </body>
</html>