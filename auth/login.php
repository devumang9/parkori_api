<?php
	require_once("../config.php");
	require_once("../middleware/logger.php");

	// Read input
	$data = json_decode(file_get_contents("php://input"), true);

	$username = trim($data['username'] ?? '');
	$password = trim($data['password'] ?? '');

	if (!$username || !$password) { respond(["status" => "error", "message" => "Username and password are required"]); }

	$db = getDB();

	// 🔍 Fetch all required data
	$stmt = $db->prepare("SELECT e.Ag_ID, AgName, AgStatus, AgValidFrom, AgValidTo, AgLogo, AgDelete, Emp_ID, EmpFirstName, EmpMiddleName, EmpLastName, EmpGender, EmpCnctCode1, EmpCnct1, EmpWtsapEnable1, EmpCnctCode2, EmpCnct2, EmpWtsapEnable2, EmpEmail, EmpEnPass, EmpStatus, e.Role_ID, RoleName, EmpImage, EmpPassChngDate, EmpDelete, EmpRegDate FROM employee e LEFT JOIN agency a ON a.Ag_ID = e.Ag_ID LEFT JOIN roles r ON r.Role_ID = e.Role_ID WHERE (EmpEmail = ? OR EmpCnct1 = ?) LIMIT 1");
	$stmt->execute([$username, $username]);   $user = $stmt->fetch();

	// ❌ User not found
	if (!$user) {
		logActivity($db, ['event' => 'LOGIN_FAILED', 'table' => 'employee', 'action'=> "Login failed - user not found: $username"]);
		respond(["status" => "error", "message" => "User not found"]);
	}

	// ❌ Password check
	if (!password_verify($password, $user['EmpEnPass'])) {
		logActivity($db, ['event' => 'LOGIN_FAILED', 'table' => 'employee', 'emp_id'=> $user['Emp_ID'] ?? null, 'action'=> "Login failed - wrong password for Emp_ID: {$user['Emp_ID']}"]);
		respond(["status" => "error", "message" => "Invalid credentials"]);
	}

	// ❌ If not superadmin
	if ($user['Role_ID'] !== 1) {
		// ❌ Employee validations
		if ($user['EmpDelete'] === 'Y') {
			logActivity($db, ['event' => 'LOGIN_FAILED', 'table' => 'employee', 'emp_id'=> $user['Emp_ID'], 'action'=> "Login failed - employee account has been deleted"]);
			respond(["status" => "error", "message" => "Your account has been deleted. Please contact admin."]);
		}
		if ($user['EmpStatus'] !== 'Enable') {
			logActivity($db, ['event' => 'LOGIN_FAILED', 'table' => 'employee', 'emp_id'=> $user['Emp_ID'], 'action'=> "Login failed - employee account is disabled"]);
			respond(["status" => "error", "message" => "Your account is disabled. Please contact admin."]);
		}

		// ❌ Agency validations
		if ($user['AgDelete'] === 'Y') {
			logActivity($db, ['event' => 'LOGIN_FAILED', 'table' => 'employee', 'emp_id'=> $user['Emp_ID'], 'action'=> "Login failed - agency is no longer active"]);
			respond(["status" => "error", "message" => "Your agency is no longer active."]);
		}
		if ($user['AgStatus'] !== 'Active') {
			logActivity($db, ['event' => 'LOGIN_FAILED', 'table' => 'employee', 'emp_id'=> $user['Emp_ID'], 'action'=> "Login failed - agency is disabled"]);
			respond(["status" => "error", "message" => "Your agency is disabled. Please contact admin."]);
		}

		// ❌ Agency expiry check
		if (!empty($user['AgValidTo'])) {
			if ($user['AgValidTo'] < $today) {
				logActivity($db, ['event' => 'LOGIN_FAILED', 'table' => 'employee', 'emp_id'=> $user['Emp_ID'], 'action'=> "Login failed - subscription has expired"]);
				respond(["status" => "error", "message" => "Your agency subscription has expired. Please renew your plan."]);
			}
		}
	}


	// 🔐 Generate tokens
	$accessToken  = bin2hex(random_bytes(16));
	$refreshToken = bin2hex(random_bytes(32));

	// 💾 Store session
	$db->prepare("INSERT INTO employee_tokens (Emp_ID, ET_AccessToken, ET_RefreshToken, ET_ExpiryTime, ET_CreatedAt) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY), NOW())")->execute([$user['Emp_ID'], $accessToken, $refreshToken]);

	// 🧠 Full name
	$fullName = trim($user['EmpFirstName'] . ' ' . $user['EmpMiddleName'] . ' ' . $user['EmpLastName']);

	// ✅ LOG SUCCESS LOGIN
	logActivity($db, ['event' => 'LOGIN', 'table' => 'employee', 'emp_id'=> $user['Emp_ID'], 'action'=> "User logged in successfully"]);

	// 🚀 FINAL RESPONSE
	respond([
		"status" => "success",
		"access_token" => $accessToken,
		"refresh_token" => $refreshToken,
		"session" => [
			// 👤 Employee
			"emp_id" => $user['Emp_ID'],
			"ag_id" => $user['Ag_ID'],
			"first_name" => $user['EmpFirstName'],
			"middle_name" => $user['EmpMiddleName'],
			"last_name" => $user['EmpLastName'],
			"name" => $fullName,
			"gender" => $user['EmpGender'],
			"contact_code1" => $user['EmpCnctCode1'],
			"contact1" => $user['EmpCnct1'],
			"whatsapp1" => $user['EmpWtsapEnable1'],
			"contact_code2" => $user['EmpCnctCode2'],
			"contact2" => $user['EmpCnct2'],
			"whatsapp2" => $user['EmpWtsapEnable2'],
			"email" => $user['EmpEmail'],
			"image" => $user['EmpImage'],
			"password_changed_at" => $user['EmpPassChngDate'],
			"registered_at" => $user['EmpRegDate'],
			"emp_status" => $user['EmpStatus'],
			"emp_deleted" => $user['EmpDelete'],

			// Roles
			"role_id" => $user['Role_ID'],
			"role_name" => $user['RoleName'],

			// 🏢 Agency (ALL fields)
			"agency_name" => $user['AgName'],
			"agency_status" => $user['AgStatus'],
			"agency_valid_from" => $user['AgValidFrom'],
			"agency_valid_to" => $user['AgValidTo'],
			"agency_logo" => $user['AgLogo'],
			"agency_deleted" => $user['AgDelete']
		]
	]);
?>