<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");

	$user = authenticate();
	$db = getDB();

	$data = json_decode(file_get_contents("php://input"), true);

	$cont_id  = $data['cont_id'] ?? null;
	$state_id = $data['state_id'] ?? null;

	// 🔹 COUNTRY → STATES
	if ($cont_id) {
		$stmt = $db->prepare("SELECT State_ID, StateName FROM states WHERE Cont_ID = ? AND StateDelete = 'N' ORDER BY StateName ASC");
		$stmt->execute([$cont_id]);   $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

		logActivity($db, ['event' => 'FETCH', 'table' => 'states', 'emp_id' => $user['Emp_ID'], 'action' => "Fetched states for country ID: $cont_id"]);
		respond(["status" => "success", "data" => $result]);
	}

	// 🔹 STATE → CITIES
	else if ($state_id) {
		$stmt = $db->prepare("SELECT City_ID, CityName FROM cities WHERE State_ID = ? AND CityDelete = 'N' ORDER BY CityName ASC");
		$stmt->execute([$state_id]);   $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

		logActivity($db, ['event' => 'FETCH', 'table' => 'cities', 'emp_id' => $user['Emp_ID'], 'action' => "Fetched cities for state ID: $state_id"]);
		respond(["status" => "success", "data" => $result]);
	}

	// 🔴 INVALID
	else { respond(["status" => "error", "message" => "Invalid request"]); }
?>