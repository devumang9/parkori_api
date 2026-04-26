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

	$id   = $data['id'] ?? null;
	$name = trim($data['name'] ?? '');
	$desc = trim($data['desc'] ?? '');

	if (!$name) { respond(["status" => "error", "message" => "Role Name is required"]); }

	if ($id) {
		// 🔹 Old Data
		$oldData = getRow($db, 'roles', 'Role_ID', $id);

		// 🔹 UPDATE
		$query = "UPDATE roles SET RoleName = ?, RoleDesc = ? WHERE Role_ID = ?";
		$stmt  = $db->prepare($query);  $stmt->execute([$name, $desc, $id]);

		// 🔹 New Data
		$newData = getRow($db, 'roles', 'Role_ID', $id);

		// ✅ LOG UPDATE
		logActivity($db, ['event' => 'UPDATE', 'table' => 'roles', 'old' => $oldData, 'new' => $newData, 'emp_id' => $user['Emp_ID'], 'action' => "Updated role ID: $id"]);

		respond(["status" => "success", "message" => "Role updated successfully"]);
	} else {
		// 🔹 INSERT
		$query = "INSERT INTO roles (RoleName, RoleDesc) VALUES (?, ?)";
		$stmt  = $db->prepare($query);  $stmt->execute([$name, $desc]);

		// 🔹 New Data
		$newId = $db->lastInsertId();
		$newData = getRow($db, 'roles', 'Role_ID', $newId);

		// ✅ LOG INSERT
		logActivity($db, ['event' => 'INSERT', 'table' => 'roles', 'new' => $newData, 'emp_id'=> $user['Emp_ID'], 'action'=> "Created role ID: $newId"]);

		respond(["status" => "success", "message" => "Role added successfully"]);
	}
?>