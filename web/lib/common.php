<?php
function getUrl($url, &$responseCode=0) {
	$curl = curl_init($url); 
	curl_setopt($curl, CURLOPT_FAILONERROR, true); 
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); 
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); 
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);   
	$result = curl_exec($curl);
	
	$info = curl_getinfo($curl);
	$responseCode = $info['http_code'];
	
	curl_close($curl);
	
	return $result;
}

function getJsonFromUrl($url, &$responseCode=0) {
	$result = getUrl($url, $responseCode);
	return json_decode($result, true);
}

function postToUrl($url, $data) {
	$curl = curl_init($url); 
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, true);
	
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));
	
	curl_setopt($curl, CURLOPT_FAILONERROR, true); 
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); 
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); 
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	
	$result = curl_exec($curl);
	
	return $result;
}

function postJsonToUrl($url, $json=array()) {
	$post = "json=".json_encode($json);
	$result = postToUrl($url, $post);
	
	return json_decode($result, true);
}
?>