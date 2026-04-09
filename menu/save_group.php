<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");

	$user = authenticate();

	$data = json_decode(file_get_contents("php://input"), true);

	$id       = $data['id'] ?? null;
	$name     = trim($data['name'] ?? '');
	$align    = trim($data['alignment'] ?? '');
	$alignID  = trim($data['alignID'] ?? '');

	if (!$name || !$align || !$alignID) { respond(["status" => "error", "message" => "All fields are required"]); }

	$db = getDB();

	try {

		// 🔥 START TRANSACTION
		$db->beginTransaction();

		if ($id) {
			// 🔹 Old Data
			$oldData = getRow($db, 'menugroup', 'Grp_ID', $id);

			// 🔹 UPDATE
			$stmt = $db->prepare("UPDATE menugroup SET GrpName = ?, GrpAlign = ?, GrpAlignID = ? WHERE Grp_ID = ?");
			$stmt->execute([$name, $align, $alignID, $id]);

			// 🔹 New Data
			$newData = getRow($db, 'menugroup', 'Grp_ID', $id);

			// ✅ LOG UPDATE
			logActivity($db, ['event' => 'UPDATE', 'table' => 'menugroup', 'old' => $oldData, 'new' => $newData, 'emp_id' => $user['Emp_ID'], 'action' => "Updated group ID: $id"]);

			$message = "Updated successfully";
		} else {
			// 🔹 INSERT
			$stmt = $db->prepare("INSERT INTO menugroup (GrpName, GrpAlign, GrpAlignID) VALUES (?, ?, ?)");
			$stmt->execute([$name, $align, $alignID]);

			// 🔹 New Data
			$newId = $db->lastInsertId();
			$newData = getRow($db, 'menugroup', 'Grp_ID', $newId);

			// ✅ LOG INSERT
			logActivity($db, ['event' => 'INSERT', 'table' => 'menugroup', 'new' => $newData, 'emp_id'=> $user['Emp_ID'], 'action'=> "Created group ID: $newId"]);

			$message = "Added successfully";
		}

		// 🔥 COMMIT
		$db->commit();

		respond(["status" => "success", "message" => $message]);
	} catch (Exception $e) {
		// 🔥 ROLLBACK
		$db->rollBack();
		respond(["status" => "error", "message" => "Something went wrong", "error" => $e->getMessage()]);  // remove in production
	}
?>