<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");

	try {
		$user = authenticate();
		$db = getDB();

		$id = $_POST['id'] ?? null;

		// 🔹 REQUIRED VALIDATION
		if (empty($_POST['agency']))  throw new Exception("Agency name is required");
		if (empty($_POST['email']))   throw new Exception("Email is required");
		if (empty($_POST['cnct1']))   throw new Exception("Contact number is required");
		if (empty($_POST['city']))    throw new Exception("City is required");

		$email = $_POST['email'];
		$cnct1 = $_POST['cnct1'];

		// 🔹 DUPLICATE CHECK (SEPARATE)
		if (!empty($id)) {
			// Email check
			$stmt = $db->prepare("SELECT Ag_ID FROM agency WHERE AgEmail = ?");
			$stmt->execute([$email]);
			if ($stmt->rowCount() > 0) { throw new Exception("Email already exists"); }

			// Contact check
			$stmt = $db->prepare("SELECT Ag_ID FROM agency WHERE AgCnct1 = ?");
			$stmt->execute([$cnct1]);
			if ($stmt->rowCount() > 0) { throw new Exception("Contact number already exists"); }
		} else {
			// Email check
			$stmt = $db->prepare("SELECT Ag_ID FROM agency WHERE AgEmail = ? AND Ag_ID != ?");
			$stmt->execute([$email, $id]);
			if ($stmt->rowCount() > 0) { throw new Exception("Email already exists"); }

			// Contact check
			$stmt = $db->prepare("SELECT Ag_ID FROM agency WHERE AgCnct1 = ? AND Ag_ID != ?");
			$stmt->execute([$cnct1, $id]);
			if ($stmt->rowCount() > 0) { throw new Exception("Contact number already exists"); }
		}

		// 🔹 FILE UPLOAD
		$logoName = null;

		if (!empty($_FILES['logo']['name'])) {
			$uploadDir = "../uploads/agency/";
			if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }

			$ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
			$allowed = ['jpg','jpeg','png','webp'];

			if (!in_array($ext, $allowed)) { throw new Exception("Invalid file type"); }

			$logoName = uniqid() . "." . $ext;
			$targetPath = $uploadDir . $logoName;

			if (!move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) { throw new Exception("File upload failed"); }

			// 🔥 DELETE OLD LOGO ON UPDATE
			if ($id) {
				$old = $db->prepare("SELECT AgLogo FROM agency WHERE Ag_ID = ?");
				$old->execute([$id]);   $oldLogo = $old->fetchColumn();

				if ($oldLogo && file_exists($uploadDir . $oldLogo)) { unlink($uploadDir . $oldLogo); }
			}
		}

		// 🔹 INSERT
		if (!empty($id)) {
			$query = "INSERT INTO agency (AgName, AgAddress, City_ID, AgPinCode, AgCnctCode1, AgCnct1, AgWtsapEnable1, AgCnctCode2, AgCnct2, AgWtsapEnable2, AgEmail, AgLogo, AgStatus, AgValidFrom, AgValidTo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

			$stmt = $db->prepare($query);
			$stmt->execute([$_POST['agency'], $_POST['addr'], $_POST['city'], $_POST['pincode'], $_POST['code1'], $_POST['cnct1'], $_POST['whatsapp1'], $_POST['code2'], $_POST['cnct2'], $_POST['whatsapp2'], $email, $logoName, $_POST['status'], $_POST['from'], $_POST['to']]);

			logActivity($db, ['event' => 'INSERT', 'table' => 'agency', 'emp_id'=> $user['Emp_ID'], 'action'=> 'Inserted agency']);
			respond(["status" => "success", "message" => "Agency created successfully"]); exit;
		}

		// 🔹 UPDATE
		else {
			$query = "UPDATE agency SET AgName = ?, AgAddress = ?, City_ID = ?, AgPinCode = ?, AgCnctCode1 = ?, AgCnct1 = ?, AgWtsapEnable1 = ?, AgCnctCode2 = ?, AgCnct2 = ?, AgWtsapEnable2 = ?, AgEmail = ?, AgStatus = ?, AgValidFrom = ?, AgValidTo = ?";

			if ($logoName) { $query .= ", AgLogo = '$logoName'"; }

			$query .= " WHERE Ag_ID = ?";

			$stmt = $db->prepare($query);
			$stmt->execute([$_POST['agency'], $_POST['addr'], $_POST['city'], $_POST['pincode'], $_POST['code1'], $_POST['cnct1'], $_POST['whatsapp1'], $_POST['code2'], $_POST['cnct2'], $_POST['whatsapp2'], $email, $_POST['status'], $_POST['from'], $_POST['to'], $id]);

			logActivity($db, ['event' => 'UPDATE', 'table' => 'agency', 'emp_id'=> $user['Emp_ID'], 'action'=> 'Updated agency ID: ' . $id]);
			respond(["status" => "success", "message" => "Agency updated successfully"]); exit;
		}
	} catch (Exception $e) { respond(["status" => "error", "message" => $e->getMessage()]); }
?>