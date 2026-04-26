<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");
	require_once("../middleware/permission.php");

	$user = authenticate();
	$db = getDB();

	// 🔐 Page permission
	requirePermission($db, $user['Role_ID']);

	$data = json_decode(file_get_contents("php://input"), true);

	$id     = $data['id'] ?? null;
	$action = $data['action'] ?? '';

	if (!$id || !in_array($action, ['delete', 'restore'])) { respond(["status" => "error", "message" => "Invalid request"]); }

	$oldData = getRow($db, 'states', 'State_ID', $id);

	$status = $action === 'delete' ? 'Y' : 'N';

	$stmt = $db->prepare("UPDATE states SET StateDelete = ? WHERE State_ID = ?");
	$stmt->execute([$status, $id]);

	$newData = getRow($db, 'states', 'State_ID', $id);

	logActivity($db, ['event' => strtoupper($action), 'table' => 'states', 'old' => $oldData, 'new' => $newData, 'emp_id' => $user['Emp_ID']]);
	respond(["status" => "success", "message" => "State " . ($action === 'delete' ? 'deleted' : 'restored') . " successfully"]);
?>