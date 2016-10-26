<?php

function packNoSql($data) {
	$nosql = array("value" => json_encode($data));
	return $nosql;
}

function unpackNoSql($data) {
	$array = json_decode($data['value'], true);
	return $array;
}

?>