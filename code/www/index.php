<?php
session_start();
if (isset($_SESSION['user'])) {
	if (isset($_POST['type']) && in_array($_POST['type'],[0,1])) {
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/persos.php';
		$responseData = '';
		if (processPOSTRequest($_POST['type'],$responseData) == 0) echo json_encode([$_POST['type'],$responseData]);
		exit;
	}
	if (isset($_GET['logout'])) {
		session_start();
		session_destroy();
		header('Location: /');
		exit;
	} ?>
<!DOCTYPE html>
<html>
<head>
	<title>Acceuil - aventures.ddns.net</title>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../include-js.php'; ?>
	<style type="text/css"><?php echo $_SESSION['menu']['style']; ?></style>
	<script type="text/javascript">
	$(document).ready(function(){
		serverResponse = function(opt) {
			if (opt[0] == 0) {
				var id, res = '';
				for (i in opt[1][0]) {
					id = opt[1][0][i]['id'];
					persos[id] = opt[1][0][i];
					res += '<option value="'+id+'">'+persos[id]['name']+'</option>';
				}
				$('#perso').append(res);
				$('#pp').append(getSelectOptionsHTMLFromImagesList(getPpsPathsListFromObj(opt[1][1])));
				imCommentsAdmin = opt[1][2];
				if (imCommentsAdmin) $('#certified').removeClass('hidden');
			}
			else window.location.replace('.');
		};

		$('#modif-1-open-btn, #modif-1-close-btn').click(function(){
			if (!persosAreManaged) {
				server(0);
				persosAreManaged = true;
			}
		});
		$('#modif-1-open-btn, #modif-1-close-btn').click(function(){$('#modif-1-open-btn, #modif-1-fld').toggleClass('hidden');});
		$('#perso').change(function(){
			var id = $(this).val();
			if (id == 0) {
				$('#name, #pp-url').val('');
				$('#pp').val(0);
				$('#pp-url').removeClass('hidden');
				$('#pp-preview').removeAttr('src');
				if (imCommentsAdmin) {
					$('#certified').removeClass('hidden');
					$('#certified > input').prop('checked',false);
				}
			}
			else {
				$('#name').val(persos[id]['name']);
				if (isImgURL(persos[id]['pp'])) {
					$('#pp').val(0);
					$('#pp-url').val(persos[id]['pp']);
					$('#pp-url').removeClass('hidden');
					$('#pp-preview').attr('src',persos[id]['pp']);
				}
				else {
					$('#pp').val(persos[id]['pp']);
					$('#pp-url').val('');
					$('#pp-url').addClass('hidden');
					$('#pp-preview').attr('src','/medias/pps/'+persos[id]['pp']);
				}
				if (imCommentsAdmin) $('#certified').addClass('hidden');
			}
		});
		$('#pp').change(function(){
			if ($(this).val() != 0) $('#pp-url').addClass('hidden');
			else $('#pp-url').removeClass('hidden');
		});
		$('#pp, #pp-url').change(function(){
			var ppVal = $('#pp').val();
			var ppURLVal = $('#pp-url').val();
			var $ppPreview = $('#pp-preview');
			if (ppVal == 0) {
				if (ppURLVal != '' && isImgURL(ppURLVal)) $ppPreview.attr('src',ppURLVal);
				else $ppPreview.removeAttr('src');
			}
			else $ppPreview.attr('src','/medias/pps/'+ppVal);
		});
		$('#modif-1-send-btn').click(function(){
			var ppVal = $('#pp').val();
			var objToSend = {
				'perso' : $('#perso').val(),
				'name' : $('#name').val()
			};
			if (ppVal == 0) objToSend['pp'] = $('#pp-url').val();
			else objToSend['pp'] = ppVal;
			if (objToSend['perso'] == 0 && $('#certified > input').is(':checked')) objToSend['certified'] = '';
			server(1,objToSend);
		});

		var persos = {};
		var persosAreManaged = false;
		var imCommentsAdmin;
	});
	</script>
</head>
<body>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../header.php'; ?>
	<div id="pg">
		<div id="sitebanner" class="flx flx-jc">
			<img src="/medias/main-logo.png"/>
			<a href="about" title="À propos"><button>?</button></a>
		</div>
		<div class="flx flx-jc"><button id="modif-1-open-btn">Gérer ses persos</button></div>
		<div class="flx flx-jc">
			<fieldset id="modif-1-fld" class="fldct hidden">
				<legend>Comptes</legend>
				<div><select id="perso"><option value="0">-Nouveau perso-</option></select></div>
				<div><input type="text" id="name" placeholder="Nom du perso ..."/></div>
				<div>
					<div><img id="pp-preview" class="pp"></div>
					<div>
						<select id="pp"><option value="0">-URL-</option></select><br/>
						<input type="text" id="pp-url" placeholder="https://..."/>
					</div>
				</div>
				<div><label id="certified" class="hidden"><input type="checkbox">Certifié</label></div>
				<div>
					<button id="modif-1-close-btn">Annuler</button>
					<button id="modif-1-send-btn">OK</button>
				</div>
			</fieldset>
		</div>
		<div id="menus" class="flx flx-jc flx-ww"><?php echo $_SESSION['menu']['HTML']; ?></div>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/../footer.php'; ?>
	</div>
</body>
</html>
<?php } else {
	if (isset($_POST['username']) && isset($_POST['passwd']) && $_POST['username'] != '' && $_POST['passwd'] != '') {
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/db.php';
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/pass.php';
		$db = new DB();
		$hash = $db->getValue("SELECT `hash` FROM `logins` WHERE BINARY `username`=?",[$_POST['username']]);
		if ($hash !== false && password_verify($_POST['passwd'],$hash)) {
			$login = $db->getRow("SELECT `id`, `pp`, `persos-group`, `comments-admin`, `scenarios` FROM `logins` WHERE BINARY `username`=?",[$_POST['username']]);
			require $_SERVER['DOCUMENT_ROOT'].'/../lib/os.php';
			$backgrounds = getFilesListFromFolder('/medias/wallpapers/');
			$_SESSION = [
				'user' => [
					'id' => $login['id'],
					'name' => $_POST['username'],
					'pp' => $login['pp'],
					'persos-group' => $login['persos-group'],
					'comments-admin' => ($login['comments-admin'] == 1),
					'scenarios' => intval($login['scenarios']),
					'canAccessScenariosOnlyPages' => ($login['scenarios'] == 1),
					'commented' => []
				],
				'wallpapers' => [
					'list' => $backgrounds,
					'lastIndex' => sizeof($backgrounds)-1
				],
				'menu' => [
					'style' => '',
					'HTML' => ''
				]
			];
			require $_SERVER['DOCUMENT_ROOT'].'/../data/menu.php';
			$pagesSectionsIcons = ['dice-iconsrepo-com','fist-iconsrepo-com','storytelling'];
			for ($i = 0; $i < sizeof($menu); $i++) $_SESSION['menu']['style'] .= '#mn-'.$i.'{background:url("/src/icons/menu/white/'.$menu[$i][3].'.svg"),url("/src/icons/menu/white/'.$pagesSectionsIcons[$menu[$i][0]].'.svg");}#mn-'.$i.':hover{background:url("/src/icons/menu/black/'.$menu[$i][3].'.svg"),url("/src/icons/menu/black/'.$pagesSectionsIcons[$menu[$i][0]].'.svg");}';
			$pagesSections = ['jdr','force','scenarios'];
			for ($i = 0; $i < sizeof($menu); $i++) $_SESSION['menu']['HTML'] .= '<a href="'.$pagesSections[$menu[$i][0]].'/'.$menu[$i][1].'"><div id="mn-'.$i.'" class="dvmenu flx flx-dc flx-je"><div>'.$menu[$i][2].'</div></div></a>';
			if (gethostname() == 'homeServer') shell_exec('curl -d "Connected: '.$_POST['username'].' ('.$_SERVER['REMOTE_ADDR'].')" ntfy.sh/aventures_ddns_net_x8m3SifWEu_connected');
		}
		$db->close();
		if (isset($_POST['page'])) header('Location: '.$_POST['page']);
		else header('Location: /');
		exit;
	} ?>
<!DOCTYPE html>
<html>
<head>
	<title>Acceuil - aventures.ddns.net</title>
	<meta charset="utf-8" name="viewport" content="width=device-width"/>
	<link rel="stylesheet" href="/src/style.css"/>
</head>
<body>
<div class="flx flx-jc flx-ac flx-dc">
	<p id="sitemessage">Ce domaine est seulement réservé aux membres. Une authentification est nécessaire.</p>
	<form action="." method="post">
		<fieldset class="fldct">
			<legend>Connexion</legend>
			<div><input type="text" name="username" placeholder="Nom d'utilisateur ..."/></div>
			<div><input type="password" name="passwd" placeholder="Mot de passe ..."/></div>
			<div><input type="submit" value="Se connecter"/></div>
		</fieldset>
		<?php if (isset($_GET['page'])) echo '<input type="text" name="page" value="'.$_GET['page'].'" class="hidden"/>'; ?>
	</form>
</div>
</body>
</html>
<?php } ?>