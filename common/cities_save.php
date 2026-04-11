<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");

	$user = authenticate();
	$db = getDB();

	$data = json_decode(file_get_contents("php://input"), true);

	$id    = $data['id'] ?? null;
	$state = $data['state'] ?? null;
	$city  = trim($data['city'] ?? '');

	if (!$state || !$city) { respond(["status"=>"error","message"=>"All fields required"]); }

	if ($id) {
		$old = getRow($db, 'cities', 'City_ID', $id);

		$stmt = $db->prepare("UPDATE cities SET State_ID = ?, CityName = ? WHERE City_ID = ?");
		$stmt->execute([$state, $city, $id]);

		$new = getRow($db, 'cities', 'City_ID', $id);

		logActivity($db, ['event' => 'UPDATE', 'table' => 'cities', 'old' => $old, 'new' => $new, 'emp_id' => $user['Emp_ID'], 'action' => "Updated city ID: $id"]);
		respond(["status"=>"success","message"=>"City updated"]);
	} else {
		$stmt = $db->prepare("INSERT INTO cities (State_ID, CityName) VALUES (?, ?)");
		$stmt->execute([$state, $city]);

		$newId = $db->lastInsertId();
		$new = getRow($db, 'cities', 'City_ID', $newId);

		logActivity($db, ['event' => 'INSERT', 'table' => 'cities', 'new' => $new, 'emp_id' => $user['Emp_ID'], 'action' => "Created city ID: $newId"]);
		respond(["status"=>"success","message"=>"City added"]);
	}
?>