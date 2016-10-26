<?php session_start(); ?>
<!DOCTYPE html>
<html>
  <head>
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
    <style type="text/css">
    
    .container {
    	margin-top: 50px;
    }
    
    </style>
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
  </head>
  <body>
    
<?php
require_once(__DIR__.'/lib/common.php');

$apiUrl = 'http://localhost:'.$_SERVER["SERVER_PORT"].'/api/';
$showForm = true;
$redirect = "/";

if (isset($_SESSION['user_id']) && isset($_SESSION['hash'])) {
	$result = postJsonToUrl($apiUrl . "/users/hash/me", array(
		"user_id" => $_SESSION['user_id'],
		"hash" => $_SESSION['hash']
	));
	if ($result['success']) {
		$showForm = false;
	}
	else {
		unset($_SESSION['user_id']);
		unset($_SESSION['hash']);
	}
}

if ($_POST["submit"] === "submit") {
	$data = array( 
		"user_id" => $_POST['username'], 
		"password" => $_POST['password']
	);
	
	$result = postJsonToUrl($apiUrl . "/users/login/me", $data);
	if ($result['success']) {
		$showForm = false;
		if ($_POST['redirect']) {
			$redirect = $_POST['redirect'];
		}
		$_SESSION['user_id'] = $_POST['username'];
		$_SESSION['hash'] = $result['hash'];
?>
	<div class="container">
		<p class="text-success"><?php echo $result['message']; ?>. Redirecting you to the page you came from...</p>
	</div>
<?php
	}
	else {
?>
	<div class="container">
		<p class="text-danger"><?php echo $result['message']; ?></p>
	</div>
<?php
	}
}
?>
    <script type="text/javascript">
<?php
if (!$showForm) {
?>
		window.location = "<?php echo $redirect; ?>";
<?php
}
?>
    	$(document).ready(function() {
    		$("input#signup").click(function () {
    			
    		});
    		<?php if ($_POST["submit"] == "submit") { ?>
    		$("input#username").val("<?php echo $_POST['username']; ?>");
    		$("input#password").focus();
    		<?php } else { ?>
    		$("input#username").focus();
    		<?php } ?>
    	});
    </script>

<?php
if ($showForm) {
?>
    <div class="container">
		<form class="form-horizontal" role="form" method="post" enctype="multipart/form-data">
			<legend>BlockParty Log In</legend>
			<div class="form-group">
				<label class="col-lg-2 control-label" for="username">Username</label>
				<div class="col-lg-4">
					<input type="text" class="form-control" name="username" id="username" placeholder="Username" />
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-2 control-label" for="password">Password</label>
				<div class="col-lg-4">
					<input type="password" class="form-control" name="password" id="password" placeholder="Password" />
				</div>
			</div>
			<div class="form-group">
				<div class="col-lg-offset-2 col-lg-4">
					<button type="submit" name="submit" value="submit" class="btn btn-primary">Submit</button>
					<input type="button" id="signup" name="signup" value="Sign Up" class="btn"></button>
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