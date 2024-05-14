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
	// require $_SERVER['DOCUMENT_ROOT'].'/../lib/db.php';
	// $db = new DB();
	// if ($type == 0) {

	// }
	// $db->close();
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
	<title>Messages - aventures.ddns.net</title>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../include-js.php'; ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js" integrity="sha512-+H4iLjY3JsKiF2V6N366in5IQHj2uEsGV7Pp/GRcm0fn76aPAk5V8xB6n8fQhhSonTqTXs/klFz4D0GIn6Br9g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/fr.min.js" integrity="sha512-RAt2+PIRwJiyjWpzvvhKAG2LEdPpQhTgWfbEkFDCo8wC4rFYh5GQzJBVIFDswwaEDEYX16GEE/4fpeDNr7OIZw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script type="text/javascript" src="/src/lib/comments.js"></script>
	<script type="text/javascript">
	$(document).ready(function(){
		serverResponse = function(opt) {
			if (opt[0] == 0) {
				prepareComments('messages',5,0,0,0,true,false,true);
			}
		}

		serverComments(0);
	});
	</script>
</head>
<body>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../header.php'; ?>
	<div id="pg">
		<div class="flx flx-jc flx-ac pgtlt">
			<img src="/src/icons/menu/white/Facebook_Messenger-Black-Logo.wine.svg"/>
			<h1>Messages</h1>
			<img src="/src/icons/menu/white/Facebook_Messenger-Black-Logo.wine.svg"/>
		</div>
		<div id="messages"></div>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/../footer.php'; ?>
	</div>
</body>
</html>
