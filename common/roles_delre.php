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

	// 🔹 Old Data
	$oldData = getRow($db, 'roles', 'Role_ID', $id);

	if ($action === "delete") {
		$query = "UPDATE roles SET RoleDelete = 'Y' WHERE Role_ID = ?";
		$stmt  = $db->prepare($query);  $stmt->execute([$id]);

		// 🔹 New Data
		$newData = getRow($db, 'roles', 'Role_ID', $id);

		// ✅ LOG DELETE
		logActivity($db, ['event' => 'DELETE', 'table' => 'roles', 'old' => $oldData, 'new' => $newData, 'emp_id'=> $user['Emp_ID'], 'action'=> "Deleted role ID: $id"]);

		respond(["status" => "success", "message" => "Role deleted successfully"]);
	}

	if ($action === "restore") {
		$query = "UPDATE roles SET RoleDelete = 'N' WHERE Role_ID = ?";
		$stmt  = $db->prepare($query);  $stmt->execute([$id]);

		// 🔹 New Data
		$newData = getRow($db, 'roles', 'Role_ID', $id);

		// ✅ LOG RESTORE
		logActivity($db, ['event' => 'RESTORE', 'table' => 'roles', 'old' => $oldData, 'new' => $newData, 'emp_id'=> $user['Emp_ID'], 'action'=> "Restored role ID: $id"]);

		respond(["status" => "success", "message" => "Role restored successfully"]);
	}
?>