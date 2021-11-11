<!DOCTYPE html>
<html>
<head>
	<title>Księga wpisów</title>
	<link rel="stylesheet" href="styles/style.css" type="text/css">
</head>
<body>
<a href="index.php"><h2>Księga wpisów</h2></a>
<hr>
<?php
	include('mysql-connect.php');

	if($conn->connect_error)
	{
		die("Connection failed: ".$conn->connect_error);
	}

	$postDone=false;

	if($_SERVER["REQUEST_METHOD"]=="POST")
	{
		if(array_key_exists("name",$_POST) && array_key_exists("entry",$_POST))
		{
			if(($_POST["name"]!="") and ($_POST["entry"]!=""))
			{
				$name=trim($_POST["name"]);
				$entry=trim($_POST["entry"]);

				$query="INSERT INTO Entries (Name, Entry, RemoteAddress, ForwardedFor, UserAgent) VALUES (?, ?, ?, ?, ?)";
				$stmt=$conn->prepare($query);
				$stmt->bind_param("sssss",$name,$entry,$_SERVER["REMOTE_ADDR"],$_SERVER["HTTP_X_FORWARDED_FOR"],$_SERVER["HTTP_USER_AGENT"]);
				$stmt->execute();

				if($conn->affected_rows==-1)
				{
					$postDone=false;
				}
				else
				{
					$postDone=true;
				}
			}
		}
	}

	$sql="SELECT COUNT(*) AS c FROM Entries";
	$result=$conn->query($sql);
	$row=$result->fetch_assoc();
	$count=$row["c"];

	$ids=0;

	if(array_key_exists("page",$_GET))
	{
		$page=$_GET["page"];
		if($page>1)
		{
			if(($page-1)*10<$count)
			{
				$ids=($page-1)*10;
			}
			else
			{
				$page=1;
			}
		}
	}
	else
	{
		$page=1;
	}

	$sql="SELECT Name, Entry, DateAdded, UserAgent FROM Entries ORDER BY ID DESC LIMIT ".$ids.",10";
	$result=$conn->query($sql);

	if($result->num_rows > 0)
	{
		$plot=true;
		while($row = $result->fetch_assoc())
		{
			if($plot)
			{
				echo "<div tabindex=\"0\" class=\"entry1\">";
				$plot=false;
			}
			else
			{
				echo "<div tabindex=\"0\" class=\"entry2\">";
				$plot=true;
			}
			echo "<span class=\"name\">".htmlspecialchars($row["Name"])."</span>";
			echo "<span class=\"date\">".htmlspecialchars($row["DateAdded"])."</span><br>\n<br>\n";
			echo "<span class=\"entry\">".nl2br(htmlspecialchars($row["Entry"]),false)."</span>\n";
			echo "<span class=\"ua\"><br>".htmlspecialchars($row["UserAgent"])."</span><br>\n";
			echo "</div>\n";
		}
	}

	echo "<hr>\n";
	$anyExists=false;
	echo "<div class=\"center\">";
	if(($count>10) && ($page>1))
	{
		echo "<a href=\"?page=".($page-1)."\">&lt;&lt;&nbsp;Poprzednia</a>";
		$anyExists=true;
	}
	if($page*10<$count)
	{
		if($anyExists)
		{
			echo "&nbsp;&nbsp;";
		}
		else
		{
			$anyExists=true;
		}
		echo "<a href=\"?page=".($page+1)."\">Następna&nbsp;&gt;&gt;</a>";
	}
	echo "</div>\n";
	if($anyExists)
	{
		echo "<hr>\n";
	}
	$conn->close();

	if($_SERVER["REQUEST_METHOD"]=="POST")
	{
		$first=true;
		if($postDone)
		{
			echo "<br>\n";
			echo "<div class=\"green\">Pomyślnie umieszczono wpis! :)</div>";
		}
		else
		{
			if((array_key_exists("name",$_POST) && $_POST["name"]!="") && (array_key_exists("entry",$_POST) && $_POST["entry"]!=""))
			{
				echo "<br>\n";
				echo "<div class=\"red\">Nie udało się umieścić nowego wpisu. :( Spróbuj ponownie za chwilę.</div>";
			}
		}
		if(array_key_exists("name",$_POST) && $_POST["name"]=="")
		{
			if($first)
			{
				echo "<br>\n";
				$first=false;
			}
			echo "<div class=\"red\">Musisz podać swojego nicka!</div>";
		}
		if(array_key_exists("entry",$_POST) && $_POST["entry"]=="")
		{
			if($first)
			{
				echo "<br>\n";
				$first=false;
			}
			echo "<div class=\"red\">Musisz cokolwiek napisać!</div>";
		}
	}
?>
<h2>Dodaj nowy wpis!</h2>
<form method="POST" action="index.php" class="margin">
	<label for="name">Nick:</label>
	<input type="text" name="name" class="w100" value="<?php

		if($_SERVER["REQUEST_METHOD"]=="POST")
		{
			if(array_key_exists("name",$_POST) && array_key_exists("entry",$_POST))
			{
				if(($_POST["entry"]=="") || (!$postDone))
				{
					echo $_POST["name"];
				}
			}
		}

	?>">
	<label for="entry">Wpis:</label>
	<textarea rows="8" cols="40" name="entry" class="w100"><?php
		if($_SERVER["REQUEST_METHOD"]=="POST")
		{
			if(array_key_exists("name",$_POST) && array_key_exists("entry",$_POST))
			{
				if(($_POST["name"]=="") || (!$postDone))
				{
					echo $_POST["entry"];
				}
			}
		}
	?></textarea>
	<input type="submit" name="submit" value="Dodaj" class="button200">
</form>
<div style="width: 100%;">
<hr>
Copyright &copy; 2021 Bartłomiej &quot;Magnetic-Fox&quot; Węgrzyn!
</div>
</body>
</html>
