<?php
require_once(__DIR__.'/lib/Slim/Slim.php');
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->run();
?>