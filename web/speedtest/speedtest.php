<?php

if ($_GET['ip']) {
	$ip = $_GET['ip'];
	//echo $ip;
}
else {
	$ip = '127.0.0.1';
}

if ($_GET['times']) {
	$times = intval($_GET['times']);
}
else {
	$times = 1;
}

if ($_GET['mb']) {
	$mb = intval($_GET['mb']);
}
else {
	$mb = 2;
}

$totalTime = 0;
$totalSpeed = 0;

header('Content-Type: text/plain');

for ($i = 0; $i < $times; $i++) {
	$url = "http://$ip/speedtest/data.php?mb=2";
	//$ch = curl_init('http://www.yahoo.com');
	$ch = curl_init($url);

	curl_setopt($ch, CURLOPT_FAILONERROR, true); 
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   

	curl_exec($ch);

	if (!curl_errno($ch)) {
		$info = curl_getinfo($ch);
		
		printf("Round %d of %d\n", $i+1, $times);
		//echo 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'] . '<br>';
		printf("Downloaded %d bytes = %.4f kB = %.4f MB in %.4f seconds.\n", $info['size_download'], $info['size_download']/1024, $info['size_download']/(1024*1024), $info['total_time']);
	
		$kbps = $info['speed_download']*8;
		$mbps = $kbps / (1024*1024);
		printf("Speed %.4f kbps, which is %.4f Mbps", $kbps, $mbps);
		
		$totalTime += $info['total_time'];
		$totalSpeed += $mbps;
		
		echo "\n\n";
		/*
		echo "\n\ncurl_getinfo() said:\n", str_repeat('-', 31 + strlen($url)), "\n";
		foreach ($info as $label => $value) {
			printf("%-30s %s\n", $label, $value);
		}
		*/
		//var_dump($info);
	}
	//echo $result;

	curl_close($ch);
}

printf("Average time = %.4f seconds\n", $totalTime/$times);
printf("Average speed = %.4f Mbps", $totalSpeed/$times);

?>