<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");

	$user = authenticate();
	$db = getDB();

	$isSuperAdmin = ($user['Role_ID'] == 1);

	$query = "SELECT p.*, g.GrpName FROM menupages p LEFT JOIN menugroup g ON g.Grp_ID = p.Grp_ID";

	if (!$isSuperAdmin) { $query .= " WHERE p.PageDelete = 'N'"; }

	$query .= " ORDER BY g.Grp_ID, p.PageAlignID ASC";

	$stmt = $db->prepare($query);   $stmt->execute();

	// ✅ LOG FETCH
	logActivity($db, ['event' => 'FETCH', 'table' => 'menupages', 'emp_id'=> $user['Emp_ID'], 'action'=> 'Fetched menu pages list']);

	respond(["status" => "success", "data" => $stmt->fetchAll()]);
?>