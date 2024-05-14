<?php
session_start();
if (!isset($_SESSION['user'])) {
	header('Location: /?page='.urlencode($_SERVER['REQUEST_URI']));
	exit;
}
function processPOSTRequest($type,&$responseData) {
	if ($type == 1) {
		if (!isset($_POST['discussion']) || $_POST['discussion'] != intval($_POST['discussion']).'' || !isset($_POST['nom']) || $_POST['nom'] == '' || !isset($_POST['script']) || $_POST['script'] == '' || !isset($_POST['thumbnail']) || $_POST['thumbnail'] == '' || $_POST['thumbnail'] == '0') return -1;
		if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/medias/force/discussions/thumbnails/'.$_POST['thumbnail'])) return -1;
	}
	require $_SERVER['DOCUMENT_ROOT'].'/../lib/db.php';
	$db = new DB();
	if ($type == 0) {
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/os.php';
		$responseData = array($db->getDirect("SELECT * FROM `force-discussions`"),getFilesListFromFolder('/medias/force/discussions/thumbnails'));
	}
	if ($type == 1) {
		if ($_POST['discussion'] == 0) $db->req("INSERT INTO `force-discussions` (`nom`, `script`, `thumbnail`) VALUES (?, ?, ?)",[$_POST['nom'],$_POST['script'],$_POST['thumbnail']]);
		else $db->req("UPDATE `force-discussions` SET `nom`=?, `script`=?, `thumbnail`=? WHERE `force-discussions`.`id`=?",[$_POST['nom'],$_POST['script'],$_POST['thumbnail'],$_POST['discussion']]);
	}
	$db->close();
	return 0;
}
if (isset($_POST['type']) && in_array($_POST['type'],[0,1])) {
	$responseData = '';
	if (processPOSTRequest($_POST['type'],$responseData) == 0) echo json_encode([$_POST['type'],$responseData]);
	exit;
} ?>
<!DOCTYPE html>
<html>
<head>
	<title>Discussions - aventures.ddns.net</title>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../include-js.php'; ?>
	<style type="text/css">header {z-index: 100;}</style>
	<script type="text/javascript">
	$(document).ready(function(){
		// Thanks to csharptest.net and Sergey
		// https://stackoverflow.com/questions/1349404/generate-random-string-characters-in-javascript
		function makeid(length) {
			var result = '';
			var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
			var charactersLength = characters.length;
			for (var i = 0; i < length; i++) result += characters.charAt(Math.floor(Math.random()*charactersLength));
			return result;
		}

		function getSplitedStringLimitingOccurences(str,delim,limit){
			var realDelim = makeid(50);
			for (var i = 1; i < limit; i++) str = str.replace(delim,realDelim);
			return str.split(realDelim);
		}

		function setTransparency(element,opacity,transitionDuration='') {
			if (transitionDuration != '') transitionDuration += 's';
			$('#'+element).css({'opacity':opacity,'transition-duration':transitionDuration});
		}

		function waitUntilClickable(waitingTime,currentDiscussion) {
			setTimeout(function(){if (currentDiscussion == listeningDiscussionNumber) clickable = true;},waitingTime*1000);
		}

		function forwardActions() {
			clickable = false;
			actionNumber++;
			waitingTime = 0;
			$('.discussion-character').css('transition-duration','');
			for (i in script[actionNumber]) {
				if (script[actionNumber][i][0] == 'WAIT') waitingTime += parseFloat(script[actionNumber][i][1]);
				else playAction(script[actionNumber][i],listeningDiscussionNumber);
			}
			waitUntilClickable(waitingTime,listeningDiscussionNumber);
		}

		function playAction(action,currentDiscussion) {
			setTimeout(function(){
				if (currentDiscussion == listeningDiscussionNumber) {
					switch (action[0]) {
						case 'SET_BACKGROUND_IMAGE':
							$('#discussion').css({'background':'','background-color':'black'});
							if (action[1] !== undefined) $('#discussion').css({
								'background' : 'linear-gradient(-45deg,rgba(0,0,0,0.25),rgba(0,0,0,0.25)),url("/medias/force/discussions/background-images/'+action[1]+'")',
								'background-size' : 'cover',
								'background-position' : 'center'
							});
							break;
						case 'SET_BACKGROUND_MUSIC':
							$('#discussion-backgroundmusic')[0].pause();
							if (action[1] !== undefined) {
								$('#discussion-backgroundmusic').attr('src','/medias/force/discussions/background-musics/'+action[1]);
								var vol = 0.1;
								if (action[2] !== undefined) vol = parseFloat(action[2]);
								$('#discussion-backgroundmusic')[0].volume = vol;
								$('#discussion-backgroundmusic')[0].play();
							}
							break;
						case 'SET_BACKGROUND_MUSIC_VOLUME':
							$('#discussion-backgroundmusic')[0].volume = parseFloat(action[1]);
							break;
						case 'SET_BACKGROUND_IMAGE_CHANGE_COLOR':
							$('#discussion-front-filter').css({'background-color':action[1],'transition-duration':''});
							break;
						case 'MAKE_BACKGROUND_IMAGE_CHANGE':
							setTransparency('discussion-front-filter',1,1);
							setTimeout(function(){setTransparency('discussion-front-filter',0,1);},2000);
							break;
						case 'ADD_CHARACTER':
							$('#discussion').append('<div id="discussion-character-'+action[1]+'" class="discussion-character"><div class="fill"><img class="fill" src="/medias/force/discussions/characters/'+action[1]+'/normal.png"/></div></div>');
							characters[action[1]]['div'] = $('#discussion-character-'+action[1]);
							characters[action[1]]['img'] = $('#discussion-character-'+action[1]+' img');
							var char = characters[action[1]]['div'];
							var charImg = characters[action[1]]['img'];
							charImg.attr('char',action[1]);
							charImg.on('error',function(){$(this).attr('src','/medias/force/discussions/characters/'+$(this).attr('char')+'/normal.png');});
							if (action[2] === undefined) placeCharacter(char,'LEFT');
							else placeCharacter(char,action[2]);
							break;
						case 'PLACE_CHARACTER':
							if (action[3] === undefined) placeCharacter(characters[action[1]]['div'],action[2]);
							else placeCharacter(characters[action[1]]['div'],action[2],action[3]);
							break;
						case 'SET_CHARACTER_TRANSPARENCY':
							if (action[3] === undefined) setTransparency('discussion-character-'+action[1],action[2]);
							else setTransparency('discussion-character-'+action[1],action[2],action[3]);
							break;
						case 'CHANGE_EXPRESSION':
							characters[action[1]]['img'].attr('src','/medias/force/discussions/characters/'+action[1]+'/'+action[2]+'.png');
							break;
						case 'TURN_CHARACTER':
							var char = characters[action[1]]['div'];
							var charImg = characters[action[1]]['img'];
							char.css('transition-duration','');
							if (action[2] == 'LEFT') charImg.addClass('char-looking-left');
							else charImg.removeClass('char-looking-left');
							break;
						case 'REMOVE_CHARACTER':
							characters[action[1]]['div'].remove();
							break;
						case 'SPEECH':
							speechNumber++;
							$('#discussion-speechsnd')[0].pause();
							if (action[1] == 'EMPTY') $('#discussion-text-sender, #discussion-text').html('');
							else {
								$('#discussion-text-sender').html(characters[action[2]]['speechName']);
								$('#discussion-text').html('');
								speechText = action[3];
								speechTextLength = speechText.length;
								affSpeech(0,speechNumber);
								if (action[1] == 'SOUND') {
									speechSoundNumber++;
									$('#discussion-speechsnd').attr('src','/medias/force/discussions/speechs/'+speechsFolder+'/'+speechSoundNumber+'.wav');
									$('#discussion-speechsnd')[0].play();
								}
							}
							break;
						case 'MAKE_SOUND':
							$('#discussion').append('<audio class="maked-sound" src="/medias/force/discussions/sounds/'+action[1]+'"></audio>');
							var vol = 1;
							if (action[2] !== undefined) vol = parseFloat(action[2])
							$('audio.maked-sound:last')[0].volume = vol;
							$('audio.maked-sound:last')[0].play();
							break;
						case 'END':
							$(document)[0].exitFullscreen();
							initDiscussion();
							break;
					}
				}
			},waitingTime*1000);
		}

		function placeCharacter(char,place,transitionDuration='') {
			var charWidthPrc = parseFloat(char.width()/$('#discussion').width()*50);
			if (place == 'LEFT') place = -charWidthPrc;
			if (place == 'RIGHT') place = 100+charWidthPrc;
			if (transitionDuration != '') transitionDuration += 's';
			char.css({'transition-duration':transitionDuration,'left':place+'%'});
		}

		function affSpeech(i,speechn) {
			if (speechn == speechNumber) {
				$('#discussion-text').append(speechText[i]);
				if (i < speechTextLength) setTimeout(function(){affSpeech(i+1,speechn);},15);
			}
		}

		function initDiscussion() {
			listeningDiscussionNumber++;
			actionNumber = -1;
			speechNumber = 0;
			speechSoundNumber = 0;
			clickable = false;
			$('#discussion audio').each(function(){$(this)[0].pause();});
			$('#discussion-text-sender, #discussion-text').html('');
			$('#discussion .discussion-character').remove();
			var txtScript = discussions[choosenDiscussion]['script'].split('\n===\n');
			characters = {};
			txtScript[0] = txtScript[0].replace(/\n{2,}/,'\n').split('\n');
			for (i in txtScript[0]) {
				var arr = getSplitedStringLimitingOccurences(txtScript[0][i],/ *: */,2);
				characters[arr[0]] = {'speechName':arr[1]};
			}
			script = [];
			txtScript[1] = txtScript[1].replace(/\n{3,}/,'\n\n').split('\n\n');
			for (i in txtScript[1]) {
				txtScript[1][i] = txtScript[1][i].split('\n');
				script.push([]);
				for (j in txtScript[1][i]) {
					if (txtScript[1][i][j].search(/^SPEECH/) != -1) script[script.length-1].push(getSplitedStringLimitingOccurences(txtScript[1][i][j],/ *: */,4));
					else script[script.length-1].push(txtScript[1][i][j].split(/ *: */));
				}
			}
			speechsFolder = txtScript[2].replace(/[\t\n]/g,'');
			$('#discussion-title > div, #discussion-playdv-title').html(discussions[choosenDiscussion]['nom'])
			$('#discussion-thumbnail').css('background','linear-gradient(-45deg,rgba(0,0,0,0.25),rgba(0,0,0,0.25)),url("/medias/force/discussions/thumbnails/'+discussions[choosenDiscussion]['thumbnail']+'")');
			$('#discussion-thumbnail-play').css('background','url("/medias/force/discussions/thumbnails/'+discussions[choosenDiscussion]['thumbnail']+'")');
			$('#discussion-thumbnail, #discussion-thumbnail-play').css({
				'background-size' : 'cover',
				'background-position' : 'center'
			});
			$('#discussion-text-box').addClass('hidden');
			$('#discussion-container, #discussion-playdv').removeClass('hidden');
			$('#discussion-container')[0].scrollIntoView({block:"center"});
		}

		serverResponse = function(opt) {
			if (opt[0] == 0) {
				for (i in opt[1][0]) {
					discussions[opt[1][0][i]['id']] = opt[1][0][i];
					$('#discussionsl').append('<option value="'+opt[1][0][i]['id']+'">'+opt[1][0][i]['nom'] +'</option>');
					$('#discussions').append('<div discussion="'+opt[1][0][i]['id']+'"><img class="thumbnail-16-9" src="/medias/force/discussions/thumbnails/'+opt[1][0][i]['thumbnail']+'"/>'+opt[1][0][i]['nom']+'</div>');
				}
				$('#discussions > div').click(function(){
					choosenDiscussion = $(this).attr('discussion');
					initDiscussion();
				});
				$('#thumbnail').append(getSelectOptionsHTMLFromImagesList(opt[1][1]));
			}
			else window.location.replace('.');
		}

		$('#modif-open-btn, #modif-close-btn').click(function(){$('#modif-open-btn, #modif-fld').toggleClass('hidden');});
		$('#discussionsl').change(function(){
			var id = $(this).val();
			if (id == 0) {
				$('#nom, #script').val('');
				$('#thumbnail').val(0);
			}
			else {
				$('#nom').val(discussions[id]['nom']);
				$('#script').val(discussions[id]['script']);
				$('#thumbnail').val(discussions[id]['thumbnail']);
			}
		});
		$('#modif-send-btn').click(function(){
			server(1,{
				'discussion' : $('#discussionsl').val(),
				'nom' : $('#nom').val(),
				'script' : $('#script').val(),
				'thumbnail' : $('#thumbnail').val()
			});
		});

		$('#discussion-fullscreen-btn').click(function(){$('#discussion-container')[0].requestFullscreen();});
		$('#discussion-clickdv').click(function(){
			$('#discussion-clicksnd')[0].play();
			if (clickable) forwardActions();
		});
		$('#discussion-playdv').click(function(){
			$('#discussion-text-box, #discussion-title').removeClass('hidden');
			$(this).addClass('hidden');
			inDiscussion = true;
			forwardActions();
			$('#discussion-front-filter').css('background-color','white');
			setTransparency('discussion-front-filter',0.25);
			setTimeout(function(){
				setTransparency('discussion-front-filter',0.5,2);
				$('#discussion-titlesnd')[0].play();
				$('#discussion-title > div').css('opacity',1);
				setTimeout(function(){
					$('#discussion-title > div').css({'opacity':'','transition-duration':'1s'});
					setTransparency('discussion-front-filter',0,1);
					setTimeout(function(){
						$('#discussion-title').addClass('hidden');
						$('#discussion-front-filter').css({'background-color':'black','transition-duration':''});
					},1000);
				},3000);
			},100);
		});

		var discussions = {};

		var choosenDiscussion;
		var listeningDiscussionNumber = 0;
		var actionNumber = -1;
		var speechText;
		var speechTextLength;
		var speechNumber = 0;
		var speechSoundNumber = 0;
		var waitingTime;
		var clickable = false;
		var characters;
		var script;
		var speechsFolder;

		server(0);
	});
	</script>
