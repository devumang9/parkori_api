<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");

	$user = authenticate();
	$db = getDB();

	$data = json_decode(file_get_contents("php://input"), true);

	$id     = $data['id'] ?? null;
	$action = $data['action'] ?? '';

	// ✅ VALIDATION
	if (!$id || !in_array($action, ['delete', 'restore'])) { respond(["status" => "error", "message" => "Invalid request"]); }

	// 🔹 OLD DATA
	$oldData = getRow($db, 'countries', 'Cont_ID', $id);

	// 🔴 DELETE
	if ($action === "delete") {
		$query = "UPDATE countries SET ContDelete = 'Y' WHERE Cont_ID = ?";
		$stmt  = $db->prepare($query);   $stmt->execute([$id]);

		// 🔹 NEW DATA
		$newData = getRow($db, 'countries', 'Cont_ID', $id);

		// ✅ LOG
		logActivity($db, ['event' => 'DELETE', 'table' => 'countries', 'old' => $oldData, 'new' => $newData, 'emp_id' => $user['Emp_ID'], 'action' => "Deleted country ID: $id"]);
		respond(["status" => "success", "message" => "Country deleted successfully"]);
	}

	// 🟢 RESTORE
	if ($action === "restore") {
		$query = "UPDATE countries SET ContDelete = 'N' WHERE Cont_ID = ?";
		$stmt  = $db->prepare($query);   $stmt->execute([$id]);

		// 🔹 NEW DATA
		$newData = getRow($db, 'countries', 'Cont_ID', $id);

		// ✅ LOG
		logActivity($db, ['event' => 'RESTORE', 'table' => 'countries', 'old' => $oldData, 'new' => $newData, 'emp_id' => $user['Emp_ID'], 'action' => "Restored country ID: $id"]);
		respond(["status" => "success", "message" => "Country restored successfully"]);
	}
?>