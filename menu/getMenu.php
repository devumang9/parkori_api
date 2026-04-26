<?php
	require_once("../config.php");
	require_once("../middleware/auth.php");

	$user = authenticate();
	$role_id = $user['Role_ID'];

	$db = getDB();

	/* Groups */
	$groups = $db->query("SELECT Grp_ID, GrpName, GrpIcon FROM menugroup WHERE GrpDelete = 'N' ORDER BY GrpAlignID ASC")->fetchAll();

	/* Pages with rights */
	$stmt = $db->prepare("SELECT p.Page_ID, p.Grp_ID, p.PageName, p.PagePath, p.PageFileName, p.PageIcon, dr.DefView FROM menupages p LEFT JOIN defaultrights dr ON dr.Page_ID = p.Page_ID AND dr.Role_ID = ? AND dr.DefDelete = 'N' WHERE p.PageDelete = 'N' ORDER BY p.PageAlignID ASC");
	$stmt->execute([$role_id]);   $pages = $stmt->fetchAll();

	/* Build menu */
	$menu = [];
	foreach ($groups as $group) {
		$groupPages = [];
		foreach ($pages as $page) {
			if ($page['Grp_ID'] == $group['Grp_ID']) {
				if ($page['DefView'] !== 'Yes') continue;
				$groupPages[] = [ "name" => $page['PageName'], "path" => $page['PagePath'] . $page['PageFileName'], "icon" => $page['PageIcon']];
			}
		}

		if (!empty($groupPages)) { $menu[] = ["group_name" => $group['GrpName'], "pages" => $groupPages]; }
	}

	respond(["status" => "success", "menu" => $menu]);
?>