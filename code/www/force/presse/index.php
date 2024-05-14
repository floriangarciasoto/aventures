<?php
session_start();
if (!isset($_SESSION['user'])) {
	header('Location: /?page='.urlencode($_SERVER['REQUEST_URI']));
	exit;
}
function affarticles(&$db,$not=0) {
	$req = "SELECT `force-presse-articles`.`id` AS `id`, `name`, `pp`, `designation`, `title`, `thumbnail`, `content`, TIMESTAMPDIFF(SECOND, `date`, NOW()) AS `seconds`, TIMESTAMPDIFF(MINUTE, `date`, NOW()) AS `minutes`, TIMESTAMPDIFF(HOUR, `date`, NOW()) AS `hours`, TIMESTAMPDIFF(DAY, `date`, NOW()) AS `days`, TIMESTAMPDIFF(WEEK, `date`, NOW()) AS `weeks`, TIMESTAMPDIFF(MONTH, `date`, NOW()) AS `months`, TIMESTAMPDIFF(YEAR, `date`, NOW()) AS `years` FROM `force-presse-journalistes`, `force-presse-articles` WHERE `force-presse-journalistes`.`id`=`force-presse-articles`.`jr`";
	if ($not != 0) $req .= " AND `force-presse-articles`.`id`!=".$not;
	foreach ($db->getDirect($req." ORDER BY `date` DESC") as $value) echo '<div><a href="./?article='.$value['id'].'"><div class="mimg"><img class="thumbnail thumbnail-16-9" src="'.addPathPartIfNotURL('/medias/force/presse/thumbnails/',$value['thumbnail']).'"/></div><div class="mtext"><div class="mtitle">'.$value['title'].'</div><div class="mcontent">'.$value['content'].'</div><div class="mdate">'.$value['name'].' - '.getSinceDate($value).'</div></div></a></div>';
}
function processPOSTRequest($type,&$responseData) {
	if ($type == 1) {
		if (!isset($_POST['jr']) || !isset($_POST['name']) || !isset($_POST['pp']) || !isset($_POST['designation'])) return -1;
		if ($_POST['jr'] == '' || $_POST['name'] == '' || $_POST['pp'] == '' || $_POST['designation'] == '') return -1;
		if ($_POST['jr'] != intval($_POST['jr']).'') return -1;
	}
	if ($type == 2) {
		if (!isset($_POST['article']) || !isset($_POST['jrarticle']) || !isset($_POST['title']) || !isset($_POST['thumbnail']) || !isset($_POST['content'])) return -1;
		if ($_POST['article'] == '' || $_POST['jrarticle'] == '' || $_POST['title'] == '' || $_POST['thumbnail'] == '' || $_POST['content'] == '') return -1;
		if ($_POST['article'] != intval($_POST['article']).'' || $_POST['jrarticle'] != intval($_POST['jrarticle']).'') return -1;
	}
	require $_SERVER['DOCUMENT_ROOT'].'/../lib/db.php';
	$db = new DB();
	if ($type == 0) {
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/os.php';
		$responseData = array($db->getDirect("SELECT * FROM `force-presse-journalistes`"),$db->getDirect("SELECT * FROM `force-presse-articles`"),getpps(['force']),getFilesListFromFolder('/medias/force/presse/thumbnails/'));
	}
	if ($type == 1 || $type == 2) require $_SERVER['DOCUMENT_ROOT'].'/../lib/misc.php';
	if ($type == 1) {
		if ($_POST['pp'] == '0' && (!isset($_POST['ppurl']) || $_POST['ppurl'] == '' || !isImgURL($_POST['ppurl']))) return -1;
		if ($_POST['pp'] != '0' && !file_exists($_SERVER['DOCUMENT_ROOT'].'/medias/pps/'.$_POST['pp'])) return -1;
		$pp = $_POST['pp'];
		if ($_POST['pp'] == '0') $pp = $_POST['ppurl'];
		if ($_POST['jr'] != '0' && !$db->isEmpty("SELECT 1 FROM `force-presse-journalistes` WHERE `id`=?",[$_POST['jr']])) $db->req("UPDATE `force-presse-journalistes` SET `name`=?, `pp`=?, `designation`=? WHERE `id`=?",[$_POST['name'],$pp,$_POST['designation'],$_POST['jr']]);
		else $db->req("INSERT INTO `force-presse-journalistes`(`name`, `pp`, `designation`) VALUES (?, ?, ?)",[$_POST['name'],$pp,$_POST['designation']]);
	}
	if ($type == 2) {
		if ($_POST['thumbnail'] == '0' && (!isset($_POST['thumbnailurl']) || $_POST['thumbnailurl'] == '' || !isImgURL($_POST['thumbnailurl']))) return -1;
		if ($_POST['thumbnail'] != '0' && !file_exists($_SERVER['DOCUMENT_ROOT'].'/medias/force/presse/thumbnails/'.$_POST['thumbnail'])) return -1;
		if ($db->isEmpty("SELECT 1 FROM `force-presse-journalistes` WHERE `id`=?",[$_POST['jrarticle']])) return -1;
		$num = $db->getValue("SELECT MAX(`num`)+1 FROM `force-presse-articles` WHERE `jr`=?",[$_POST['jrarticle']]);
		if (is_null($num)) $num = 1;
		$thumbnail = $_POST['thumbnail'];
		if ($_POST['thumbnail'] == '0') $thumbnail = $_POST['thumbnailurl'];
		if ($_POST['article'] == 0) $db->req("INSERT INTO `force-presse-articles`(`jr`, `num`, `title`, `thumbnail`, `content`, `date`) VALUES (?, ?, ?, ?, ?, NOW())",[$_POST['jrarticle'],$num,$_POST['title'],$thumbnail,$_POST['content']]);
		else $db->req("UPDATE `force-presse-articles` SET `jr`=?, `title`=?, `thumbnail`=?, `content`=? WHERE `id`=?",[$_POST['jrarticle'],$_POST['title'],$thumbnail,$_POST['content'],$_POST['article']]);
	}
	$db->close();
	return 0;
}
if (isset($_POST['type']) && in_array($_POST['type'],[0,1,2])) {
	$responseData = '';
	if (processPOSTRequest($_POST['type'],$responseData) == 0) echo json_encode([$_POST['type'],$responseData]);
	exit;
} ?>
<!DOCTYPE html>
<html>
<head>
	<title>Presse - aventures.ddns.net</title>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../include-js.php'; ?>
	<script type="text/javascript">
	$(document).ready(function(){
		serverResponse = function(opt) {
			if (opt[0] == 0) {
				for (i in opt[1][0]) journalistes[opt[1][0][i]['id']] = opt[1][0][i];
				for (i in opt[1][1]) articles[opt[1][1][i]['id']] = opt[1][1][i];
				for (i in journalistes) $('#jr, #jrarticle').append('<option value="'+i+'">'+journalistes[i]['name']+'</option>');
				for (i in articles) $('#article').append('<option value="'+i+'">'+articles[i]['title']+'</option>');
				$('#pp').append(getSelectOptionsHTMLFromImagesList(getPpsPathsListFromObj(opt[1][2])));
				$('#thumbnail').append(getSelectOptionsHTMLFromImagesList(opt[1][3]));
			}
			else window.location.replace('.');
		};

		$('#modif-1-open-btn, #modif-1-close-btn').click(function(){$('#modif-1-open-btn, #modif-1-fld').toggleClass('hidden');});
		$('#jr').change(function(){
			var id = $(this).val();
			if (id == 0) {
				$('#name, #ppurl, #designation').val('');
				$('#pp').val(0);
			}
			else {
				$('#name').val(journalistes[id]['name']);
				if (isImgURL(journalistes[id]['pp'])) {
					$('#pp').val(0);
					$('#ppurl').val(journalistes[id]['pp']);
					$('#ppurl').removeClass('hidden');
				}
				else {
					$('#pp').val(journalistes[id]['pp']);
					$('#ppurl').val('');
					$('#ppurl').addClass('hidden');
				}
				$('#designation').val(journalistes[id]['designation']);
			}
		});
		$('#pp').change(function(){
			if ($(this).val() != 0) $('#ppurl').addClass('hidden');
			else $('#ppurl').removeClass('hidden');
		});
		$('#modif-1-send-btn').click(function(){
			server(1,{
				'jr' : $('#jr').val(),
				'name' : $('#name').val(),
				'pp' : $('#pp').val(),
				'ppurl' : $('#ppurl').val(),
				'designation' : $('#designation').val()
			});
		});

		$('#modif-2-open-btn, #modif-2-close-btn').click(function(){$('#modif-2-open-btn, #modif-2-fld').toggleClass('hidden');});
		$('#article').change(function(){
			var id = $(this).val();
			if (id == 0) {
				$('#title, #thumbnailurl, #content').val('');
				$('#jrarticle, #thumbnail').val(0);
			}
			else {
				$('#jrarticle').val(articles[id]['jr']);
				$('#title').val(articles[id]['title']);
				if (isImgURL(articles[id]['thumbnail'])) {
					$('#thumbnail').val(0);
					$('#thumbnailurl').val(articles[id]['thumbnail']);
					$('#thumbnailurl').removeClass('hidden');
				}
				else {
					$('#thumbnail').val(articles[id]['thumbnail']);
					$('#thumbnailurl').val('');
					$('#thumbnailurl').addClass('hidden');
				}
				$('#content').val(articles[id]['content']);
			}
		});
		$('#thumbnail').change(function(){
			if ($(this).val() != 0) $('#thumbnailurl').addClass('hidden');
			else $('#thumbnailurl').removeClass('hidden');
		});
		$('#modif-2-send-btn').click(function(){
			server(2,{
				'article' : $('#article').val(),
				'jrarticle' : $('#jrarticle').val(),
				'title' : $('#title').val(),
				'thumbnail' : $('#thumbnail').val(),
				'thumbnailurl' : $('#thumbnailurl').val(),
				'content' : $('#content').val()
			});
		});

		var journalistes = {};
		var articles = {};

		server(0);

<?php
require $_SERVER['DOCUMENT_ROOT'].'/../lib/db.php';
require $_SERVER['DOCUMENT_ROOT'].'/../lib/misc.php';
$db = new DB();
if (isset($_GET['article']) && !$db->isEmpty("SELECT 1 FROM `force-presse-articles` WHERE `id`=?",[intval($_GET['article'])])) {
	$watch = intval($_GET['article']);
	$article = $db->getRow("SELECT * FROM `force-presse-journalistes`, `force-presse-articles` WHERE `force-presse-journalistes`.`id`=`force-presse-articles`.`jr` AND `force-presse-articles`.`id`=?",[$watch]);
	echo "$('#header-returnpage').attr('href','.');";
} ?>

	});
	</script>
