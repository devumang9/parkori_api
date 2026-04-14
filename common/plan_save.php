<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");

	$user = authenticate();
	$db = getDB();

	$data = json_decode(file_get_contents("php://input"), true);

	// 🔹 GET DATA
	$id     = $data['id'] ?? null;
	$type   = $data['type'] ?? '';
	$plan   = $data['plan'] ?? '';
	$desc   = $data['desc'] ?? '';
	$pm     = $data['priceprmnth'] ?? null;
	$py     = $data['pricepryr'] ?? null;
	$from   = $data['from'] ?? null;
	$to     = $data['to'] ?? null;
	$status = $data['status'] ?? '';

	$features = $data['features'] ?? [];
	$limits   = $data['limits'] ?? [];

	// ✅ VALIDATION
	if (!$plan) respond(["status"=>"error","message"=>"Plan name required"]);

	$db->beginTransaction();

	try {
		if ($id) {
			// 🔹 OLD DATA (PLAN)
			$oldPlan = getRow($db, 'subscribe_plan', 'Plan_ID', $id);

			// 🔹 OLD FEATURES
			$oldFeatures = $db->query("SELECT FeatureName FROM plan_features WHERE Plan_ID=$id AND FeatureDelete='N'")->fetchAll(PDO::FETCH_ASSOC);

			// 🔹 OLD LIMITS
			$oldLimits = $db->query("SELECT LimitKey, LimitValue FROM plan_limits WHERE Plan_ID=$id AND LimitDelete='N'")->fetchAll(PDO::FETCH_ASSOC);

			$oldData = ['plan' => $oldPlan, 'features' => $oldFeatures, 'limits' => $oldLimits];

			// 🔹 UPDATE PLAN
			$stmt = $db->prepare("UPDATE subscribe_plan SET PlanType = ?, PlanName = ?, PlanDesc = ?, PlanPricePerMnth = ?, PlanPricePerYr = ?, PlanFrom = ?, PlanTo = ?, PlanStatus = ? WHERE Plan_ID = ?");
			$stmt->execute([$type, $plan, $desc, $pm, $py, $from, $to, $status, $id]);

			// 🔹 DELETE OLD FEATURES & LIMITS
			$db->prepare("UPDATE plan_features SET FeatureDelete = 'Y' WHERE Plan_ID = ?")->execute([$id]);
			$db->prepare("UPDATE plan_limits SET LimitDelete = 'Y' WHERE Plan_ID = ?")->execute([$id]);
		} else {
			// 🔹 INSERT PLAN
			$stmt = $db->prepare("INSERT INTO subscribe_plan (PlanType, PlanName, PlanDesc, PlanPricePerMnth, PlanPricePerYr, PlanFrom, PlanTo, PlanStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
			$stmt->execute([$type, $plan, $desc, $pm, $py, $from, $to, $status]);

			$id = $db->lastInsertId();
			$oldData = null;
		}

		// 🔹 INSERT FEATURES
		foreach ($features as $f) { if (!empty($f['name'])) { $db->prepare("INSERT INTO plan_features (Plan_ID, FeatureName) VALUES (?, ?)")->execute([$id, $f['name']]); } }

		// 🔹 INSERT LIMITS
		foreach ($limits as $l) { if (!empty($l['key'])) { $db->prepare("INSERT INTO plan_limits (Plan_ID, LimitKey, LimitValue) VALUES (?, ?, ?)")->execute([$id, $l['key'], $l['value'] ?? 0]); } }

		// 🔹 NEW DATA
		$newPlan = getRow($db, 'subscribe_plan', 'Plan_ID', $id);

		$newFeatures = $db->query("SELECT FeatureName FROM plan_features WHERE Plan_ID=$id AND FeatureDelete='N'")->fetchAll(PDO::FETCH_ASSOC);

		$newLimits = $db->query("SELECT LimitKey, LimitValue FROM plan_limits WHERE Plan_ID=$id AND LimitDelete='N'")->fetchAll(PDO::FETCH_ASSOC);

		$newData = ['plan' => $newPlan, 'features' => $newFeatures, 'limits' => $newLimits];

		$db->commit();

		// ✅ LOG
		logActivity($db, ['event' => $oldData ? 'UPDATE' : 'INSERT', 'table' => 'subscribe_plan', 'old' => $oldData, 'new' => $newData, 'emp_id' => $user['Emp_ID'], 'action' => ($oldData ? "Updated" : "Created") . " plan ID: $id"]);
		respond(["status"=>"success","message"=>"Plan saved successfully"]);
	} catch(Exception $e) { $db->rollBack(); respond(["status"=>"error","message"=>$e->getMessage()]); }
?>