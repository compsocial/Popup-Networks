<?php
require_once(__DIR__.'/../lib/Slim/Slim.php');
require_once(__DIR__.'/../lib/common.php');
require_once(__DIR__.'/../lib/db-mysqli.php');

function write_log($api, $url, $data) {
	$ip = $_SERVER['REMOTE_ADDR'];
	
	db::getInstance()->connectDb("log");
	
	$sql = "INSERT DELAYED INTO url_call_log (ip, api, url, data) VALUES (?, ?, ?, ?)";
	if ($statement = db::getInstance()->prepareStatement($sql)) {
		db::getInstance()->statementBindParameter($statement, 'ssss', 
			$ip, 
			$api, 
			$url, 
			$data
		); 
		
		$result = db::getInstance()->executeSqlStatement($statement);
		
		if (!$result) {
			die_error("unable to insert the row to log");
		}
	}
	
	/*
	$file = fopen(__DIR__."/../log/calllog.log", "a");
	$log = "$ip,$api,$url,$data," . date("Y-m-d H:i:s") . "\n";
	$result = fwrite($file, $log);
	if ($result == false) {
		die_error("unable to insert the row to log");
	}
	fclose($file);
	*/
}

function die_error($message, $lastwish=null) {
	$error = array('success' => false, 'message' => $message);
	
	if ($lastwish != null) {
		$lastwish();
	}
	
	write_log("error", NULL, $message);
	die(json_encode($error, JSON_PRETTY_PRINT));
}

function echo_success($message, $info=null) {
	$success = array('success' => true, 'message' => $message);
	if ($info != null) {
		$success = array_merge($success, $info);
	}
	
	echo json_encode($success, JSON_PRETTY_PRINT);
}

function echo_response($array, $singular=false) {
	if ($singular) {
		$array = $array[0];
	}
	
	$response = array('success' => true, 'data' => $array);
	
	echo json_encode($response, JSON_PRETTY_PRINT);
}

function check_for_required_value($params, $name) {
	if ($params == null || !is_array($params) || count($params) < 1 || $params[$name] == null) {
		die_error("missing required value: '". $name ."'");
	}
}

function get_params_from_request($request) {
	$params = $request->post();
	if ($params == null || count($params) < 1) {
		$params = $request->get();
	}
	
	if ($params == null || count($params) < 1) {
		return array();
	}
	
	if ($params["json"]) {
		$params = json_decode($params["json"], true);
		if ($params == null) {
			// bad json
			die_error("Bad JSON format");
		}
	}
	
	return $params;
}

function unable_to_prepare_sql_statement($sql) {
	die_error("Unable to prepare SQL statement '". $sql ."'");
}

function request_from_localhost_only () {
	$requestFromIp = $_SERVER['REMOTE_ADDR'];
	if (strcmp($requestFromIp, "127.0.0.1") != 0) {
		write_log("request_not_from_localhost", "", "");
		die_error("only request from localhost is allowed");
	}
}


\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->hook('slim.before.router', function() use ($app) {
	$route = $app->request()->getPathInfo();
	
	// log the call
	if ($app->request()->isPost()) {
		$data = $_POST;
	}
	else {
		$data = $_GET;
	}
	
	write_log(NULL, $route, json_encode($data));
	
	if (strcmp($route, '/users/me') == 0 && $app->request()->isPost()) {
		// do nothing here, pass along
	}
	else {
		db::getInstance()->connectDb("api");
		$sql = "SELECT * FROM users_me LIMIT 1";
		$me = db::getInstance()->queryResult($sql);
		
		if ($me == null) {
			// users_me hasn't been set up
			// so that means this server is not ready to serve any application
			die_error("this server has not been set up");
		}
	}
});

// No API specified
$app->get('/', function() {
	die_error("Please specify the api you want to call.");
});

// apps API
//////////////////

// get apps
$app->get('/apps', function () {
	db::getInstance()->connectDb("api");
	$sql = "SELECT * FROM apps";
	$apps = db::getInstance()->queryResult($sql);
	
	echo_response($apps);
});

$app->get('/apps/id/:id', function($app_id) use ($app) {
	db::getInstance()->connectDb("api");
	$sql = "SELECT * FROM apps WHERE app_id=\"$app_id\"";
	$app = db::getInstance()->queryResult($sql);
	
	if (count($app) == 0) {
		die_error("no app with app_id $app_id");
	}
	else {
		echo_response($app);
		write_log("get apps", $app->request()->getPathInfo(), "get unknown app \"$app_id\"");
	}
});

//$app->get('/postapps', function() use ($app) {
$app->post('/apps', function() use ($app) {
	request_from_localhost_only();
	
	$params = get_params_from_request($app->request());
	
	// required parameters
	check_for_required_value($params, "app_id");
	check_for_required_value($params, "app_short_name");
	check_for_required_value($params, "app_description");
	check_for_required_value($params, "app_version");
	check_for_required_value($params, "app_build_date");
	
	// optional parameters
	if ($params["app_long_name"] == null) {
		$params["app_long_name"] = $params["app_short_name"];
	}
	
	db::getInstance()->connectDb("api");
	$sql = "INSERT INTO apps (app_id, app_short_name, app_long_name, app_description, app_version, app_build_date) VALUES (?,?,?,?,?,?)";
	if ($statement = db::getInstance()->prepareStatement($sql)) {
		db::getInstance()->statementBindParameter($statement, 'ssssss', 
			$params["app_id"], 
			$params["app_short_name"], 
			$params["app_long_name"], 
			$params["app_description"], 
			$params["app_version"], 
			$params["app_build_date"]
		); 
		
		$result = db::getInstance()->executeSqlStatement($statement);
		
		if (!$result) {
			die_error("unable to insert the row to apps, maybe the app_id is not unique");
		}
		else {
			$sql = "CREATE DATABASE IF NOT EXISTS {$params['app_id']}";
			$result = db::getInstance()->executeSqlQuery($sql);
			if ($result < 0) {
				die_error("unable to create database for application {$params['app_short_name']}");
			}
			else {
				$sql = "CREATE TABLE IF NOT EXISTS {$params['app_id']}.database (" . 
					"id INTEGER PRIMARY KEY AUTO_INCREMENT,  " . 
					"name VARCHAR(100) UNIQUE KEY, " . 
					"value BLOB" . 
					")";
				$result = db::getInstance()->executeSqlQuery($sql);
				if ($result < 0) {
					die_error("unable to create table for application {$params['app_short_name']}");
				}
				else {
					echo_success("successfully added '". $params["app_short_name"] ."' application");
				}
			}
		}
	}
	else {
		write_log("post apps", $app->request()->getPathInfo(), "unable to post app \"{$params['app_id']}\"");
		unable_to_prepare_sql_statement($sql);
	}
});


