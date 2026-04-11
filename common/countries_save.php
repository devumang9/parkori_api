<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");

	$user = authenticate();
	$db = getDB();

	$data = json_decode(file_get_contents("php://input"), true);

	$id     = $data['id'] ?? null;
	$cont   = trim($data['cont'] ?? '');
	$code   = trim($data['code'] ?? '');
	$flag   = trim($data['flag'] ?? '');
	$native = trim($data['native'] ?? '');

	// ✅ VALIDATION
	if (!$cont)   respond(["status" => "error", "message" => "Country Name is required"]);
	if (!$code)   respond(["status" => "error", "message" => "Phone Code is required"]);
	if (!$flag)   respond(["status" => "error", "message" => "Flag is required"]);
	if (!$native) respond(["status" => "error", "message" => "Native is required"]);

	if ($id) {
		// 🔹 OLD DATA
		$oldData = getRow($db, 'countries', 'Cont_ID', $id);

		// 🔹 UPDATE
		$query = "UPDATE countries SET ContName = ?, ContPhoneCode = ?, ContEmojiU = ?, ContNative = ? WHERE Cont_ID = ?";
		$stmt  = $db->prepare($query);   $stmt->execute([$cont, $code, $flag, $native, $id]);

		// 🔹 NEW DATA
		$newData = getRow($db, 'countries', 'Cont_ID', $id);

		// ✅ LOG
		logActivity($db, ['event' => 'UPDATE', 'table' => 'countries', 'old' => $oldData, 'new' => $newData, 'emp_id' => $user['Emp_ID'], 'action' => "Updated country ID: $id"]);
		respond(["status" => "success", "message" => "Country updated successfully"]);
	} else {
		// 🔹 INSERT
		$query = "INSERT INTO countries (ContName, ContPhoneCode, ContEmojiU, ContNative) VALUES (?, ?, ?, ?)";
		$stmt  = $db->prepare($query);   $stmt->execute([$cont, $code, $flag, $native]);

		// 🔹 NEW DATA
		$newId = $db->lastInsertId();
		$newData = getRow($db, 'countries', 'Cont_ID', $newId);

		// ✅ LOG
		logActivity($db, ['event' => 'INSERT', 'table' => 'countries', 'new' => $newData, 'emp_id' => $user['Emp_ID'], 'action' => "Created country ID: $newId"]);
		respond(["status" => "success", "message" => "Country added successfully"]);
	}
?>