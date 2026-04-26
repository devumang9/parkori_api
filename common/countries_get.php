<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");
	require_once("../middleware/permission.php");

	$user = authenticate();
	$db = getDB();

	// 🔐 Page permission
	requirePermission($db, $user['Role_ID']);

	$query = "SELECT Cont_ID, ContName, ContPhoneCode, ContEmojiU, ContNative, ContDelete FROM countries ORDER BY ContName ASC";
	$stmt = $db->prepare($query);   $stmt->execute();   $data = $stmt->fetchAll();

	// ✅ LOG FETCH
	logActivity($db, ['event' => 'FETCH', 'table' => 'countries', 'emp_id'=> $user['Emp_ID'], 'action'=> 'Fetched countries list']);
	respond(["status" => "success", "data" => $data]);
?>