<?php

include('mysql-connect.php');
header("Content-Type: application/json");

$srv_tz="Europe/Warsaw";
$server_info=array("name" => "NoterAPI", "version" => "1.0");

$response=array();

/*

NoterAPI v1.0
(C)2021 Bartłomiej "Magnetic-Fox" Węgrzyn!

 Actions:
----------

register	User registration
change		Change user password
info		Get user information
remove		User removal
list		Brief notes listing
retrieve	Get note
add		Add note
update		Update note
delete		Delete note


 Information codes:
--------------------

-512	Internal Server Error (DB Connection Error)

-11	User removal failure
-10	User does not exist
-9	Note does not exist
-8	No necessary information
-7	User deactivated
-6	Login incorrect
-5	Unknown action
-4	No credentials provided
-3	User exists
-2	No usable information in POST
-1	Invalid request method

0	OK

1	User successfully created
2	User successfully updated
3	User successfully removed
4	List command successful
5	Note retrieved successfully
6	Note created successfully
7	Note updated successfully
8	Note deleted successfully
9	User info retrieved successfully

*/

if($conn->connect_error)
{
	$answer_info=array("code" => -512, "attachment" => array());
	$response=array("server" => $server_info, "answer_info" => $answer_info);
	die(json_encode($response));
}

function userExists($username)
{
	global $conn;
	$query="SELECT COUNT(*) FROM Noter_Users WHERE UserName=?";
	$stmt=$conn->prepare($query);
	$stmt->bind_param("s",$username);
	$stmt->execute();
	$stmt->bind_result($resp);
	$stmt->fetch();
	return $resp;
}

function login($username, $password)
{
	global $conn;
	if(userExists($username))
	{
		$query="SELECT ID, UserName, PasswordHash, Active FROM Noter_Users WHERE UserName=?";
		$stmt=$conn->prepare($query);
		$stmt->bind_param("s",$username);
		$stmt->execute();
		$stmt->bind_result($id,$username,$passwordHash,$active);
		$stmt->fetch();
		if($active)
		{
			if(password_verify($password,$passwordHash))
			{
				return $id;
			}
		}
		else
		{
			return -1;
		}
	}
	return 0;
}

function register($username, $password)
{
	global $conn;
	if(userExists($username))
	{
		return -1;
	}
	else
	{
		if(($username!="") && ($password!=""))
		{
			$passwordHash=password_hash($password,PASSWORD_DEFAULT);
			$query="INSERT INTO Noter_Users(UserName, PasswordHash, RemoteAddress, ForwardedFor, UserAgent, LastRemoteAddress, LastForwardedFor, LastUserAgent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
			$stmt=$conn->prepare($query);
			$stmt->bind_param("ssssssss",$username,$passwordHash,$_SERVER["REMOTE_ADDR"],$_SERVER["HTTP_X_FORWARDED_FOR"],$_SERVER["HTTP_USER_AGENT"],$_SERVER["REMOTE_ADDR"],$_SERVER["HTTP_X_FORWARDED_FOR"],$_SERVER["HTTP_USER_AGENT"]);
			$stmt->execute();
			if($conn->affected_rows!=-1)
			{
				return 1;
			}
		}
	}
	return 0;
}

function userUpdate($username, $password, $newPassword)
{
	global $srv_tz;
	global $conn;
	if(($username!="") && ($password!="") && ($newPassword!=""))
	{
		$res=login($username,$password);
		if($res>=1)
		{
			date_default_timezone_set($srv_tz);
			$now=date("Y-m-d H:i:s");
			$passwordHash=password_hash($newPassword,PASSWORD_DEFAULT);
			$query="UPDATE Noter_Users SET PasswordHash=?, LastChanged=?, LastRemoteAddress=?, LastForwardedFor=?, LastUserAgent=? WHERE ID=?";
			$stmt=$conn->prepare($query);
			$stmt->bind_param("sssssi",$passwordHash,$now,$_SERVER["REMOTE_ADDR"],$_SERVER["HTTP_X_FORWARDED_FOR"],$_SERVER["HTTP_USER_AGENT"],$res);
			$stmt->execute();
			if($conn->affected_rows!=-1)
			{
				return 1;
			}
		}
		else if($res==-1)
		{
			return -1;
		}
	}
	return 0;
}

