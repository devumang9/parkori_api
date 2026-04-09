<?php
	require_once("../middleware/auth.php");
	$user = authenticate();
	respond(["status" => "success"]);
?>