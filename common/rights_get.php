<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");

	$user = authenticate();
	$db = getDB();

	$data = json_decode(file_get_contents("php://input"), true);
	$role_id = $data['role_id'] ?? null;

	if ($role_id) {
		// 🔹 ROLE BASED (FOR MODAL)
		$stmt = $db->prepare("SELECT p.Page_ID, p.PageName, COALESCE(d.DefView, 'No') AS DefView, COALESCE(d.DefAdd, 'No') AS DefAdd, COALESCE(d.DefEdit, 'No') AS DefEdit, COALESCE(d.DefDel, 'No') AS DefDel FROM menupages p LEFT JOIN defaultrights d ON d.Page_ID = p.Page_ID AND d.Role_ID = ? AND d.DefDelete = 'N' WHERE p.PageDelete = 'N' ORDER BY p.PageName ASC");
		$stmt->execute([$role_id]);   $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} else {
		// 🔹 TABLE VIEW
		$stmt = $db->prepare("SELECT d.Def_ID, r.RoleName, p.PageName, d.DefView, d.DefAdd, d.DefEdit, d.DefDel FROM defaultrights d JOIN roles r ON r.Role_ID = d.Role_ID JOIN menupages p ON p.Page_ID = d.Page_ID WHERE d.DefDelete = 'N' ORDER BY d.Def_ID DESC");
		$stmt->execute();   $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// ✅ LOG
	logActivity($db, ['event' => 'FETCH', 'table' => 'defaultrights', 'emp_id' => $user['Emp_ID'], 'action' => 'Fetched default rights']);
	respond(["status"=>"success","data"=>$result]);
?>