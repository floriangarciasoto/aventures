<?php
session_start();
if (!isset($_SESSION['user'])) {
	header('Location: /?page='.urlencode($_SERVER['REQUEST_URI']));
	exit;
}
function isNotOwner(&$db,$accountID) {
	return $db->isEmpty("SELECT 1 FROM `force-yt-accounts`, `persos` WHERE `force-yt-accounts`.`id`=? AND `force-yt-accounts`.`perso`=`persos`.`id` AND (`persos`.`group`=0 OR `persos`.`group`=?)",[$accountID,$_SESSION['user']['persos-group']]);
}
function getVideos(&$db,$from,$nb) {
	return $db->get("SELECT * FROM `force-yt-videos` WHERE `scenarios`<=? ORDER BY `date` DESC LIMIT $from, $nb",[$_SESSION['user']['scenarios']]);
}
function processPOSTRequest($type,&$responseData) {
	if ($type == 1) {
		if (!isset($_POST['ac']) || !isset($_POST['subscribers'])) return -1;
		if ($_POST['ac'] == '' || $_POST['subscribers'] == '') return -1;
		if ($_POST['ac'] != intval($_POST['ac']).'' || $_POST['subscribers'] != intval($_POST['subscribers']).'') return -1;
		if ($_POST['ac'] == 0 && (!isset($_POST['perso']) || $_POST['perso'] == '' || $_POST['perso'] == '0' || $_POST['perso'] != intval($_POST['perso']).'')) return -1;
	}
	if ($type == 2) {
		if (!isset($_POST['video']) || $_POST['video'] != intval($_POST['video']).'' || intval($_POST['video']) < 0) return -1;
		if ($type != 2 && intval($_POST['video']) == 0) return -1;
		if (!isset($_POST['acvd']) || !isset($_POST['title']) || !isset($_POST['thumbnail']) || !isset($_POST['views']) || !isset($_POST['likes']) || !isset($_POST['dislikes']) || !isset($_POST['description'])) return -1;
		if ($_POST['acvd'] == '' || $_POST['title'] == '' || $_POST['thumbnail'] == '' || $_POST['views'] == '' || $_POST['likes'] == '' || $_POST['dislikes'] == '') return -1;
		if ($_POST['acvd'] != intval($_POST['acvd']).'' || $_POST['views'] != intval($_POST['views']).'' || $_POST['likes'] != intval($_POST['likes']).'' || $_POST['dislikes'] != intval($_POST['dislikes']).'') return -1;
		if ($_POST['acvd'] == '0' || intval($_POST['views']) < 0 || intval($_POST['likes']) < 0 || intval($_POST['dislikes']) < 0) return -1;
		if (isset($_POST['url']) && $_POST['url'] == '') return -1;
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/misc.php';
		if (!isImgURL($_POST['thumbnail']) && !file_exists($_SERVER['DOCUMENT_ROOT'].'/medias/force/youtube/thumbnails/'.$_POST['thumbnail'])) return -1;
		$video = $_POST['video'];
	}
	if ($type == 3) {
		if (!isset($_POST['from']) || $_POST['from'] != intval($_POST['from']).'') return -1;
	}
	require $_SERVER['DOCUMENT_ROOT'].'/../lib/db.php';
	$db = new DB();
	if ($type == 0) {
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/os.php';
		$responseData = [
			$db->getDirect("SELECT * FROM `force-yt-accounts`"),
			$db->get("SELECT `force-yt-videos`.* FROM `force-yt-videos`, `force-yt-accounts`, `persos` WHERE `scenarios`<=? AND `force-yt-videos`.`ac`=`force-yt-accounts`.`id` AND `force-yt-accounts`.`perso`=`persos`.`id` AND (`persos`.`group`=0 OR `persos`.`group`=?) ORDER BY `date` DESC",[$_SESSION['user']['scenarios'],$_SESSION['user']['persos-group']]),
			getFilesListFromFolder('/medias/force/youtube/thumbnails/'),
			getFilesListFromFolder('/medias/force/youtube/videos/'),
			getVideos($db,0,10)
		];
	}
	if ($type == 1) {
		if ($_POST['ac'] != '0') {
			if (isNotOwner($db,$_POST['ac'])) return -1;
			$db->req("UPDATE `force-yt-accounts` SET `subscribers`=?, `last-used`=NOW() WHERE `id`=?",[$_POST['subscribers'],$_POST['ac']]);
		}
		else {
			if (!$db->isEmpty("SELECT 1 FROM `force-yt-accounts` WHERE `perso`=?",[$_POST['perso']])) return -1;
			$db->req("INSERT INTO `force-yt-accounts`(`perso`, `subscribers`, `last-used`) VALUES (?, ?, NOW())",[$_POST['perso'],$_POST['subscribers']]);
		}
	}
	if ($type == 2) {
		if (isNotOwner($db,$_POST['acvd'])) return -1;
		$num = $db->getValue("SELECT MAX(`num`)+1 FROM `force-yt-videos` WHERE `ac`=?",[$_POST['acvd']]);
		if (is_null($num)) $num = 1;
		$url = null;
		if (isset($_POST['url'])) $url = $_POST['url'];
		if ($video == 0) $db->req("INSERT INTO `force-yt-videos`(`scenarios`, `ac`, `num`, `title`, `thumbnail`, `url`, `date`, `views`, `likes`, `dislikes`, `description`) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)",[$_SESSION['user']['scenarios'],$_POST['acvd'],$num,$_POST['title'],$_POST['thumbnail'],$url,$_POST['views'],$_POST['likes'],$_POST['dislikes'],$_POST['description']]);
		else {
			if ($db->isEmpty("SELECT 1 FROM `force-yt-videos`, `force-yt-accounts`, `persos` WHERE `force-yt-videos`.`id`=? AND `force-yt-videos`.`ac`=`force-yt-accounts`.`id` AND `force-yt-accounts`.`perso`=`persos`.`id` AND (`persos`.`group`=0 OR `persos`.`group`=?)",[$video,$_SESSION['user']['persos-group']])) return -1;
			$db->req("UPDATE `force-yt-videos` SET `ac`=?, `title`=?, `thumbnail`=?, `url`=?, `views`=?, `likes`=?, `dislikes`=?, `description`=? WHERE `id`=?",[$_POST['acvd'],$_POST['title'],$_POST['thumbnail'],$url,$_POST['views'],$_POST['likes'],$_POST['dislikes'],$_POST['description'],$video]);
		}
	}
	if ($type == 3) {
		$responseData = getVideos($db,$_POST['from'],10);
	}
	$db->close();
	return 0;
}
if (isset($_POST['type']) && in_array($_POST['type'],[0,1,2,3])) {
	$responseData = '';
	if (processPOSTRequest($_POST['type'],$responseData) == 0) echo json_encode([$_POST['type'],$responseData]);
	exit;
} ?>
<!DOCTYPE html>
<html>
<head>
	<title>YouTube - aventures.ddns.net</title>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../include-js.php'; ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js" integrity="sha512-+H4iLjY3JsKiF2V6N366in5IQHj2uEsGV7Pp/GRcm0fn76aPAk5V8xB6n8fQhhSonTqTXs/klFz4D0GIn6Br9g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/fr.min.js" integrity="sha512-RAt2+PIRwJiyjWpzvvhKAG2LEdPpQhTgWfbEkFDCo8wC4rFYh5GQzJBVIFDswwaEDEYX16GEE/4fpeDNr7OIZw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script type="text/javascript" src="/src/lib/comments.js"></script>
	<script type="text/javascript">
	$(document).ready(function(){
		function getFormatedNumber(n) {
			if (n >= 10000000000) return Math.floor(n/1000000000)+' Md';
			if (n >= 1000000000) return (Math.floor(n/100000000)/10+'').replace('.',',')+' Md';
			if (n >= 10000000) return Math.floor(n/1000000)+' M';
			if (n >= 1000000) return (Math.floor(n/100000)/10+'').replace('.',',')+' M';
			if (n >= 10000) return Math.floor(n/1000)+' k';
			if (n >= 1000) return (Math.floor(n/100)/10+'').replace('.',',')+' k';
			return n;
		}

		function addVideos(opt) {
			var res = '';
			for (i in opt) {
				videos[opt[i]['id']] = opt[i];
				res += '<div id="yt-vd-'+opt[i]['id']+'" class="beenadded"><div><div><img class="thumbnail-16-9" src="'+addPathPartIfNotURL('/medias/force/youtube/thumbnails/',opt[i]['thumbnail'])+'"/></div><div><div class="yt-vd-title colored-text-container customemoji-container">'+getReplacedHashtagsWithHTMLTags(replaceBulk(getEscapedLineStr(opt[i]['title']),searchArrayOnName,replaceArrayOnName))+'</div><div class="yt-vd-infos flx"><div class="yt-vd-vpp flx flx-dc flx-jc"><img class="pp" src="'+addPathPartIfNotURL('/medias/pps/',persos[accounts[opt[i]['ac']]['perso']]['pp'])+'"/></div><div class="yt-vd-txts"><div class="yt-vd-author customemoji-container">'+getFormatedPersoNameByID(accounts[opt[i]['ac']]['perso'])+'</div><div class="yt-vd-views">'+getFormatedNumber(opt[i]['views'])+' vues - '+moment(opt[i]['date']).fromNow()+'</div><div class="yt-vd-likesdislikes flx flx-ac"><div class="flx">'+getFormatedNumber(opt[i]['likes'])+' <img src="/src/icons/force/youtube/368098.svg"></div><div><div style="width: '+(parseInt(opt[i]['likes'])/(parseInt(opt[i]['likes'])+parseInt(opt[i]['dislikes']))*100)+'%"></div></div><div class="flx">'+getFormatedNumber(opt[i]['dislikes'])+' <img src="/src/icons/force/youtube/red/368098.svg"></div></div></div></div></div></div></div>';
			}
			$('#yt-videos').append(res);
		}

		function addVideosEventListeners() {
			$('#yt-videos > div.beenadded > div').click(function(){changeVideo(this.parentNode.id.replace('yt-vd-',''));});
			$('#yt-videos > div.beenadded').removeClass('beenadded');
		}

		function changeVideo(id) {
			ytVideo = id;
			$('#yt-vd-videotag, #yt-vd-realplayer')[0].pause();
			$('#yt-vd-videotag, #yt-vd-realplayer').removeAttr('src');
			$('#yt-vd-play-dv, #yt-vd-videotag, #yt-vd-realplayer').addClass('hidden');
			$('#yt-vd-thumbnail').removeClass('hidden');
			if (id == 0) {
				$('#yt-page').removeClass('yt-page-showing-video');
				$('#yt-videos > div').removeClass('hidden');
			}
			else {
				var isYtURL = (videos[id]['url'] !== null && videos[id]['url'].search(ytURLReg) != -1);
				if (!isYtURL) $('#yt-vd-thumbnail').attr('src',addPathPartIfNotURL('/medias/force/youtube/thumbnails/',videos[id]['thumbnail']));
				if (videos[id]['url'] !== null) {
					if (isYtURL) {
						$('#yt-vd-realplayer').attr('src','https://www.youtube.com/embed/'+videos[id]['url'].replace(ytURLReg,''));
						$('#yt-vd-realplayer').removeClass('hidden');
						$('#yt-vd-thumbnail').addClass('hidden');
					}
					else {
						$('#yt-vd-videotag').attr('src',addPathPartIfNotURL('/medias/force/youtube/videos/',videos[id]['url']));
						$('#yt-vd-play-dv').removeClass('hidden');
					}
				}
				$('#yt-vd-title').html(getReplacedHashtagsWithHTMLTags(replaceBulk(getEscapedLineStr(videos[id]['title']),searchArrayOnName,replaceArrayOnName)));
				$('#yt-vd-views').html(parseInt(videos[id]['views']).toLocaleString('fr-FR'));
				$('#yt-vd-since').html(moment(videos[id]['date']).fromNow());
				$('#yt-vd-likes').html(getFormatedNumber(videos[id]['likes']));
				$('#yd-vd-likesbar').css('width',(parseInt(videos[id]['likes'])/(parseInt(videos[id]['likes'])+parseInt(videos[id]['dislikes']))*100)+'%');
				$('#yt-vd-dislikes').html(getFormatedNumber(videos[id]['dislikes']));
				$('#yt-vd-channel .pp').attr('src',addPathPartIfNotURL('/medias/pps/',persos[accounts[videos[id]['ac']]['perso']]['pp']));
				$('#yt-vd-channel-author').html(getFormatedPersoNameByID(accounts[videos[id]['ac']]['perso']));
				$('#yt-vd-channel-subs > span').html(getFormatedNumber(accounts[videos[id]['ac']]['subscribers']));
				$('#yt-vd-description').html(getReplacedURIsWithHTMLTags(replaceBulk(getEscapedLinesStr(videos[id]['description']),searchArrayOnText,replaceArrayOnText)));
				$('#yt-videos > div').removeClass('hidden');
				$('#yt-vd-'+ytVideo).addClass('hidden');
				$('#yt-page').addClass('yt-page-showing-video');
				window.scrollTo({
					top: $('#yt-page')[0].getBoundingClientRect().top + window.pageYOffset - ($('header')[0].offsetHeight),
					behavior: 'smooth'
				});
				prepareComments('yt-vd-comments-dv',0,ytVideo,0,accounts[videos[ytVideo]['ac']]['perso'],true,false);
			}
		}

		serverResponse = function(opt) {
			if (opt[0] == 0) {
				for (i in opt[1][0]) accounts[opt[1][0][i]['id']] = opt[1][0][i];
				var myAccountsOptions = opt[1][0].filter(account => ownLastUsedPersosIndexes.includes(account['perso'])).orderByKeyDesc('last-used');
				var res = '';
				for (i in myAccountsOptions) res += '<option value="'+myAccountsOptions[i]['id']+'">'+persos[myAccountsOptions[i]['perso']]['name-sl']+'</option>';
				$('#ac, #acvd').append(res);
				var myPersosWithAccount = myAccountsOptions.map(account => account['perso']);
				var myPersosWithoutAccount = [];
				for (i in ownLastUsedPersosIndexes) if (!myPersosWithAccount.includes(ownLastUsedPersosIndexes[i])) myPersosWithoutAccount.push(ownLastUsedPersosIndexes[i]);
				res = '';
				for (i in myPersosWithoutAccount) res += '<option value="'+myPersosWithoutAccount[i]+'">'+persos[myPersosWithoutAccount[i]]['name-sl']+'</option>';
				$('#ac-perso').append(res);
				res = '';
				for (i in opt[1][1]) {
					id = opt[1][1][i]['id'];
					videos[id] = opt[1][1][i];
					res += '<option value="'+id+'">'+videos[id]['title']+'</option>';
				}
				$('#video').append(res);
				$('#thumbnail').append(getSelectOptionsHTMLFromImagesList(opt[1][2]));
				$('#vdurl').append(getSelectOptionsHTMLFromImagesList(opt[1][3]));
				addVideos(opt[1][4]);
				videosNumber = opt[1][4].length;
				addVideosEventListeners();
			}
			if (opt[0] == 1 || opt[0] == 2) window.location.replace('.');
			if (opt[0] == 3) {
				if (opt[1].length < 3) $('#yt-videos-more').addClass('hidden');
				videosNumber += opt[1].length;
				addVideos(opt[1]);
				addVideosEventListeners();
			}
		}

		$('#modif-1-open-btn, #modif-1-close-btn').click(function(){$('#modif-1-open-btn, #modif-1-fld').toggleClass('hidden');});
		$('#ac').change(function(){
			var id = $(this).val();
			if (id == 0) {
				$('#ac-perso').val(0);
				$('#subscribers').val('');
				$('#ac-perso').removeClass('hidden');
			}
			else {
				$('#subscribers').val(accounts[id]['subscribers']);
				$('#ac-perso').addClass('hidden');
			}
		});
		$('#modif-1-send-btn').click(function(){
			var ac = $('#ac').val();
			var objToSend = {
				'ac' : ac,
				'subscribers' : $('#subscribers').val()
			};
			if (ac == 0) objToSend['perso'] = $('#ac-perso').val();
			server(1,objToSend);
		});

		$('#modif-2-open-btn, #modif-2-close-btn').click(function(){$('#modif-2-open-btn, #modif-2-fld').toggleClass('hidden');});
		$('#video').change(function(){
			var id = $(this).val();
			if (id == 0) {
				$('#title, #thumbnailurl, #views, #likes, #dislikes, #description').val('');
				$('#acvd, #thumbnail, #vdurl').val(0);
			}
			else {
				$('#acvd').val(videos[id]['ac']);
				$('#title').val(videos[id]['title']);
				if (isImgURL(videos[id]['thumbnail'])) {
					$('#thumbnail').val(0);
					$('#thumbnailurl').val(videos[id]['thumbnail']);
					$('#thumbnailurl').removeClass('hidden');
				}
				else {
					$('#thumbnail').val(videos[id]['thumbnail']);
					$('#thumbnailurl').val('');
					$('#thumbnailurl').addClass('hidden');
				}
				if (videos[id]['url'] !== null && !isURL(videos[id]['url'])) {
					$('#vdurl').val(videos[id]['url']);
					$('#vdurlurl').val('');
					$('#vdurlurl').addClass('hidden');
				}
				else {
					$('#vdurl').val(0);
					if (videos[id]['url'] !== null) $('#vdurlurl').val(videos[id]['url']);
					else $('#vdurlurl').val('');
					$('#vdurlurl').removeClass('hidden');
				}
				$('#views').val(videos[id]['views']);
				$('#likes').val(videos[id]['likes']);
				$('#dislikes').val(videos[id]['dislikes']);
				$('#description').val(videos[id]['description']);
			}
		});
		$('#thumbnail').change(function(){
			if ($(this).val() != 0) $('#thumbnailurl').addClass('hidden');
			else $('#thumbnailurl').removeClass('hidden');
		});
		$('#vdurl').change(function(){
			if ($(this).val() != 0) $('#vdurlurl').addClass('hidden');
			else $('#vdurlurl').removeClass('hidden');
		});
		$('#modif-2-send-btn').click(function(){
			var thumbnailVal = $('#thumbnail').val();
			var objToSend = {
				'video' : $('#video').val(),
				'acvd' : $('#acvd').val(),
				'title' : $('#title').val(),
				'views' : $('#views').val(),
				'likes' : $('#likes').val(),
				'dislikes' : $('#dislikes').val(),
				'description' : $('#description').val()
			};
			if (thumbnailVal == 0) objToSend['thumbnail'] = $('#thumbnailurl').val();
			else objToSend['thumbnail'] = thumbnailVal;
			if ($('#vdurlurl').val() != '') objToSend['url'] = $('#vdurlurl').val();
			if ($('#vdurl').val() != 0) objToSend['url'] = $('#vdurl').val();
			server(2,objToSend);
		});

		$('#yt-page-logo').click(function(){changeVideo(0);});
		$('#yt-videos-more').click(function(){server(3,{'from':videosNumber});});
		$('#yt-vd-play-dv').click(function(){
			$('#yt-vd-thumbnail, #yt-vd-play-dv, #yt-vd-videotag').toggleClass('hidden');
			$('#yt-vd-videotag')[0].play();
		});

		var accounts = {};
		var videos = {};
		var videosNumber;
		var ytVideo = 0;

		const ytURLReg = /^https:\/\/(www\.)?youtube\.(com|fr)\/watch\?v=/;

		serverComments(0);
	});
	</script>
