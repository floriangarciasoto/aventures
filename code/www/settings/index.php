<?php
session_start();
if (!isset($_SESSION['user'])) {
	header('Location: /?page='.urlencode($_SERVER['REQUEST_URI']));
	exit;
}
if (isset($_POST['oldpass']) && isset($_POST['newpass']) && isset($_POST['newpassv']) && $_POST['oldpass'] != '' && $_POST['newpass'] != '' && $_POST['newpassv'] != '' && $_POST['newpass'] == $_POST['newpassv']) {
	require $_SERVER['DOCUMENT_ROOT'].'/../lib/db.php';
	require $_SERVER['DOCUMENT_ROOT'].'/../lib/pass.php';
	$db = new DB();
	$hash = $db->getValue("SELECT `hash` FROM `logins` WHERE `id`=?",[$_SESSION['user']['id']]);
	if ($hash !== false && password_verify($_POST['oldpass'],$hash)) $db->req("UPDATE `logins` SET `hash`=? WHERE `id`=?",[getPasswordHash($_POST['newpass']),$_SESSION['user']['id']]);
	$db->close();
	header('Location: /settings/');
} ?>
<!DOCTYPE html>
<html>
<head>
	<title>Paramètres d'utilisateur - aventures.ddns.net</title>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../include.php'; ?>
</head>
<body>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../header.php'; ?>
	<div id="pg">
		<div class="flx flx-jc flx-ac pgtlt pgtlt-small">
			<img src="/src/icons/buttons/white/Ic_settings_48px.svg"/>
			<h3>Paramètres d'utilisateur</h3>
			<img src="/src/icons/buttons/white/Ic_settings_48px.svg"/>
		</div>
		<form action="." method="post">
			<div class="flx flx-jc">
				<fieldset class="fldct">
					<legend>Changement de mot de passe</legend>
					<div><input type="password" name="oldpass" placeholder="Mot de passe actuel ..."></div>
					<div><input type="password" name="newpass" placeholder="Nouveau mot de passe ..."></div>
					<div><input type="password" name="newpassv" placeholder="Confirmation ..."></div>
					<div><input type="submit" value="OK"/></div>
				</fieldset>
			</div>
		</form>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/../footer.php'; ?>
	</div>
</body>
</html>
