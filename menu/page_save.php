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

	$id = $data['id'] ?? null;
	
	try {
		$db->beginTransaction();

		if ($id) {
			// 🔹 Old Data
			$oldData = getRow($db, 'menupages', 'Page_ID', $id);

			$stmt = $db->prepare("UPDATE menupages SET Grp_ID = ?, PageName = ?, PageAlignID = ?, PagePath = ?, PageFileName = ?, PageAlign = ?, PageIcon = ?, PageAccess = ? WHERE Page_ID = ?");
			$stmt->execute([$data['grp_id'], $data['name'], $data['alignID'], $data['path'], $data['file'], $data['align'], $data['icon'], $data['access'], $id]);

			// 🔹 New Data
			$newData = getRow($db, 'menupages', 'Page_ID', $id);

			// ✅ LOG UPDATE
			logActivity($db, ['event' => 'UPDATE', 'table' => 'menupages', 'old' => $oldData, 'new' => $newData, 'emp_id' => $user['Emp_ID'], 'action' => "Updated Page ID: $id"]);

			$msg = "Updated successfully";
		} else {
			$stmt = $db->prepare("INSERT INTO menupages (Grp_ID, PageName, PageAlignID, PagePath, PageFileName, PageAlign, PageIcon, PageAccess) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

			$stmt->execute([$data['grp_id'], $data['name'], $data['alignID'], $data['path'], $data['file'], $data['align'], $data['icon'], $data['access']]);

			// 🔹 New Data
			$newId = $db->lastInsertId();
			$newData = getRow($db, 'menupages', 'Page_ID', $newId);

			// ✅ LOG INSERT
			logActivity($db, ['event' => 'INSERT', 'table' => 'menupages', 'new' => $newData, 'emp_id'=> $user['Emp_ID'], 'action'=> "Created Page ID: $newId"]);

			$msg = "Added successfully";
		}

		$db->commit();
		respond(["status" => "success", "message" => $msg]);
		
	} catch (Exception $e) {
		$db->rollBack();
		respond(["status" => "error", "message" => "Something went wrong"]);
	}
?>