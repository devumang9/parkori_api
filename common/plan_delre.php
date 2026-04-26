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
	$action = trim($data['action'] ?? '');

	// ✅ VALIDATION
	if (!$id) { respond(["status" => "error", "message" => "Invalid ID"]); }
	if (!in_array($action, ['delete', 'restore'])) { respond(["status" => "error", "message" => "Invalid action"]); }

	// 🔹 OLD DATA
	$oldData = getRow($db, 'subscribe_plan', 'Plan_ID', $id);

	if (!$oldData) { respond(["status" => "error", "message" => "Record not found"]); }

	// 🔹 DETERMINE FLAG
	$deleteFlag = ($action === 'delete') ? 'Y' : 'N';

	// 🔹 UPDATE
	$stmt  = $db->prepare("UPDATE subscribe_plan SET PlanDelete = ? WHERE Plan_ID = ?");
	$stmt->execute([$deleteFlag, $id]);

	// 🔹 NEW DATA
	$newData = getRow($db, 'subscribe_plan', 'Plan_ID', $id);

	// ✅ LOG
	logActivity($db, ['event' => strtoupper($action), 'table' => 'subscribe_plan', 'old' => $oldData, 'new' => $newData, 'emp_id' => $user['Emp_ID'], 'action' => ucfirst($action) . " plan ID: $id"]);

	respond(["status" => "success", "message" => $action === 'delete' ? "Plan deleted successfully" : "Plan restored successfully"]);
?>