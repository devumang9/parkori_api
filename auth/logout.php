<?php
	require_once("../config.php");
	require_once("../middleware/logger.php");

	// Get token
	$headers = getallheaders();
	$auth = $headers['Authorization'] ?? '';

	$token = str_replace("Bearer ", "", $auth);

	$db = getDB();

	if (!$token) {
		logActivity($db, ['event' => 'LOGOUT', 'table' => 'employee', 'action'=> 'Logout attempt without token']);
		respond(["status" => "error", "message" => "Token missing"]);
	}

	// 🔍 Get user before deleting token
	$stmt = $db->prepare("SELECT Emp_ID FROM employee_tokens WHERE ET_AccessToken = ?");
	$stmt->execute([$token]);   $session = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$session) {
		logActivity($db, ['event' => 'LOGOUT', 'table' => 'employee', 'action'=> 'Logout attempt with invalid token']);
		respond(["status" => "error", "message" => "Invalid token"]);
	}

	// 🧾 Store Emp_ID for logging
	$empId = $session['Emp_ID'];

	// 🗑 Delete session
	$stmt = $db->prepare("DELETE FROM employee_tokens WHERE ET_AccessToken = ?");
	$stmt->execute([$token]);

	// ✅ LOG LOGOUT
	logActivity($db, ['event' => 'LOGOUT', 'table' => 'employee', 'emp_id'=> $empId, 'action'=> "User logged out successfully"]);

	respond(["status" => "success", "message" => "Logged out successfully"]);
?>