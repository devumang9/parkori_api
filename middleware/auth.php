<?php
	require_once("../config.php");

	function getBearerToken() {
		$headers = getallheaders();

		if (!isset($headers['Authorization'])) return null;
		if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) { return $matches[1]; }
		return null;
	}

	function authenticate() {
		$db = getDB();
		$token = getBearerToken();

		if (!$token) { respond(["status" => "error", "message" => "Token missing"]); }

		$stmt = $db->prepare("SELECT e.*, et.* FROM employee_tokens et JOIN employee e ON e.Emp_ID = et.Emp_ID WHERE et.ET_AccessToken = ? AND et.ET_ExpiryTime > NOW() AND e.EmpStatus = 'Enable' AND e.EmpDelete = 'N' LIMIT 1");
		$stmt->execute([$token]);   $user = $stmt->fetch();

		if (!$user) { respond(["status" => "expired", "message" => "Session expired"]); }

		// Update last activity
		$db->prepare("UPDATE employee_tokens SET ET_UpdatedAt = NOW() WHERE ET_ID = ?")->execute([$user['ET_ID']]);
		return $user;
	}
?>