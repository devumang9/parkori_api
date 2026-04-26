<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");
	require_once("../middleware/logger.php");
	require_once("../middleware/permission.php");

	$user = authenticate();
	$db = getDB();

	// 🔐 Page permission
	requirePermission($db, $user['Role_ID']);

	$data = json_decode(file_get_contents("php://input"), true);

	$role_id = $data['role_id'] ?? null;
	$rights  = $data['rights'] ?? [];

	if (!$role_id) { respond(["status" => "error", "message" => "Role required"]); }

	$db->beginTransaction();

	try {
		// 🔹 GET OLD DATA (include restore)
		$stmt = $db->prepare("SELECT Page_ID, DefView, DefAdd, DefEdit, DefDel, DefRestore FROM defaultrights WHERE Role_ID = ? AND DefDelete = 'N'");
		$stmt->execute([$role_id]);   $oldData = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$oldMap = [];
		foreach ($oldData as $o) { $oldMap[$o['Page_ID']] = $o; }

		// 🔹 PROCESS EACH RIGHT
		foreach ($rights as $r) {
			$pageId = $r['Page_ID'];
			$new = ['DefView' => $r['view'], 'DefAdd' => $r['add'], 'DefEdit' => $r['edit'], 'DefDel' => $r['del'], 'DefRestore' => $r['restore']];

			// 🔥 IF EXISTS
			if (isset($oldMap[$pageId])) {
				$old = $oldMap[$pageId];

				// 🔥 UPDATE ONLY IF CHANGED
				if ($old['DefView'] != $new['DefView'] || $old['DefAdd'] != $new['DefAdd'] || $old['DefEdit'] != $new['DefEdit'] || $old['DefDel'] != $new['DefDel'] || $old['DefRestore'] != $new['DefRestore']) {
					$db->prepare("UPDATE defaultrights SET DefView = ?, DefAdd = ?, DefEdit = ?, DefDel = ?, DefRestore = ? WHERE Role_ID = ? AND Page_ID = ? AND DefDelete = 'N'")->execute([$new['DefView'], $new['DefAdd'], $new['DefEdit'], $new['DefDel'], $new['DefRestore'], $role_id, $pageId]);
				}
			} else {
				// 🔥 INSERT NEW
				$db->prepare("INSERT INTO defaultrights (Role_ID, Page_ID, DefView, DefAdd, DefEdit, DefDel, DefRestore) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([$role_id, $pageId, $new['DefView'], $new['DefAdd'], $new['DefEdit'], $new['DefDel'], $new['DefRestore']]);
			}
		}

		$db->commit();

		// ✅ LOG
		logActivity($db, ['event' => 'UPDATE', 'table' => 'defaultrights', 'emp_id' => $user['Emp_ID'], 'action' => "Updated rights for role ID: $role_id"]);
		respond(["status" => "success"]);
	} catch (Exception $e) {
		$db->rollBack();
		respond(["status" => "error", "message" => $e->getMessage()]);
	}
?>