<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");

	$user = authenticate();

	$db = getDB();

	$isSuperAdmin = ($user['Role_ID'] == 1);

	$query = "SELECT Role_ID, RoleName, RoleDesc, RoleDelete FROM roles";

	if (!$isSuperAdmin) {  $query .= " WHERE RoleDelete = 'N' AND Role_ID != 1";  }

	$query .= " ORDER BY Role_ID ASC";

	$stmt = $db->prepare($query);   $stmt->execute();   $data = $stmt->fetchAll();

	// ✅ LOG FETCH
	logActivity($db, ['event' => 'FETCH', 'table' => 'roles', 'emp_id'=> $user['Emp_ID'], 'action'=> 'Fetched role list']);

	respond(["status" => "success", "data" => $data]);
?>