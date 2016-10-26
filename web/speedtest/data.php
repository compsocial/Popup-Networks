<?php

$kb = 1024;
if ($_GET['kb']) {
	$kb = intval($_GET['kb']);
}
else if ($_GET['mb']) {
	$kb = intval($_GET['mb'])*1024;
}

flush();

for ($x = 0; $x < $kb; $x++) {
	echo str_pad('', 1024, '.');
	flush();
}

?>