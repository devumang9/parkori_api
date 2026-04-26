<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");
	require_once("../middleware/permission.php");

	$user = authenticate();
	$db = getDB();

	// 🔐 Page permission
	requirePermission($db, $user['Role_ID']);

	$query = "SELECT Ag_ID, AgName, AgAddress, c.City_ID, CityName, s.State_ID, StateName, co.Cont_ID, ContName, AgPinCode, AgCnctCode1, AgCnct1, AgWtsapEnable1, AgCnctCode2, AgCnct2, AgWtsapEnable2, AgEmail, AgLogo, AgStatus, AgValidFrom, AgValidTo, AgDelete, AgRegDate FROM agency a LEFT JOIN cities c ON c.City_ID = a.City_ID LEFT JOIN states s ON s.State_ID = c.State_ID LEFT JOIN countries co ON co.Cont_ID = s.Cont_ID ORDER BY Ag_ID DESC";
	$stmt = $db->prepare($query);   $stmt->execute();   $data = $stmt->fetchAll();

	// ✅ LOG FETCH
	logActivity($db, ['event' => 'FETCH', 'table' => 'agency', 'emp_id'=> $user['Emp_ID'], 'action'=> 'Fetched agency list']);
	respond(["status" => "success", "data" => $data]);
?>