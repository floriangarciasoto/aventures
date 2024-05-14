<?php
session_start();
if (!isset($_SESSION['user'])) {
	header('Location: /');
	exit;
}
function isNotOwner(&$db,$persoID) {
	return $db->isEmpty("SELECT 1 FROM `persos` WHERE `id`=? AND (`group`=0 OR `group`=?)",[$persoID,$_SESSION['user']['persos-group']]);
}
function addBan(&$db,$by,$for) {
	if ($db->isEmpty("SELECT 1 FROM `comments-bans` WHERE `banisher`=? AND `banned`=?",[$by,$for])) $db->req("INSERT INTO `comments-bans`(`banisher`, `banned`) VALUES (?, ?)",[$by,$for]);
}
function processPOSTRequest($type,&$responseData) {
	if (in_array($type,[1,2,4,5,6,7])) {
		if (!isset($_POST['selectorid']) || $_POST['selectorid'] == '') return -1;
		if (!isset($_POST['page']) || $_POST['page'] != intval($_POST['page']).'' || intval($_POST['page']) < 0) return -1;
		if (!isset($_POST['content']) || $_POST['content'] != intval($_POST['content']).'' || intval($_POST['content']) < 0) return -1;
		if (!isset($_POST['subcontent']) || $_POST['subcontent'] != intval($_POST['subcontent']).'' || intval($_POST['subcontent']) < 0) return -1;
		$selectorID = $_POST['selectorid'];
		$page = $_POST['page'];
		$content = $_POST['content'];
		$subContent = $_POST['subcontent'];
	}
	if (in_array($type,[2,4,5,6,7])) {
		if (isset($_POST['nbpersos']) && $_POST['nbpersos'] != intval($_POST['nbpersos']).'') return -1;
		if (isset($_POST['nbbans']) && $_POST['nbbans'] != intval($_POST['nbbans']).'') return -1;
		if (isset($_POST['nbconvs'])  && (!$_SESSION['user']['comments-admin'] || $_POST['nbconvs'] != intval($_POST['nbconvs']).'')) return -1;
		$updated = '1970-01-01 00:00:00';
		if (isset($_POST['updated'])) $updated = $_POST['updated'];
		if (isset($_POST['notloadedcommentsnums']) && $_POST['notloadedcommentsnums'] != '') $notLoadedCommentsNums = explode('-',$_POST['notloadedcommentsnums']);
	}
	if ($type == 4) {
		if (!isset($_POST['author']) || !isset($_POST['comment'])) return -1;
		if ($_POST['author'] == '' || $_POST['comment'] == '') return -1;
		if ($_POST['author'] != intval($_POST['author']).'') return -1;
		if ($_POST['author'] < 1) return -1;
		if (isset($_POST['num']) && ($_POST['num'] != intval($_POST['num']).'' || intval($_POST['num']) < 0)) return -1;
		if (isset($_POST['resp']) && ($_POST['resp'] != intval($_POST['resp']).'' || intval($_POST['resp']) < 0)) return -1;
	}
	if ($type == 6) {
		if (!isset($_POST['by']) || $_POST['by'] != intval($_POST['by']).'') return -1;
		if (!isset($_POST['for']) || $_POST['for'] != intval($_POST['for']).'') return -1;
	}
	if ($type == 7) {
		if (!$_SESSION['user']['comments-admin']) return -1;
		if (!isset($_POST['managetype']) || $_POST['managetype'] != intval($_POST['managetype']).'') return -1;
		if (!isset($_POST['len']) || $_POST['len'] != intval($_POST['len']).'') return -1;
		$manageType = $_POST['managetype'];
		$len = $_POST['len'];
		if ($len == 0) return -1;
		if ($manageType == 3) {
			for ($i=1; $i <= $len; $i++) if (!isset($_POST['for'.$i]) || $_POST['for'.$i] != intval($_POST['for'.$i]).'') return -1;
		}
		else {
			for ($i=1; $i <= $len; $i++) {
				if (!isset($_POST['comment'.$i.'num']) || $_POST['comment'.$i.'num'] != intval($_POST['comment'.$i.'num']).'') return -1;
				if (!isset($_POST['comment'.$i.'resp']) || $_POST['comment'.$i.'resp'] != intval($_POST['comment'.$i.'resp']).'') return -1;
			}
			if ($manageType == 2) {
				if (!isset($_POST['destinationpage']) || $_POST['destinationpage'] != intval($_POST['destinationpage']).'') return -1;
				$destinationPage = $_POST['destinationpage'];
				if ($destinationPage != 5 && $destinationPage != 6) return -1;
				if (isset($_POST['conv']) && $_POST['conv'] != intval($_POST['conv']).'') return -1;
			}
		}
	}
	require $_SERVER['DOCUMENT_ROOT'].'/../lib/db.php';
	$db = new DB();
	if ($type == 0) {
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/os.php';
		$responseData = [
			[
				getFilesListFromFolder('/medias/comments/custom-emojis/'),
				getFilesListFromFolder('/medias/comments/sounds/'),
				getFilesListFromFolder('/medias/comments/videos/')
			],
			$_SESSION['user']['persos-group'],
			$_SESSION['user']['comments-admin'],
			$db->getDirect("SELECT * FROM `persos`"),
			$db->getDirect("SELECT `banisher`, `banned` FROM `comments-bans`")
		];
	}
	if ($type == 1) {
		$responseData = [$selectorID,$db->get("SELECT `num` FROM `comments` WHERE `page`=? AND `content`=? AND `sub-content`=? GROUP BY `num` ORDER BY MAX(`updated`) DESC",[$page,$content,$subContent])];
	}
	if ($type == 2) {
		$responseData = [$selectorID];
		foreach ($notLoadedCommentsNums as $num) {
			if (!$db->isEmpty("SELECT 1 FROM `comments` WHERE `page`=? AND `content`=? AND `sub-content`=? AND `num`=?",[$page,$content,$subContent,$num])) {
				array_push($responseData,$db->get("SELECT `num`, `resp`, `author`, `comment`, `updated`, `hidden` FROM `comments` WHERE `page`=? AND `content`=? AND `sub-content`=? AND `num`=? ORDER BY `resp`",[$page,$content,$subContent,$num]));
				break;
			}
		}
		array_push($responseData,$db->getValue("SELECT COUNT(`id`) FROM `comments` WHERE `page`=? AND `content`=? AND `sub-content`=?",[$page,$content,$subContent]));
	}
	if ($type == 4) {
		if (isNotOwner($db,$_POST['author']) || !$db->isEmpty("SELECT 1 FROM `comments-bans` WHERE `banisher`=0 AND `banned`=?",[$_POST['author']]) && $page != 6) return -1;
		$lastCommentID = $db->getValueDirect("SELECT MAX(`id`) FROM `comments`");
		if (!$db->isEmpty("SELECT 1 FROM `comments` WHERE `id`=? AND `author`=? AND `comment`=?",[$lastCommentID,$_POST['author'],$_POST['comment']])) return -1;
		if (isset($_POST['num'])) {
			if (isset($_POST['resp'])) {
				$db->req("UPDATE `comments` SET `author`=?, `comment`=?, `updated`=NOW() WHERE `page`=? AND `content`=? AND `sub-content`=? AND `num`=? AND `resp`=?",[$_POST['author'],$_POST['comment'],$page,$content,$subContent,$_POST['num'],$_POST['resp']]);
			}
			else {
				$resp = $db->getValue("SELECT MAX(`resp`)+1 FROM `comments` WHERE `page`=? AND `content`=? AND `sub-content`=? AND `num`=?",[$page,$content,$subContent,$_POST['num']]);
				$db->req("INSERT INTO `comments`(`page`, `content`, `sub-content`, `num`, `resp`, `author`, `comment`, `posted`, `updated`, `hidden`) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 0)",[$page,$content,$subContent,$_POST['num'],$resp,$_POST['author'],$_POST['comment']]);
				if (gethostname() == 'homeServer') {
					$commentFullIndex = $page.'-'.$content.'-'.$subContent;
					if (!in_array($commentFullIndex,$_SESSION['user']['commented'])) {
						$pages = ['youtube','epreuves','leaderboards','foot','discussions','messages','presse'];
						shell_exec('curl -d "Commented: '.$_SESSION['user']['name'].' ('.$pages[$page].'/'.$content.'-'.$subContent.')" ntfy.sh/aventures_ddns_net_x8m3SifWEu_commented');
						array_push($_SESSION['user']['commented'],$commentFullIndex);
					}
				}
			}
		}
		else {
			// if ($db->isEmpty("SELECT `id` FROM `force-yt-videos` WHERE `id`=?",[$page,$content,$subContent])) return -1;
			$num = $db->getValue("SELECT MAX(`num`)+1 FROM `comments` WHERE `page`=? AND `content`=? AND `sub-content`=?",[$page,$content,$subContent]);
			if (is_null($num)) $num = 1;
			$db->req("INSERT INTO `comments`(`page`, `content`, `sub-content`, `num`, `resp`, `author`, `comment`, `posted`, `updated`, `hidden`) VALUES (?, ?, ?, ?, 1, ?, ?, NOW(), NOW(), 0)",[$page,$content,$subContent,$num,$_POST['author'],$_POST['comment']]);
		}
		$db->req("UPDATE `persos` SET `last-used`=NOW() WHERE `id`=?",[$_POST['author']]);
	}
	if ($type == 6) {
		if (isNotOwner($db,$_POST['by'])) return -1;
		// if ($_POST['for'] == $db->getValue("SELECT `ac` FROM `force-yt-videos` WHERE `id`=?",[$page,$content,$subContent])) return -1;
		addBan($db,$_POST['by'],$_POST['for']);
	}
	if ($type == 7) {
		switch ($manageType) {
			case 0:
				for ($i=1; $i <= $len; $i++) $db->req("UPDATE `comments` SET `hidden`=1, `updated`=NOW() WHERE `page`=? AND `content`=? AND `sub-content`=? AND `num`=? AND `resp`=?",[$page,$content,$subContent,$_POST['comment'.$i.'num'],$_POST['comment'.$i.'resp']]);
				break;
			case 1:
				for ($i=1; $i <= $len; $i++) $db->req("DELETE FROM `comments` WHERE `page`=? AND `content`=? AND `sub-content`=? AND `num`=? AND `resp`=?",[$page,$content,$subContent,$_POST['comment'.$i.'num'],$_POST['comment'.$i.'resp']]);
				break;
			case 2:
				if (isset($_POST['conv']) && !$db->isEmpty("SELECT 1 FROM `comments` WHERE `page`=? AND `num`=?",[$destinationPage,$_POST['conv']])) {
					$conv = $_POST['conv'];
					$respOffset = $db->getValue("SELECT MAX(`resp`) FROM `comments` WHERE `page`=? AND `num`=?",[$destinationPage,$conv]);
				}
				else {
					$conv = $db->getValue("SELECT MAX(`num`)+1 FROM `comments` WHERE `page`=?",[$destinationPage]);
					if (is_null($conv)) $conv = 1;
					$respOffset = 0;
				}
				if ($destinationPage == 5) for ($i=1; $i <= $len; $i++) $db->req("UPDATE `comments` SET `page`=?, `content`=0, `sub-content`=0, `num`=?, `resp`=?, `updated`=NOW() WHERE `page`=? AND `content`=? AND `sub-content`=? AND `num`=? AND `resp`=?",[$destinationPage,$conv,$i+$respOffset,$page,$content,$subContent,$_POST['comment'.$i.'num'],$_POST['comment'.$i.'resp']]);
				else for ($i=1; $i <= $len; $i++) $db->req("UPDATE `comments` SET `page`=?, `content`=0, `sub-content`=0, `num`=?, `resp`=?, `updated`=NOW(), `hidden`=0 WHERE `page`=? AND `content`=? AND `sub-content`=? AND `num`=? AND `resp`=?",[$destinationPage,$conv,$i+$respOffset,$page,$content,$subContent,$_POST['comment'.$i.'num'],$_POST['comment'.$i.'resp']]);
				break;
			case 3:
				for ($i=1; $i <= $len; $i++) addBan($db,0,$_POST['for'.$i]);
				break;
		}
	}
	if (in_array($type,[4,5,6,7])) {
		$loadedCommentsSQLCondition = "";
		if (isset($notLoadedCommentsNums)) $loadedCommentsSQLCondition = " AND `num` NOT IN (".implode(', ',$notLoadedCommentsNums).")";
		$responseData = [$selectorID,$db->get("SELECT `num`, `resp` FROM `comments` WHERE `page`=? AND `content`=? AND `sub-content`=?".$loadedCommentsSQLCondition,[$page,$content,$subContent]),$db->get("SELECT `num`, `resp`, `author`, `comment`, `updated`, `hidden` FROM `comments` WHERE `page`=? AND `content`=? AND `sub-content`=? AND `updated` > ?".$loadedCommentsSQLCondition." ORDER BY `num`, `resp`",[$page,$content,$subContent,$updated]),$db->getValue("SELECT COUNT(`id`) FROM `comments` WHERE `page`=? AND `content`=? AND `sub-content`=?",[$page,$content,$subContent]),false,false,false];
		if ($db->getValueDirectNoNull("SELECT COUNT(`id`) FROM `persos`") > $_POST['nbpersos']) $responseData[4] = $db->getDirect("SELECT * FROM `persos`");
		if ($db->getValueDirectNoNull("SELECT COUNT(`id`) FROM `comments-bans`") > $_POST['nbbans']) $responseData[5] = $db->getDirect("SELECT `banisher`, `banned` FROM `comments-bans`");
		if ($_SESSION['user']['comments-admin'] && $db->getValueDirectNoNull("SELECT COUNT(`id`) FROM `comments` WHERE (`page`=5 OR `page`=6) AND `resp`=1") != $_POST['nbconvs']) $responseData[6] = $db->getDirect("SELECT `page`, `num`, `author`, SUBSTRING(`comment`,1,30) AS `comment` FROM `comments` WHERE (`page`=5 OR `page`=6) AND `resp`=1 ORDER BY `updated` DESC");
	}
	$db->close();
	return 0;
}
if (isset($_POST['type']) && in_array($_POST['type'],[0,1,2,3,4,5,6,7])) {
	$responseData = '';
	if (processPOSTRequest($_POST['type'],$responseData) == 0) echo json_encode([$_POST['type'],$responseData]);
} ?>
