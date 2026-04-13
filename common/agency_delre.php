<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");

	$user = authenticate();
	$db = getDB();

	$data = json_decode(file_get_contents("php://input"), true);

	$id = $data['id'];
	$action = $data['action'];

	$status = ($action === "delete") ? 'Y' : 'N';

	$stmt = $db->prepare("UPDATE agency SET AgDelete = ? WHERE Ag_ID = ?");
	$stmt->execute([$status, $id]);

	logActivity($db, ['event' => strtoupper($action), 'table' => 'agency', 'emp_id'=> $user['Emp_ID'], 'action'=> ucfirst($action) . ' agency ID: ' . $id]);
	respond(["status" => "success", "message" => $action === "delete" ? "Agency deleted" : "Agency restored"]);
?>