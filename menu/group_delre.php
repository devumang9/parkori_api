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
	$action = $data['action'] ?? ''; // delete / restore

	if (!$id || !in_array($action, ['delete', 'restore'])) { respond(["status" => "error", "message" => "Invalid request"]); }

	// 🔹 Old Data
	$oldData = getRow($db, 'menugroup', 'Grp_ID', $id);

	try {
		// 🔥 START TRANSACTION
		$db->beginTransaction();

		// 🔍 Check record exists
		$stmt = $db->prepare("SELECT Grp_ID, GrpDelete FROM menugroup WHERE Grp_ID = ?");
		$stmt->execute([$id]);   $record = $stmt->fetch();

		if (!$record) { throw new Exception("Record not found"); }

		// =========================
		// 🔴 DELETE LOGIC
		// =========================
		if ($action === 'delete') {
			if ($record['GrpDelete'] === 'Y') { throw new Exception("Already deleted"); }

			// Check dependency
			$stmt = $db->prepare("SELECT COUNT(*) as total FROM menupages WHERE Grp_ID = ? AND PageDelete = 'N'");
			$stmt->execute([$id]);   $count = $stmt->fetch()['total'];

			if ($count > 0) { throw new Exception("Cannot delete: Pages exist under this group"); }

			$stmt = $db->prepare("UPDATE menugroup SET GrpDelete = 'Y' WHERE Grp_ID = ?");
			$stmt->execute([$id]);

			// 🔹 New Data
			$newData = getRow($db, 'menugroup', 'Grp_ID', $id);

			// ✅ LOG DELETE
			logActivity($db, ['event' => 'DELETE', 'table' => 'menugroup', 'old' => $oldData, 'new' => $newData, 'emp_id'=> $user['Emp_ID'], 'action'=> "Deleted group ID: $id"]);

			$message = "Deleted successfully";
		}

		// =========================
		// 🟢 RESTORE LOGIC
		// =========================
		if ($action === 'restore') {
			if ($user['Role_ID'] != 1) { throw new Exception("Unauthorized"); }
			if ($record['GrpDelete'] === 'N') { throw new Exception("Already active"); }

			$stmt = $db->prepare("UPDATE menugroup SET GrpDelete = 'N' WHERE Grp_ID = ?");
			$stmt->execute([$id]);

			// 🔹 New Data
			$newData = getRow($db, 'menugroup', 'Grp_ID', $id);

			// ✅ LOG RESTORE
			logActivity($db, ['event' => 'RESTORE', 'table' => 'menugroup', 'old' => $oldData, 'new' => $newData, 'emp_id'=> $user['Emp_ID'], 'action'=> "Restored group ID: $id"]);

			$message = "Restored successfully";
		}

		// 🔥 COMMIT
		$db->commit();

		respond(["status" => "success", "message" => $message]);
	} catch (Exception $e) {
		// 🔥 ROLLBACK
		$db->rollBack();
		respond(["status" => "error", "message" => $e->getMessage()]);
	}
?>