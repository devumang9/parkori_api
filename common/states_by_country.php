<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");

	$user = authenticate();
	$db = getDB();

	$data = json_decode(file_get_contents("php://input"), true);

	$cont_id = $data['cont_id'] ?? null;

	if (!$cont_id) { respond(["status" => "error", "message" => "Country ID is required"]); }

	// 🔹 FETCH STATES
	$stmt = $db->prepare("SELECT State_ID, StateName FROM states WHERE Cont_ID = ? AND StateDelete = 'N' ORDER BY StateName ASC");
	$stmt->execute([$cont_id]);   $states = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// ✅ LOG
	logActivity($db, ['event' => 'FETCH', 'table' => 'states', 'emp_id' => $user['Emp_ID'], 'action' => "Fetched states for country ID: $cont_id"]);
	respond(["status" => "success", "data" => $states]);
?>