/**
 * apps/message API
 */
// get message
$app->get('/apps/message', function() use ($app) {
	$requestFromIp = $_SERVER['REMOTE_ADDR'];
	
	$params = get_params_from_request($app->request());
	check_for_required_value($params, "app_id");
	
	$singular = false;
	db::getInstance()->connectDb("api");
	$sql = "SELECT * FROM apps_message WHERE " .
		"app_id=\"". $params["app_id"]. "\"";
	if ($params["message_id"] != null) {
		$sql .= " AND id=". $params["message_id"];
		$singular = true;
	}
	
	if ($params["author_id"] != null) {
		$sql .= " AND author_id=\"". $params["author_id"]. "\"";
	}
	
	if ($params["order_by"] != null) {
		$sql .= " ORDER BY " . $params["order_by"];
	}
	
	$messages = db::getInstance()->queryResult($sql);
	
	$authorized_messages = array();
	
	if (strcmp($requestFromIp, "127.0.0.1") == 0) { // request from localhost, all messages can be seen
		$authorized_messages = $messages;
	}
	else {
		foreach ($messages as $message) {
			$sql = "SELECT count(*) FROM apps_message_recipient WHERE message_id={$message['id']}";
			$count = db::getInstance()->queryResult($sql);
		
			$count = intval($count[0]['count(*)']);
			if ($count == 0) { // anyone can see this message
				array_push($authorized_messages, $message);
			}
			else { // need to check if this user can see this message
				$sql = "SELECT * FROM users WHERE user_ip=\"$requestFromIp\"";
				$user = db::getInstance()->queryResult($sql);
			
				if (count($user) > 0) {
					$user = $user[0];
					$sql = "SELECT count(*) FROM apps_message_recipient WHERE message_id={$message['id']} " .
						"AND recipient_id=\"{$user['user_id']}\"";
					$count = db::getInstance()->queryResult($sql);
					$count = intval($count[0]['count(*)']);
				
					if ($count > 0) { // this user is included in the recipients list
						array_push($authorized_messages, $message);
					}
					else { // this user is not in the recipients list
						continue;
					}
				}
				else { // this ip is not recognized
					continue;
				}
			}
		}
	}
	
	echo_response($authorized_messages, $singular);
});

$app->get('/apps/publicmessage', function() use ($app) {
	$params = get_params_from_request($app->request());
	check_for_required_value($params, "app_id");
	
	db::getInstance()->connectDb("api");
	$sql = "SELECT m.* FROM apps_message m LEFT JOIN apps_message_recipient r " . 
		"ON m.id=r.message_id " . 
		"WHERE m.app_id=\"{$params['app_id']}\" " .
		"AND r.message_id IS NULL"
		;
	
	if ($params["author_id"] != null) {
		$sql .= " AND author_id=\"". $params["author_id"]. "\"";
	}
	
	if ($params["order_by"] != null) {
		$sql .= " ORDER BY " . $params["order_by"];
	}
	
	$result = db::getInstance()->queryResult($sql);
	
	echo_response($result);
});

//$app->get('/apps/postmessage', function() use ($app) {
$app->post('/apps/message', function() use ($app) {
	$params = get_params_from_request($app->request());
	check_for_required_value($params, "app_id");
	check_for_required_value($params, "author_id");
	check_for_required_value($params, "message");
	
	db::getInstance()->connectDb("api");
	$sql = "INSERT INTO apps_message (app_id, author_id, message) VALUES (?, ?, ?)";
	if ($statement = db::getInstance()->prepareStatement($sql)) {
		db::getInstance()->statementBindParameter($statement, 'sss', 
			$params["app_id"],
			$params["author_id"],
			$params["message"]
		);
		
		$result = db::getInstance()->executeSqlStatement($statement);
		
		if (!$result) {
			die_error("unable to insert the row to apps_message");
		}
		else {
			$message_id = db::getInstance()->insertId();

			$recipients = explode(",", $params["recipients"]);
			if (strlen($recipients[0]) == 0) {
				$recipients = array();
			}
			
			if (count($recipients) > 0) {
				foreach($recipients as $user) {
					$sql = "INSERT INTO apps_message_recipient (message_id, recipient_id) VALUES (?, ?)";
					if ($statement = db::getInstance()->prepareStatement($sql)) {
						db::getInstance()->statementBindParameter($statement, 'is',
							$message_id,
							$user
						);
					
						$result = db::getInstance()->executeSqlStatement($statement);
						
						if (!$result) {
							die_error("unable to insert recipients to apps_message_recipient");
						}
					}
					else {
						unable_to_prepare_sql_statement($sql);
					}
				}
			}
			
			/*
			else {
				$sql = "INSERT INTO apps_message_recipient (message_id) VALUES (?)";
				
				if ($statement = db::getInstance()->prepareStatement($sql)) {
					db::getInstance()->statementBindParameter($statement, 'i',
						$message_id
					);
				
					$result = db::getInstance()->executeSqlStatement($statement);
				
					if (!$result) {
						die_error("unable to insert recipients to apps_message_recipient");
					}
				}
				else {
					unable_to_prepare_sql_statement($sql);
				}
			}
			*/
			
			echo_success("successfully added the message", array("message_id" => $message_id));
		}
	}
	else {
		unable_to_prepare_sql_statement($sql);
	}
});

function retrieveUserInfoFromIp($ip) {
	$url = "http://$ip:{$_SERVER['SERVER_PORT']}/api/users/me";
	$data = getJsonFromUrl($url, $responseCode);
	if ($responseCode == 200 && $data["success"]) {
		$user = $data["data"];
		$user["user_ip"] = $ip;
		/*
		if (!isset($user['address'])) $user['address'] = null;
		if (!isset($user['movein_date'])) $user['movein_date'] = null;
		if (!isset($user['join_date'])) $user['join_date'] = null;
		*/
		return $user;
	}
	else {
		return null;
	}
}