</head>
<body>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../header.php'; ?>
	<div id="pg" class="presse">
		<div class="flx flx-jc flx-ac pgtlt">
			<img src="/src/icons/menu/white/newspaper-svgrepo-com.svg"/>
			<h1>Presse</h1>
			<img src="/src/icons/menu/white/newspaper-svgrepo-com.svg"/>
		</div>
<?php if (!isset($watch)) { ?>
		<div class="flx flx-jc"><button id="modif-1-open-btn">Gérer les journalistes</button></div>
		<div class="flx flx-jc">
			<fieldset id="modif-1-fld" class="fldct hidden">
				<legend>Journalistes</legend>
				<div><select id="jr"><option value="0">- Nouveau journaliste -</option></select></div>
				<div><input type="text" id="name" placeholder="Nom du journaliste"/></div>
				<div>
					<div>PP :</div>
					<div>
						<select id="pp"><option value="0">- URL -</option></select><br/>
						<input type="text" id="ppurl" placeholder="https://..."/>
					</div>
				</div>
				<div><input type="text" id="designation" placeholder="Désignation"/></div>
				<div>
					<button id="modif-1-close-btn">Annuler</button>
					<button id="modif-1-send-btn">OK</button>
				</div>
			</fieldset>
		</div>
		<div class="flx flx-jc"><button id="modif-2-open-btn">Gérer les articles</button></div>
		<div class="flx flx-jc">
			<fieldset id="modif-2-fld" class="fldct hidden">
				<legend>Articles</legend>
				<div>Article : <select id="article"><option value="0">- Nouvel article -</option></select></div>
				<div>Publié par : <select id="jrarticle"><option value="0">-</option></select></div>
				<div>Titre : <input type="text" id="title" placeholder="Titre"/></div>
				<div>
					<div>Miniature :</div>
					<div>
						<select id="thumbnail"><option value="0">- URL -</option></select><br/>
						<input type="text" id="thumbnailurl" placeholder="https://..."/>
					</div>
				</div>
				<div><textarea id="content" class="maxedwidth" rows="15" placeholder="Contenu ...

Attention : il est fortement déconseillé d'écrire l'article directement dans le champ présent, car la connexion avec le site pourrait être perdue entre temps, et le fait d'envoyer l'article ne ferait que rediriger vers la page d'acceuil, entraînant la perte du texte saisi.

Il est recommandé de saisir l'article dans un logiciel de traitement de texte afin de pouvoir vérifier les eventuelles erreurs, puis d'aller sur cette page en l'ayant rechargée afin de coller le texte dans ce champ."></textarea></div>
				<div>
					<button id="modif-2-close-btn">Annuler</button>
					<button id="modif-2-send-btn">OK</button>
				</div>
			</fieldset>
		</div>
		<div class="flx flx-jc flx-ww articles"><?php affarticles($db); ?></div>
<?php } else { ?>
		<div id="varticle">
			<img src="<?php echo addPathPartIfNotURL('/medias/force/presse/thumbnails/',$article['thumbnail']); ?>"/>
			<div id="vtitle"><?php echo getEscapedAngleBrackets($article['title']); ?></div>
			<div id="vcontent"><?php echo getLines(getEscapedAngleBrackets($article['content'])); ?></div>
			<div id="vdate">Publié le <?php echo $article['date']; ?></div>
			<div id="vauthor" class="flx flx-ac">
				<div>
					<div><?php echo getEscapedAngleBrackets($article['name']); ?></div>
					<div><?php echo getEscapedAngleBrackets($article['designation']); ?></div>
				</div>
				<div><img src="<?php echo addPathPartIfNotURL('/medias/pps/',$article['pp']); ?>"/></div>
			</div>
			<hr/>
			<div class="flx flx-jc flx-ww articles"><?php affarticles($db,$watch); ?></div>
		</div>
<?php } $db->close(); ?>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/../footer.php'; ?>
	</div>
</body>
</html>
