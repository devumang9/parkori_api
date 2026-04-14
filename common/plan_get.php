<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");

	$user = authenticate();
	$db = getDB();

	$stmt = $db->prepare("SELECT p.* FROM subscribe_plan p ORDER BY p.Plan_ID DESC");
	$stmt->execute();   $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// 🔹 ATTACH FEATURES & LIMITS
	foreach ($plans as &$p) {
		// FEATURES
		$stmtF = $db->prepare("SELECT Feature_ID, FeatureName FROM plan_features WHERE Plan_ID = ? AND FeatureDelete = 'N'");
		$stmtF->execute([$p['Plan_ID']]);   $features = $stmtF->fetchAll(PDO::FETCH_ASSOC);

		// LIMITS
		$stmtL = $db->prepare("SELECT LimitKey, LimitValue FROM plan_limits WHERE Plan_ID = ? AND LimitDelete = 'N'");
		$stmtL->execute([$p['Plan_ID']]);   $limits = $stmtL->fetchAll(PDO::FETCH_ASSOC);

		$p['features'] = json_encode($features);
		$p['limits']   = json_encode($limits);
	}

	// ✅ LOG
	logActivity($db, ['event' => 'FETCH', 'table' => 'subscribe_plan', 'emp_id' => $user['Emp_ID'], 'action' => 'Fetched plans with features & limits']);
	respond(["status"=>"success","data"=>$plans]);
?>