function updateUserInfo($new_user, $user) {
	db::getInstance()->connectDb("api");
	$sql = "UPDATE users SET user_id=?, user_name=?, user_email=?, user_address=?, user_bio=?, user_picture_path=?, user_ip=?, movein_date=?, join_date=? WHERE id=?";
	
	if ($statement = db::getInstance()->prepareStatement($sql)) {
		db::getInstance()->statementBindParameter($statement, 'sssssssssi', 
			$new_user['user_id'], 
			$new_user['user_name'],
			$new_user['user_email'],
			$new_user['user_address'],
			$new_user['user_bio'],
			$new_user['user_picture_path'],
			$new_user['user_ip'],
			$new_user['movein_date'],
			$new_user['join_date'],
			$user['id']
		);
		
		$result = db::getInstance()->executeSqlStatement($statement);
		
		if (!$result) {
			die_error("unable to update {$new_user['user_name']}");
		}
		else {
			//echo_success("successfully updated '". $new_user['user_id'] ."'");
		}
	}
}

function checkUserRetrievalDate($users) {
	date_default_timezone_set('America/New_York');
	$count = count($users);
	for ($i=0; $i < $count; $i++) {
		$user = $users[$i];
		$expired = true;
		if ($user['retrieval_date']) {
			$retrieval_date = strtotime($user['retrieval_date']);
			if ($retrieval_date == false) $retrieval_date = 0;
			$now = time();
			$diff = $now - $retrieval_date;
			if ($diff < 7*24*60*60) { // 7 days
				$expired = false;
			}
			else {
				$expired = true;
			}
		}
		
		if ($expired) {
			// the data is expired
			$new_user = retrieveUserInfoFromIp($user['user_ip']);
			if ($new_user != null) {
				updateUserInfo($new_user, $user);
				$users[$i] = $new_user;
			}
		}
	}
	
	return $users;
}

/**
 * users API
 */
$app->get('/users', function() use ($app) {
	db::getInstance()->connectDb("api");
	$sql = "SELECT * FROM users";
	
	$users = db::getInstance()->queryResult($sql);
	
	$users = checkUserRetrievalDate($users);
	
	echo_response($users);
});