</head>
<body>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../header.php'; ?>
	<div id="pg">
		<div class="flx flx-jc flx-ac pgtlt">
			<img src="/src/icons/menu/white/discussion.svg"/>
			<h1>Discussions</h1>
			<img src="/src/icons/menu/white/discussion.svg"/>
		</div>
		<div class="flx flx-jc">
			<button id="modif-open-btn">Gérer les discussions</button>
			<fieldset id="modif-fld" class="fldct hidden">
				<legend>Discussion</legend>
				<div><select id="discussionsl"><option value="0">-Nouvelle discussion-</option></select></div>
				<div><input type="text" id="nom" placeholder="Nom de la discussion ..."/></div>
				<div><textarea id="script" class="maxedwidth" placeholder="Script ..." rows="5"></textarea></div>
				<div>Miniature : <select id="thumbnail"><option value="0">-</option></select></div>
				<div><button id="modif-close-btn">Annuler</button> <button id="modif-send-btn">OK</button></div>
			</fieldset>
		</div>
		<div id="discussions" class="flx flx-jc flx-ww"></div>
		<div id="discussion-container" class="hidden">
			<div id="discussion-thumbnail" class="fillrelativeparent"></div>
			<div id="discussion-centering" class="fillrelativeparent flx flx-dc flx-jc">
				<div id="discussion">
					<audio id="discussion-backgroundmusic"></audio>
					<audio id="discussion-speechsnd"></audio>
					<audio id="discussion-titlesnd" src="/medias/force/discussions/sounds/beginning.wav"></audio>
					<audio id="discussion-clicksnd" src="/medias/force/discussions/sounds/click.wav"></audio>
					<div id="discussion-back-filter" class="fillrelativeparent"></div>
					<div id="discussion-text-box">
						<div id="discussion-text-sender"></div>
						<div id="discussion-text"></div>
					</div>
					<div id="discussion-front-filter" class="fillrelativeparent"></div>
					<div id="discussion-title" class="fillrelativeparent flx flx-jc flx-ac"><div></div></div>
					<div id="discussion-clickdv" class="fillrelativeparent"></div>
					<div id="discussion-playdv" class="fillrelativeparent">
						<div class="fill">
							<div id="discussion-thumbnail-play" class="fillrelativeparent"></div>
							<div class="fillrelativeparent flx flx-jc flx-ac"><img src="/src/icons/force/discussions/play-button.svg"/></div>
							<div id="discussion-playdv-title"></div>
						</div>
					</div>
				</div>
			</div>
			<div id="discussion-fullscreen-btn" class="buttonlike buttonlike-fullscreen"></div>
		</div>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/../footer.php'; ?>
	</div>
	<textarea id="discussion-script-txt" class="hidden"></textarea>
</body>
</html>
