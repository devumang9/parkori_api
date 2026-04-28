<?php
	require_once("../config.php");
	require_once("../middleware/logger.php");

	header("Content-Type: application/json");

	$db = getDB();

	// 📥 Input
	$data = json_decode(file_get_contents("php://input"), true);

	$username = trim($data['username'] ?? '');
	$otp      = strtoupper(trim($data['otp'] ?? ''));

	// 🔍 Validate
	if (empty($username) || empty($otp)) {
		logActivity($db, ['event' => 'ERROR', 'table' => 'PASSWORD_RESET', 'emp_id' => null, 'action' => "OTP verify failed: missing username/otp"]);
		respond(["status" => "error", "message" => "Username and OTP are required"]);
	}

	// 🔍 Find user
	$stmt = $db->prepare("SELECT Emp_ID FROM employee WHERE EmpEmail = ? OR EmpCnct1 = ? LIMIT 1");
	$stmt->execute([$username, $username]);   $user = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$user) {
		logActivity($db, ['event' => 'ERROR', 'table' => 'PASSWORD_RESET', 'emp_id' => null, 'action' => "OTP verify failed: user not found ($username)"]);
		respond(["status" => "error", "message" => "User not found"]);
	}

	$emp_id = $user['Emp_ID'];

	// 🔍 Get latest OTP
	$stmt = $db->prepare("SELECT PrToken, PrExpires FROM password_resets WHERE Emp_ID = ? ORDER BY Pr_ID DESC LIMIT 1");
	$stmt->execute([$emp_id]);   $otpData = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$otpData) {
		logActivity($db, ['event' => 'ERROR', 'table' => 'PASSWORD_RESET', 'emp_id' => $emp_id, 'action' => "OTP verify failed: no OTP found"]);
		respond(["status" => "error", "message" => "OTP not found or expired"]);
	}

	// ⏰ Check expiry
	if (strtotime($otpData['PrExpires']) < time()) {
		// delete expired OTP
		$db->prepare("DELETE FROM password_resets WHERE Emp_ID = ?")->execute([$emp_id]);

		logActivity($db, ['event' => 'ERROR', 'table' => 'PASSWORD_RESET', 'emp_id' => $emp_id, 'action' => "OTP expired"]);
		respond(["status" => "error", "message" => "OTP expired"]);
	}

	// 🔐 Verify OTP
	if (!password_verify($otp, $otpData['PrToken'])) {
		logActivity($db, ['event' => 'ERROR', 'table' => 'PASSWORD_RESET', 'emp_id' => $emp_id, 'action' => "Invalid OTP attempt"]);
		respond(["status" => "error", "message" => "Invalid OTP"]);
	}

	// ✅ OTP correct → delete it
	$db->prepare("DELETE FROM password_resets WHERE Emp_ID = ?")->execute([$emp_id]);

	// ✅ LOG
	logActivity($db, ['event' => 'OTP_VERIFY', 'table' => 'PASSWORD_RESET', 'emp_id' => $emp_id, 'action' => "OTP verified successfully"]);
	respond(["status" => "success", "message" => "OTP verified successfully"]);
?>