//$app->get('/postusers', function() use ($app) {
$app->post('/users', function() use ($app) {
	$params = get_params_from_request($app->request());
	
	// required parameter
	check_for_required_value($params, "user_ip");
	
	// check validity of ip
	try {
		$isvalidip = inet_pton($params["user_ip"]);
	} catch (ErrorException $e) {
		$isavalidip = false;
	}
	
	if (!$isvalidip) {
		die_error("invalid format of 'user_ip'");
	}
	
	db::getInstance()->connectDb("api");
	$sql = "SELECT COUNT(*) FROM users WHERE user_ip=\"{$params['user_ip']}\"";
	$result = db::getInstance()->queryResult($sql);
	
	$id = 0;
	if (intval($result[0]["COUNT(*)"]) > 0) {
		// user already exists
		$sql = "SELECT * FROM users WHERE user_ip=\"{$params['user_ip']}\"";
		$result = db::getInstance()->queryResult($sql);
		if (count($result) > 0) {
			$user = $result[0];
			$new_user = retrieveUserInfoFromIp($params["user_ip"]);
			if ($new_user != null) {
				updateUserInfo($new_user, $user);
			}
		}
		else {
			die_error("unable to retrieve local information of user at IP address {$params['user_ip']}");
		}
	}
	else {
		// new user
		$user = retrieveUserInfoFromIp($params["user_ip"]);
		if ($user != null) {
			$sql = "INSERT INTO users (user_id, user_name, user_email, user_address, user_bio, user_picture_path, user_ip, movein_date, join_date, known_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
			if ($statement = db::getInstance()->prepareStatement($sql)) {
				db::getInstance()->statementBindParameter($statement, 'sssssssss',
					$user["user_id"],
					$user["user_name"],
					$user["user_email"],
					$user['user_address'],
					$user['user_bio'],
					$user['user_picture_path'],
					$params["user_ip"],
					$user['movein_date'],
					$user["join_date"]
				);
		
				$result = db::getInstance()->executeSqlStatement($statement);
		
				if (!$result) {
					die_error("unable to insert the row to users");
				}
				else {
					echo_success("successfully added '". $user["user_id"] ."'");
					$id = db::getInstance()->insertId();
				}
			}
			else {
				unable_to_prepare_sql_statement($sql);
			}
		
			$sql = "INSERT INTO vouch (user_id) VALUES (?)";
			if ($statement = db::getInstance()->prepareStatement($sql)) {
				db::getInstance()->statementBindParameter($statement, 's',
					$user["user_id"]
				);
		
				$result = db::getInstance()->executeSqlStatement($statement);
				
				/*
				if (!$result) {
					die_error("unable to insert the row to vouch");
				}
				*/
			}
			else {
				unable_to_prepare_sql_statement($sql);
			}
		}	
	}
	
	/*
	// required parameters
	check_for_required_value($params, "user_id");
	check_for_required_value($params, "user_name");
	check_for_required_value($params, "user_ip");
	check_for_required_value($params, "join_date");
	
	// check validity of ip
	try {
		$isvalidip = inet_pton($params["user_ip"]);
	} catch (ErrorException $e) {
		$isavalidip = false;
	}
	
	if (!$isvalidip) {
		die_error("invalid format of 'user_ip'");
	}
	
	$db = db_connect_db("api");
	$sql = "SELECT COUNT(*) FROM users WHERE user_id=\"{$params['user_id']}\" AND user_ip=\"{$params['user_ip']}\"";
	$result = db_query_result($db, $sql);
	
	$id = 0;
	if (intval($result[0]["COUNT(*)"]) > 0) {
		// user already exists
		$sql = "SELECT id FROM users WHERE user_id=\"{$params['user_id']}\" AND user_ip=\"{$params['user_ip']}\"";
		$result = db_query_result($db, $sql);
		if (intval($result[0]["id"]) > 0) {
			$id = intval($result[0]["id"]);
		}
		else {
			die_error("unable to retrieve internal id of {$params['user_name']}", function () use ($db) {
				db_close_db($db);
			});
		}
		
		$sql = "UPDATE users SET user_name=? WHERE id=?";
		if ($statement = db_prepare_statement($db, $sql)) {
			db_statement_bind_parameter($statement, 'si', 
				$params['user_name'], 
				$id
			);
			
			$result = db_execute_statement($statement);
		
			if (!$result) {
				die_error("unable to update {$params['user_name']}", function () use ($db) {
					db_close_db($db);
				});
			}
			else {
				echo_success("successfully updated '". $params["user_id"] ."'");
			}
		}
		else {
			unable_to_prepare_sql_statement($sql);
		}
	}
	else {
		// new user
		$sql = "INSERT INTO users (user_id, user_name, user_ip, join_date, known_date) VALUES (?, ?, ?, ?, NOW())";
		if ($statement = db_prepare_statement($db, $sql)) {
			db_statement_bind_parameter($statement, 'ssss',
				$params["user_id"],
				$params["user_name"],
				$params["user_ip"],
				$params["join_date"]
			);
		
			$result = db_execute_statement($statement);
		
			if (!$result) {
				die_error("unable to insert the row to users", function () use ($db) {
					db_close_db($db);
				});
			}
			else {
				echo_success("successfully added '". $params["user_id"] ."'");
				$id = db_insert_id($db);
			}
		}
		else {
			unable_to_prepare_sql_statement($sql);
		}
		
		$sql = "INSERT INTO vouch (user_id) VALUES (?)";
		if ($statement = db_prepare_statement($db, $sql)) {
			db_statement_bind_parameter($statement, 's',
				$params["user_id"]
			);
		
			$result = db_execute_statement($statement);
		
			if (!$result) {
				die_error("unable to insert the row to vouch", function () use ($db) {
					db_close_db($db);
				});
			}
		}
		else {
			unable_to_prepare_sql_statement($sql);
		}
	}
	
			
	if (isset($params["user_email"])) {
		$sql = "UPDATE users SET user_email = ? WHERE id=$id";
		if ($statement = db_prepare_statement($db, $sql)) {
			if (strlen($params["user_email"]) > 0) {
				db_statement_bind_parameter($statement, 's', $params["user_email"]);
			}
			else {
				$null = NULL;
				db_statement_bind_parameter($statement, 's', $null);
			}

			$result = db_execute_statement($statement);

			if (!$result) {
				die_error("unable to update users", function () use ($db) {
					db_close_db($db);
				});
			}
		}
		else {
			unable_to_prepare_sql_statement($sql);
		}
	}

	if (isset($params["user_bio"])) {
		$sql = "UPDATE users SET user_bio = ? WHERE id=$id";
		if ($statement = db_prepare_statement($db, $sql)) {
			if (strlen($params["user_bio"]) > 0) {
				db_statement_bind_parameter($statement, 's', $params["user_bio"]);
			}
			else {
				$null = NULL;
				db_statement_bind_parameter($statement, 's', $null);
			}

			$result = db_execute_statement($statement);

			if (!$result) {
				die_error("unable to update users", function () use ($db) {
					db_close_db($db);
				});
			}
		}
		else {
			unable_to_prepare_sql_statement($sql);
		}
	}

	if (isset($params["user_picture_path"])) {
		$sql = "UPDATE users SET user_picture_path = ? WHERE id=$id";
		if ($statement = db_prepare_statement($db, $sql)) {
			if (strlen($params["user_picture_path"]) > 0) {
				db_statement_bind_parameter($statement, 's', $params["user_picture_path"]);
			}
			else {
				$null = NULL;
				db_statement_bind_parameter($statement, 's', $null);
			}

			$result = db_execute_statement($statement);

			if (!$result) {
				die_error("unable to update users", function () use ($db) {
					db_close_db($db);
				});
			}
		}
		else {
			unable_to_prepare_sql_statement($sql);
		}
	}
    
    if (isset($params["user_address"])) {
		$sql = "UPDATE users SET user_address = ? WHERE id=$id";
		if ($statement = db_prepare_statement($db, $sql)) {
			if (strlen($params["user_address"]) > 0) {
				db_statement_bind_parameter($statement, 's', $params["user_address"]);
			}
			else {
				$null = NULL;
				db_statement_bind_parameter($statement, 's', $null);
			}

			$result = db_execute_statement($statement);

			if (!$result) {
				die_error("unable to update users", function () use ($db) {
					db_close_db($db);
				});
			}
		}
		else {
			unable_to_prepare_sql_statement($sql);
		}
	}
    
    if (isset($params["movein_date"])) {
		$sql = "UPDATE users SET movein_date = ? WHERE id=$id";
		if ($statement = db_prepare_statement($db, $sql)) {
			if (strlen($params["movein_date"]) > 0) {
				db_statement_bind_parameter($statement, 's', $params["movein_date"]);
			}
			else {
				$null = NULL;
				db_statement_bind_parameter($statement, 's', $null);
			}

			$result = db_execute_statement($statement);

			if (!$result) {
				die_error("unable to update users", function () use ($db) {
					db_close_db($db);
				});
			}
		}
		else {
			unable_to_prepare_sql_statement($sql);
		}
	}
	
	db_close_db($db);
	*/
});

/**
 * users/id/:name API
 * get user by user_id (username)
 */
$app->get('/users/id/:name', function ($name) use ($app) {
	db::getInstance()->connectDb("api");
	$sql = "SELECT * FROM users WHERE user_id=\"". $name . "\"";
	
	$user = db::getInstance()->queryResult($sql);
	
	if ($user != null && count($user) > 0) {
		echo_response($user, true);
	}
	else {
		write_log("get user", $app->request()->getPathInfo(), "get unknown user \"$name\"");
		die_error("user '". $name ."' does not exist");
	}
});

/**
 * users/nearby API
 * get nearby users
 * option: all, known, unknown
 */
$app->get('/users/nearby(/:option)', function($option = "all") {
	if (strlen($option) == 0) {
		// no option provided, go with "all"
		$option = "all";
	}
	
	$url = "http://localhost:9090/neighbors";
	$nearby = json_decode("{" . getUrl($url), true);
	$neighbors = $nearby["data"][0]["neighbors"];
	
	db::getInstance()->connectDb("api");
	$result = array();
	foreach($neighbors as $node) {
		$ip = $node["ipv4Address"];
		$sql = "SELECT * FROM users WHERE user_ip=\"". $ip ."\"";
		
		$users = db::getInstance()->queryResult($sql);
		if (count($users) > 0) {
			if ($option == "all" || $option == "known") {
				$result = array_merge($result, $users);
			}
		}
		else {
			if ($option == "all" || $option == "unknown") {
				$url = "http://$ip:{$_SERVER['SERVER_PORT']}/api/users/me";
				$data = getJsonFromUrl($url, $responseCode);
				if ($responseCode == 200 && $data["success"]) {
					$user = $data["data"];
					$user["user_ip"] = $ip;
					array_push($result, $user);
				}
			}
		}
	}
	
	echo_response($result);
})->conditions(array('option' => '(all|known|unknown)?'));

