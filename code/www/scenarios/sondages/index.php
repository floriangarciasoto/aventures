<?php
session_start();
if (!isset($_SESSION['user'])) {
	header('Location: /?page='.urlencode($_SERVER['REQUEST_URI']));
	exit;
}
if (!$_SESSION['user']['canAccessScenariosOnlyPages']) {
	header('Location: /');
	exit;
}
function setPropValidData(&$propData,$n) {
	$propData['img'] = null;
	if (isset($_POST['prop'.$n.'img'])) {
		$propImg = $_POST['prop'.$n.'img'];
		if ($propImg != '' && $propImg != '0' && file_exists($_SERVER['DOCUMENT_ROOT'].'/medias/pps/'.$propImg)) $propData['img'] = $propImg;
	}
	$propData['active'] = 1;
	if (isset($_POST['prop'.$n.'no'])) $propData['active'] = 0;
}
function isNotSelf(&$db,$sondage) {
	return $db->isEmpty("SELECT 1 FROM `scenarios-sondages` WHERE `id`=? AND `author`=?",[$sondage,$_SESSION['user']['id']]);
}
function getUpdateDecision(&$db,$sondage,$type) {
	if ($type == 1) return 0;
	$lastModified = $db->get("SELECT `last-modified` FROM `scenarios-sondages` WHERE `id`=?",[$sondage]);
	if ($lastModified == null) return 2;
	if ($lastModified[0]['last-modified'] != $_POST['last-modified']) return 0;
	if ($type == 2 && $_POST['sum'] == $db->getValue("SELECT SUM(`votes`) FROM `scenarios-sondages-props` WHERE `sondage`=? AND `active`=1",[$sondage])) return -1;
	$order = array();
	foreach ($db->get("SELECT `num` FROM `scenarios-sondages-props` WHERE `sondage`=? ORDER BY `active` DESC, `votes` DESC, `num`",[$sondage]) as $value) array_push($order,$value['num']);
	if ($_POST['order'] != implode('-',$order)) return 0;
	return 1;
}
function processPOSTRequest($type,&$responseData) {
	if (in_array($type,[1,2,3,4,5,6])) {
		if (!isset($_POST['sondage']) || $_POST['sondage'] != intval($_POST['sondage']).'') return -1;
		$sondage = $_POST['sondage'];
	}
	if (in_array($type,[1,2,3,4])) {
		if (!isset($_POST['propsnumber']) || $_POST['propsnumber'] != intval($_POST['propsnumber']).'' || $_POST['propsnumber'] == 0) return -1;
		$propsNumber = intval($_POST['propsnumber']);
	}
	if ($type == 1) {
		if (!isset($_POST['name']) || $_POST['name'] == '') return -1;
		for ($i = 1; $i <= $propsNumber; $i++) if (!isset($_POST['prop'.$i]) || $_POST['prop'.$i] == '') return -1;
	}
	if (in_array($type,[2,3,4])) {
		for ($i = 1; $i <= $propsNumber; $i++) if (!isset($_POST['prop'.$i.'gdlen']) || $_POST['prop'.$i.'gdlen'] != intval($_POST['prop'.$i.'gdlen']).'') return -1;
	}
	if (in_array($type,[2,3,4,5])) {
		if (!isset($_POST['last-modified']) || $_POST['last-modified'] == '') return -1;
		if (!isset($_POST['order']) || $_POST['order'] == '') return -1;
	}
	if ($type == 2) {
		if (!isset($_POST['sum']) || $_POST['sum'] != intval($_POST['sum']).'') return -1;
	}
	if ($type == 3 || $type == 4) {
		if (!isset($_POST['num']) || $_POST['num'] != intval($_POST['num']).'') return -1;
	}
	if ($type == 3) {
		if (!isset($_POST['votes']) || $_POST['votes'] != intval($_POST['votes']).'') return -1;
	}
	if ($type == 4) {
		if (!isset($_POST['img'])) return -1;
	}
	require $_SERVER['DOCUMENT_ROOT'].'/../lib/db.php';
	$db = new DB();
	if ($type == 0) {
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/os.php';
		$responseData = array($db->getDirect("SELECT `scenarios-sondages`.`id`, `name`, `author`, `username`, `goal`, `reserved`, `last-modified` FROM `scenarios-sondages`, `logins` WHERE `scenarios-sondages`.`author`=`logins`.`id` ORDER BY `last-modified` DESC"),getpps(['scenarios','force']),$_SESSION['user']['name']);
		foreach ($responseData[0] as $key => $value) {
			$responseData[0][$key]['isSelf'] = ($responseData[0][$key]['author'] == $_SESSION['user']['id']);
			$responseData[0][$key]['props'] = $db->get("SELECT `num`, `prop`, `img`, `votes`, `active` FROM `scenarios-sondages-props` WHERE `sondage`=? ORDER BY `active` DESC, `votes` DESC, `num`",[$responseData[0][$key]['id']]);
			foreach ($responseData[0][$key]['props'] as $key2 => $value2) $responseData[0][$key]['props'][$key2]['gdVotes'] = $db->get("SELECT `img` FROM `scenarios-sondages-gdvotes` WHERE `sondage`=? AND `prop`=? ORDER BY `num`",[$responseData[0][$key]['id'],$responseData[0][$key]['props'][$key2]['num']]);
		}
	}
	if ($type == 1) {
		$goal = 0;
		if (isset($_POST['goal']) && $_POST['goal'] == intval($_POST['goal']).'') $goal = $_POST['goal'];
		$reserved = 0;
		if (isset($_POST['reserved'])) $reserved = 1;
		$propData = [];
		if ($sondage == 0) {
			$db->req("INSERT INTO `scenarios-sondages`(`name`, `author`, `goal`, `reserved`, `last-modified`) VALUES (?, ?, ?, ?, NOW())",[$_POST['name'],$_SESSION['user']['id'],$goal,$reserved]);
			$sondageNewId = $db->getLastInstertID();
			for ($i = 1; $i <= $propsNumber; $i++) {
				setPropValidData($propData,$i);
				$db->req("INSERT INTO `scenarios-sondages-props`(`sondage`, `num`, `prop`, `img`, `active`) VALUES (?, ?, ?, ?, ?)",[$sondageNewId,$i,$_POST['prop'.$i],$propData['img'],$propData['active']]);
			}
			$sondage = $sondageNewId;
		}
		else {
			if (isNotSelf($db,$sondage)) return -1;
			$db->req("UPDATE `scenarios-sondages` SET `name`=?, `goal`=?, `reserved`=?, `last-modified`=NOW() WHERE `id`=?",[$_POST['name'],$goal,$reserved,$sondage]);
			$ancPropsNumber = $db->getValue("SELECT COUNT(`id`) FROM `scenarios-sondages-props` WHERE `sondage`=?",[$sondage]);
			$propsToUpdateNumber = $ancPropsNumber;
			if ($propsNumber < $ancPropsNumber) $propsToUpdateNumber = $propsNumber;
			for ($i=1; $i <= $propsToUpdateNumber; $i++) {
				setPropValidData($propData,$i);
				$db->req("UPDATE `scenarios-sondages-props` SET `prop`=?, `img`=?, `active`=? WHERE `sondage`=? AND `num`=?",[$_POST['prop'.$i],$propData['img'],$propData['active'],$sondage,$i]);
			}
			for ($i=$ancPropsNumber+1; $i <= $propsNumber; $i++) {
				setPropValidData($propData,$i);
				$db->req("INSERT INTO `scenarios-sondages-props`(`sondage`, `num`, `prop`, `img`, `active`) VALUES (?, ?, ?, ?, ?)",[$sondage,$i,$_POST['prop'.$i],$propData['img'],$propData['active']]);
			}
			if ($propsNumber < $ancPropsNumber) {
				$propsNumsToDelete = array();
				for ($i=$propsNumber+1; $i <= $ancPropsNumber; $i++) array_push($propsNumsToDelete,$i);
				$db->req("DELETE FROM `scenarios-sondages-props` WHERE `sondage`=? AND `num` IN (".implode(', ',$propsNumsToDelete).")",[$sondage]);
			}
		}
	}
	if ($type == 3) {
		if ($db->getValue("SELECT `reserved` FROM `scenarios-sondages` WHERE `id`=?",[$sondage]) == 1) return -1;
		$db->req("UPDATE `scenarios-sondages-props` SET `votes`=`votes`+? WHERE `sondage`=? AND `num`=? AND `active`=1",[$_POST['votes'],$sondage,$_POST['num']]);
	}
	if ($type == 4) {
		$propImg = $_POST['img'];
		if ($propImg == '' || $propImg == '0' || !file_exists($_SERVER['DOCUMENT_ROOT'].'/medias/pps/'.$propImg)) return -1;
		if (!$db->isEmpty("SELECT 1 FROM `scenarios-sondages-gdvotes` WHERE `sondage`=? AND `img`=?",[$sondage,$propImg])) return -1;
		$db->req("UPDATE `scenarios-sondages-props` SET `votes`=`votes`+1 WHERE `sondage`=? AND `num`=? AND `active`=1",[$sondage,$_POST['num']]);
		$imgNum = $db->getValue("SELECT COUNT(`id`)+1 FROM `scenarios-sondages-gdvotes` WHERE `sondage`=? AND `prop`=?",[$sondage,$_POST['num']]);
		$responseData = $imgNum;
		$db->req("INSERT INTO `scenarios-sondages-gdvotes`(`sondage`, `prop`, `num`, `img`) VALUES (?, ?, ?, ?)",[$sondage,$_POST['num'],$imgNum,$propImg]);
	}
	if ($type == 5) {
		if (isNotSelf($db,$sondage)) return -1;
		$db->req("UPDATE `scenarios-sondages-props` SET `votes`=0 WHERE `sondage`=?",[$sondage]);
		$db->req("DELETE FROM `scenarios-sondages-gdvotes` WHERE `sondage`=?",[$sondage]);
	}
	if (in_array($type,[3,4,5])) {
		$db->req("UPDATE `scenarios-sondages` SET `last-modified`=NOW() WHERE `id`=?",[$sondage]);
	}
	if ($type == 6) {
		if (isNotSelf($db,$sondage)) return -1;
		$db->req("DELETE FROM `scenarios-sondages` WHERE `id`=?",[$sondage]);
		$db->req("DELETE FROM `scenarios-sondages-props` WHERE `sondage`=?",[$sondage]);
		$db->req("DELETE FROM `scenarios-sondages-gdvotes` WHERE `sondage`=?",[$sondage]);
	}
	if (in_array($type,[1,2,3,4,5,6])) {
		switch (getUpdateDecision($db,$sondage,$type)) {
			case 0:
				$responseData = $db->getRow("SELECT `name`, `goal`, `reserved`, `last-modified` FROM `scenarios-sondages` WHERE `id`=?",[$sondage]);
				$responseData['id'] = $sondage;
				$responseData['props'] = $db->get("SELECT `num`, `prop`, `img`, `votes`, `active` FROM `scenarios-sondages-props` WHERE `sondage`=? ORDER BY `active` DESC, `votes` DESC, `num`",[$sondage]);
				foreach ($responseData['props'] as $key => $value) $responseData['props'][$key]['gdVotes'] = $db->get("SELECT `img` FROM `scenarios-sondages-gdvotes` WHERE `sondage`=? AND `prop`=? ORDER BY `num`",[$sondage,$responseData['props'][$key]['num']]);
				break;
			case 1:
				$responseData = ['id'=>$sondage,'props'=>$db->get("SELECT `num`, `votes` FROM `scenarios-sondages-props` WHERE `sondage`=? AND `active`=1 ORDER BY `votes` DESC, `num`",[$sondage])];
				if ($type != 5) foreach ($responseData['props'] as $key => $value) $responseData['props'][$key]['gdVotes'] = $db->get("SELECT `img` FROM `scenarios-sondages-gdvotes` WHERE `sondage`=? AND `prop`=? AND `num`>? ORDER BY `num`",[$sondage,$responseData['props'][$key]['num'],$_POST['prop'.($key+1).'gdlen']]);
				break;
			case 2:
				$responseData = $sondage;
				break;
			default:
				return -1;
		}
	}
	$db->close();
	return 0;
}
if (isset($_POST['type']) && in_array($_POST['type'],[0,1,2,3,4,5,6])) {
	$responseData = '';
	if (processPOSTRequest($_POST['type'],$responseData) == 0) echo json_encode([$_POST['type'],$responseData]);
	exit;
} ?>
<!DOCTYPE html>
<html>
<head>
	<title>Sondages - aventures.ddns.net</title>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../include-js.php'; ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js" integrity="sha512-UXumZrZNiOwnTcZSHLOfcTs0aos2MzBWHXOHOuB0J/R44QB0dwY5JgfbvljXcklVf65Gc4El6RjZ+lnwd2az2g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script src="https://cdn.jsdelivr.net/npm/jquery-hammerjs@2.0.0/jquery.hammer.js" integrity="sha512-B0pgEpIG3Vx6pv+FyJMlq6kDd4Lry0F7ErWBwFLbPN3ZQ4haFmgrFBMG0x1RvFwjDtxcZaeXXukoYuG3xrmXEQ==" crossorigin="anonymous"></script>
	<script type="text/javascript">
	$(document).ready(function(){
		function addSondage(sondage) {
			sondages[sondage['id']] = {
				'props' : [],
				'propsVoting' : [],
				'propsEdit' : [],
				'gdVotes' : [],
				'ranked' : true,
				'prcs' : true,
				'hasImgs' : true,
				'voting' : false,
				'goal' : 0,
				'reserved' : false,
				'last-modified' : 0
			};
			HTMLToAppend += '<div id="sondage-'+sondage['id']+'" class="section-style-1 flx flx-dc beenadded"><div class="sondage-show flx flx-dc flx-ac"><div class="sondage-title table-style-1-title"></div><table class="sondage-show table-style-1"></table><table class="sondage-voting"></table><div class="sondage-specs"><span class="sondage-votetotal"></span><span class="sondage-votetoget100"> / <span></span></span> votes - Proposé par '+sondage['username']+'</div></div>'+editDvHTML+'<div class="sondage-buttons">';
			if (sondage['isSelf']) HTMLToAppend += '<img class="sondage-delete" src="/src/icons/scenarios/sondages/9dceb08e628e5683ca8b0fa57c439c1a.svg"/><img class="sondage-edit" src="/src/icons/scenarios/sondages/39d0ca31697da0067cc6fc38e42d849d.svg"/><img class="sondage-reset" src="/src/icons/scenarios/sondages/982dd707b92a3e10b12773cbe4efe500.svg"/>';
			HTMLToAppend += '<img class="sondage-vote" src="/src/icons/scenarios/sondages/hand-holding-vote-paper-svgrepo-com.svg"/><img class="sondage-update" src="/src/icons/Refresh_font_awesome.svg"/></div></div>';
		}

		function addSondageContainers(id) {
			sondages[id]['$'] = {
				'root' : $('#sondage-'+id),
				'show' : $('#sondage-'+id+' > div.sondage-show'),
				'show-title' : $('#sondage-'+id+' > div.sondage-show > div.sondage-title'),
				'show-table' : $('#sondage-'+id+' > div.sondage-show > table.sondage-show'),
				'show-trs' : [],
				'voting-table' : $('#sondage-'+id+' > div.sondage-show > table.sondage-voting'),
				'voting-trs' : [],
				'show-votetotal' : $('#sondage-'+id+' > div.sondage-show > div.sondage-specs > span.sondage-votetotal'),
				'show-votetoget100' : $('#sondage-'+id+' > div.sondage-show > div.sondage-specs > span.sondage-votetoget100 > span'),
				'edit-title' : $('#sondage-'+id+' > div.sondage-edition > input.sondage-titleedit'),
				'edit-table' : $('#sondage-'+id+' > div.sondage-edition > table'),
				'edit-trs' : [],
				'edit-goal' : $('#sondage-'+id+' > div.sondage-edition > input.sondage-goal'),
				'edit-reserved' : $('#sondage-'+id+' > div.sondage-edition > label.sondage-reservedchk > input')
			};
		}

		function addSondagesEventListeners() {
			$('#sondages > div.beenadded .sondage-buttons > img:not(.sondage-update)').click(function(){
				var id = this.parentNode.parentNode.id.replace('sondage-','');
				switch ($(this).attr('class')) {
					case 'sondage-vote':
						if (sondages[id]['voting']) {
							if (!$('#sondage-'+id).hasClass('sondage-inedit')) {
								sondages[id]['voting'] = false;
								$('#sondage-'+id+' > div.sondage-show').removeClass('sondage-voting');
							}
						}
						else {
							sondages[id]['voting'] = true;
							$('#sondage-'+id+' > div.sondage-show').addClass('sondage-voting');
						}
						$('#sondage-'+id).removeClass('sondage-inedit');
						break;
					case 'sondage-reset':
						if (confirm('Réinitialiser les votes ?')) server(5,{'sondage':id,'last-modified':sondages[id]['last-modified'],'order':getSondageOrder(id)});
						break;
					case 'sondage-edit':
						$('#sondage-'+id).toggleClass('sondage-inedit');
						break;
					case 'sondage-delete':
						if (confirm('Supprimer le sondage ?')) server(6,{'sondage':id});
						break;
				}
			});
			$('#sondages > div.beenadded .sondage-buttons > img.sondage-update').hammer().on({
				tap : function(){
					var id = this.parentNode.parentNode.id.replace('sondage-','');
					askSondageUpdate(id);
					if (sondages[id]['autoUpdating'] !== undefined) {
						$(this).removeClass('rotate');
						delete sondages[id]['autoUpdating'];
					}
				},
				doubletap : function(){
					var id = this.parentNode.parentNode.id.replace('sondage-','');
					if (sondages[id]['autoUpdating'] !== undefined) delete sondages[id]['autoUpdating'];
					else {
						sondages[id]['autoUpdating'] = true;
						up(id);
					}
					$(this).toggleClass('rotate');
				}
			});
			$('#sondages > div.beenadded .sondage-addprop').click(function(){
				$(this.parentNode).children('table').append(inEditBlankTrHTML);
				addPropsEventListeners();
			});
			$('#sondages > div.beenadded .sondage-cancel').click(function(){$(this.parentNode.parentNode.parentNode).removeClass('sondage-inedit');});
			$('#sondages > div.beenadded .sondage-send').click(function(){
				var id = this.parentNode.parentNode.parentNode.id.replace('sondage-','');
				var obj = {'sondage':id,'name':sondages[id]['$']['edit-title'].val(),'goal':sondages[id]['$']['edit-goal'].val()};
				var propsNumber = 0;
				sondages[id]['$']['edit-table'].find('> tr').each(function(){
					propsNumber++;
					obj['prop'+propsNumber] = $(this).find('> td.sondage-ptxt > input').val();
					var propImg = $(this).find('> td.sondage-pimg > select').val();
					if (propImg != '0') obj['prop'+propsNumber+'img'] = propImg;
					var active = $(this).find('> td.sondage-pactive > input').is(':checked');
					if (!active) obj['prop'+propsNumber+'no'] = '';
				});
				obj['propsnumber'] = propsNumber;
				if (sondages[id]['$']['edit-reserved'].is(':checked')) obj['reserved'] = '';
				server(1,obj);
			});
			$('#sondages > div.beenadded').removeClass('beenadded');
		}

		function addPropsEventListeners() {
			$('#sondages .beenadded .sondage-padd button').click(function(){
				var id = this.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.id.replace('sondage-','');
				var num = $(this.parentNode.parentNode.parentNode).attr('num');
				var votes = $(this).html().replace('+','');
				var obj = {'sondage':id,'last-modified':sondages[id]['last-modified'],'order':getSondageOrder(id),'num':num,'votes':votes};
				setSondageGdVotesLenghts(obj,id);
				server(3,obj);
			});
			$('#sondages .beenadded .sondage-ptxt').click(function(){
				var id = this.parentNode.parentNode.parentNode.parentNode.id.replace('sondage-','');
				$(this.parentNode.parentNode).find('.sondage-ptxt').removeClass('blink');
				$(this).addClass('blink');
				sondages[id]['selectedProp'] = $(this.parentNode).attr('num');
			});
			$('#sondages .beenadded .sondage-gdvotes-icons .rollable > div:first-child').click(function(){
				$rollable = $(this.parentNode);
				if ($rollable.hasClass('show-content')) {
					$rollable.removeClass('show-content');
					$rollable.find('.rollable').removeClass('show-content');
				}
				else $rollable.addClass('show-content');
			});
			$('#sondages .beenadded .sondage-gdvotes-icons img').click(function(){
				var id = this.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.id.replace('sondage-','');
				if (sondages[id]['selectedProp'] != 0) {
					var img = $(this).attr('imgpath');
					var obj = {'sondage':id,'last-modified':sondages[id]['last-modified'],'order':getSondageOrder(id),'num':sondages[id]['selectedProp'],'img':img};
					setSondageGdVotesLenghts(obj,id);
					server(4,obj);
				}
			});
			$('#sondages .beenadded .sondage-pdel .buttonlike').click(function(){$(this.parentNode.parentNode).remove();});
			$('#sondages .beenadded').removeClass('beenadded');
		}

		function setPropGdVotes(id,sondage,propArrayIndex,i,refs) {
			if (sondage[propArrayIndex][i]['gdVotes'] === undefined) return;
			var newGdVotesLen = sondage[propArrayIndex][i]['gdVotes'].length;
			var gdVotesLen = sondages[id][propArrayIndex][i]['gdVotes'].length;
			var tableContainerIndex = 'show-trs';
			if (propArrayIndex == 'propsVoting') tableContainerIndex = 'voting-trs';
			if (newGdVotesLen > 0 && (gdVotesLen == 0 || gdVotesLen > 0 && sondage[propArrayIndex][i]['gdVotes'][newGdVotesLen-1]['img'] != sondages[id][propArrayIndex][i]['gdVotes'][gdVotesLen-1]['img'])) {
				var gdVotesHTML = '';
				var gdVoteImg;
				for (j in sondage[propArrayIndex][i]['gdVotes']) {
					gdVoteImg = sondage[propArrayIndex][i]['gdVotes'][j]['img'];
					gdVotesHTML = '<img src="/medias/pps/'+gdVoteImg+'"/>'+gdVotesHTML;
					sondages[id][propArrayIndex][i]['gdVotes'].push(sondage[propArrayIndex][i]['gdVotes'][j]);
					if (propArrayIndex == 'props' && !sondages[id]['gdVotes'].includes(gdVoteImg)) refs['newGdVotes'].push(gdVoteImg);
				}
				sondages[id]['$'][tableContainerIndex][i]['gdVotes'].prepend(gdVotesHTML);
				refs['remakeGdVotesSl'] = true;
			}
		}

		function updateSondage(sondage) {
			var id = sondage['id'];
			if (sondage['name'] != sondages[id]['name']) {
				sondages[id]['$']['show-title'].html(sondage['name']);
				sondages[id]['$']['edit-title'].val(sondage['name']);
			}
			var hasImgs = false;
			for (i in sondage['props']) {
				if (sondage['props'][i]['img'] !== null) {
					hasImgs = true;
					break;
				}
			}
			if (hasImgs != sondages[id]['hasImgs']) {
				if (hasImgs) sondages[id]['$']['show'].removeClass('sondage-hasnoimgs');
				else sondages[id]['$']['show'].addClass('sondage-hasnoimgs');
			}
			var newPropsLen = sondage['props'].length;
			sondage['propsEdit'] = [];
			var n = 0, searchNum = 0;
			while (n < newPropsLen) {
				searchNum++;
				for (i in sondage['props']) {
					if (sondage['props'][i]['num'] == searchNum) {
						sondage['propsEdit'].push(sondage['props'][i]);
						n++;
						break;
					}
				}
			}
			sondage['propsVoting'] = [];
			for (i in sondage['propsEdit']) {
				if (sondage['propsEdit'][i]['active'] == 1) {
					sondage['propsVoting'].push($.extend(true,{},sondage['propsEdit'][i]));
				}
			}
			var newPropsVotingLen = sondage['propsVoting'].length;
			var ancPropsLen = sondages[id]['props'].length;
			var ancPropsVotingLen = sondages[id]['propsVoting'].length;
			var ancEditPropsLen = sondages[id]['$']['edit-table'].find('> tr').length;
			if (newPropsLen > ancPropsLen) {
				var showTrsHTML = '';
				var editTrsHTML = '';
				for (var i = ancPropsLen; i < newPropsLen; i++) showTrsHTML += '<tr><td class="sondage-prank"><span>#<span></span></span></td><td class="sondage-pimg table-style-1-pp"></td><td class="sondage-ptxt"></td><td class="sondage-pbar"><div class="sondage-pbardv"></div><div class="sondage-gdvotes flx hide-scrollbar"></div></td><td class="sondage-pvotes"></td><td class="sondage-pprc"><span>(<span></span>%)</span></td></tr>';
				for (var i = ancEditPropsLen; i < newPropsLen; i++) editTrsHTML += inEditBlankTrHTML;
				sondages[id]['$']['show-table'].append(showTrsHTML);
				sondages[id]['$']['edit-table'].append(editTrsHTML);
				var $showTrs = sondages[id]['$']['show-table'].find('> tr');
				var $editTrs = sondages[id]['$']['edit-table'].find('> tr');
				var $showTr, $editTr;
				for (var i = ancPropsLen; i < newPropsLen; i++) {
					$showTr = $showTrs.eq(i);
					sondages[id]['$']['show-trs'].push({
						'tr' : $showTr,
						'rank' : $showTr.find('> td.sondage-prank > span > span'),
						'txt' : $showTr.find('> td.sondage-ptxt'),
						'img' : $showTr.find('> td.sondage-pimg'),
						'votes' : $showTr.find('> td.sondage-pvotes'),
						'bar' : $showTr.find('> td.sondage-pbar > div.sondage-pbardv'),
						'gdVotes' : $showTr.find('> td.sondage-pbar > div.sondage-gdvotes'),
						'prc' : $showTr.find('> td.sondage-pprc > span > span')
					});
				}
				for (var i = ancPropsLen; i < newPropsLen; i++) {
					$editTr = $editTrs.eq(i);
					sondages[id]['$']['edit-trs'].push({
						'txt' : $editTr.find('> td.sondage-ptxt > input'),
						'img' : $editTr.find('> td.sondage-pimg > select'),
						'active' : $editTr.find('> td.sondage-pactive > input')
					});
				}
			}
			if (newPropsVotingLen > ancPropsVotingLen) {
				var votingTrsHTML = '';
				for (var i = ancPropsVotingLen; i < newPropsVotingLen; i++) votingTrsHTML += '<tr class="sondage-voterow beenadded"><td class="sondage-ptxt"></td><td class="sondage-pvotes"><div></div><div class="sondage-gdvotes flx hide-scrollbar"></div></td><td class="sondage-padd"><div><button>+1</button><button>+10</button><button>+100</button></div><div><button>+1000</button><button>+10000</button></div></td></tr>';
				sondages[id]['$']['voting-table'].append(votingTrsHTML);
				var $votingTrs = sondages[id]['$']['voting-table'].find('> tr');
				var $votingTr;
				for (var i = ancPropsVotingLen; i < newPropsVotingLen; i++) {
					$votingTr = $votingTrs.eq(i);
					sondages[id]['$']['voting-trs'].push({
						'tr' : $votingTr,
						'txt' : $votingTr.find('> td.sondage-ptxt'),
						'votes' : $votingTr.find('> td.sondage-pvotes > div:first'),
						'gdVotes' : $votingTr.find('> td.sondage-pvotes > div.sondage-gdvotes')
					});
				}
			}
			if (newPropsLen < ancPropsLen) {
				sondages[id]['$']['show-table'].find('> tr').slice(newPropsLen-ancPropsLen).remove();
				sondages[id]['$']['show-trs'] = sondages[id]['$']['show-trs'].slice(0,newPropsLen-ancPropsLen);
				sondages[id]['$']['edit-trs'] = sondages[id]['$']['edit-trs'].slice(0,newPropsLen-ancPropsLen);
			}
			if (newPropsVotingLen < ancPropsVotingLen) {
				sondages[id]['$']['voting-table'].find('> tr').slice(newPropsVotingLen-ancPropsVotingLen).remove();
				sondages[id]['$']['voting-trs'] = sondages[id]['$']['voting-trs'].slice(0,newPropsVotingLen-ancPropsVotingLen);
			}
			if (newPropsLen < ancEditPropsLen) sondages[id]['$']['edit-table'].find('> tr').slice(newPropsLen-ancEditPropsLen).remove();
			if (newPropsVotingLen != ancPropsVotingLen || newPropsLen != ancPropsLen) {
				if (ancPropsVotingLen == 0 && newPropsVotingLen != ancPropsVotingLen) {
					sondages[id]['$']['voting-trs'][0]['tr'].append('<td class="sondage-gdvotes-icons" rowspan="100"><div class="rollables-dv rollables-min rollables-pps hide-scrollbar">'+gdVotesPpsSelectHTML+'</div></td>');
					sondages[id]['$']['gdVotesSelection'] = sondages[id]['$']['voting-trs'][0]['tr'].find('> td.sondage-gdvotes-icons > div');
				}
				if (newPropsVotingLen > 1) {
					sondages[id]['selectedProp'] = 0;
					sondages[id]['$']['voting-table'].find('> tr > td.sondage-ptxt').removeClass('blink');
				}
				else if (newPropsVotingLen > 0) sondages[id]['selectedProp'] = sondage['propsVoting'][0]['num'];
			}
			var prc;
			for (var i = 0; i < newPropsLen; i++) {
				sondages[id]['$']['show-trs'][i]['txt'].html(sondage['props'][i]['prop']);
				if (sondage['props'][i]['active'] == 1) sondages[id]['$']['show-trs'][i]['tr'].removeClass('sondage-pinactive');
				else sondages[id]['$']['show-trs'][i]['tr'].addClass('sondage-pinactive');
				sondages[id]['$']['show-trs'][i]['txt'].html(sondage['props'][i]['prop']);
				if (sondage['props'][i]['img'] !== null) sondages[id]['$']['show-trs'][i]['img'].html('<img src="/medias/pps/'+sondage['props'][i]['img']+'"/>');
				else sondages[id]['$']['show-trs'][i]['img'].html('');
				if (sondages[id]['props'][i] === undefined) sondages[id]['props'].push({'gdVotes':[]})
				else sondages[id]['props'][i]['gdVotes'] = [];
				sondages[id]['$']['show-trs'][i]['gdVotes'].html('');
			}
			for (var i = 0; i < newPropsVotingLen; i++) {
				sondages[id]['$']['voting-trs'][i]['tr'].attr('num',sondage['propsVoting'][i]['num']);
				sondages[id]['$']['voting-trs'][i]['txt'].html(sondage['propsVoting'][i]['prop']);
				sondages[id]['$']['voting-trs'][i]['votes'].html(sondage['propsVoting'][i]['votes']);
				if (sondages[id]['propsVoting'][i] === undefined) sondages[id]['propsVoting'].push({'gdVotes':[]})
				else sondages[id]['propsVoting'][i]['gdVotes'] = [];
				sondages[id]['$']['voting-trs'][i]['gdVotes'].html('');
			}
			for (var i = 0; i < newPropsLen; i++) {
				sondages[id]['$']['edit-trs'][i]['txt'].val(sondage['propsEdit'][i]['prop']);
				if (sondage['propsEdit'][i]['img'] !== null) sondages[id]['$']['edit-trs'][i]['img'].val(sondage['propsEdit'][i]['img']);
				else sondages[id]['$']['edit-trs'][i]['img'].val(0);
				sondages[id]['$']['edit-trs'][i]['active'].prop('checked',sondage['propsEdit'][i]['active'] == 1);
			}
			var goal = parseInt(sondage['goal']);
			if (goal != sondages[id]['goal']) {
				if (goal > 0) {
					sondages[id]['$']['show-votetoget100'].html(goal);
					sondages[id]['$']['edit-goal'].val(goal);
					sondages[id]['$']['show'].addClass('sondage-hasgoal');
				}
				else {
					sondages[id]['$']['show-votetoget100'].html('');
					sondages[id]['$']['edit-goal'].val('');
					sondages[id]['$']['show'].removeClass('sondage-hasgoal');
				}
				sondages[id]['goal'] = goal;
			}
			if (sondage['reserved'] != sondages[id]['reserved']) {
				if (sondage['reserved'] == 0) sondages[id]['$']['show'].removeClass('sondage-reserved');
				else sondages[id]['$']['show'].addClass('sondage-reserved');
				sondages[id]['$']['edit-reserved'].prop('checked',sondage['reserved'] == 1);
			}
			updateSondageVotes(sondage);
			sondages[id]['name'] = sondage['name'];
			sondages[id]['hasImgs'] = hasImgs;
			sondages[id]['props'] = sondage['props'];
			sondages[id]['propsVoting'] = sondage['propsVoting'];
			sondages[id]['propsEdit'] = sondage['propsEdit'];
			sondages[id]['reserved'] = sondage['reserved'];
			if (sondage['last-modified'] != sondages[id]['last-modified']) sondages[id]['last-modified'] = sondage['last-modified'];
		}

		function updateSondageVotes(sondage) {
			var id = sondage['id'];
			var propsVotingLen = sondage['propsVoting'].length;
			var totalVotes = 0;
			for (i in sondage['propsVoting']) totalVotes += parseInt(sondage['propsVoting'][i]['votes']);
			var votesToGet100 = totalVotes;
			var ranked = (totalVotes > 0 && propsVotingLen > 1);
			if (ranked != sondages[id]['ranked']) {
				if (ranked) sondages[id]['$']['show'].removeClass('sondage-notranked');
				else sondages[id]['$']['show'].addClass('sondage-notranked');
			}
			var goal = sondages[id]['goal'];
			var isGoalWithOneProp = (goal > 0 && propsVotingLen == 1);
			if (isGoalWithOneProp) votesToGet100 = goal;
			var prcs = (totalVotes > 0 && (propsVotingLen > 1 || isGoalWithOneProp));
			if (prcs != sondages[id]['prcs']) {
				if (prcs) sondages[id]['$']['show'].removeClass('sondage-notprced');
				else sondages[id]['$']['show'].addClass('sondage-notprced');
			}
			var propsLen = sondage['props'].length;
			var rank = 0, rankaff;
			var ancVotes = 0;
			var votes, prc, prcWidth, prc0;
			var refs = {'newGdVotes':[],'remakeGdVotesSl':false};
			for (var i = 0; i < propsLen; i++) {
				votes = sondage['props'][i]['votes'];
				rank++;
				if (votes != ancVotes) rankaff = rank;
				sondages[id]['$']['show-trs'][i]['rank'].html(rankaff);
				sondages[id]['$']['show-trs'][i]['votes'].html(votes);
				if (prcs) {
					prc = (votes/votesToGet100*100).round(4);
					prcWidth = prc;
					if (prcWidth > 100) prcWidth = 100;
				 	sondages[id]['$']['show-trs'][i]['bar'].css('width',prcWidth+'%');
					if (0 < prc && prc < 1) prc = '<1';
					else if (99 < prc && prc < 100) prc = '>99';
					else prc = prc.round(0);
					sondages[id]['$']['show-trs'][i]['prc'].html(prc);
					prc0 = (prc == 0);
					if (prc0 != sondages[id]['props'][i]['prc0']) {
						if (prc0) sondages[id]['$']['show-trs'][i]['bar'].addClass('sondage-prc0');
						else sondages[id]['$']['show-trs'][i]['bar'].removeClass('sondage-prc0');
						sondages[id]['props'][i]['prc0'] = prc0;
					}
				}
				setPropGdVotes(id,sondage,'props',i,refs);
				sondages[id]['props'][i]['votes'] = votes;
				ancVotes = votes;
			}
			for (var i = 0; i < propsVotingLen; i++) {
				sondages[id]['$']['voting-trs'][i]['votes'].html(sondage['propsVoting'][i]['votes']);
				setPropGdVotes(id,sondage,'propsVoting',i,refs);
				sondages[id]['propsVoting'][i]['votes'] = sondage['propsVoting'][i]['votes'];
			}
			sondages[id]['$']['show-votetotal'].html(totalVotes.toLocaleString('fr-FR'));
			if (refs['remakeGdVotesSl']) {
				var newGdVotesSelectors = [];
				for (i in refs['newGdVotes']) newGdVotesSelectors.push('img.sondage-gdvote-pp-'+gdVotesPps[refs['newGdVotes'][i]]);
				sondages[id]['$']['gdVotesSelection'].find(newGdVotesSelectors.join(', ')).addClass('sondage-gdvote-pp-used');
				sondages[id]['gdVotes'] = sondages[id]['gdVotes'].concat(refs['newGdVotes']);
			}
			sondages[id]['ranked'] = ranked;
			sondages[id]['prcs'] = prcs;
		}

		function getSondageOrder(id) {
			var order = [];
			for (i in sondages[id]['props']) order.push(sondages[id]['props'][i]['num']);
			return order.join('-');
		}

		function setSondageGdVotesLenghts(obj,id) {
			for (i in sondages[id]['propsVoting']) obj['prop'+(parseInt(i)+1)+'gdlen'] = sondages[id]['props'][i]['gdVotes'].length;
			obj['propsnumber'] = sondages[id]['propsVoting'].length;
		}

		function askSondageUpdate(id) {
			var sum = 0;
			for (i in sondages[id]['propsVoting']) sum += parseInt(sondages[id]['propsVoting'][i]['votes']);
			var obj = {'sondage':id,'last-modified':sondages[id]['last-modified'],'order':getSondageOrder(id),'sum':sum};
			setSondageGdVotesLenghts(obj,id);
			server(2,obj);
		}

		function up(id) {
			setTimeout(function(){
				askSondageUpdate(id);
				if (sondages[id]['autoUpdating'] !== undefined) up(id);
			},500);
		}

		serverResponse = function(opt) {
			if (opt[0] == 0) {
				for (i in opt[1][0]) addSondage(opt[1][0][i]);
				$('#sondages').append(HTMLToAppend);
				for (i in sondages) addSondageContainers(i);
				ppsSelectHTML = '<select class="sondage-img-sl"><option value="0">-Image-</option>'+getSelectOptionsHTMLFromImagesList(getPpsPathsListFromObj(opt[1][1]))+'</select>';
				var imgPath;
				var ppsIndex = 0;
				for (i in opt[1][1]) {
					gdVotesPpsSelectHTML += '<div class="rollable"><div>'+i+'</div><div>';
					for (j in opt[1][1][i]) {
						gdVotesPpsSelectHTML += '<div class="rollable rollable-min"><div>'+j+'</div><div class="flx flx-ww">';
						for (k in opt[1][1][i][j]) {
							imgPath = i+'/'+j+'/'+opt[1][1][i][j][k];
							ppsIndex++;
							gdVotesPps[imgPath] = ppsIndex;
							gdVotesPpsSelectHTML += '<img class="sondage-gdvote-pp-'+ppsIndex+'" src="/medias/pps/'+imgPath+'" imgpath="'+imgPath+'" title="'+opt[1][1][i][j][k]+'"/>';
						}
						gdVotesPpsSelectHTML += '</div></div>';
					}
					gdVotesPpsSelectHTML += '</div></div>';
				}
				inEditBlankTrHTML = '<tr class="beenadded"><td class="sondage-pdel"><div class="buttonlike buttonlike-sondagedel"></div></td><td class="sondage-pimg">'+ppsSelectHTML+'</td><td class="sondage-ptxt"><input type="text" placeholder="Proposition ..."/></td><td class="sondage-pactive"><input type="checkbox" checked/></td></tr>';
				myName = opt[1][2];
				$('#sondage-0').prepend(editDvHTML);
				sondages[0] = {
					'$' : {
						'root' : $('#sondage-0'),
						'edit-title' : $('#sondage-0 > div.sondage-edition > input.sondage-titleedit'),
						'edit-table' : $('#sondage-0 > div.sondage-edition > table'),
						'edit-goal' : $('#sondage-0 > div.sondage-edition > input.sondage-goal'),
						'edit-reserved' : $('#sondage-0 > div.sondage-edition > label.sondage-reservedchk')
					}
				};
				sondages[0]['$']['edit-table'].append(inEditBlankTrHTML);
				addSondagesEventListeners();
				for (i in opt[1][0]) updateSondage(opt[1][0][i]);
				addPropsEventListeners();
			}
			if (['1','2','3','4','5','6'].includes(opt[0])) {
				if (opt[1]['name'] !== undefined) {
					var id = opt[1]['id'];
					if (sondages[id] !== undefined) {
						updateSondage(opt[1]);
						addPropsEventListeners();
						$('#sondage-'+id).removeClass('sondage-inedit');
					}
					else {
						HTMLToAppend = '';
						opt[1]['username'] = myName;
						opt[1]['isSelf'] = true;
						addSondage(opt[1]);
						$(HTMLToAppend).insertAfter('#sondage-0');
						addSondageContainers(id);
						addSondagesEventListeners();
						updateSondage(opt[1]);
						sondages[0]['$']['edit-title'].val('');
						sondages[0]['$']['edit-goal'].val('');
						sondages[0]['$']['edit-table'].html(inEditBlankTrHTML);
						sondages[0]['$']['root'].removeClass('sondage-inedit');
						addPropsEventListeners();
					}
				}
				else if (opt[1]['id'] !== undefined) {
					var id = opt[1]['id'];
					opt[1]['propsVoting'] = [];
					var propsLen = opt[1]['props'].length;
					var n = 0, searchNum = 0;
					while (n < propsLen) {
						searchNum++;
						for (i in opt[1]['props']) {
							if (sondages[id]['props'][i]['num'] == searchNum) {
								opt[1]['propsVoting'].push(opt[1]['props'][i]);
								n++;
								break;
							}
						}
					}
					updateSondageVotes(opt[1]);
				}
				else {
					sondages[opt[1]]['$']['root'].remove();
					delete sondages[opt[1]];
				}
				if (opt[1]['name'] !== undefined || opt[1]['id'] !== undefined) {
					var sum = 0;
					for (i in sondages[id]['props']) sum += parseInt(sondages[id]['props'][i]['votes']);
					if (sum == 0) {
						for (i in opt[1]['props']) {
							sondages[id]['props'][i]['gdVotes'] = [];
							sondages[id]['$']['show-trs'][i]['gdVotes'].html('');
						}
						for (i in opt[1]['propsVoting']) {
							sondages[id]['propsVoting'][i]['gdVotes'] = [];
							sondages[id]['$']['voting-trs'][i]['gdVotes'].html('');
						}
						sondages[id]['gdVotes'] = [];
						sondages[id]['$']['gdVotesSelection'].find('img.sondage-gdvote-pp-used').removeClass('sondage-gdvote-pp-used');
					}
				}
			}
		}

		$('#sondage-add').click(function(){$('#sondage-0').addClass('sondage-inedit');});

		var sondages = {};
		var ppsSelectHTML = '';
		var HTMLToAppend = '';
		var editDvHTML = '<div class="sondage-edition flx flx-dc flx-ac"><input type="text" class="sondage-titleedit" placeholder="Nom du sondage ..."/><table></table><button class="sondage-addprop">Ajouter une proposition</button><input class="sondage-goal" type="text" placeholder="Max de votants ..."/><label class="sondage-reservedchk"><input type="checkbox"/>Réservé aux grands votants</label><div><button class="sondage-cancel">Annuler</button><button class="sondage-send">Valider</button></div></div>';
		var inEditBlankTrHTML;
		var gdVotesPps = {};
		var gdVotesPpsSelectHTML = '';
		var myName;

		server(0);
	});
	</script>
</head>
<body>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../header.php'; ?>
	<div id="pg">
		<div class="flx flx-jc flx-ac pgtlt">
			<img src="/src/icons/menu/white/election-envelopes-and-box-iconsrepo-com.svg"/>
			<h1>Sondages</h1>
			<img src="/src/icons/menu/white/election-envelopes-and-box-iconsrepo-com.svg"/>
		</div>
		<div id="sondages">
			<div id="sondage-0" class="flx flx-jc beenadded">
				<div id="sondage-adddv" class="flx flx-jc"><button id="sondage-add">Ajouter un sondage</button></div>
			</div>
		</div>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/../footer.php'; ?>
	</div>
</body>
</html>
