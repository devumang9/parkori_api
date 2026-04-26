<?php
	/*
		For eg.
		1. If file name doesn’t follow pattern use:
		requirePermission($db, $user['role_id'], 'countries', 'add');

		2. Else if follows:
		requirePermission($db, $user['Role_ID']);
	*/

	/* 🔍 Detect module from filename */
	function getModuleFromFile() {
		$file = basename($_SERVER['PHP_SELF']);
		$file = str_replace('.php', '', $file);
		$parts = explode('_', $file);
		return strtolower($parts[0]);
	}

	/* 🔍 Detect action from filename */
	function getActionFromFile() {
		$file = basename($_SERVER['PHP_SELF']);
		$file = str_replace('.php', '', $file);
		$parts = explode('_', $file);

		// Default = view
		$action = $parts[1] ?? 'view';

		// Normalize action names
		switch ($action) {
			case 'get':
			case 'list':
				return 'view';

			case 'add':
			case 'create':
				return 'add';

			case 'update':
			case 'edit':
				return 'edit';

			case 'delete':
			case 'remove':
				return 'delete';

			case 'restore':
			case 'undo':
				return 'restore';

			default:
				return 'view';
		}
	}

	/* 🔍 Get Page_ID using PageModuleKey */
	function getPageIdByModule($db, $module) {
		$stmt = $db->prepare("SELECT Page_ID FROM menupages WHERE PageModuleKey = ? AND PageDelete = 'N' LIMIT 1");
		$stmt->execute([$module]);   $page = $stmt->fetch(PDO::FETCH_ASSOC);

		return $page ? $page['Page_ID'] : null;
	}

	/* 🔐 Check permission */
	function checkPermission($db, $role_id, $page_id, $action) {

		$stmt = $db->prepare("SELECT DefView, DefAdd, DefEdit, DefDel, DefRestore FROM defaultrights WHERE Role_ID = ? AND Page_ID = ? AND DefDelete = 'N' LIMIT 1");
		$stmt->execute([$role_id, $page_id]);   $perm = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$perm) return false;

		switch ($action) {
			case 'view':    return $perm['DefView'] === 'Yes';
			case 'add':     return $perm['DefAdd'] === 'Yes';
			case 'edit':    return $perm['DefEdit'] === 'Yes';
			case 'delete':  return $perm['DefDel'] === 'Yes';
			case 'restore': return $perm['DefRestore'] === 'Yes';
			default:        return false;
		}
	}

	/* 🚀 MAIN FUNCTION */
	function requirePermission($db, $role_id, $module = null, $action = null) {

		// 🔥 Auto detect module
		if (!$module) { $module = getModuleFromFile(); }

		// 🔥 Auto detect action
		if (!$action) { $action = getActionFromFile(); }

		// 🔍 Get page_id
		$page_id = getPageIdByModule($db, $module);

		if (!$page_id || !checkPermission($db, $role_id, $page_id, $action)) {
			respond(["status" => "error", "message" => "Access Denied", "module" => $module, "action" => $action]);
		}
	}
?>