/**
 * users/me API
 * get info about me
 */
$app->get('/users/me', function() use ($app) {
	db::getInstance()->connectDb("api");
	$sql = "SELECT user_id, user_name FROM users_me";
	$mes = db::getInstance()->queryResult($sql);
	
	if ($mes != null) {
		echo_response($mes);
	}
	else {
		write_log("get users_me", $app->request()->getPathInfo(), "users_me hasn't been setup");
		die_error("users/me hasn't been setup");
	}
});

$app->get('/users/me/:name', function ($name) use ($app) {
	db::getInstance()->connectDb("api");
	
	$sql = "SELECT user_id, user_name, user_email, user_address, user_bio, " . 
		"user_picture_path, movein_date, join_date " . 
		"FROM users_me " . 
		"WHERE user_id=\"" . $name . "\"";
	$me = db::getInstance()->queryResult($sql);
	
	if ($me != null) {
		echo_response($me, true);
	}
	else {
		// should be impossible to get here
		write_log("get users_me", $app->request()->getPathInfo(), "get unknown user_me \"$name\"");
		die_error("user '". $name ."' does not exist on this router");
	}
});

//$app->get('/users/postme', function()  use ($app) {
$app->post('/users/me', function()  use ($app) {
	request_from_localhost_only();
	
	$params = get_params_from_request($app->request());
	check_for_required_value($params, "user_name");
	check_for_required_value($params, "user_id");
	
	db::getInstance()->connectDb("api");
	
	$now = date('Y-m-d H:i:s');
	
	$sql = "INSERT INTO users_me (user_id, user_name, join_date) VALUES (?, ?, ?)";
	$sql .= " ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), user_name = VALUES(user_name)";
		
	if ($statement = db::getInstance()->prepareStatement($sql)) {
		db::getInstance()->statementBindParameter($statement, 'sss',
			$params["user_id"],
			$params["user_name"],
			$now
		);
		
		$result = db::getInstance()->executeSqlStatement($statement);
		
		if (!$result) {
			die_error("unable to insert the row to users_me");
		}
	}
	else {
		unable_to_prepare_sql_statement($sql);
	}
	
	if (isset($params["user_email"])) {
		$sql = "UPDATE users_me SET user_email = ? WHERE id=1";
		if ($statement = db::getInstance()->prepareStatement($sql)) {
			if (strlen($params["user_email"]) > 0) {
				db::getInstance()->statementBindParameter($statement, 's', $params["user_email"]);
			}
			else {
				$null = NULL;
				db::getInstance()->statementBindParameter($statement, 's', $null);
			}
	
			$result = db::getInstance()->executeSqlStatement($statement);
	
			if (!$result) {
				die_error("unable to update users_me");
			}
		}
		else {
			unable_to_prepare_sql_statement($sql);
		}
	}
	
	if (isset($params["user_bio"])) {
		$sql = "UPDATE users_me SET user_bio = ? WHERE id=1";
		if ($statement = db::getInstance()->prepareStatement($sql)) {
			if (strlen($params["user_bio"]) > 0) {
				db::getInstance()->statementBindParameter($statement, 's', $params["user_bio"]);
			}
			else {
				$null = NULL;
				db::getInstance()->statementBindParameter($statement, 's', $null);
			}
	
			$result = db::getInstance()->executeSqlStatement($statement);
	
			if (!$result) {
				die_error("unable to update users_me");
			}
		}
		else {
			unable_to_prepare_sql_statement($sql);
		}
	}
	
	if (isset($params["user_picture_path"])) {
		$sql = "UPDATE users_me SET user_picture_path = ? WHERE id=1";
		if ($statement = db::getInstance()->prepareStatement($sql)) {
			if (strlen($params["user_picture_path"]) > 0) {
				db::getInstance()->statementBindParameter($statement, 's', $params["user_picture_path"]);
			}
			else {
				$null = NULL;
				db::getInstance()->statementBindParameter($statement, 's', $null);
			}
	
			$result = db::getInstance()->executeSqlStatement($statement);
	
			if (!$result) {
				die_error("unable to update users_me");
			}
		}
		else {
			unable_to_prepare_sql_statement($sql);
		}
	}
    
    if (isset($params["user_address"])) {
		$sql = "UPDATE users_me SET user_address = ? WHERE id=1";
		if ($statement = db::getInstance()->prepareStatement($sql)) {
			if (strlen($params["user_address"]) > 0) {
				db::getInstance()->statementBindParameter($statement, 's', $params["user_address"]);
			}
			else {
				$null = NULL;
				db::getInstance()->statementBindParameter($statement, 's', $null);
			}
	
			$result = db::getInstance()->executeSqlStatement($statement);
	
			if (!$result) {
				die_error("unable to update users_me");
			}
		}
		else {
			unable_to_prepare_sql_statement($sql);
		}
	}
    
    if (isset($params["movein_date"])) {
		$sql = "UPDATE users_me SET movein_date = ? WHERE id=1";
		if ($statement = db::getInstance()->prepareStatement($sql)) {
			if (strlen($params["movein_date"]) > 0) {
				db::getInstance()->statementBindParameter($statement, 's', $params["movein_date"]);
			}
			else {
				$null = NULL;
				db::getInstance()->statementBindParameter($statement, 's', $null);
			}
	
			$result = db::getInstance()->executeSqlStatement($statement);
	
			if (!$result) {
				die_error("unable to update users_me");
			}
		}
		else {
			unable_to_prepare_sql_statement($sql);
		}
	}
	
	$sql = "SELECT * FROM users_me WHERE id=1";
	$me = db::getInstance()->queryResult($sql);
	$me = $me[0];
	
	$sql = "INSERT INTO users (user_id, user_name, user_email, user_address, user_bio, user_picture_path, user_ip, movein_date, known_date, join_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)" . 
		" ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), user_name = VALUES(user_name), user_email = VALUES(user_email)," . 
        " user_address = VALUES(user_address)," . 
		" user_bio = VALUES(user_bio), user_picture_path = VALUES(user_picture_path), user_ip = VALUES(user_ip)," . 
        " movein_date = VALUES(movein_date)";
	
	if ($statement = db::getInstance()->prepareStatement($sql)) {
		db::getInstance()->statementBindParameter($statement, 'ssssssssss',
			$me["user_id"],
			$me["user_name"],
			$me["user_email"],
            $me["user_address"],
			$me["user_bio"],
			$me["user_picture_path"],
			"127.0.0.1", 
            $me["movein_date"],
			$now, 
			$now
		);
		
		$result = db::getInstance()->executeSqlStatement($statement);
		
		if (!$result) {
			die_error("unable to update in users table");
		}
	}
	else {
		unable_to_prepare_sql_statement($sql);
	}
	
	echo_success("successfully updated me");
});

