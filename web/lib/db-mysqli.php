<?php

class db {
	private $database;
	
	private static $instance = false;
	
	private function __construct() {
	}
	
	public function __destruct() {
		self::getInstance()->closeDb();
	}
	
	public static function getInstance() {
		if (self::$instance == false) {
			self::$instance = new db();
		}
		
		return self::$instance;
	}
	
	private function setDatabase($db) {
		self::getInstance()->database = $db;
	}
	
	public function connectDb($database_name=null) {
		if ($database_name == null) {
			$db = mysqli_connect("localhost", "root", "[mysql_password]");
		}
		else {
			$db = mysqli_connect("localhost", "root", "[mysql_password]", $database_name);
		}
	
		if (mysqli_connect_errno($db)) {
			//die("Count not connect to database with mysqli plugin: " . mysqli_connect_error());
			return null;
		}
		else {
			self::getInstance()->setDatabase($db);
		}
	}
	
	public function getDatabase() {
		$db = self::getInstance()->database;
		if ($db == null) {
			$db = self::getInstance()->connectDb();
		}
		
		return $db;
	}

	public function closeDb() {
		mysqli_close(self::getInstance()->getDatabase());
	}

	public function queryResult($sql) {
		$db = self::getInstance()->getDatabase();
		
		if ($result = mysqli_query($db, $sql)) {
			$no_result = mysqli_num_rows($result);
			$result_array = array();
			for ($i = 0; $i < $no_result; $i++) {
				$row = $result->fetch_array(MYSQLI_ASSOC);
				$result_array[$i] = $row;
			}
		
			mysqli_free_result($result);
		
			return $result_array;
		}
		else {
			return array();
		}
	}

	public function executeSqlQuery($sql) {
		$db = self::getInstance()->getDatabase();
		
		if (mysqli_query($db, $sql)) {
			return mysqli_affected_rows($db);
		}
		else {
			return -1;
		}
	}

	function prepareStatement($sql) {
		$db = self::getInstance()->getDatabase();
		
		return mysqli_prepare($db, $sql);
	}

	function statementBindParameter() {
		// parameters: statement, type, value1, value2, value3, ...
		call_user_func_array('mysqli_stmt_bind_param', func_get_args()); //equivalent to mysqli_stmt_bind_param(func_get_args());
	}

	function executeSqlStatement($statement) {
		$result = mysqli_stmt_execute($statement);
		mysqli_stmt_close($statement);
	
		return $result;
	}

	function insertId() {
		$db = self::getInstance()->getDatabase();
		
		return mysqli_insert_id($db);
	}
}

?>