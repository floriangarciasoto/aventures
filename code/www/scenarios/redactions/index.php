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
	if ($type == 1) {
		if (!isset($_POST['redaction']) || $_POST['redaction'] != intval($_POST['redaction']).'' || !isset($_POST['nom']) || $_POST['nom'] == '' || !isset($_POST['text']) || $_POST['text'] == '' || !isset($_POST['chapter']) || $_POST['chapter'] != intval($_POST['chapter']).'') return -1;
	}
	require $_SERVER['DOCUMENT_ROOT'].'/../lib/db.php';
	$db = new DB();
	if ($type == 0) {
		$responseData = $db->getDirect("SELECT `scenarios-redactions`.`id`, `author`, `username`, `nom`, `text`, `chapter` FROM `scenarios-redactions`, `logins` WHERE `scenarios-redactions`.`author`=`logins`.`id` ORDER BY `order`");
		for ($i=0; $i < sizeof($responseData); $i++) {
			$text = preg_split('/\r?\n===\r?\n/',$responseData[$i]['text']);
			$responseData[$i]['text'] = array($text[0]);
			if ($responseData[$i]['author'] == $_SESSION['user']['id'] && sizeof($text) > 1) array_push($responseData[$i]['text'],$text[1]);
		}
	}
	if ($type == 1) {
		if ($_POST['redaction'] == 0) {
			$order = $db->getValueDirect("SELECT MAX(`order`)+1 FROM `scenarios-redactions`");
			$db->req("INSERT INTO `scenarios-redactions` (`order`, `nom`, `author`, `text`, `chapter`) VALUES (?, ?, ?, ?, ?)",[$order,$_POST['nom'],$_SESSION['user']['id'],$_POST['text'],$_POST['chapter']]);
		}
		else {
			if ($db->getValue("SELECT `author` FROM `scenarios-redactions` WHERE `id`=?",[$_POST['redaction']]) != $_SESSION['user']['id']) return -1;
			$db->req("UPDATE `scenarios-redactions` SET `nom`=?, `text`=?, `chapter`=? WHERE `scenarios-redactions`.`id`=? AND `author`=?",[$_POST['nom'],$_POST['text'],$_POST['chapter'],$_POST['redaction'],$_SESSION['user']['id']]);
		}
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
	<title>Rédactions - aventures.ddns.net</title>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../include-js.php'; ?>
	<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
	<script type="text/javascript">
	$(document).ready(function(){
		function getTitleSize(line) {
			if (line.search(/^#{1,} /) == -1) return 0;
			for (n in line) if (line[n] != '#') break;
			return parseInt(n);
		}

		function getHTMLFromChapterText(redactionIndex,startingIndex=0,currentTitleSize=0,chapterPath='') {
			var res = '';
			var noMoreText = false;
			var currentlyHiddenText = false;
			var n = 0;
			for (var i = startingIndex; i < redactions[redactionIndex]['splitedText'].length; i++) {
				if (redactions[redactionIndex]['splitedText'][i].search(/^#{1,} /) == -1) {
					if (!noMoreText) {
						if (redactions[redactionIndex]['splitedText'][i] == '===') {
							currentlyHiddenText = true;
							redactions[redactionIndex]['isNowHidden'] = true;
							var hiddenText = '';
							continue;
						}
						else {
							if (currentlyHiddenText) hiddenText += redactions[redactionIndex]['splitedText'][i]+'\n';
							else res += redactions[redactionIndex]['splitedText'][i]+'\n';
						}
					}
				}
				else {
					if (currentlyHiddenText && hiddenText.replace(/\t|\n/g,'') != '') return marked.parse(res)+'<span class="hidden-text">'+marked.parse(hiddenText)+'</span>';
					var titleSize = getTitleSize(redactions[redactionIndex]['splitedText'][i]);
					if (titleSize == currentTitleSize+1) {
						n += 1;
						res += '<div class="chapter';
						if (redactions[redactionIndex]['isNowHidden']) res += ' hidden-text';
						var nextChapterPath = chapterPath+'-'+n;
						redactions[redactionIndex]['chapters'].push(nextChapterPath);
						res += ' chapter'+nextChapterPath+'"><div class="flx flx-ac"><img src="/src/icons/right-arrow-svgrepo-com.svg"/><div>'+marked.parse(redactions[redactionIndex]['splitedText'][i])+'</div></div><div>'+getHTMLFromChapterText(redactionIndex,i+2,titleSize,nextChapterPath)+'</div></div>';
						noMoreText = true;
					}
					else if (titleSize <= currentTitleSize) break;
				}
			}
			return marked.parse(res);
		}

		function toggleChapterFill(chapterDv,fillall=false) {
			if (chapterDv.hasClass('filled')) {
				chapterDv.removeClass('filled');
				chapterDv.find('.chapter').removeClass('filled');
			}
			else {
				chapterDv.addClass('filled');
				if (fillall) chapterDv.find('.chapter').addClass('filled');
				focusChapter(chapterDv);
			}
		}

		function focusChapter(chapterDv) {
			// Thanks to Arseniy-II
			// https://stackoverflow.com/questions/24665602/scrollintoview-scrolls-just-too-far
			window.scrollTo({
				top: chapterDv[0].getBoundingClientRect().top + window.pageYOffset - ($('header')[0].offsetHeight),
				behavior: 'smooth'
			});
		}

		serverResponse = function(opt) {
			if (opt[0] == 0) {
				var order = opt[1].map(redaction => redaction['id']);
				for (i in opt[1]) redactions[opt[1][i]['id']] = opt[1][i];
				var res = '';
				for (i in order) {
					if (redactions[order[i]]['author'] == myId) $('#redaction').append('<option value="'+order[i]+'">'+redactions[order[i]]['nom']+'</option>');
					res += '<div id="redaction-'+order[i]+'" class="redaction chapter" redactionindex="'+order[i]+'"><div class="flx flx-ac"><img src="/src/icons/right-arrow-svgrepo-com.svg"/><div>Rédaction de '+redactions[order[i]]['username']+' s\'intitulant « '+redactions[order[i]]['nom']+' »</div></div><div class="md-text">';
					if (redactions[order[i]]['chapter'] == 0) {
						res += marked.parse(redactions[order[i]]['text'][0]);
						if (redactions[order[i]]['text'].length > 1) res += '<span class="hidden-text">'+marked.parse(redactions[order[i]]['text'][1])+'</span>';
					}
					else {
						redactions[order[i]]['chapters'] = [];
						redactions[order[i]]['isNowHidden'] = false;
						var splitedTextBegin = '';
						var splitedText = redactions[order[i]]['text'][0].replace('#','===SPLIT===#').split('===SPLIT===');
						if (splitedText.length > 1) {
							splitedTextBegin = splitedText[0];
							splitedText = splitedText[1];
						}
						else splitedText = splitedText[0];
						if (redactions[order[i]]['text'].length > 1) splitedText += '\n===\n'+redactions[order[i]]['text'][1];
						redactions[order[i]]['splitedText'] = splitedText.split('\n');
						var contents = '';
						var isNowHiddenChapter = false;
						var n = -1;
						for (line in redactions[order[i]]['splitedText']) {
							if (redactions[order[i]]['splitedText'][line] == '===') {
								isNowHiddenChapter = true;
								continue;
							}
							if (redactions[order[i]]['splitedText'][line].search(/^#{1,} /) != -1) {
								n += 1;
								var tlt = redactions[order[i]]['splitedText'][line].replace('# ','- ').replace(/#/g,'\t');
								var spanHiddenPart = '';
								if (isNowHiddenChapter) spanHiddenPart += ' class="hidden-text"';
								contents += tlt.replace('- ','- <span'+spanHiddenPart+' chapterindex="'+n+'">')+'</span>\n';
							}
						}
						if (splitedTextBegin != '') res += marked.parse(splitedTextBegin);
						res += '<div class="chapter chapter-contents"><div class="flx flx-ac"><img src="/src/icons/right-arrow-svgrepo-com.svg"/><div><strong><em>Sommaire</em></strong></div></div><div class="contents">'+marked.parse(contents)+'</div></div>'+getHTMLFromChapterText(order[i]);
					}
					res += '</div></div>';
				}
				$('#redactions').html(res);
				$('#redactions input[type=checkbox]').removeAttr('disabled');
				$('#redactions div.chapter > div:first-child > div').click(function(){toggleChapterFill($(this.parentNode.parentNode));});
				$('#redactions div.chapter > div:first-child > img').click(function(){toggleChapterFill($(this.parentNode.parentNode),true);});
				$('#redactions div.contents span').click(function(){
					var redactionIndex = $(this).closest('.redaction').attr('redactionindex');
					var targetChapter = redactions[redactionIndex]['chapters'][$(this).attr('chapterindex')];
					var allTargetChapters = [];
					while (targetChapter != '') {
						allTargetChapters.push(targetChapter);
						targetChapter = targetChapter.replace(/-[0-9]*$/,'');
					}
					var allTargetChaptersSelector = '';
					for (i in allTargetChapters) allTargetChaptersSelector += ', #redaction-'+redactionIndex+' .chapter'+allTargetChapters[i];
					allTargetChaptersSelector = allTargetChaptersSelector.replace(/^, /,'');
					$(allTargetChaptersSelector).addClass('filled');
					focusChapter($('#redaction-'+redactionIndex+' .chapter'+allTargetChapters[0]));
				});
			}
			else window.location.replace('.');
		}

		$('#modif-open-btn, #modif-close-btn').click(function(){$('#modif-open-btn, #modif-fld').toggleClass('hidden');});
		$('#redaction').change(function(){
			var id = $(this).val();
			if (id == 0) {
				$('#nom, #text').val('');
				$('#chapter').prop('checked',false);
			}
			else {
				$('#nom').val(redactions[id]['nom']);
				var txt = redactions[id]['text'][0];
				if (redactions[id]['text'].length > 1) txt += "\n===\n"+redactions[id]['text'][1];
				$('#text').val(txt);
				$('#chapter').prop('checked',redactions[id]['chapter'] == 1);
			}
		});
		$('#modif-send-btn').click(function(){
			var chapter = 0;
			if ($('#chapter').is(':checked')) chapter = 1;
			server(1,{
				'redaction' : $('#redaction').val(),
				'nom' : $('#nom').val(),
				'text' : $('#text').val(),
				'chapter' : chapter
			});
		});

		var myId = <?php echo $_SESSION['user']['id'] ?>;
		var redactions = {};

		server(0);
	});
	</script>
</head>
<body>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../header.php'; ?>
	<div id="pg">
		<div class="flx flx-jc flx-ac pgtlt">
			<img src="/src/icons/menu/white/7752404.svg"/>
			<h1>Rédactions</h1>
			<img src="/src/icons/menu/white/7752404.svg"/>
		</div>
		<div class="flx flx-jc">
			<button id="modif-open-btn">Gérer ses rédactions</button>
			<fieldset id="modif-fld" class="fldct hidden">
				<legend>Rédaction</legend>
				<div><select id="redaction"><option value="0">-Nouvelle rédaction-</option></select></div>
				<div><input type="text" id="nom" placeholder="Nom de la rédaction ..."/></div>
				<div><textarea id="text" class="maxedwidth" placeholder="Contenu à coller ici ...&#10;&#10;Partie visible&#10;&#10;===&#10;&#10;Partie cachée (optionnelle)" rows="5"></textarea></div>
				<div><input type="checkbox" id="chapter" name="chapter"/><label for="chapter">Chapitrée</label></div>
				<div><button id="modif-close-btn">Annuler</button> <button id="modif-send-btn">OK</button></div>
			</fieldset>
		</div>
		<div id="redactions"></div>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/../footer.php'; ?>
	</div>
</body>
</html>