function checkPassword($user_id, $password) {
	db::getInstance()->connectDb("api");
	
	$sql = "SELECT hash FROM users_me_password WHERE user_id=\"" . $user_id . "\"";
	
	$pwd = db::getInstance()->queryResult($sql);
	
	if ($pwd != null) {
		if (crypt($password, $pwd[0]['hash']) === $pwd[0]['hash']) {
			// old password verified
			return 1;
		}
		else {
			// wrong old password
			return 0;
		}
	}
	else {
		// user doesn't exist
		return -1;
	}
}

function changePassword($user_id, $password, &$newhash) {
	$newhash = null;
	$cost = 10;
	$salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
	$salt = sprintf("$2a$%02d$", $cost) . $salt;
	$hash = crypt($password, $salt);
	
	db::getInstance()->connectDb("api");
	
	$sql = "INSERT INTO users_me_password (user_id, hash) VALUES (?, ?)";
	$sql .= " ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), hash = VALUES(hash)";
	
	if ($statement = db::getInstance()->prepareStatement($sql)) {
		db::getInstance()->statementBindParameter($statement, 'ss',
			$user_id,
			$hash
		);
		
		$result = db::getInstance()->executeSqlStatement($statement);
		
		if (!$result) {
			return false;
			//die_error("unable to insert the row to users_me_password");
		}
		else {
			$newhash = $hash;
			return true;
		}
	}
	else {
		return false;
		//unable_to_prepare_sql_statement($sql);
	}
}

//$app->get('/users/postchangepassword/me', function()  use ($app) {
$app->post('/users/changepassword/me', function()  use ($app) {
	request_from_localhost_only();
	
	$params = get_params_from_request($app->request());
	check_for_required_value($params, "user_id");
	check_for_required_value($params, "old_password");
	check_for_required_value($params, "new_password");
	
	$chkPwd = checkPassword($params['user_id'], $params['old_password']);
	
	if ($chkPwd > 0) {
		// old password verified
		if (changePassword($params['user_id'], $params['new_password'])) {
			echo_success("Successfully changed password");
		}
		else {
			die_error("Unable to change password");
		}
	}
	else if ($chkPwd == 0) {
		// wrong old password
		write_log("post changepassword", null, 
			"wrong existing password for \"{$param['user_id']}\"");
		die_error("The existing password doesn't match our database.");
	}
	else {
		// user doesn't exist
		write_log("post changepassword", null, 
			"change password for unknown user \"{$param['user_id']}\"");
		die_error("Cannot change the password for '". $params['user_id'] ."' since " . 
			"the user does not exist on this router");
	}
});

$app->post('/users/login/me', function()  use ($app) {
	request_from_localhost_only();
	
	$params = get_params_from_request($app->request());
	check_for_required_value($params, "user_id");
	check_for_required_value($params, "password");
	
	$chkPwd = checkPassword($params['user_id'], $params['password']);
	
	if ($chkPwd > 0) {
		// old password verified
		changePassword($params['user_id'], $params['password'], $hash);
		write_log("post login", null, 
			"user \"{$param['user_id']}\" logged in");
		echo_success("Successfully logged in", array(
			"hash" => $hash
		));
	}
	else if ($chkPwd == 0) {
		// wrong old password
		write_log("post login", null, 
			"wrong password for \"{$param['user_id']}\"");
		die_error("The password doesn't match our database.");
	}
	else {
		// user doesn't exist
		write_log("post login", null, 
			"login for unknown user \"{$param['user_id']}\"");
		die_error("The user '". $params['user_id'] ."' does not exist on this router");
	}
});

function checkPasswordHash($user_id, $passwordHash) {
	db::getInstance()->connectDb("api");
	
	$sql = "SELECT hash FROM users_me_password WHERE user_id=\"" . $user_id . "\"";
	
	$pwd = db::getInstance()->queryResult($sql);
	
	if ($pwd != null) {
		if ($passwordHash === $pwd[0]['hash']) {
			// old password verified
			return 1;
		}
		else {
			// wrong old password
			return 0;
		}
	}
	else {
		// user doesn't exist
		return -1;
	}
}

$app->post('/users/hash/me', function()  use ($app) {
	request_from_localhost_only();
	
	$params = get_params_from_request($app->request());
	check_for_required_value($params, "user_id");
	check_for_required_value($params, "hash");
	
	$chkPwd = checkPasswordHash($params['user_id'], $params['hash']);
	
	if ($chkPwd > 0) {
		// verified
		write_log("post hash", null, 
			"user \"{$param['user_id']}\" verified hash");
		echo_success("Hash is verified");
	}
	else if ($chkPwd == 0) {
		// wrong old password
		write_log("post hash", null, 
			"wrong hash for \"{$param['user_id']}\"");
		die_error("The hash doesn't match our database. Logging you out...");
	}
	else {
		// user doesn't exist
		write_log("post hash", null, 
			"verfying hash for unknown user \"{$param['user_id']}\"");
		die_error("The user '". $params['user_id'] ."' does not exist on this router");
	}
});

/**
 * following_users API
 */
$app->get('/following/users', function() use ($app) {
	$params = get_params_from_request($app->request());
	check_for_required_value($params, "app_id");
	
	db::getInstance()->connectDb("api");
	$sql = "SELECT following_id FROM following_users WHERE " .
		"app_id=\"". $params["app_id"]. "\"";
	
	$followings = db::getInstance()->queryResult($sql);
	
	$followings_id = array();
	foreach($followings as $following) {
		array_push($followings_id, $following["following_id"]);
	}
	
	//echo_response(implode(",", $followings_id));
	echo_response($followings_id);
});

