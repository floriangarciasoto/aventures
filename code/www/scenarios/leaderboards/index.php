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
	require $_SERVER['DOCUMENT_ROOT'].'/../lib/db.php';
	$db = new DB();
	if ($type == 0) {
		$responseData = $db->getDirect("SELECT * FROM `force-leaderboards` ORDER BY `order`");
		for ($i=0; $i < sizeof($responseData); $i++) {
			if (preg_match('/SELECT[^\n]*/',$responseData[$i]['content'],$matches)) $responseData[$i]['select'] = $db->getDirect($matches[0]);
			$responseData[$i]['content'] = preg_replace('/\r\nSELECT[^\r\n]*/','',$responseData[$i]['content']);
		}
	}
	$db->close();
	return 0;
}
if (isset($_POST['type']) && in_array($_POST['type'],[0])) {
	$responseData = '';
	if (processPOSTRequest($_POST['type'],$responseData) == 0) echo json_encode([$_POST['type'],$responseData]);
	exit;
} ?>
<!DOCTYPE html>
<html>
<head>
	<title>Classements - aventures.ddns.net</title>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../include-js.php'; ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js" integrity="sha512-+H4iLjY3JsKiF2V6N366in5IQHj2uEsGV7Pp/GRcm0fn76aPAk5V8xB6n8fQhhSonTqTXs/klFz4D0GIn6Br9g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/fr.min.js" integrity="sha512-RAt2+PIRwJiyjWpzvvhKAG2LEdPpQhTgWfbEkFDCo8wC4rFYh5GQzJBVIFDswwaEDEYX16GEE/4fpeDNr7OIZw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script type="text/javascript" src="/src/lib/comments.js"></script>
	<script type="text/javascript">
	$(document).ready(function(){
		serverResponse = function(opt) {
			if (opt[0] == 0) {
				var res = '';
				var id;
				var headers;
				var rows;
				for (i in opt[1]) {
					id = opt[1][i]['id'];
					headers = opt[1][i]['content'].split(/\r\n/)[0].split(',');
					for (j in headers) headers[j] = headers[j].split(':');
					res += '<div id="ldb-'+id+'" class="section-style-1 flx flx-dc flx-ac"><div class="table-style-1-title">'+opt[1][i]['name']+'</div><table class="table-style-1 table-style-1-not-dynamic hide-scrollbar">';
					if (opt[1][i]['select'] === undefined) {
						var content = opt[1][i]['content'].split(/\r\n/);
						content.shift();
						rows = [];
						var row;
						for (j in content) {
							if (content[j] == '') continue;
							content[j] = content[j].split(',');
							row = {};
							for (k in content[j]) {
								if (k == content[j].length-1) row['n'] = content[j][k];
								else row['data-'+k] = content[j][k];
							}
							rows.push(row);
						}
					}
					else rows = opt[1][i]['select'];
					var rank = 0;
					var realRank = 1;
					for (j in rows) {
						rank++;
						if (j > 0 && rows[j-1]['n'] != rows[j]['n']) realRank = rank;
						res += '<tr><td>#'+realRank;
						var n = -1;
						var header;
						for (k in rows[j]) {
							n++;
							header = '';
							if (headers[n] !== undefined) header = headers[n][0];
							switch (header) {
								case 'perso':
									res += '<td class="table-style-1-pp"><img src="'+addPathPartIfNotURL('/medias/pps/',persos[rows[j][k]]['pp'])+'"><td class="customemoji-container">'+getFormatedPersoNameByID(rows[j][k]);
									break;
								case 'img':
									res += '<td class="table-style-1-img';
									if (headers[n][2] !== undefined) res += ' table-style-1-img-'+headers[n][2];
									res += '"><img src="'+addPathPartIfNotURL('/medias/'+headers[n][1],rows[j][k])+'">';
									break;
								case 'replace':
									res += '<td class="colored-text-container customemoji-container">'+getReplacedHashtagsWithHTMLTags(replaceBulk(getEscapedLineStr(rows[j][k]),searchArrayOnName,replaceArrayOnName));
									break;
								default:
									res += '<td>'+rows[j][k];
									break;
							}
						}
					}
					res += '</table><div id="ldb-comments-'+id+'"></div></div>';
				}
				$('#ldbs').html(res);
				for (i in opt[1]) prepareComments('ldb-comments-'+opt[1][i]['id'],2,opt[1][i]['id'],0);
				addShowCommentsEventListeners();
			}
		};

		serverComments(0);
	});
	</script>
</head>
<body>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../header.php'; ?>
	<div id="pg">
		<div class="flx flx-jc flx-ac pgtlt">
			<img src="/src/icons/menu/white/podium-iconsrepo-com.svg"/>
			<h1>Classements</h1>
			<img src="/src/icons/menu/white/podium-iconsrepo-com.svg"/>
		</div>
		<div id="ldbs-bkg-mus-dv" class="flx flx-dc flx-ac"><div>Musique de fond</div><audio id="ldbs-bkg-mus" src="/medias/force/leaderboards/Naruto Shippūden_ Gekitō Ninja Taisen! EX ‒ _Results_ [1080p60].m4a" controls loop></audio></div>
		<div id="ldbs"></div>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/../footer.php'; ?>
	</div>
</body>
</html>
