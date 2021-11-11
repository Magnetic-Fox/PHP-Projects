<!DOCTYPE html>
<html>
	<head>
		<title>Testowy plik PHP</title>
	</head>
	<body>
		<?php
			echo "Furry!<br>\n";
			if(array_key_exists('pass',$_POST))
			{
				echo htmlspecialchars($_POST["pass"]);
				echo "\n";
			}
		?>
	</body>
</html>
