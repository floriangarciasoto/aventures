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
function processPOSTRequest($type,&$responseData) {
	if ($type == 2) {
		if (!isset($_POST['sender']) || $_POST['sender'] != intval($_POST['sender']).'' || intval($_POST['sender']) < 1 || !isset($_POST['msg']) || $_POST['msg'] == '') return -1;
		if (isset($_POST['tweet']) && $_POST['tweet'] != intval($_POST['tweet']).'') return -1;
	}
	if ($type == 6) {
		if (!isset($_POST['account']) || $_POST['account'] != intval($_POST['account']).'' || !isset($_POST['name']) || $_POST['name'] == '' || !isset($_POST['at']) || $_POST['at'] == '' || !isset($_POST['img']) || $_POST['img'] == '') return -1;
	}
	require $_SERVER['DOCUMENT_ROOT'].'/../lib/db.php';
	require $_SERVER['DOCUMENT_ROOT'].'/../lib/misc.php';
	$db = new DB();
	if ($type == 0) {
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/os.php';
		$responseData = array(array(),getpps(['force','scenarios']),$db->getDirect("SELECT `tweet`, SUM(`num`) AS `sum` FROM `scenarios-tweets` GROUP BY `tweet`"));
		foreach ($db->getDirect("SELECT `tweet` FROM `scenarios-tweets` GROUP BY `tweet` ORDER BY `tweet`") as $value) array_push($responseData[0],$db->getDirect("SELECT *, TIMESTAMPDIFF(SECOND, `date`, NOW()) AS `seconds`, TIMESTAMPDIFF(MINUTE, `date`, NOW()) AS `minutes`, TIMESTAMPDIFF(HOUR, `date`, NOW()) AS `hours`, TIMESTAMPDIFF(DAY, `date`, NOW()) AS `days`, TIMESTAMPDIFF(WEEK, `date`, NOW()) AS `weeks`, TIMESTAMPDIFF(MONTH, `date`, NOW()) AS `months`, TIMESTAMPDIFF(YEAR, `date`, NOW()) AS `years` FROM `scenarios-tweets`, `scenarios-tweets-accounts` WHERE `scenarios-tweets`.`sender`=`scenarios-tweets-accounts`.`id` AND `tweet`=".$value['tweet']." ORDER BY `tweet` DESC, `num`"));
		for ($i = 0; $i < sizeof($responseData[0]); $i++) {
			for ($j = 0; $j < sizeof($responseData[0][$i]); $j++) {
				$responseData[0][$i][$j]['msg'] = getEscapedAngleBrackets($responseData[0][$i][$j]['msg']);
				$responseData[0][$i][$j]['since'] = getSinceDate($responseData[0][$i][$j]);
			}
		}
	}
	if ($type == 1) {
		$responseData = $db->getDirect("SELECT `tweet`, SUM(`num`) AS `sum` FROM `scenarios-tweets` GROUP BY `tweet`");
	}
	if ($type == 2) {
		if (isset($_POST['tweet'])) {
			$db->req("INSERT INTO `scenarios-tweets`(`tweet`, `num`, `sender`, `msg`, `date`) VALUES (?, ?, ?, ?, NOW())",[$_POST['tweet'],$db->getValue("SELECT MAX(`num`)+1 FROM `scenarios-tweets` WHERE `tweet`=?",[$_POST['tweet']]),$_POST['sender'],$_POST['msg']]);
			$responseData = $_POST['tweet'];
		}
		else {
			$tweetNewId = $db->getValueDirect("SELECT MAX(`tweet`)+1 FROM `scenarios-tweets`");
			if (is_null($tweetNewId)) $tweetNewId = 1;
			$db->req("INSERT INTO `scenarios-tweets`(`tweet`, `num`, `sender`, `msg`, `date`) VALUES (?, 1, ?, ?, NOW())",[$tweetNewId,$_POST['sender'],$_POST['msg']]);
			$responseData = 'newtweet';
		}
	}
	if ($type == 3) {
		$num = 0;
		$i = 0;
		while ($num < $_POST['sum']) {
			$i++;
			$num += $i;
		}
		$responseData = $db->get("SELECT *, TIMESTAMPDIFF(SECOND, `date`, NOW()) AS `seconds`, TIMESTAMPDIFF(MINUTE, `date`, NOW()) AS `minutes`, TIMESTAMPDIFF(HOUR, `date`, NOW()) AS `hours`, TIMESTAMPDIFF(DAY, `date`, NOW()) AS `days`, TIMESTAMPDIFF(WEEK, `date`, NOW()) AS `weeks`, TIMESTAMPDIFF(MONTH, `date`, NOW()) AS `months`, TIMESTAMPDIFF(YEAR, `date`, NOW()) AS `years` FROM `scenarios-tweets`, `scenarios-tweets-accounts` WHERE `scenarios-tweets`.`sender`=`scenarios-tweets-accounts`.`id` AND `tweet`=? AND `num`>?",[$_POST['tweet'],$i]);
		for ($i=0; $i < sizeof($responseData); $i++) $responseData[$i]['since'] = getSinceDate($responseData[$i]);
	}
	if ($type == 4) {
		$responseData = $db->get("SELECT *, TIMESTAMPDIFF(SECOND, `date`, NOW()) AS `seconds`, TIMESTAMPDIFF(MINUTE, `date`, NOW()) AS `minutes`, TIMESTAMPDIFF(HOUR, `date`, NOW()) AS `hours`, TIMESTAMPDIFF(DAY, `date`, NOW()) AS `days`, TIMESTAMPDIFF(WEEK, `date`, NOW()) AS `weeks`, TIMESTAMPDIFF(MONTH, `date`, NOW()) AS `months`, TIMESTAMPDIFF(YEAR, `date`, NOW()) AS `years` FROM `scenarios-tweets`, `scenarios-tweets-accounts` WHERE `scenarios-tweets`.`sender`=`scenarios-tweets-accounts`.`id` AND `tweet`=?",[$_POST['tweet']]);
		for ($i=0; $i < sizeof($responseData); $i++) $responseData[$i]['since'] = getSinceDate($responseData[$i]);
	}
	if ($type == 5) {
		$responseData = $db->getDirect("SELECT * FROM `scenarios-tweets-accounts`");
	}
	if ($type == 6) {
		if ($_POST['img'] == '0' && (!isset($_POST['imgurl']) || $_POST['imgurl'] == '' || !isImgURL($_POST['imgurl']))) return -1;
		$img = $_POST['img'];
		if ($_POST['img'] == '0') $img = $_POST['imgurl'];
		if ($_POST['account'] == 0) $db->req("INSERT INTO `scenarios-tweets-accounts`(`name`, `at`, `img`, `verified`) VALUES (?, ?, ?, 0)",[$_POST['name'],$_POST['at'],$img]);
		else $db->req("UPDATE `scenarios-tweets-accounts` SET `name`=?, `at`=?, `img`=? WHERE `id`=?",[$_POST['name'],$_POST['at'],$img,$_POST['account']]);
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
	<title>Twitter - aventures.ddns.net</title>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../include-js.php'; ?>
	<script type="text/javascript">
	$(document).ready(function(){
		function createtweets(tweets) {
			var id = 'tweet'+tweets[0]['tweet'];
			$('#tweets').prepend('<div id="'+id+'" class="tweets"><div class="tweetsin"></div></div>');
			addtweet(tweets[0],true);
			for (var i = 1; i < tweets.length; i++) addtweet(tweets[i],false);
			$('#'+id).append('<hr/><div class="tweetrep"><fieldset class="tweet hidden"><legend>Réponse</legend><div class="flx flx-ac">Posté par : <select class="accounts"></select></div><hr/><div><textarea class="msg maxedwidth" rows="5" placeholder="Tweet ..."></textarea></div><div class="flx flx-je"><button class="cancl">Annuler</button><button class="send">Poster</button></div></fieldset></div><div class="tweetrepbtn"><button>Répondre</button></div>');
			setinps(id);
		}

		function addtweet(tweet,first) {
			var txt = '';
			if (!first) txt = '<hr/>';
			txt += '<div';
			if (first) txt += ' class="firsttweet"';
			txt += '><div class="hdtweet flx flx-ac"><img class="pic" src="';
			if (isURL(tweet['img'])) txt += tweet['img'];
			else txt += '/medias/pps/'+tweet['img'];
			txt += '"/><div class="tweetnames flx flx-ww"><span class="twittername flx flx-ac">'+tweet['name'];
			if (tweet['verified'] == 1) txt += '<img class="twitterverified" src="/src/icons/scenarios/twitter/Twitter_Verified_Badge.svg" title="Compte certifié">';
			txt += '</span><span class="twitterat">@'+tweet['at']+'</span></div></div>';
			tweet['msg'] = tweet['msg'].split(' ');
			for (i in tweet['msg']) {
				if (tweet['msg'][i][0] == '@' || tweet['msg'][i][0] == '#') txt += '<span class="rspto">'+tweet['msg'][i]+'</span>';
				else if (isURL(tweet['msg'][i])) {
					if (isImgURL(tweet['msg'][i])) txt += '<br/><img src="'+tweet['msg'][i]+'"/><br/>';
					else txt += '<a class="rspto" href="'+tweet['msg'][i]+'">'+tweet['msg'][i]+'</a>';
				}
				else txt += tweet['msg'][i];
				txt += ' ';
			}
			txt += '<div class="tweetdate">'+tweet['since']+'</div></div>';
			$('#tweet'+tweet['tweet']+' .tweetsin').append(txt);
		}

		function setinps(id) {
			$('#'+id+' .tweetrepbtn button').click(function(){
				var id = $(this.parentNode.parentNode).attr('id');
				$('#'+id+' fieldset').removeClass('hidden');
				$(this).addClass('hidden');
			});
			$('#'+id+' .cancl').click(function(){setinps2(this.parentNode.parentNode.parentNode.parentNode.id);});
			$('#'+id+' .send').click(function(){
				var id = this.parentNode.parentNode.parentNode.parentNode.id;
				var objToSend = {'sender':$('#'+id+' .accounts').val(),'msg':$('#'+id+' .msg').val()}
				if (id != 'newtweet') objToSend['tweet'] = id.replace('tweet','');
				server(2,objToSend);
			});
		}

		function setinps2(id) {
			$('#'+id+' fieldset').addClass('hidden');
			$('#'+id+' .tweetrepbtn button').removeClass('hidden');
			$('#'+id+' .accounts').val(0);
			$('#'+id+' .msg').val('');
		}

		function updt() {setTimeout(function(){if(sync)server(1);updt();},1000);}

		serverResponse = function(opt) {
			if (opt[0] == 0) {
				textForHTMLs['imgs'] = getSelectOptionsHTMLFromImagesList(getPpsPathsListFromObj(opt[1][1]));
				$('#imgs').append(textForHTMLs['imgs']);
				for (i in opt[1][0]) createtweets(opt[1][0][i]);
				resume = opt[1][2];
				setinps('newtweet');
				server(5);
				sync = true;
			}
			if (opt[0] == 1) {
				if (JSON.stringify(opt[1]) != JSON.stringify(resume)) {
					for (i in resume) if (opt[1][i]['sum'] != resume[i]['sum']) server(3,{'tweet':resume[i]['tweet'],'sum':resume[i]['sum']});
					for (var i = resume.length; i < opt[1].length; i++) server(4,{'tweet':opt[1][i]['tweet']});
					resume = opt[1];
				}
			}
			if (opt[0] == 2) {
				if (opt[1] == 'newtweet') setinps2('newtweet');
				else setinps2('tweet'+opt[1]);
			}
			if (opt[0] == 3) for (i in opt[1]) addtweet(opt[1][i],false);
			if (opt[0] == 4) {
				createtweets(opt[1]);
				for (i in opt[1]) $('#tweet'+opt[1][i]['tweet']+' .accounts').html(textForHTMLs['accounts']);
			}
			if (opt[0] == 5) {
				for (i in opt[1]) accounts[opt[1][i]['id']] = opt[1][i];
				textForHTMLs['accounts'] = '<option value="0">-</option>';
				for (i in accounts) textForHTMLs['accounts'] += '<option value="'+i+'">'+accounts[i]['at']+'</option>';
				$('#accounts, #newtweet .accounts, #tweets .accounts').html(textForHTMLs['accounts']);
				$('#accounts option:nth-child(1)').html('-Nouveau compte-');
			}
			if (opt[0] == 6) window.location.replace('.');
		};

		$('#modif-open-btn, #modif-close-btn').click(function(){$('#modif-open-btn, #modif-fld').toggleClass('hidden');});
		$('#accounts').change(function(){
			var id = $(this).val();
			if (id == 0) {
				$('#acname').val('');
				$('#acat').val('');
				$('#imgs').val(0);
				$('#imgurl').val('');
				$('#imgurl').removeClass('hidden');
			}
			else {
				$('#acname').val(accounts[id]['name']);
				$('#acat').val(accounts[id]['at']);
				if (isURL(accounts[id]['img'])) {
					$('#imgs').val(0);
					$('#imgurl').val(accounts[id]['img']);
					$('#imgurl').removeClass('hidden');
				}
				else {
					$('#imgs').val(accounts[id]['img']);
					$('#imgurl').val('');
					$('#imgurl').addClass('hidden');
				}
			}
		});
		$('#imgs').change(function(){
			if ($(this).val() != 0) $('#imgurl').addClass('hidden');
			else $('#imgurl').removeClass('hidden');
		});
		$('#modif-send-btn').click(function(){
			server(6,{
				'account' : $('#accounts').val(),
				'name' : $('#acname').val(),
				'at' : $('#acat').val(),
				'img' : $('#imgs').val(),
				'imgurl' : $('#imgurl').val()
			});
		});

		var accounts = {};
		var textForHTMLs = {
			'accounts' : '',
			'imgs' : ''
		};
		var resume = {};
		var sync = false;

		server(0);
		updt();
	});
	</script>
</head>
<body>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../header.php'; ?>
	<div id="pg" class="twitter">
		<div class="flx flx-jc flx-ac pgtlt">
			<img src="/src/icons/menu/white/twitter-iconsrepo-com.svg"/>
			<h1>Twitter</h1>
			<img src="/src/icons/menu/white/twitter-iconsrepo-com.svg"/>
		</div>
		<div class="tweetrepbtn flx flx-jc"><button id="modif-open-btn">Gérer les comptes</button></div>
		<div class="flx flx-jc">
			<fieldset id="modif-fld" class="fldct hidden">
				<legend>Comptes Twitter</legend>
				<div><select id="accounts"><option value="0">-Nouveau compte-</option></select></div>
				<div><input type="text" id="acname" placeholder="Nom de compte"/></div>
				<div>@<input type="text" id="acat" placeholder="ID unique"/></div>
				<div>
					<div>PP : </div>
					<div>
						<select id="imgs"><option value="0">-URL-</option></select><br/>
						<input type="text" id="imgurl" placeholder="https:// ..."/>
					</div>
				</div>
				<div><button id="modif-close-btn">Annuler</button> <button id="modif-send-btn">OK</button></div>
			</fieldset>
		</div>
		<div id="newtweet">
			<div class="tweetrepbtn flx flx-jc"><button>Poster un nouveau Tweet</button></div>
			<div class="tweetrep flx flx-jc">
				<fieldset class="hidden">
					<legend>Nouveau Tweet</legend>
					<div class="flx flx-ac">Posté par : <select class="accounts"></select></div>
					<hr/>
					<div><textarea class="msg maxedwidth" rows="5" placeholder="Tweet ..."></textarea></div>
					<div class="flx flx-je">
						<button class="cancl">Annuler</button>
						<button class="send">Poster</button>
					</div>
				</fieldset>
			</div>
		</div>
		<div id="tweets"></div>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/../footer.php'; ?>
	</div>
</body>
</html>
