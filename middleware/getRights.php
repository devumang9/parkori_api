<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");

	$user = authenticate();
	$db = getDB();

	$module = $_GET['module'] ?? null;

	if (!$module) { respond(["status" => "error", "message" => "Module required"]); }

	// Get Page_ID
	$stmt = $db->prepare("SELECT Page_ID FROM menupages WHERE PageFileName = ? AND PageDelete = 'N' LIMIT 1");
	$stmt->execute([$module]);   $page = $stmt->fetch();

	if (!$page) { respond(["status" => "error", "message" => "Invalid module"]); }

	// Get rights
	$stmt = $db->prepare("SELECT DefView, DefAdd, DefEdit, DefDel, DefRestore FROM defaultrights WHERE Role_ID = ? AND Page_ID = ? AND DefDelete = 'N' LIMIT 1");
	$stmt->execute([$user['Role_ID'], $page['Page_ID']]);   $rights = $stmt->fetch();

	if (!$rights) { $rights = ["DefView" => "No", "DefAdd" => "No", "DefEdit" => "No", "DefDel" => "No", "DefRestore" => "No"]; }
	respond(["status" => "success", "rights" => $rights]);
?>