<?php
	require_once("../config.php");
	require_once("../middleware/logger.php");

	$data = json_decode(file_get_contents("php://input"), true);
	$refreshToken = $data['refresh_token'] ?? '';

	$db = getDB();

	// ❌ Missing token
	if (!$refreshToken) {
		logActivity($db, ['event' => 'LOGIN_FAILED', 'table' => 'employee', 'action'=> 'Refresh token missing']);
		respond(["status" => "error", "message" => "Refresh token required"]);
	}

	// 🔍 Find session
	$stmt = $db->prepare("SELECT * FROM employee_tokens WHERE ET_RefreshToken = ? LIMIT 1");
	$stmt->execute([$refreshToken]);   $session = $stmt->fetch();

	if (!$session) {
		logActivity($db, ['event' => 'LOGIN_FAILED', 'table' => 'employee', 'action'=> 'Invalid refresh token used']);
		respond(["status" => "error", "message" => "Invalid refresh token"]);
	}

	// 🔐 Generate new access token
	$newAccessToken = bin2hex(random_bytes(16));

	// 🔄 Update token
	$db->prepare("UPDATE employee_tokens SET ET_AccessToken = ?, ET_ExpiryTime = DATE_ADD(NOW(), INTERVAL 1 DAY), ET_UpdatedAt = NOW() WHERE ET_ID = ?")->execute([$newAccessToken, $session['ET_ID']]);

	// ✅ LOG TOKEN REFRESH (treated as LOGIN continuation)
	logActivity($db, ['event' => 'LOGIN', 'table' => 'employee', 'emp_id'=> $session['Emp_ID'], 'action'=> 'Access token refreshed']);

	respond(["status" => "success", "access_token" => $newAccessToken]);
?>