//$app->get('/following/postusers', function() use ($app) {
$app->post('/following/users', function() use ($app) {
	$params = get_params_from_request($app->request());
	check_for_required_value($params, "app_id");
	check_for_required_value($params, "following_id");
	
	db::getInstance()->connectDb("api");
	$sql = "INSERT INTO following_users (app_id, following_id) VALUES (?, ?)";
	if ($statement = db::getInstance()->prepareStatement($sql)) {
		db::getInstance()->statementBindParameter($statement, 'ss',
			$params["app_id"],
			$params["following_id"]
		);
		
		$result = db::getInstance()->executeSqlStatement($statement);
		
		if (!$result) {
			die_error("unable to insert the row to following_users");
		}
		else {
			echo_success("successfully added '". $params["following_id"] ."'");
		}
	}
	else {
		unable_to_prepare_sql_statement($sql);
	}
});

/**
 * follower_users API
 */
$app->get('/follower/users', function() use ($app) {
	$params = get_params_from_request($app->request());
	check_for_required_value($params, "app_id");
	
	db::getInstance()->connectDb("api");
	$sql = "SELECT follower_id FROM follower_users WHERE " .
		"app_id=\"". $params["app_id"]. "\"";
	
	$followers = db::getInstance()->queryResult($sql);
	
	$followers_id = array();
	foreach($followers as $follower) {
		array_push($followers_id, $follower["follower_id"]);
	}
	
	//echo_response(implode(",", $followers_id));
	echo_response($followers_id);
});

//$app->get('/follower/postusers', function() use ($app) {
$app->post('/follower/users', function() use ($app) {
	$params = get_params_from_request($app->request());
	check_for_required_value($params, "app_id");
	check_for_required_value($params, "follower_id");
	
	db::getInstance()->connectDb("api");
	$sql = "INSERT INTO follower_users (app_id, follower_id) VALUES (?, ?)";
	if ($statement = db::getInstance()->prepareStatement($sql)) {
		db::getInstance()->statementBindParameter($statement, 'ss',
			$params["app_id"],
			$params["follower_id"]
		);
		
		$result = db::getInstance()->executeSqlStatement($statement);
		
		if (!$result) {
			die_error("unable to insert the row to follower_users");
		}
		else {
			echo_success("successfully added '". $params["follower_id"] ."'");
		}
	}
	else {
		unable_to_prepare_sql_statement($sql);
	}
});

/**
 * image API
 */
$app->get('/img/:url', function ($url) {
	$headers = get_headers($url, 1);
	$type = $headers["Content-Type"];
	
	header("Content-type: $type;");
	$img = file_get_contents($url);
	echo $img;
});

/**
 * vouch API
 */
$app->get('/vouchfor', function () {
	db::getInstance()->connectDb("api");
	$sql = 'SELECT user_id FROM vouch WHERE status="vouched"';
	$result = db::getInstance()->queryResult($sql);
	
	echo_response($result);
});

//$app->get('/postvouch', function () use ($app) {
$app->post('/vouchfor', function () use ($app) {
	$params = get_params_from_request($app->request());
	check_for_required_value($params, "user_id");
	check_for_required_value($params, "vouch_status");
	
	if ($params["vouch_status"] == "waiting") {
		check_for_required_value($params, "code");
	}
	
	db::getInstance()->connectDb("api");
    
    // check user
    $sql = "SELECT * FROM users WHERE user_id=\"". $params["user_id"] . "\"";
	$user = db::getInstance()->queryResult($sql);
	
	if ($user == null || count($user) == 0) {
		// user not in database, add them
        $url = "http://{$_SERVER['REMOTE_ADDR']}:{$_SERVER['SERVER_PORT']}/api/users/me";
        $user = getJsonFromUrl($url);
        if ($user['success']) {
            $user = $user['data'];
            
            if (strcmp($user['user_id'], $params["user_id"]) == 0) {
                $sql = "INSERT INTO users (user_id, user_name, user_email, user_bio, user_picture_path, user_ip, join_date, known_date) " . 
                	"VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
				if ($statement = db::getInstance()->prepareStatement($sql)) {
					db::getInstance()->statementBindParameter($statement, 'ssss',
						$user["user_id"],
						$user["user_name"],
						$user["email"],
						$user["user_bio"],
						$user["user_picture_path"],
						$_SERVER['REMOTE_ADDR'],
						$params["join_date"]
					);
	
					$result = db::getInstance()->executeSqlStatement($statement);
	
					if (!$result) {
						die_error("unable to insert the row to users");
					}
				}
				else {
					unable_to_prepare_sql_statement($sql);
				}
            }
            else {
            	die_error("user information mismatch");
            }
        }
        else {
            die_error("unable to retrieve user's information from {$_SERVER['REMOTE_ADDR']}");
        }
	}
	else if ($user[0]['user_ip'] == "127.0.0.1") {
		die_error("you can't vouch for yourself");
	}
    
	if ($params["vouch_status"] == "notvouch") {
		$string = uniqid();
	
		$sql = "INSERT INTO vouch_code (user_id, code) VALUES (?, ?) " . 
			 "ON DUPLICATE KEY UPDATE code = VALUES(code)";
		if ($statement = db::getInstance()->prepareStatement($sql)) {
			db::getInstance()->statementBindParameter($statement, 'ss',
				$params["user_id"],
				$string
			);
		
			$result = db::getInstance()->executeSqlStatement($statement);
		
			if (!$result) {
				die_error("unable to insert the code to vouch_code");
			}
			else {
				echo_success("vouch initiation successful");
			}
		}
		else {
			unable_to_prepare_sql_statement($sql);
		}
	}
	else if ($params["vouch_status"] == "waiting") {
		$sql = "SELECT code FROM vouch_code WHERE user_id=\"{$params['user_id']}\"";
		$result = db::getInstance()->queryResult($sql);
		
		if (count($result) != 1) {
			die_error("you have not initiated the vouching process");
		}
		$code = $result[0]["code"];
		
		if (strcmp($code, $params["code"]) != 0) {
			die_error("invalid confirmation code");
		}
		else {
			echo_success("confirmation code verified");
		}
	}
	else if ($params["vouch_status"] == "vouched") {
		$sql = "DELETE FROM vouch_code WHERE user_id=\"{$params['user_id']}\"";
		$result = db::getInstance()->executeSqlQuery($sql);
		
		if ($result > 0) {
			echo_success("Thank you for vouching");
		}
		else {
			die_error("there's an error in removing confirmation code");
		}
	}
});

