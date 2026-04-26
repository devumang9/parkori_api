<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");
	require_once("../middleware/permission.php");

	$user = authenticate();
	$db = getDB();

	// 🔐 Page permission
	requirePermission($db, $user['Role_ID']);

	$isSuperAdmin = ($user['Role_ID'] == 1);

	$query = "SELECT Grp_ID, GrpName, GrpAlign, GrpAlignID, GrpDelete FROM menugroup";

	if (!$isSuperAdmin) { $query .= " WHERE GrpDelete = 'N'"; }

	$query .= " ORDER BY GrpAlignID ASC";

	$stmt = $db->prepare($query);   $stmt->execute();   $data = $stmt->fetchAll();

	// ✅ LOG FETCH
	logActivity($db, ['event' => 'FETCH', 'table' => 'menugroup', 'emp_id'=> $user['Emp_ID'], 'action'=> 'Fetched menu group list']);

	respond(["status" => "success", "data" => $data]);
?>