if($_SERVER["REQUEST_METHOD"]=="GET")
{
	$answer_info=array("code" => 0, "attachment" => array());
	$response=array("server" => $server_info, "answer_info" => $answer_info);
}
else if($_SERVER["REQUEST_METHOD"]=="POST")
{
	if(array_key_exists("action",$_POST) && (array_key_exists("username",$_POST)) && array_key_exists("password",$_POST))
	{
		$ut=trim($_POST["username"]);
		$pt=trim($_POST["password"]);
		if($_POST["action"]=="register")
		{
			$res=register($ut,$pt);
			if($res==-1)
			{
				$answer_info=array("code" => -3, "attachment" => array());
			}
			else if($res==1)
			{
				$answer_info=array("code" => 1, "attachment" => array());
			}
			else
			{
				$answer_info=array("code" => -4, "attachment" => array());
			}
			$response=array("server" => $server_info, "answer_info" => $answer_info);
		}
		else if($_POST["action"]=="change")
		{
			if(($ut!="") && ($pt!=""))
			{
				if((array_key_exists("newPassword",$_POST)) && ($_POST["newPassword"]!=""))
				{
					$res=userUpdate($ut,$pt,$_POST["newPassword"]);
					if($res==1)
					{
						$answer_info=array("code" => 2, "attachment" => array());
					}
					else if($res==-1)
					{
						$answer_info=array("code" => -7, "attachment" => array());
					}
					else
					{
						$answer_info=array("code" => -6, "attachment" => array());
					}
				}
				else
				{
					$answer_info=array("code" => -8, "attachment" => array());
				}
			}
			else
			{
				$answer_info=array("code" => -4, "attachment" => array());
			}
			$response=array("server" => $server_info, "answer_info" => $answer_info);
		}
		else if($_POST["action"]=="info")
		{
			if(($ut!="") && ($pt!=""))
			{
				$res=login($ut,$pt);
				if($res>=1)
				{
					$query="SELECT ID, UserName, DateRegistered, UserAgent, LastChanged, LastUserAgent FROM Noter_Users WHERE ID=?";
					$stmt=$conn->prepare($query);
					$stmt->bind_param("i",$res);
					$stmt->execute();
					$stmt->bind_result($id, $username, $dateRegistered, $userAgent, $lastChanged, $lastUserAgent);
					$stmt->fetch();
					$answer=array("user" => array("id" => $id, "username" => $username, "date_registered" => $dateRegistered, "user_agent" => $userAgent, "last_changed" => $lastChanged, "last_user_agent" => $lastUserAgent));
					$answer_info=array("code" => 9, "attachment" => array("user"));
				}
				else if($res==-1)
				{
					$answer_info=array("code" => -7, "attachment" => array());
				}
				else
				{
					$answer_info=array("code" => -6, "attachment" => array());
				}
			}
			else
			{
				$answer_info=array("code" => -4, "attachment" => array());
			}
			$response=array("server" => $server_info, "answer_info" => $answer_info);
			if(isset($answer))
			{
				$response["answer"]=$answer;
			}
		}
		else if($_POST["action"]=="remove")
		{
			if(($ut!="") && ($pt!=""))
			{
				$res=login($ut,$pt);
				if($res>=1)
				{
					$query="DELETE FROM Noter_Entries WHERE UserID=?";
					$stmt=$conn->prepare($query);
					$stmt->bind_param("i",$res);
					$stmt->execute();
					if($conn->affected_rows!=-1)
					{
						$query="DELETE FROM Noter_Users WHERE ID=?";
						$stmt=$conn->prepare($query);
						$stmt->bind_param("i",$res);
						$stmt->execute();
						if($conn->affected_rows>0)
						{
							$answer_info=array("code" => 3, "attachment" => array());
						}
						else
						{
							$answer_info=array("code" => -10, "attachment" => array());
						}
					}
					else
					{
						$answer_info=array("code" => -11, "attachment" => array());
					}
				}
				else if($res==-1)
				{
					$answer_info=array("code" => -7, "attachment" => array());
				}
				else
				{
					$answer_info=array("code" => -6, "attachment" => array());
				}
			}
			else
			{
				$answer_info=array("code" => -4, "attachment" => array());
			}
			$response=array("server" => $server_info, "answer_info" => $answer_info);
		}
		else if($_POST["action"]=="list")
		{
			if(($ut!="") && ($pt!=""))
			{
				$res=login($ut,$pt);
				if($res>=1)
				{
					$query="SELECT ID, Subject, LastModified FROM Noter_Entries WHERE UserID = ? ORDER BY LastModified DESC";
					$stmt=$conn->prepare($query);
					$stmt->bind_param("i",$res);
					$stmt->execute();
					$stmt->bind_result($id, $subject, $lastModified);
					$count=0;
					$notesSummary=array();
					while($stmt->fetch())
					{
						$count++;
						array_push($notesSummary,array("id" => $id, "subject" => $subject, "last_modified" => $lastModified));
					}
					$answer_info=array("code" => 4, "attachment" => array("count","notes_summary"));
					$answer=array("count" => $count, "notes_summary" => $notesSummary);
				}
				else if($res==-1)
				{
					$answer_info=array("code" => -7, "attachment" => array());
				}
				else
				{
					$answer_info=array("code" => -6, "attachment" => array());
				}
			}
			else
			{
				$answer_info=array("code" => -4, "attachment" => array());
			}
			$response=array("server" => $server_info, "answer_info" => $answer_info);
			if(isset($answer))
			{
				$response["answer"]=$answer;
			}
		}
		else if($_POST["action"]=="retrieve")
		{
			if(($ut!="") && ($pt!=""))
			{
				$res=login($ut,$pt);
				if($res>=1)
				{
					if(array_key_exists("noteID",$_POST))
					{
						$query="SELECT ID, Subject, Entry, DateAdded, LastModified, UserAgent, LastUserAgent FROM Noter_Entries WHERE ID=? AND UserID=?";
						$stmt=$conn->prepare($query);
						$stmt->bind_param("ii",$_POST["noteID"],$res);
						$stmt->execute();
						$stmt->bind_result($id,$subject,$entry,$dateAdded,$lastModified,$userAgent,$lastUserAgent);
						if($stmt->fetch())
						{
							$answer_info=array("code" => 5, "attachment" => array("note"));
							$answer=array("note" => array("id" => $id, "subject" => $subject, "entry" => $entry, "date_added" => $dateAdded, "last_modified" => $lastModified, "user_agent" => $userAgent, "last_user_agent" => $lastUserAgent));
						}
						else
						{
							$answer_info=array("code" => -9, "attachment" => array());
						}
					}
					else
					{
						$answer_info=array("code" => -8, "attachment" => array());
					}
				}
				else if($res==-1)
				{
					$answer_info=array("code" => -7, "attachment" => array());
				}
				else
				{
					$answer_info=array("code" => -6, "attachment" => array());
				}
			}
			else
			{
				$answer_info=array("code" => -4, "attachment" => array());
			}
			$response=array("server" => $server_info, "answer_info" => $answer_info);
			if(isset($answer))
			{
				$response["answer"]=$answer;
			}
		}
		else if($_POST["action"]=="add")
		{
			if(($ut!="") && ($pt!=""))
			{
				$res=login($ut,$pt);
				if($res>=1)
				{
					if(array_key_exists("subject",$_POST) && array_key_exists("entry",$_POST))
					{
						$subt=trim($_POST["subject"]);
						$entt=trim($_POST["entry"]);
						if(($subt!="") && ($entt!=""))
						{
							$query="INSERT INTO Noter_Entries(UserID, Subject, Entry, RemoteAddress, ForwardedFor, UserAgent, LastRemoteAddress, LastForwardedFor, LastUserAgent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
							$stmt=$conn->prepare($query);
							$stmt->bind_param("issssssss",$res,$subt,$entt,$_SERVER["REMOTE_ADDR"],$_SERVER["HTTP_X_FORWARDED_FOR"],$_SERVER["HTTP_USER_AGENT"],$_SERVER["REMOTE_ADDR"],$_SERVER["HTTP_X_FORWARDED_FOR"],$_SERVER["HTTP_USER_AGENT"]);
							$stmt->execute();
							if($conn->affected_rows!=-1)
							{
								$answer_info=array("code" => 6, "attachment" => array("new_id"));
								$query="SELECT MAX(ID) FROM Noter_Entries WHERE UserID=?";
								$stmt=$conn->prepare($query);
								$stmt->bind_param("i",$res);
								$stmt->execute();
								$stmt->bind_result($newID);
								$stmt->fetch();
								$answer=array("new_id" => $newID);
							}
							else
							{
								$answer_info=array("code" => -512, "attachment" => array());
							}
						}
						else
						{
							$answer_info=array("code" => -8, "attachment" => array());
						}
					}
					else
					{
						$answer_info=array("code" => -8, "attachment" => array());
					}
				}
				else if($res==-1)
				{
					$answer_info=array("code" => -7, "attachment" => array());
				}
				else
				{
					$answer_info=array("code" => -6, "attachment" => array());
				}
			}
			else
			{
				$answer_info=array("code" => -4, "attachment" => array());
			}
			$response=array("server" => $server_info, "answer_info" => $answer_info);
			if(isset($answer))
			{
				$response["answer"]=$answer;
			}
		}
		else if($_POST["action"]=="update")
		{
			if(($ut!="") && ($pt!=""))
			{
				$res=login($ut,$pt);
				if($res>=1)
				{
					if((array_key_exists("noteID",$_POST)) && (array_key_exists("subject",$_POST)) && (array_key_exists("entry",$_POST)))
					{
						$subt=trim($_POST["subject"]);
						$entt=trim($_POST["entry"]);
						if(($subt!="") && ($entt!=""))
						{
							date_default_timezone_set($srv_tz);
							$now=date("Y-m-d H:i:s");
							$query="UPDATE Noter_Entries SET Subject=?, Entry=?, LastModified=?, LastRemoteAddress=?, LastForwardedFor=?, LastUserAgent=? WHERE ID=? AND UserID=?";
							$stmt=$conn->prepare($query);
							$stmt->bind_param("ssssssii",$subt,$entt,$now,$_SERVER["REMOTE_ADDR"],$_SERVER["HTTP_X_FORWARDED_FOR"],$_SERVER["HTTP_USER_AGENT"],$_POST["noteID"],$res);
							$stmt->execute();
							if($conn->affected_rows>0)
							{
								$answer_info=array("code" => 7, "attachment" => array());
							}
							else
							{
								$answer_info=array("code" => -9, "attachment" => array());
							}
						}
						else
						{
							$answer_info=array("code" => -8, "attachment" => array());
						}
					}
					else
					{
						$answer_info=array("code" => -8, "attachment" => array());
					}
				}
				else if($res==-1)
				{
					$answer_info=array("code" => -7, "attachment" => array());
				}
				else
				{
					$answer_info=array("code" => -6, "attachment" => array());
				}
			}
			else
			{
				$answer_info=array("code" => -4, "attachment" => array());
			}
			$response=array("server" => $server_info, "answer_info" => $answer_info);
		}
		else if($_POST["action"]=="delete")
		{
			if(($ut!="") && ($pt!=""))
			{
				$res=login($ut,$pt);
				if($res>=1)
				{
					if(array_key_exists("noteID",$_POST))
					{
						$query="DELETE FROM Noter_Entries WHERE ID=? AND UserID=?";
						$stmt=$conn->prepare($query);
						$stmt->bind_param("ii",$_POST["noteID"],$res);
						$stmt->execute();
						if($conn->affected_rows>0)
						{
							$answer_info=array("code" => 8, "attachment" => array());
						}
						else
						{
							$answer_info=array("code" => -9, "attachment" => array());
						}
					}
					else
					{
						$answer_info=array("code" => -8, "attachment" => array());
					}
				}
				else if($res==-1)
				{
					$answer_info=array("code" => -7, "attachment" => array());
				}
				else
				{
					$answer_info=array("code" => -6, "attachment" => array());
				}
			}
			else
			{
				$answer_info=array("code" => -4, "attachment" => array());
			}
			$response=array("server" => $server_info, "answer_info" => $answer_info);
		}
		else
		{
			$answer_info=array("code" => -5, "attachment" => array());
			$response=array("server" => $server_info, "answer_info" => $answer_info);
		}
	}
	else
	{
		$answer_info=array("code" => -2, "attachment" => array());
		$response=array("server" => $server_info, "answer_info" => $answer_info);
	}
}
else
{
	$answer_info=array("code" => -1, "attachment" => array());
	$response=array("server" => $server_info, "answer_info" => $answer_info);
}

echo json_encode($response);

?>
