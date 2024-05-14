<?php
function processPOSTRequest($type,&$responseData) {
	if ($type == 1) {
		if (!isset($_POST['perso']) || !isset($_POST['name']) || !isset($_POST['pp'])) return -1;
		if ($_POST['perso'] == '' || $_POST['name'] == '' || $_POST['pp'] == '') return -1;
		if ($_POST['perso'] != intval($_POST['perso']).'' || intval($_POST['perso']) < 0) return -1;
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/misc.php';
		if (!isImgURL($_POST['pp']) && !file_exists($_SERVER['DOCUMENT_ROOT'].'/medias/pps/'.$_POST['pp'])) return -1;
	}
	require $_SERVER['DOCUMENT_ROOT'].'/../lib/db.php';
	$db = new DB();
	if ($type == 0) {
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/os.php';
		$responseData = [$db->get("SELECT `id`, `name`, `pp` FROM `persos` WHERE `group`=? OR `group`=0 ORDER BY `last-used` DESC",[$_SESSION['user']['persos-group']]),getpps(['force']),$_SESSION['user']['comments-admin']];
	}
	if ($type == 1) {
		if ($_POST['perso'] != 0 && !$db->isEmpty("SELECT 1 FROM `persos` WHERE `id`=? AND (`group`=0 OR `group`=?)",[$_POST['perso'],$_SESSION['user']['persos-group']])) $db->req("UPDATE `persos` SET `name`=?, `pp`=?, `last-used`=NOW() WHERE `id`=?",[$_POST['name'],$_POST['pp'],$_POST['perso']]);
		else {
			$certified = 0;
			if ($_SESSION['user']['comments-admin'] && isset($_POST['certified'])) $certified = 1;
			$db->req("INSERT INTO `persos`(`name`, `pp`, `certified`, `group`, `last-used`) VALUES (?, ?, $certified, ?, NOW())",[$_POST['name'],$_POST['pp'],$_SESSION['user']['persos-group']]);
		}
	}
	$db->close();
	return 0;
}
?>