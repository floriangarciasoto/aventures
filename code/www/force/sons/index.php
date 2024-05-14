<?php
session_start();
if (!isset($_SESSION['user'])) {
	header('Location: /?page='.urlencode($_SERVER['REQUEST_URI']));
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Boîte à sons - aventures.ddns.net</title>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../include.php'; ?>
</head>
<body>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../header.php'; ?>
	<div id="pg">
		<div class="flx flx-jc flx-ac pgtlt">
			<img src="/src/icons/menu/white/sound-bars-svgrepo-com.svg"/>
			<h1>Boîte à sons</h1>
			<img src="/src/icons/menu/white/sound-bars-svgrepo-com.svg"/>
		</div>
		<div id="sons" class="flx flx-jc flx-ww"><?php
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/os.php';
		foreach (getFilesListFromFolder('/medias/force/sons/') as $key => $value) echo '<button onclick="document.getElementById(\'sound'.$key.'\').play();">'.preg_replace('/\.[^\.]*$/','',$value).'</button> <audio id="sound'.$key.'" src="/medias/force/sons/'.$value.'"></audio>';
		?></div>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/../footer.php'; ?>
	</div>
</body>
</html>
