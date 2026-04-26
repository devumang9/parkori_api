<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");
	require_once("../middleware/permission.php");

	$user = authenticate();
	$db = getDB();

	// 🔐 Page permission
	requirePermission($db, $user['Role_ID']);

	$input = json_decode(file_get_contents("php://input"), true);

	$draw   = $input['draw'] ?? 1;
	$start  = $input['start'] ?? 0;
	$length = $input['length'] ?? 10;
	$search = trim($input['search'] ?? '');

	$where = " WHERE 1=1 ";
	$params = [];

	if ($search !== '') {  $where .= " AND (c.CityName LIKE ? OR s.StateName LIKE ? OR co.ContName LIKE ?)";  $params[] = "%$search%";  $params[] = "%$search%";  $params[] = "%$search%";  }

	// 🔹 TOTAL
	$total = $db->query("SELECT COUNT(*) FROM cities")->fetchColumn();

	// 🔹 FILTERED
	$stmt = $db->prepare("SELECT COUNT(*) FROM cities c LEFT JOIN states s ON c.State_ID = s.State_ID LEFT JOIN countries co ON s.Cont_ID = co.Cont_ID $where");
	$stmt->execute($params);   $filtered = $stmt->fetchColumn();

	// 🔹 DATA
	$query = "SELECT c.City_ID, c.CityName, c.CityDelete, s.State_ID, s.StateName, co.Cont_ID, co.ContName FROM cities c LEFT JOIN states s ON c.State_ID = s.State_ID LEFT JOIN countries co ON s.Cont_ID = co.Cont_ID $where LIMIT ?, ?";

	$params[] = (int)$start;
	$params[] = (int)$length;

	$stmt = $db->prepare($query);   $stmt->execute($params);
	$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// ✅ LOG (OPTIMIZED)
	$safeSearch = str_replace("'", "", $search);

	logActivity($db, ['event' => 'FETCH', 'table' => 'cities', 'emp_id' => $user['Emp_ID'], 'action' => "Fetched cities | draw:$draw start:$start length:$length search:$safeSearch"]);
	echo json_encode(["draw" => (int)$draw, "recordsTotal" => (int)$total, "recordsFiltered" => (int)$filtered, "data" => $data]);
	exit;
?>