<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");

	$user = authenticate();
	$data = json_decode(file_get_contents("php://input"), true);

	$id = $data['id'] ?? null;
	$action = $data['action'] ?? '';

	if (!$id) respond(["status" => "error", "message" => "Invalid ID"]);

	$db = getDB();

	// 🔹 Old Data
	$oldData = getRow($db, 'menupages', 'Page_ID', $id);

	try {
		$db->beginTransaction();

		$stmt = $db->prepare("SELECT PageDelete FROM menupages WHERE Page_ID = ?");
		$stmt->execute([$id]);   $row = $stmt->fetch();

		if (!$row) throw new Exception("Not found");

		if ($action === 'delete') {
			$db->prepare("UPDATE menupages SET PageDelete = 'Y' WHERE Page_ID = ?")->execute([$id]);
			
			// 🔹 New Data
			$newData = getRow($db, 'menupages', 'Page_ID', $id);

			// ✅ LOG DELETE
			logActivity($db, ['event' => 'DELETE', 'table' => 'menupages', 'old' => $oldData, 'new' => $newData, 'emp_id'=> $user['Emp_ID'], 'action'=> "Deleted Page ID: $id"]);

			$msg = "Deleted successfully";
		}

		if ($action === 'restore') {
			if ($user['Role_ID'] != 1) throw new Exception("Unauthorized");

			$db->prepare("UPDATE menupages SET PageDelete = 'N' WHERE Page_ID = ?")->execute([$id]);
			
			// 🔹 New Data
			$newData = getRow($db, 'menupages', 'Page_ID', $id);

			// ✅ LOG DELETE
			logActivity($db, ['event' => 'RESTORE', 'table' => 'menupages', 'old' => $oldData, 'new' => $newData, 'emp_id'=> $user['Emp_ID'], 'action'=> "Deleted Page ID: $id"]);

			$msg = "Restored successfully";
		}

		$db->commit();
		respond(["status" => "success", "message" => $msg]);

	} catch (Exception $e) {
		$db->rollBack();
		respond(["status" => "error", "message" => $e->getMessage()]);
	}
?>