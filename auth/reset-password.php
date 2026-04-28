<?php
	require_once("../config.php");
	require_once("../middleware/logger.php");

	header("Content-Type: application/json");

	$db = getDB();

	// 📥 Input
	$data = json_decode(file_get_contents("php://input"), true);

	$username = trim($data['username'] ?? '');
	$password = trim($data['password'] ?? '');

	// =========================
	// 🔍 VALIDATION
	// =========================
	if (empty($username) || empty($password)) {
		logActivity($db, ['event' => 'ERROR', 'table' => 'employee', 'emp_id' => null, 'action' => "Password reset failed: empty input"]);
		respond(["status" => "error", "message" => "All fields are required"]);
	}

	if (strlen($password) < 6) {
		logActivity($db, ['event' => 'ERROR', 'table' => 'employee', 'emp_id' => null, 'action' => "Password reset failed: weak password"]);
		respond(["status" => "error", "message" => "Password must be at least 6 characters"]);
	}

	// =========================
	// 🔍 FIND USER
	// =========================
	$stmt = $db->prepare("SELECT Emp_ID, EmpEnPass, EmpOldEnPass, EmpOrPass FROM employee WHERE EmpEmail = ? OR EmpCnct1 = ? LIMIT 1");
	$stmt->execute([$username, $username]);   $user = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$user) {
		logActivity($db, ['event' => 'ERROR', 'table' => 'employee', 'emp_id' => null, 'action' => "Password reset failed: user not found ($username)"]);
		respond(["status" => "error", "message" => "User not found"]);
	}

	$emp_id = $user['Emp_ID'];

	// =========================
	// 🔒 PREVENT PASSWORD REUSE
	// =========================
	if (!empty($user['EmpEnPass']) && password_verify($password, $user['EmpEnPass'])) {

		logActivity($db, [
			'event'  => 'ERROR',
			'table'  => 'employee',
			'emp_id' => $emp_id,
			'action' => "Password reuse attempt (current password)"
		]);

		respond(["status" => "error", "message" => "New password cannot be same as current password"]);
	}

	if (!empty($user['EmpOldEnPass']) && password_verify($password, $user['EmpOldEnPass'])) {

		logActivity($db, [
			'event'  => 'ERROR',
			'table'  => 'employee',
			'emp_id' => $emp_id,
			'action' => "Password reuse attempt (old password)"
		]);

		respond(["status" => "error", "message" => "New password cannot be same as previous password"]);
	}

	// =========================
	// 🔐 HASH
	// =========================
	$newHash = password_hash($password, PASSWORD_BCRYPT);

	try {

		// =========================
		// 🔄 UPDATE PASSWORD
		// =========================
		$stmt = $db->prepare("
			UPDATE employee SET
				EmpOldEnPass = EmpEnPass,
				EmpOldOrPass = EmpOrPass,
				EmpEnPass = ?,
				EmpOrPass = ?,
				EmpPassChngDate = NOW()
			WHERE Emp_ID = ?
		");
		$stmt->execute([$newHash, $password, $emp_id]);

		// =========================
		// 🧹 CLEAN OTP
		// =========================
		$db->prepare("DELETE FROM password_resets WHERE Emp_ID = ?")
		->execute([$emp_id]);

		// =========================
		// ✅ SUCCESS LOG
		// =========================
		logActivity($db, [
			'event'  => 'PASSWORD_RESET',
			'table'  => 'employee',
			'emp_id' => $emp_id,
			'action' => "Password reset via forgot password"
		]);

		respond([
			"status" => "success",
			"message" => "Password reset successful"
		]);

	} catch (Exception $e) {

		logActivity($db, [
			'event'  => 'ERROR',
			'table'  => 'employee',
			'emp_id' => $emp_id,
			'action' => "Password reset failed: " . $e->getMessage()
		]);

		respond([
			"status" => "error",
			"message" => "Failed to reset password"
		]);
	}
?>