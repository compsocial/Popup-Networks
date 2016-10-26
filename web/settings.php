<!DOCTYPE html>
<html>
<head>
	<title>Settings</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Bootstrap -->
	<link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
	<style type="text/css">

	.container {
		margin-top: 50px;
	}

	</style>
</head>

<body>
	<script src="js/jquery.js"></script>
	<script src="js/bootstrap.min.js"></script>
    
<?php
require_once(__DIR__.'/lib/common.php');

$baseApiUrl = 'http://localhost:'.$_SERVER["SERVER_PORT"].'/api';

$installedApps = array();
$installedAppIDs = array();

$apiUrl = "$baseApiUrl/apps";
$result = getJsonFromUrl($apiUrl);

if ($result["success"]) {
	$installedApps = $result["data"];
	
	if (count($installedApps) > 0) {
?>
	<h1>Installed Application</h1>
	<ul>
<?php
		foreach ($installedApps as $app) {
			array_push($installedAppIDs, $app['app_id']);
?>
		<li><a href="apps/<?php echo $app['app_id']; ?>"><?php echo $app['app_short_name']; ?></a></li>
<?php
		}
?>
	</ul>
<?php
	}
	else {
?>
	<h1>No installed application</h1>
<?php
	}
}
else {
?>
	<h1>Cannot connect to API</h1>
<?php
	die();
}
?>

	<h1>Install New Application</h1>
<?php
$app_dir = "/opt/www/apps/";
$files = scandir($app_dir);
$newApps = array();
if ($files != false) {
	foreach ($files as $file) {
		if ($file == "." || $file == "..") {
			continue;
		}
		
		$fullPath = $app_dir.$file;
		if (is_dir($fullPath)) {
			if (!in_array($file, $installedAppIDs)) {
				// new app
				array_push($newApps, $file);
			}
		}
	}
	
	
}
else {
	// no app directory
}
?>

</body>
</html>