<?php
	/*
		Very simple guest book (experimental code from 2021, patched a little in 2024)
		(C)2021-2024 Bartłomiej "Magnetic-Fox" Węgrzyn!
	*/
	include_once("mysql-connect.php");

	// MySQL connection starter (preparing everything if needed)
	function prepareConnection() {
		global $conn;
		if(!isset($conn)) {
			$conn=new mysqli(DB_SERVERNAME,DB_USERNAME,DB_PASSWORD,DB_NAME);
			mysqli_set_charset($conn,"utf8");
		}
		return;
	}

	// Proper data formatting function
	function exportDate($dateString) {
		return date("Y-m-d H:i:s", strtotime($dateString));
	}
?>
