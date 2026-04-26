<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");
	require_once("../middleware/permission.php");

	$user = authenticate();
	$db = getDB();

	// 🔐 Page permission
	requirePermission($db, $user['Role_ID']);

	// ✅ JSON INPUT
	$input = json_decode(file_get_contents("php://input"), true) ?? [];

	$draw   = $input['draw'] ?? 1;
	$start  = $input['start'] ?? 0;
	$length = $input['length'] ?? 10;
	$search = trim($input['search'] ?? '');

	// 🔹 BASE
	$base = "FROM states s LEFT JOIN countries c ON s.Cont_ID = c.Cont_ID";
	
	$search = strtolower(trim($input['search'] ?? ''));

	$where = " WHERE 1=1";
	$params = [];

	if ($search !== '') {
		$where .= " AND (LOWER(c.ContName) LIKE ? OR LOWER(s.StateName) LIKE ? OR LOWER(s.StateCode) LIKE ?)";

		$params[] = "%$search%";
		$params[] = "%$search%";
		$params[] = "%$search%";
	}

	// 🔹 TOTAL
	$total = $db->query("SELECT COUNT(*) FROM states")->fetchColumn();

	// 🔹 FILTERED
	$stmt = $db->prepare("SELECT COUNT(*) $base $where");
	$stmt->execute($params);  $filtered = $stmt->fetchColumn();

	// 🔹 DATA
	$query = "SELECT s.State_ID, s.Cont_ID, c.ContName, s.StateName, s.StateCode, s.StateDelete $base $where ORDER BY c.ContName, s.StateName LIMIT ?, ?";

	// ⚠️ IMPORTANT: merge params with limit
	$dataParams = $params;   $dataParams[] = (int)$start;   $dataParams[] = (int)$length;

	$stmt = $db->prepare($query);   $stmt->execute($dataParams);
	$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// ✅ LOG
	$safeSearch = str_replace("'", "", $search);

	logActivity($db, ['event' => 'FETCH', 'table' => 'states', 'emp_id' => $user['Emp_ID'], 'action' => "Fetched states | draw:$draw start:$start length:$length search:$safeSearch"]);

	// ✅ RESPONSE
	echo json_encode(["draw" => (int)$draw, "recordsTotal" => (int)$total, "recordsFiltered" => (int)$filtered, "data" => $data]);
	exit;
?>