$app->post('/vouchfor/status/update', function () use ($app) {
	request_from_localhost_only();
	
	$params = get_params_from_request($app->request());
	check_for_required_value($params, "user_id");
	check_for_required_value($params, "new_vouch_status");
	
	db::getInstance()->connectDb("api");
	
	// check if user_id exists in vouch table
	$sql = "SELECT * FROM vouch WHERE user_id=\"{$params['user_id']}\"";
	$result = db::getInstance()->queryResult($sql);
	
	if (count($result) == 0) {
		die_error("no user with id {$params['user_id']} in vouching process");
	}
	
	$newstatus = $params['new_vouch_status'];
	$vouch = $result[0];
	if ($newstatus != 'notvouch' && $newstatus != 'waiting' && $newstatus != 'vouched') {
		die_error("invalid value \"$newstatus\"for vouch status");
	}
	else if (($newstatus == 'notvouch') ||
		($newstatus == 'waiting' && $vouch['status'] != 'notvouch') ||
		($newstatus == 'vouched' && $vouch['status'] != 'waiting')) {
		die_error("status \"$newstatus\" mismatched with current vouch status");
	}
	
	$sql = "UPDATE vouch SET status=\"$newstatus\" WHERE id={$vouch['id']}";
	$result = db::getInstance()->executeSqlQuery($sql);
	
	if ($result > 0) {
		echo_success("sucessfully updated vouch status");
	}
	else {
		die_error("unable to update vouch status");
	}
});

$app->get('/vouchfor/id/:id', function ($id) {
	db::getInstance()->connectDb("api");
	$sql = "SELECT COUNT(*) FROM vouch WHERE user_id=\"$id\" AND status=\"vouched\"";
	
	$result = db::getInstance()->queryResult($sql);
	
	if (intval($result[0]["COUNT(*)"]) > 0) {
		echo_response(array("vouched" => true));
	}
	else {
		echo_response(array("vouched" => false));
	}
});

$app->get('/vouchfor/status', function () {
	db::getInstance()->connectDb("api");
	$sql = 'SELECT user_id, status, timestamp FROM vouch';
	$result = db::getInstance()->queryResult($sql);
	
	echo_response($result);
});

$app->get('/vouchfor/status/id/:id', function ($id) {
	db::getInstance()->connectDb("api");
	$sql = "SELECT user_id, status, timestamp FROM vouch WHERE user_id=\"$id\"";
	
	$result = db::getInstance()->queryResult($sql);
	
	echo_response($result, true);
});

$app->get('/whovouchfor/id/:id', function ($id) {
	$url = "http://localhost:9090/neighbors";
	$nearby = json_decode("{" . getUrl($url), true);
	$neighbors = $nearby["data"][0]["neighbors"];
	
	$vouchfor = array();
	foreach($neighbors as $node) {
		$ip = $node["ipv4Address"];
		$apiUrl = "http://$ip:{$_SERVER['SERVER_PORT']}/api/vouchfor/id/$id";
		$result = getJsonFromUrl($apiUrl, $responseCode);
		
		if ($responseCode == 200 && $result['success']) {
			if ($result['data']['vouched']) {
				$apiUrl = "http://$ip:{$_SERVER['SERVER_PORT']}/api/users/me";
				$result = getJsonFromUrl($apiUrl, $responseCode);
				if ($responseCode == 200 && $result["success"]) {
					$user = $result["data"];
					$user["user_ip"] = $ip;
					array_push($vouchfor, $user);
				}
			}
		}
	}
	
	echo_response($vouchfor);
});

$app->get('/vouchcode', function () {
	request_from_localhost_only();
	
	db::getInstance()->connectDb("api");
	$sql = "SELECT u.user_id, u.user_name, v.code FROM vouch_code v, users u WHERE u.user_id=v.user_id";
	
	$result = db::getInstance()->queryResult($sql);
	
	echo_response($result);
});

$app->get('/db/:app_name/:key', function ($app_name, $key) {
	try {
		db::getInstance()->connectDb($app_name);
	} catch(ErrorException $e) {
		die_error("database for application '$app_name' does not exist");
	};
	
	$sql = "SELECT value FROM $app_name.database WHERE name=\"$key\"";
	$result = db::getInstance()->queryResult($sql);
	if (count($result) > 0) {
		echo_response($result[0]);
	}
	else {
		die_error("key \"$key\" does not exist in $app_name database");
	}
});

//$app->get('/postdb/:app_name/:key', function ($app_name, $key) use ($app) {
$app->post('/db/:app_name/:key', function ($app_name, $key) use ($app) {
	$params = get_params_from_request($app->request());
	check_for_required_value($params, "value");
	
	try {
		db::getInstance()->connectDb($app_name);
	} catch(ErrorException $e) {
		die_error("database for application '$app_name' does not exist");
	};
	
	$sql = "INSERT INTO $app_name.database (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)";
	if ($statement = db::getInstance()->prepareStatement($sql)) {
		db::getInstance()->statementBindParameter($statement, 'ss',
			$key,
			$params['value']
		);
	
		$result = db::getInstance()->executeSqlStatement($statement);
		
		if (!$result) {
			die_error("unable to insert '$key' to $app_name database");
		}
		else {
			echo_success("successfully added '$key' to $app_name database");
		}
	}
	else {
		unable_to_prepare_sql_statement($sql);
	}
});

$app->post('/log', function () use ($app) {
	request_from_localhost_only();
	
	$params = get_params_from_request($app->request());
	
	check_for_required_value($params, "app_id");
	check_for_required_value($params, "log");
	
	db::getInstance()->connectDb("log");
	
	$sql = "INSERT INTO app_log (app_id, log) VALUES (?, ?)";
	if ($statement = db::getInstance()->prepareStatement($sql)) {
		db::getInstance()->statementBindParameter($statement, 'ss', 
			$params['app_id'], 
			$params['log']
		); 
		
		$result = db::getInstance()->executeSqlStatement($statement);
		
		if (!$result) {
			die_error("unable to write application log ({$params['app_id']}, {$params['log']})");
		}
	}
});

$app->get('/mymeship', function () {
	//request_from_localhost_only();
	
	$url = "http://localhost:9090/config";
	$data = json_decode("{" . getUrl($url), true);
	$config = $data["data"][0]["config"];
	
	echo_response(array("ip" => $config['mainIpAddress']));
});

$app->run();

?>