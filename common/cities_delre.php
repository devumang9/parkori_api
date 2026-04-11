<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");

	$user = authenticate();
	$db = getDB();

	$data = json_decode(file_get_contents("php://input"), true);

	$id     = $data['id'] ?? null;
	$action = $data['action'] ?? '';

	if (!$id || !in_array($action, ['delete','restore'])) { respond(["status"=>"error","message"=>"Invalid request"]); }

	$old = getRow($db, 'cities', 'City_ID', $id);

	$status = $action === 'delete' ? 'Y' : 'N';

	$stmt = $db->prepare("UPDATE cities SET CityDelete=? WHERE City_ID=?");
	$stmt->execute([$status, $id]);

	$new = getRow($db, 'cities', 'City_ID', $id);

	logActivity($db, ['event' => strtoupper($action), 'table' => 'cities', 'old' => $old, 'new' => $new, 'emp_id' => $user['Emp_ID'], 'action' => ucfirst($action) . " city ID: $id"]);
	respond(["status"=>"success", "message"=>"City ".$action." successfully"]);
?>