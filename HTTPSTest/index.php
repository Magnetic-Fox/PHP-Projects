<?php

function isSecure() {
	return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
}

if(isSecure()) {
	echo "HTTPS";
}
else {
	echo "HTTP";
}

?>
