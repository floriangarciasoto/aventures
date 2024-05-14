<?php
session_start();
if (!isset($_SESSION['user'])) {
	header('Location: /?page='.urlencode($_SERVER['REQUEST_URI']));
	exit;
}
function processPOSTRequest($type,&$responseData) {
	if ($type == 0) {
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/misc.php';
		$responseData = getStringFromTextData('force/epreuves');
	}
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
	<title>Epreuves - aventures.ddns.net</title>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../include-js.php'; ?>
	<script type="text/javascript">
	$(document).ready(function(){
		serverResponse = function(opt) {
			if (opt[0] == 0) {
				var txt =  opt[1].split(/\r?\n/);
				var obj = [];
				var pass = 0;

				for (i in txt) {
					i = parseInt(i);
					if (pass > 0) {
						pass--;
						continue;
					}
					if (txt[i][0] == '#' && txt[i][1] == ' ') {
						obj.push([txt[i].replace('# ',''),txt[i+1],[]]);
						pass = 1;
						continue;
					}
					if (txt[i][0] == '#' && txt[i][1] == '#') {
						obj[obj.length-1][2].push([txt[i].replace('## ',''),txt[i+1],[]]);
						pass = 1;
						continue;
					}
					if (txt[i][0] == '|') {
						n = i;
						ar = [];
						while (txt[n][0] == '|') {
							ar.push(txt[n].replace(/\| {0,1}/,''));
							n++;
							pass++;
						}
						obj[obj.length-1][2][obj[obj.length-1][2].length-1][2].push(ar);
					}
				}

				var res = '';
				for (i in obj) {
					res += '<h2>'+obj[i][0]+'</h2>';
					for (j in obj[i][2]) {
						res += '<div i="'+i+'" j="'+j+'"><h3';
						var stats = [0,0,false,false];
						var cl = false;
						for (k in obj[i][2][j][2]) {
							if (obj[i][2][j][2][k][0][0] == '+' && obj[i][2][j][2][k][0][2] == '+') stats[0]++;
							if (obj[i][2][j][2][k][0][0] == '+') stats[1]++;
							if (obj[i][2][j][2][k][0][0] == '?') stats[2] = true;
							if (obj[i][2][j][2][k][0][0] == '!') stats[3] = true;
						}
						if (stats[1] >= 15) cl =  ' style="color: #0ff;"';
						if (stats[0] >= 15) cl =  ' style="color: #0e0;"';
						if (stats[2]) cl =  ' style="color: yellow;"';
						if (stats[3]) cl =  ' style="color: red;"';
						if (cl) res += cl;
						res += '>'+obj[i][2][j][0]+'</h3><div></div></div>';
					}
				}
				$('#epreuves').html(res);

				$('#epreuves h3').click(function(){
					var epreuvesDv = $(this.parentNode);
					epreuvesDv.toggleClass('filled');
					if (epreuvesDv.hasClass('filled')) {
						var res = '';
						var i = epreuvesDv.attr('i');
						var j = epreuvesDv.attr('j');
						for (k in obj[i][2][j][2]) {
							res += '<div class="epreuve';
							if (obj[i][2][j][2][k][0][0] == '+') {
								if (obj[i][2][j][2][k][0][2] == '-') res += ' epreuvevdwt';
								else res += ' epreuvewin';
							}
							if (obj[i][2][j][2][k][0][0] == '?') res += ' epreuveval';
							if (obj[i][2][j][2][k][0][0] == '!') res += ' epreuvepb';
							res += '"><div class="epreuvenum';
							res += '">Epreuve N°'+(parseInt(k)+1)+'</div></hr><div class="epreuvetxt"><div><b>'+obj[i][2][j][2][k][1]+'</b><span class="epreuvedetails">';
							for (var l = 2; l < obj[i][2][j][2][k].length; l++) {
								if (obj[i][2][j][2][k][0][0] == '!' && l == obj[i][2][j][2][k].length-1) res += '<br/><b>Problème : '+obj[i][2][j][2][k][l]+'</b>';
								else {
									if ((obj[i][2][j][2][k][l]).search(/^No: /) != -1) res += '<span style="text-decoration: line-through;"><br/>'+obj[i][2][j][2][k][l].replace(/^No: /,'')+'</span>';
									else res += '<br/>'+obj[i][2][j][2][k][l];
								}
							}
							res += '</span></div>';
							if (obj[i][2][j][2][k][0][2] == '+') {
								var vd = 'SkyEp-'+obj[i][1]+'-'+obj[i][2][j][1]+'-'+(parseInt(k)+1)+'.mp4';
								if (obj[i][2][j][2][k][0].replace(/^.{7}/,'') != '') vd = obj[i][2][j][2][k][0].replace(/^.{8}/,'')+'.mp4';
								res += '<video controls><source src="/medias/force/epreuves/videos/'+vd+'"/>Sorry, your browser doesn\'t support embedded videos.</video><a href="/medias/force/epreuves/videos/'+vd+'"><i>Voir la vidéo à part</i></a>';
							}
							res += '<div class="epreuvesspecs flx flx-je flx-ac">';
							// if (obj[i][2][j][2][k][0][2] == '+') res += '<div><img src="/src/icons/force/epreuves/video-player-iconsrepo-com.svg"/> Filmé</div>';
							if (obj[i][2][j][2][k][0][4] == '+') res += '<div class="flx flx-ac"><img src="/src/icons/force/epreuves/no-console.png"/> Pas de console</div>';
							if (obj[i][2][j][2][k][0][6] == '+') res += '<div class="flx flx-ac"><img src="/src/icons/force/epreuves/fast-forward-iconsrepo-com.svg"/> Continuité</div>';
							res += '</div></div></div>';
						}
						epreuvesDv.find('div:nth-child(2)').html(res);

					}
					else epreuvesDv.find('div:nth-child(2)').html('');
				});
			}
		};

		server(0);
	});
	</script>
</head>
<body>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../header.php'; ?>
	<div id="pg">
		<div class="flx flx-jc flx-ac pgtlt">
			<img src="/src/icons/menu/white/check-list-svgrepo-com.svg"/>
			<h1>Epreuves</h1>
			<img src="/src/icons/menu/white/check-list-svgrepo-com.svg"/>
		</div>
		<div id="epreuves"></div>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/../footer.php'; ?>
	</div>
</body>
</html>
