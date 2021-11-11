<!DOCTYPE html>
<html>
	<head>
		<title>Kolejny testowy plik</title>
	</head>
	<body>
		<?php
			$pass="cc";
			if(array_key_exists('pass',$_GET))
			{
				echo "GET pass=".htmlspecialchars($_GET["pass"])."<br>";
			}
			echo "pass=".$pass."<br>";
		?>
	</body>
</html>
