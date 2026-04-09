<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");

	// 🔐 Validate user
	$user = authenticate();

	$db = getDB();

	// 🔹 Fetch all groups (not deleted)
	$groupStmt = $db->prepare("SELECT Grp_ID, GrpName, GrpIcon, GrpAlign, GrpAlignID FROM menugroup WHERE GrpDelete = 'N' ORDER BY GrpAlignID ASC");
	$groupStmt->execute();   $groups = $groupStmt->fetchAll();

	// 🔹 Fetch all pages (not deleted)
	$pageStmt = $db->prepare("SELECT Page_ID, Grp_ID, PageName, PagePath, PageFileName, PageIcon, PageAlignID, PageAlign, PageAccess FROM menupages WHERE PageDelete = 'N' ORDER BY PageAlignID ASC");
	$pageStmt->execute();   $pages = $pageStmt->fetchAll();

	// 🧠 Group pages under groups
	$menu = [];

	foreach ($groups as $group) {
		$groupPages = [];
		foreach ($pages as $page) {
			if ($page['Grp_ID'] == $group['Grp_ID']) {
				$groupPages[] = [
					"page_id"   => $page['Page_ID'],
					"name"      => $page['PageName'],
					"path"      => $page['PagePath'] . $page['PageFileName'],
					"icon"      => $page['PageIcon'],
					"align"     => $page['PageAlign'],
					"access"    => $page['PageAccess']
				];
			}
		}

		// Only include group if it has pages
		if (!empty($groupPages)) {
			$menu[] = [
				"group_id"   => $group['Grp_ID'],
				"group_name" => $group['GrpName'],
				"icon"       => $group['GrpIcon'],
				"align"      => $group['GrpAlign'],
				"pages"      => $groupPages
			];
		}
	}

	// 🚀 Response
	respond(["status" => "success", "menu" => $menu]);
?>