</head>
<body>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../header.php'; ?>
	<div id="pg">
		<div id="yt-page-logo" class="flx flx-jc flx-ac pgtlt">
			<img src="/src/icons/menu/white/youtube-svgrepo-com.svg"/>
			<h1>YouTube</h1>
			<img src="/src/icons/menu/white/youtube-svgrepo-com.svg"/>
		</div>
		<div class="flx flx-jc"><button id="modif-1-open-btn">Gérer ses comptes</button></div>
		<div class="flx flx-jc">
			<fieldset id="modif-1-fld" class="fldct hidden">
				<legend>Comptes</legend>
				<div><select id="ac"><option value="0">-Nouveau compte-</option></select></div>
				<div><select id="ac-perso"><option value="0">-Perso-</option></select></div>
				<div>Abonnés : <input type="number" id="subscribers" placeholder="Abonnés"/></div>
				<div>
					<button id="modif-1-close-btn">Annuler</button>
					<button id="modif-1-send-btn">OK</button>
				</div>
			</fieldset>
		</div>
		<div class="flx flx-jc"><button id="modif-2-open-btn">Gérer ses vidéos</button></div>
		<div class="flx flx-jc">
			<fieldset id="modif-2-fld" class="fldct hidden">
				<legend>Vidéos</legend>
				<div>Vidéo : <select id="video"><option value="0">- Nouvelle vidéo -</option></select></div>
				<div>Posté par : <select id="acvd"><option value="0">-Compte-</option></select></div>
				<div>Titre : <input type="text" id="title" placeholder="Titre"/></div>
				<div><textarea id="description" class="maxedwidth" rows="5" placeholder="Description ..."></textarea></div>
				<div>
					<div>Miniature :</div>
					<div>
						<select id="thumbnail"><option value="0">-URL-</option></select><br/>
						<input type="text" id="thumbnailurl" placeholder="https://..."/>
					</div>
				</div>
				<div>
					<div>URL<br/>(optionnel) :</div>
					<div>
						<select id="vdurl"><option value="0">-URL-</option></select><br/>
						<input type="text" id="vdurlurl" placeholder="https://..."/>
					</div>
				</div>
				<div>Vues : <input type="number" id="views" placeholder="Vues"/></div>
				<div>Likes : <input type="number" id="likes" placeholder="Likes"/></div>
				<div>Dislikes : <input type="number" id="dislikes" placeholder="Dislikes"/></div>
				<div>
					<button id="modif-2-close-btn">Annuler</button>
					<button id="modif-2-send-btn">OK</button>
				</div>
			</fieldset>
		</div>
		<div id="yt-page">
			<div id="yt-video">
				<div id="yt-vd-player" class="thumbnail-16-9">
					<img id="yt-vd-thumbnail"/>
					<div id="yt-vd-play-dv" class="fillrelativeparent hidden"><div class="fill flx flx-jc flx-ac"><img src="/src/icons/menu/white/youtube-svgrepo-com.svg"/></div></div>
					<video id="yt-vd-videotag" class="hidden" controls>Sorry, your browser doesn\'t support embedded videos.</video>
					<iframe id="yt-vd-realplayer" frameborder="0"></iframe>
				</div>
				<div id="yt-vd-title" class="colored-text-container customemoji-container"></div>
				<div id="yt-vd-views-likes">
					<div class="yt-vd-views"><span id="yt-vd-views"></span> vues - <span id="yt-vd-since"></span></div>
					<div class="yt-vd-likesdislikes flx flx-ac">
						<div class="flx"><span id="yt-vd-likes"></span> <img src="/src/icons/force/youtube/368098.svg"></div>
						<div><div id="yd-vd-likesbar"></div></div>
						<div class="flx"><span id="yt-vd-dislikes"></span> <img src="/src/icons/force/youtube/red/368098.svg"></div>
					</div>
				</div>
				<div id="yt-vd-channel" class="flx flx-ac">
					<div><img class="pp"/></div>
					<div>
						<div id="yt-vd-channel-author" class="customemoji-container"></div>
						<div id="yt-vd-channel-subs"><span></span> abonnés</div>
					</div>
				</div>
				<div id="yt-vd-description" class="formated-text colored-text-container customemoji-container"></div>
				<div id="yt-vd-comments-dv"></div>
			</div>
			<div>
				<div id="yt-videos"></div>
				<div class="flx flx-jc"><button id="yt-videos-more">Plus</button></div>
			</div>
		</div>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/../footer.php'; ?>
	</div>
</body>
</html>
