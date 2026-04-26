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
	$cont   = $data['cont'] ?? null;
	$code   = trim($data['code'] ?? '');
	$name   = trim($data['name'] ?? '');

	if (!$cont) respond(["status" => "error", "message" => "Country is required"]);
	if (!$code) respond(["status" => "error", "message" => "State code is required"]);
	if (!$name) respond(["status" => "error", "message" => "State name is required"]);

	if ($id) {
		$oldData = getRow($db, 'states', 'State_ID', $id);

		$stmt = $db->prepare("UPDATE states SET Cont_ID = ?, StateCode = ?, StateName = ? WHERE State_ID = ?");
		$stmt->execute([$cont, $code, $name, $id]);

		$newData = getRow($db, 'states', 'State_ID', $id);

		logActivity($db, ['event' => 'UPDATE', 'table' => 'states', 'old' => $oldData, 'new' => $newData, 'emp_id' => $user['Emp_ID']]);
		respond(["status" => "success", "message" => "State updated successfully"]);
	} else {
		$stmt = $db->prepare("INSERT INTO states (Cont_ID, StateCode, StateName) VALUES (?, ?, ?)");
		$stmt->execute([$cont, $code, $name]);

		$newId = $db->lastInsertId();

		logActivity($db, ['event' => 'INSERT', 'table' => 'states', 'emp_id' => $user['Emp_ID'], 'action' => "Created state ID: $newId"]);
		respond(["status" => "success", "message" => "State added successfully"]);
	}
?>