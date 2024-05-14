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
	if ($type == 0) {
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/misc.php';
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/os.php';
		$responseData = getPageJSONData('scenarios/foot');
		$matchsNotes = [];
		foreach (getFilesListFromFolder('/../data/txt/scenarios/foot/') as $value) $matchsNotes[preg_replace('/^match-|\.txt$/','',$value)] = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/../data/txt/scenarios/foot/'.$value);
		array_push($responseData,$matchsNotes);
	}
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
	<title>Foot - aventures.ddns.net</title>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../include-js.php'; ?>
	<style type="text/css">
/*
		@keyframes timebar {
			0% {
				left: 0;
				right: 100%;
			}
			15% {
				left: 0;
				right: 0;
			}
			30% {
				left: 100%;
				right: 0;
			}
			45% {
				left: 0;
				right: 0;
			}
			60% {
				left: 0;
				right: 100%;
			}
			100% {}
		}
*/

		.foot-box {
			background-color: #333;
			border-radius: 10px;
			margin: 7.5px;
			padding: 10px 15px 10px 15px;
		}
		.foot-box-selectable.selected, .foot-box-selectable:hover {background-color: #555;}

		.foot-contain-cats-boxes .foot-box {
			width: 170px;
			height: 40px;
			font-size: 15px;
		}
		.foot-contain-cats-boxes .foot-box img {
			width: 30px;
			height: 30px;
			margin-right: 10px;
		}

		.foot-box-team-icon-dv {
			width: 27.5px;
			height: 27.5px;
			aspect-ratio: 1;
		}
		.foot-box-team-icon-dv img {
			width: 100%;
			height: 100%;
			object-fit: contain;
		}
		.foot-box-team-name {
			overflow-x: auto;
			white-space: nowrap;
		}
		.foot-team-name-and-icon-temp {opacity: 0.3;}

		.foot-contain-match-boxes .foot-box {width: 265px;}
		.foot-contain-match-boxes .foot-box > div {padding: 2.5px;}
		.foot-contain-match-boxes .foot-box-team-icon-dv,
		.foot-box-group .foot-box-team-icon-dv {margin-right: 7.5px;}
		.foot-contain-match-boxes .foot-box-team-name {width: 100%;}
		.foot-contain-match-boxes .foot-box > div > :nth-child(3) {
			padding-left: 10px;
			white-space: nowrap;
		}
		.foot-match-box-team-looser {color: #aaa;}

		#foot-cat-content-display-buttons {
			position: sticky;
			top: 0;
			max-width: 100%;
			height: 105px;
			padding-bottom: 1.25px;
			overflow-x: auto;
			background-color: black;
			z-index: 1;
		}
		#foot-cat-content-display-buttons > button {
			background-color: #333;
			border-color: #333;
			border-radius: 15px;
			white-space: nowrap;
		}
		#foot-cat-content-display-buttons > button.selected, #foot-cat-content-display-buttons > button:hover {
			color: var(--main-color);
			border-color: #555;
			background-color: #555;
		}

		#foot-cup-winner .foot-box {width: 360px;}
		#foot-cup-winner .foot-box > * {margin: 5px;}
		#foot-cup-winner .foot-box > img {
			width: 70px;
			height: 70px;
		}
		#foot-cup-winner .foot-box > :nth-child(2) {
			font-size: 25px;
			font-weight: bold;
			margin-top: -25px;
			text-shadow: -2px -2px 0 #555, 2px -2px 0 #555, -2px 2px 0 #555, 2px 2px 0 #555;
		}
		#foot-cup-winner .foot-box-team-icon-dv {
			width: 65px;
			height: 65px;
			margin-right: 10px;
		}
		#foot-cup-winner .foot-box-team-name {
			font-size: 40px;
			max-width: 275px;
		}

		#foot-cup-grid-dv {overflow-y: auto;}
		#foot-cup-grid {
			--foot-box-total-height: calc(65px + 10px*2 + 7.5px*2);
			width: 1230px;
		}
		#foot-cup-grid .foot-box {margin: 7.5px 0 7.5px 0;}
		.foot-grid-separation-1 {width: 10px;}
		.foot-grid-separation-2 {width: 5.5px;}
		.foot-grid-separation-1 > div {
			border: 5px solid #333;
			border-left: none;
			border-radius: 0 5px 5px 0;
		}
		.foot-grid-separation-1-1 > div {height: calc(var(--foot-box-total-height)*1 - 5px);}
		.foot-grid-separation-1-2 > div {height: calc(var(--foot-box-total-height)*2 - 5px);}
		.foot-grid-separation-1-3 > div {height: calc(var(--foot-box-total-height)*4 - 5px);}
		.foot-grid-separation-2 > div {
			height: 5px;
			background-color: #333;
		}
		#foot-cup-grid > div:last-child {position: relative;}
		#foot-cup-grid > div:last-child > div:last-child {
			position: absolute;
			margin-top: 207.5px;
		}
		#foot-cup-grid .foot-match-forfeit-text {font-size: 0;}
		#foot-cup-grid .foot-match-forfeit-text:first-letter {font-size: 20px;}

		.foot-box-group {text-align: center;}
		.foot-box-group > div {
			font-weight: bold;
			margin-bottom: 10px;
		}
		.foot-box-group .foot-cup-group-table-team-name {text-align: left;}
		.foot-box-group tr:first-child {
			color: #ddd;
			font-size: 17.5px;
		}
		.foot-box-group tr:not(:first-child):hover {background-color: #555;}
		.foot-box-group th {font-weight: normal;}
		.foot-box-group th:not(.foot-cup-group-table-team-name) {width: 3ex;}
		.foot-box-group .foot-box-team-name {width: 200px;}
		.foot-box-group tr > :first-child {
			width: 5px;
			padding: 0;
		}
		.foot-box-group tr > :last-child {font-weight: bold;}
		.foot-box-group tr > :nth-child(2) {width: 2.5px;}
		.foot-contain-cup-groups .foot-box-group table tr:nth-child(-n+3) td:first-child {background-color: #4285f4;}
		.foot-box-group table {border-collapse: collapse;}
		.foot-box-group :is(th, td) {padding: 2.5px;}

		#foot-cat-content-matchs {
			max-height: 535px;
			overflow-y: overlay;
		}
		.foot-matchs-separation {
			color: #bbb;
			font-size: 17.5px;
			white-space: nowrap;
		}
		.foot-matchs-separation > :first-child,
		.foot-matchs-separation > :last-child {
			width: 50%;
			height: 2px;
			background-color: #bbb;
			margin: 15px;
		}
		#foot-cat-content-matchs .foot-box > div > :last-child img {
			height: 1.375em;
			margin-right: 2.5px;
		}
		#foot-cat-content-matchs .foot-match-forfeit-text {font-size: 12.5px;}

		#foot-cat-content-manage {
			position: sticky;
			bottom: 0;
			pointer-events: none;
		}
		#foot-cat-content-manage > button {
			background-color: #333;
			padding: 7.5px;
			border: 2px solid var(--main-bg-color);
			pointer-events: initial;
		}
		#foot-cat-content-manage > button:hover {
			background-color: #555;
			color: var(--main-color);
		}
		#foot-cat-content-manage > :last-child {margin-right: 22.5px;}

		#foot-cat-content-match-stats-dv-dv-dv {
			position: fixed;
			left: 0;
			right: 0;
			top: 0;
			bottom: 0;
			z-index: 2;
		}
		#foot-cat-content-match-stats-dv-dv {
			position: relative;
			height: 100%;
		}
		#foot-cat-content-match-stats-bkg {
			width: 100%;
			height: 100%;
			background-color: black;
			opacity: 0.5;
		}
		#foot-cat-content-match-stats-dv {pointer-events: none;}
		#foot-cat-content-match-stats {
			max-height: 80%;
			pointer-events: initial;
			overflow-y: auto;
		}
		.foot-cat-content-match-stats-head-text {
			font-size: 14px;
			text-align: center;
			width: 500px;
			margin-top: 2.5px;
			word-break: break-word;
		}
		#foot-match-stats-persos {margin-top: 5px;}
		#foot-match-stats-header {
			font-size: 12.5px;
			text-align: center;
			margin-bottom: 5px;
		}
		#foot-match-stats-teams-names > :first-child,
		#foot-match-stats-teams-names > :last-child {width: 50%;}
		#foot-match-stats-teams-names > :first-child {text-align: right;}
		#foot-match-stats-teams-names > .foot-box-team-icon-dv {margin: 0 5px;}
		#foot-match-stats-teams-names > :nth-child(3) {
			padding: 0 5px;
			font-size: 12.5px;
		}
		#foot-match-stats-score {margin-top: 2.5px;}
		#foot-match-stats-score-team-0, #foot-match-stats-score-team-1 {
			width: 7ex;
			text-align: center;
		}
		.foot-match-stats-lists {
			margin: 5px 0;
			font-size: 15px;
			word-break: break-word;
		}
		#foot-match-stats-red-cards-lists {margin-top: 7.5px;}
		.foot-match-stats-lists > :first-child,
		.foot-match-stats-lists > :last-child {width: 50%;}
		.foot-match-stats-lists > :last-child {text-align: right;}
		.foot-match-stats-lists img {
			height: 30px;
			margin-top: -5px;
		}

		#foot-match-stats-probabilities {width: 500px;}
		#foot-match-stats-probabilities > div {
			background-color: #444;
			margin-top: 7.5px;
			border-radius: 5px;
			padding: 7.5px;
		}
		#foot-match-stats-probabilities > :last-child {margin-bottom: 2.5px;}
		.foot-match-stats-probability-operator-logo {
			height: 25px;
			margin-bottom: 5px;
		}
		.foot-match-stats-probability-names {
			font-size: 15px;
			white-space: nowrap;
		}
		.foot-match-stats-probability-names > :first-child,
		.foot-match-stats-probability-names > :last-child {
			width: calc(50% - 50px);
			overflow-x: auto;
		}
		.foot-match-stats-probability-names > :last-child {text-align: right;}
		.foot-match-stats-probability-prcs {
			font-size: 13.75px;
			margin: 2.5px 0;
		}
		.foot-match-stats-probability-prcs > div {width: 33.33%;}
		.foot-match-stats-probability-prcs > :nth-child(2) {text-align: center;}
		.foot-match-stats-probability-prcs > :nth-child(3) {text-align: right;}
		.foot-match-stats-probability-bar > div {
			text-align: center;
			height: 10px;
		}
		.foot-match-stats-probability-bar > :first-child {
			background-color: #666;
			border-radius: 5px 0 0 5px;
		}
		.foot-match-stats-probability-bar > :nth-child(2) {background-color: #aaa;}
		.foot-match-stats-probability-bar > :last-child {
			background-color: #888;
			border-radius: 0 5px 5px 0;
		}

		.foot-field-team-header {
			background-color: #188038;
			white-space: nowrap;
		}
		.foot-field-team-header > * {margin: 15px;}
		.foot-field-team-header > :nth-child(2) {
			width: 100%;
			margin-left: 0;
			margin-right: 0;
			overflow-x: auto;
		}
		.foot-field-team-header .foot-box-team-icon-dv {
			width: 30px;
			height: 30px;
		}

		.foot-perso-pp-dv {position: relative;}
		.foot-perso-pp {
			width: 45px;
			height: 45px;
			border-radius: 45px;
			border: 3px solid;
		}
		.foot-perso-pp-add {position: absolute;}
		.foot-perso-pp-add-score {
			left: -10px;
			bottom: 2.5px;
			width: 30px;
		}
		.foot-perso-has-scored-plus .foot-perso-pp-add-score {
			left: -19px;
			bottom: 10.5px;
		}
		.foot-perso-pp-add-score-number {
			font-size: 17px;
			width: 20px;
			height: 20px;
			background-color: white;
			color: black;
			font-weight: bolder;
			border-radius: 20px;
			border: 1.75px solid black;
			left: -9px;
			bottom: 4.5px;
			line-height: 20px;
			text-align: center;
		}
		.foot-perso-pp-add-substitution {
			right: -4px;
			bottom: 5px;
			display: flex;
			justify-content: center;
			width: 19px;
			height: 19px;
			border-radius: 20px;
			background-color: white;
		}
		.foot-perso-pp-add-card {
			width: 30px;
			right: -13px;
			top: -3px;
		}

		.foot-schema {
			display: flex;
			flex-direction: column;
			justify-content: space-around;
		}
		.foot-schema > div {
			height: 100%;
			display: flex;
			justify-content: space-around;
		}
		.foot-schema > div > div {
			width: 100%;
			height: 100%;
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;
		}
		.foot-schema .foot-perso-pp {margin: 0;}

		.foot-perso-name {
			font-size: 17.5px;
			overflow-x: auto;
			white-space: nowrap;
		}

		.foot-field {
			background: url('/medias/scenarios/foot/Soccer_Field_Football_Pitch_clip_art.svg');
			background-size: cover;
			aspect-ratio: 578.07 / 529.605;
		}
		.foot-field > div {height: 100%;}
		.foot-field-bottom {background-position-y: 100%;}
		.foot-field-bottom > div {flex-direction: column-reverse;}
		.foot-field .foot-schema > div > div {padding: 0 5px;}
		.foot-schema > div.foot-field-row-with-1-persos > div {width: calc(100% - 10px);}
		.foot-schema > div.foot-field-row-with-2-persos > div {width: calc(50% - 10px);}
		.foot-schema > div.foot-field-row-with-3-persos > div {width: calc(33.333% - 10px);}
		.foot-schema > div.foot-field-row-with-4-persos > div {width: calc(25% - 10px);}
		.foot-schema > div.foot-field-row-with-5-persos > div {width: calc(20% - 10px);}
		.foot-field-top > div > div {flex-direction: row-reverse;}
		.foot-field-perso-name-dv {
			margin-top: -5px;
			width: 100%;
		}

		.foot-persos-lists-title {
			margin: 10px 0 5px 0;
			font-size: 15px;
			text-align: center;
		}
		.foot-persos-lists .foot-box-team-icon-dv {
			width: 30px;
			height: 30px;
		}
		.foot-persos-lists > div > div {width: 50%;}
		.foot-persos-lists > div > div:last-child {
			text-align: right;
			flex-direction: row-reverse;
		}
		.foot-perso-infos {margin: 0 7.5px 0 7.5px;}
		.foot-persos-lists .foot-perso-infos {width: calc(100% - 70px);}
		.foot-persos-lists .foot-perso-name {max-width: 100%;}
		.foot-perso-position {
			font-size: 15px;
			color: #ccc;
		}

		#foot-match-stats-notes {font-size: 15px;}
		#foot-match-stats-notes > :first-child {
			text-align: center;
			margin-bottom: 5px;
		}
		#foot-match-stats-notes-content {
			font-size: 12.5px;
			word-break: break-word;
		}

/*
		#matchs-thumbnails {margin-bottom: 5px;}
		#matchs-thumbnails > div {
			position: relative;
			aspect-ratio: 16/9;
			height: 200px;
			margin: 5px;
			border: 3px solid var(--main-bg-color);
			border-radius: 7.5px;
			cursor: pointer;
		}
		#matchs-thumbnails > div.selected {border: 3px solid;}
		#matchs-thumbnails > div > div {
			position: absolute;
			width: 100%;
			bottom: 5px;
		}
		#matchs-thumbnails > div > div > div {
			background-color: var(--main-bg-color);
			text-align: center;
			max-width: 90%;
			padding: 5px;
			border-radius: 5px;
		}
		#matchs-thumbnails > div > img {
			position: absolute;
			height: 60px;
			bottom: 25px;
		}
		#matchs-thumbnails .match-thumbnail-icon-0 {left: 70px;}
		#matchs-thumbnails .match-thumbnail-icon-1 {right: 70px;}

		#foot-view {
			height: 400px;
			border: 2px solid;
			position: relative;
		}
		#foot-view:fullscreen {border: none;}
		#foot-view:fullscreen #foot-button-fullscreen {display: none;}
		#foot-view-content {
			flex-grow: 1;
			overflow: auto;
		}
		#foot-view-content > div {
			width: 600px;
			overflow-y: scroll;
			margin: 0 7.5px 0 7.5px;
		}

		#foot-view-no-display {background-color: var(--main-bg-color);}

		.foot-score > div:first-child, .foot-score > div:last-child {width: 50%;}
		.foot-score > div:first-child {text-align: right;}
		.foot-score-nb {
			width: 3ex;
			text-align: center;
		}
		.foot-score-nb.foot-score-nb-trb {width: 5ex;}
		.foot-score > :not(.foot-score-nb) {margin: 0 10px 0 10px;}
		.foot-score-scored {font-weight: bold;}
		.foot-score-containing-icons > div:not(.foot-score-team-name-with-icon) {margin-bottom: 25px;}
		.foot-score-team-name-with-icon {font-size: 20px;}

		#foot-before-match-brand {
			position: absolute;
			left: 0;
			right: 0;
			bottom: 10%;
		}
		#foot-before-match-brand > div {
			position: relative;
			height: 100px;
		}
		#foot-before-match-brand > div > div {
			position: absolute;
			left: 0;
			right: 0;
		}
		#foot-before-match-brand-bar {
			top: 25%;
			bottom: 25%;
			background-color: black;
			border-top: 1px solid;
			border-bottom: 1px solid;
		}
		#foot-before-match-brand-infos {height: 100%;}
		#foot-before-match-brand img {height: 100%;}
		#foot-before-match-brand .foot-score {
			width: 430px;
			font-size: 25px;
		}

		#foot-view-header {
			width: 550px;
			margin-top: 10px;
			text-align: center;
		}
		#foot-view-header > div:first-child {position: relative;}
		#foot-view-header .foot-score-nb {font-size: 50px;}
		#foot-view-header .foot-score > div:first-child,
		#foot-view-header .foot-score > div:last-child {width: 30%;}
		#foot-view-header img {height: 70px;}
		#foot-view-header-time {
			display: flex;
			flex-direction: column;
			align-items: center;
			width: 120px;
			font-size: 15px;
		}
		#foot-view-header-time > div:last-child {
			width: 50px;
			height: 3px;
			margin-top: 5px;
			position: relative;
		}
		#foot-view-header-time > div:last-child > div {
			position: absolute;
			top: 0;
			bottom: 0;
			background-color: #00a439;
			animation: timebar 4s infinite;
		}
		#foot-view-header-time.foot-match-in-progress {color: #00a439;}
		#foot-view-header-time:not(.foot-match-in-progress) > div:last-child {display: none;}
		#foot-previous-score, #foot-view-header-trb {
			position: absolute;
			left: 0;
			right: 0;
		}
		#foot-previous-score {top: 0;}
		#foot-previous-score, #foot-view-header #foot-previous-score .foot-score-nb {font-size: 15px;}
		#foot-view-header-trb {bottom: 10px;}
		#foot-view-header-trb span {font-weight: bold;}
		#foot-match-name {
			padding: 5px 0 10px 0;
			font-size: 15px;
		}
		#foot-match-scores-and-red-cards-lists {
			max-height: 80px;
			overflow-y: auto;
		}
		#foot-match-scores-list-team-0, #foot-match-red-cards-list-team-0 {text-align: left;}

		#foot-highlights {
			font-size: 15px;
			flex-grow: 1;
			overflow: auto;
		}
		#foot-view.foot-playing-match #foot-highlights > div {transition-duration: 0.4s;}
		#foot-view.foot-playing-match #foot-highlights > div.beenadded {opacity: 0;}
		#foot-highlights > :first-child {margin-top: 0;}
		#foot-view.foot-playing-match #foot-highlights > :last-child {margin-bottom: 0;}
		#foot-view:not(.foot-playing-match) #foot-highlights > :last-child {margin-bottom: 5px;}

		.foot-highlight-time {
			display: flex;
			flex-direction: column;
			align-items: center;
			font-weight: bold;
			margin: 10px 0 10px 0;
		}
		.foot-highlight-time img {width: 25px;}
		.foot-highlight-time-title {width: 100%;}
		.foot-highlight-time-title div {
			width: 50%;
			height: 1.5px;
			background-color: white;
		}
		.foot-highlight-time-title span {
			white-space: nowrap;
			padding: 5px 10px 5px 10px;
		}
		.foot-highlight-time-showing-3-lines > :last-child {margin-top: 2.5px;}
		.foot-highlight-time-score {width: 100%;}

		.foot-highlight-box {
			background-color: rgba(0, 0, 0, 0.75);
			margin: 15px 0 15px 0;
			border: 1.5px solid;
			border-radius: 10px;
			overflow: hidden;
		}
		.foot-highlight-box-inner {
			padding: 10px;
		}
		.foot-highlight-box-hr {
			background-color: white;
			height: 1.5px;
			margin: 0 10px 0 10px;
		}
		.foot-highlight-box-inner-up {
			display: flex;
			justify-content: space-between;
			font-weight: bold;
			border-bottom: 1.5px solid;
		}
		.foot-highlight-box-inner-up > div {
			display: flex;
			align-items: center;
		}
		.foot-highlight-box-inner-up img {
			width: 25px;
			height: 25px;
			margin-right: 15px;
		}
		.foot-highlight-box-inner-up span {margin-top: 2px;}

		.foot-field-in-box {
			margin-top: 5px;
			background: url('/medias/scenarios/foot/Soccer_Field_Football_Pitch_clip_art-rot-90.svg');
			background-repeat: no-repeat;
			background-size: cover;
			aspect-ratio: 833 / 578;
		}
		.foot-field-in-box > div {height: 100%;}
		.foot-field-in-box > div > div > div > div {
			display: flex;
			align-items: center;
		}
		.foot-field-in-box .foot-perso-pp {
			width: 40px;
			height: 40px;
			border-radius: 40px;
			border: 2.75px solid;
		}
		.foot-field-in-box > div > div > div > div > :nth-child(2) {margin-left: -15px;}

		.foot-highlight-box-inner-perso-description, .foot-highlight-box-inner-perso-description > div:first-child > div:last-child {
			display: flex;
			align-items: center;
		}
		.foot-highlight-box-inner-perso-description {
			justify-content: space-between;
		}
		.foot-highlight-box-inner-perso-description > div:first-child > div:first-child {font-size: 18px;}
		.foot-highlight-box-inner-perso-description > div:first-child > div:last-child {margin-top: 5px;}
		.foot-highlight-box-inner-perso-description > div:first-child > div:last-child img {
			width: 20px;
			margin-right: 10px;
		}

		.foot-highlight-box-inner-substitution .foot-highlight-box-inner-perso-description {padding-top: 0;}
		.foot-highlight-box-inner-substitution-text {padding-bottom: 0;}
		.foot-highlight-box-inner-substitution-text-in {color: #00a439;}
		.foot-highlight-box-inner-substitution-text-out {
			color: red;
			padding-top: 0;
		}

		.foot-highlight-box-var {background-color: rgba(138, 21, 56, 0.75);}

		.foot-highlight-box-inner-up-goal {
			display: flex;
			flex-direction: column;
			align-items: center;
			background-color: #333;
			padding: 5px 0 0 0;
		}
		.foot-highlight-box-inner-up-goal > img {height: 25px;}
		.foot-highlight-box-inner-up-goal > span {font-weight: bold;}
		.foot-highlight-box-inner-up-goal > :nth-child(1),
		.foot-highlight-box-inner-up-goal > :nth-child(2) {margin-top: 5px;}
		.foot-highlight-box-inner-up-goal > :nth-child(3) {margin: 2.5px 0 10px 0;}
		.foot-highlight-box-inner-up-goal .foot-score {
			width: 100%;
			background-color: #444;
			padding: 10px;
		}
		.foot-highlight-box-inner-up-goal-missed {background-color: #300;}
		.foot-highlight-box-inner-up-goal-missed .foot-score {background-color: #400;}

		.foot-highlight-box-inner-showing-main-icon {
			display: flex;
			flex-direction: column;
			align-items: center;
		}
		.foot-highlight-box-inner-showing-main-icon img {height: 150px;}

		.foot-highlight-box-inner-announce-time {
			text-align: center;
			font-size: 60px;
			font-weight: bold;
		}

		.foot-highlight-box-decision {
			font-size: 20px;
			font-weight: bold;
		}
		.foot-highlight-box-decision-accepted {color: #00a439;}
		.foot-highlight-box-decision-refused {color: red;}

		.foot-highlight-box-inner-draw-result {
			display: flex;
			justify-content: center;
			align-items: center;
		}
		.foot-highlight-box-inner-draw-result > * {margin: 0 10px 0 10px;}
		.foot-highlight-box-inner-draw-result img {height: 80px;}
		.foot-highlight-box-inner-draw-result div {
			font-size: 25px;
			text-align: center;
			font-weight: bold;
		}

		.foot-highlight-box-containing-recap-score .foot-score {
			width: 80%;
			font-size: 30px;
		}
		.foot-highlight-box-containing-recap-score .foot-score img {height: 60px;}

		.foot-highlight-box-trb-match-point > :nth-child(2) {padding-bottom: 0;}
		.foot-highlight-box-trb-match-point > :nth-child(3) {padding-top: 0;}

		.foot-highlight-box-inner-trb-result .foot-score {padding: 5px;}

		.foot-highlight-trb-rows > div {
			display: flex;
			justify-content: center;
			align-items: center;
			padding: 5px;
			border-bottom: 1px solid;
		}
		.foot-highlight-trb-rows > div > :first-child,
		.foot-highlight-trb-rows > div > :last-child {width: 50%;}
		.foot-highlight-trb-rows > div > :last-child {text-align: right;}
		.foot-highlight-trb-rows > div > div > div {margin: 2.5px 0 2.5px 0;}
		.foot-highlight-trb-rows > div > div > div:last-child {font-size: 12.5px;}
		.foot-highlight-trb-rows > div > div > div:last-child span {font-weight: bold;}
		.foot-highlight-trb-rows > :first-child img {
			width: 25px;
			margin: 0 10px 0 10px;
		}
		.foot-highlight-trb-pen-dv {
			width: 30px;
			margin: 0 10px 0 10px;
		}
		.foot-highlight-trb-pen-dv img {width: 100%;}

		.foot-highlight-trb-first-shoot-message {
			text-align: center;
			padding: 10px 5px 0 5px;
		}

		.foot-highlight-box-match-over {font-weight: bold;}
		.foot-highlight-box-match-over.foot-highlight-box-match-over-winner {
			background-image: url('/src/icons/scenarios/foot/6929171_3483566.svg');
			background-size: cover;
			background-position: 50% 0%;
		}
		.foot-highlight-box-match-over > :first-child {font-size: 30px;}
		.foot-highlight-box-match-over.foot-highlight-box-match-over-winner > :first-child {
			padding: 10px;
			font-size: 20px;
			text-shadow: -2px -2px 0 #00b15d, 2px -2px 0 #00b15d, -2px 2px 0 #00b15d, 2px 2px 0 #00b15d;
		}
		.foot-highlight-box-match-over-winner > :last-child {
			margin-bottom: 10px;
			font-size: 40px;
			text-shadow: -3px -3px 0 #00b15d, 3px -3px 0 #00b15d, -3px 3px 0 #00b15d, 3px 3px 0 #00b15d;
		}
		.foot-highlight-box-match-over-winner img {height: 80px;}
		.foot-highlight-box-match-over:not(.foot-highlight-box-match-over-winner) > :first-child {margin: 10px;}
		.foot-highlight-box-match-over:not(.foot-highlight-box-match-over-winner) > :last-child {margin: 0 0 10px 0;}

		#foot-view:not(.foot-playing-match) #foot-next-btn {display: none;}
*/

		@media screen and (min-width: 601px) {
			#foot-cat-content-match-stats {
				min-width: 400px;
				max-width: 80%;
			}
			#foot-match-stats-persos, #foot-match-stats-notes {
				min-width: 500px;
				max-width: 600px;
			}
			#foot-comments {margin: 0 15px;}
		}

		@media screen and (max-width: 600px) {
			.foot-box-team-icon-dv {
				width: 25px;
				height: 25px;
			}
			.foot-contain-match-boxes, .foot-contain-cup-groups {font-size: 17.5px;}
			#foot-cup-winner .foot-box {width: 250px;}
			#foot-cup-winner .foot-box > img {
				width: 55px;
				height: 55px;
			}
			#foot-cup-winner .foot-box > :nth-child(2) {
				font-size: 20px;
				margin-top: -20px;
			}
			#foot-cup-winner .foot-box-team-icon-dv {
				width: 47.5px;
				height: 47.5px;
				margin-right: 7.5px;
			}
			#foot-cup-winner .foot-box-team-name {
				font-size: 27.5px;
				max-width: 190px;
			}
			#foot-cup-grid {
				width: 950px;
				--foot-box-total-height: calc(60px + 10px*2 + 7.5px*2);
			}
			#foot-cup-grid .foot-box {width: 195px;}
			#foot-cup-grid > div:last-child > div:last-child {margin-top: 197.5px;}
			#foot-cup-grid .foot-match-forfeit-text:first-letter {font-size: 17.5px;}
			.foot-box-group {font-size: 15px;}
			.foot-box-group tr:first-child {font-size: 12.5px}
			.foot-box-group .foot-box-team-name {width: 100px;}
			#foot-cat-content-matchs {max-height: 400px;}
			#foot-cat-content-matchs .foot-box {width: 65%;}
			#foot-cat-content-manage {display: none;}

			#foot-cat-content-match-stats {width: 85%;}
			.foot-cat-content-match-stats-head-text {
				font-size: 12px;
				width: 100%;
			}
			.foot-match-stats-lists {font-size: 12.5px;}
			#foot-match-stats-probabilities {width: 100%;}
			.foot-match-stats-probability-operator-logo {height: 20px;}
			.foot-match-stats-probability-bar > div {height: 7.5px;}
			.foot-match-stats-probability-names {font-size: 12.5px;}
			.foot-match-stats-probability-names > :first-child,
			.foot-match-stats-probability-names > :last-child {width: calc(50% - 45px);}
			.foot-match-stats-probability-prcs {
				margin: 1.25px 0 1.75px 0;
				font-size: 12px;
			}

			#foot-match-stats-persos, #foot-match-stats-notes {
				width: 100%;
				max-width: 100%;
			}
			#foot-cat-content-match-stats .foot-box-team-name, .foot-field-team-header {font-size: 17.5px;}
			.foot-field-team-header .foot-box-team-icon-dv {
				width: 25px;
				height: 25px;
			}
			.foot-perso-pp-add-score {
				left: -7px;
				bottom: 3.5px;
				width: 22.5px;
			}
			.foot-perso-has-scored-plus .foot-perso-pp-add-score {left: -13px;}
			.foot-perso-pp-add-score-number {
				font-size: 12.5px;
				width: 15px;
				height: 15px;
				left: -5px;
				bottom: 5px;
				line-height: 16px;
			}
			.foot-perso-pp-add-substitution {
				width: 15px;
				height: 15px;
			}
			.foot-perso-pp-add-card {width: 25px;}
			.foot-perso-pp {
				width: 35px;
				height: 35px;
				border-radius: 35px;
				border: 2px solid;
			}
			.foot-perso-name {font-size: 12.5px;}
			.foot-perso-position {font-size: 11px;}
			.foot-persos-lists .foot-perso-infos {width: calc(100% - 52.5px);}
		}

		@media (hover: hover) {
			.foot-box-selectable, .foot-box-group, #foot-cup-manage > div, #foot-cat-content-match-stats-bkg {cursor: pointer;}
			.foot-box-selectable, .foot-box-group tr, #foot-cup-manage > div {transition-duration: 0.4s;}
		}
	</style>
	<script src="https://cdn.jsdelivr.net/npm/jquery.scrollto@2.1.3/jquery.scrollTo.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js" integrity="sha512-+H4iLjY3JsKiF2V6N366in5IQHj2uEsGV7Pp/GRcm0fn76aPAk5V8xB6n8fQhhSonTqTXs/klFz4D0GIn6Br9g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/fr.min.js" integrity="sha512-RAt2+PIRwJiyjWpzvvhKAG2LEdPpQhTgWfbEkFDCo8wC4rFYh5GQzJBVIFDswwaEDEYX16GEE/4fpeDNr7OIZw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script type="text/javascript" src="/src/lib/comments.js"></script>
	<script type="text/javascript">
	$(document).ready(function(){
		function getRandomInt(max) {
			return Math.floor(Math.random()*max);
		}

		function getProbablyReversedRandomScore(score,reversalProbability) {
			if (getRandomInt(reversalProbability) != 0) return [score[1],score[0]];
			return score;
		}

		function getTrbAddedRandomScore(randomScore) {
			var randomTrbScore = [0,0];
			if (getRandomInt(5) == 0) {
				randomTrbScore[0] = 4+getRandomInt(5);
				randomTrbScore[1] = randomTrbScore[0]-1;
			}
			randomTrbScore[0] = 3+getRandomInt(3);
			randomTrbScore[1] = randomTrbScore[0]-getRandomInt(2)-1;
			return randomScore.concat(getProbablyReversedRandomScore(randomTrbScore,2));
		}

		function getRandomScore(needsAWinner=false,previousScore=null,team0IsAtHome=true,teamsWinProbabilityBonus=null) {
			if (needsAWinner) {
				var randomScore = getRandomScore(false,null,team0IsAtHome);
				if (previousScore !== null) {
					previousScore = [previousScore[1],previousScore[0]];
					if (previousScore[0]+randomScore[0] == previousScore[1]+randomScore[1]) {
						if (getRandomInt(2) == 0) return getTrbAddedRandomScore(randomScore);
						while (previousScore[0]+randomScore[0] == previousScore[1]+randomScore[1]) randomScore = getRandomScore(false,null,team0IsAtHome);
						return randomScore;
					}
					return randomScore;
				}
				if (randomScore[0] == randomScore[1] && getRandomInt(2) == 0) return getTrbAddedRandomScore(randomScore);
				while (randomScore[0] == randomScore[1]) randomScore = getRandomScore(false,null,team0IsAtHome);
				return randomScore;
			}
			var randomScoreNb = randomScores['res'][getRandomInt(randomScores['resLen'])];
			var randomScoreTeamsDistribution = getRandomInt(100);
			if (teamsWinProbabilityBonus !== null) {
				randomScoreTeamsDistribution += teamsWinProbabilityBonus[0]-teamsWinProbabilityBonus[1];
				if (randomScoreTeamsDistribution < 0) randomScoreTeamsDistribution = 0;
				if (randomScoreTeamsDistribution > 100) randomScoreTeamsDistribution = 100;
			}
			var randomScore = [
				Math.round(randomScoreNb*randomScoreTeamsDistribution/100),
				Math.round(randomScoreNb*(100-randomScoreTeamsDistribution)/100)
			];
			if (team0IsAtHome && randomScore[0] < randomScore[1] && teamsWinProbabilityBonus === null) return getProbablyReversedRandomScore(randomScore,2);
			return randomScore;
		}

		function getTeamRow(teamsToUse,teamIndex) {
			teamRow = {'mj':0,'g':0,'n':0,'p':0,'bp':0,'bc':0,'db':0,'pts':0};
			if (teamsToUse !== null) {
				if (teamsToUse[teamIndex] !== undefined) teamRow['team'] = {'id':teamIndex,'type':'normal','name':teamsToUse[teamIndex]};
				else teamRow['team'] = {'id':teamIndex,'type':'generic','name':'Équipe '+teamIndex};
			}
			else {
				teamRow['team'] = {'type':'blank'};
				teamRow['temp'] = true;
			}
			return teamRow;
		}

		function getRandomizedMatchsToUse(catType,matchs,randomization) {
			var matchsToUseRandomCompleted = $.extend(true,[],matchs);
			var matchsToUseRandomized = [];
			if (catType == 0) {
				for (var k = 0; k < 284; k++) matchsToUseRandomized.push(getRandomScore());
				for (var k = 0; k < 2; k++) matchsToUseRandomized.push(getRandomScore(true,null,false));
			}
			else {
				for (k in championshipMatchsOrder) for (l in championshipMatchsOrder[k]) matchsToUseRandomized.push(getRandomScore(false,null,true,[(20-championshipMatchsOrder[k][l][1])/20*50,(20-championshipMatchsOrder[k][l][0])/20*50]));
				for (k in championshipMatchsOrder) for (l in championshipMatchsOrder[k]) matchsToUseRandomized.push(getRandomScore(false,null,true,[(20-championshipMatchsOrder[k][l][0])/20*50,(20-championshipMatchsOrder[k][l][1])/20*50]));
			}
			if (randomization == 1) for (var i = matchsToUseRandomCompleted.length; i < matchsToUseRandomized.length; i++) matchsToUseRandomCompleted.push(matchsToUseRandomized[i]);
			else matchsToUseRandomCompleted = matchsToUseRandomized;
			if (catType == 0 && matchs[283] === undefined) {
				for (var i = 264; i < 272; i++) if (matchs[i] === undefined) matchsToUseRandomCompleted[i] = getRandomScore(true,matchsToUseRandomCompleted[i-8]);
				for (var i = 276; i < 280; i++) if (matchs[i] === undefined) matchsToUseRandomCompleted[i] = getRandomScore(true,matchsToUseRandomCompleted[i-4]);
				for (var i = 282; i < 284; i++) if (matchs[i] === undefined) matchsToUseRandomCompleted[i] = getRandomScore(true,matchsToUseRandomCompleted[i-2]);
			}
			return matchsToUseRandomCompleted;
		}

		function setMatchGoalsSum(match) {
			match[0]['goalsSum'] = match[0]['goals0'];
			match[1]['goalsSum'] = match[1]['goals0'];
			if (match[0]['goals1'] !== undefined) {
				match[0]['goalsSum'] += match[0]['goals1'];
				match[1]['goalsSum'] += match[1]['goals1'];
			}
			if (match[0]['trb'] !== undefined) {
				match[0]['goalsSum'] += match[0]['trb'];
				match[1]['goalsSum'] += match[1]['trb'];
			}
			if (match[0]['previousGoals'] !== undefined) {
				match[0]['goalsSum'] += match[0]['previousGoals'];
				match[1]['goalsSum'] += match[1]['previousGoals'];
			}
			if (match[0]['goalsSum'] > match[1]['goalsSum']) match[1]['isLooser'] = true;
			else if (match[0]['goalsSum'] < match[1]['goalsSum']) match[0]['isLooser'] = true;
		}

		function addGroupMatch(group,teamsIndexes,matchToUse,matchsRecapDay) {
			match = [
				{'team':$.extend(true,{},group[teamsIndexes[0]]['team'])},
				{'team':$.extend(true,{},group[teamsIndexes[1]]['team'])}
			];
			if (matchToUse === undefined || matchToUse === null) {
				match[0]['temp'] = true;
				match[1]['temp'] = true;
			}
			else {
				var winnerTeamIndex = 2;
				group[teamsIndexes[0]]['mj']++;
				group[teamsIndexes[1]]['mj']++;
				if (!isNaN(matchToUse[0]) && !isNaN(matchToUse[1])) {
					match[0]['goals0'] = matchToUse[0];
					match[1]['goals0'] = matchToUse[1];
					setMatchGoalsSum(match);
					group[teamsIndexes[0]]['bp'] += matchToUse[0];
					group[teamsIndexes[0]]['bc'] += matchToUse[1];
					group[teamsIndexes[1]]['bp'] += matchToUse[1];
					group[teamsIndexes[1]]['bc'] += matchToUse[0];
					winnerTeamIndex = (matchToUse[0] > matchToUse[1] ? 0 : (matchToUse[0] < matchToUse[1] ? 1 : 2));
				}
				else {
					if (matchToUse[0] == 'F') {
						match[0]['isLooser'] = true;
						match[0]['forfeit'] = true;
						winnerTeamIndex = 1;
					}
					if (matchToUse[1] == 'F') {
						match[1]['isLooser'] = true;
						match[1]['forfeit'] = true;
						winnerTeamIndex = 0;
					}
					if (matchToUse[0] == 'F' && matchToUse[1] == 'F') winnerTeamIndex = 2;
				}
				if (winnerTeamIndex == 2) {
					group[teamsIndexes[0]]['n']++;
					group[teamsIndexes[1]]['n']++;
				}
				else {
					group[teamsIndexes[winnerTeamIndex]]['g']++;
					group[teamsIndexes[1-winnerTeamIndex]]['p']++;
				}
				if (matchToUse[4] !== undefined) match[0]['stats'] = matchToUse[4];
			}
			matchsRecapDay.push(match);
		}

		function setCatEditionStats(catID,editionID,randomization=0) {
			var catType = footCats[catID]['type'];
			var usedStrats = footCats[catID]['editions'][editionID]['usedStrats'];
			var stratsDistribution = {};
			if (usedStrats !== undefined) {
				var maxMatchDayIndex = (catType == 0 ? 18 : 39);
				var teamUsedStrats;
				var from;
				var to;
				var strat;
				for (team in usedStrats) {
					teamUsedStrats = Array(maxMatchDayIndex+1).fill(0);
					for (stratDistribution in usedStrats[team]) {
						strat = usedStrats[team][stratDistribution];
						stratDistribution = stratDistribution.split(',');
						for (k in stratDistribution) {
							stratDistribution[k] = stratDistribution[k].split('-');
							if (stratDistribution[k].length == 2) {
								from = 0;
								to = maxMatchDayIndex;
								if (stratDistribution[k][0] != '') from = parseInt(stratDistribution[k][0]);
								if (stratDistribution[k][1] != '') to = parseInt(stratDistribution[k][1]);
								if (from < 0) from = 0;
								if (to > maxMatchDayIndex) to = maxMatchDayIndex;
								for (var l = from; l <= to; l++) teamUsedStrats[l] = strat;
							}
							else teamUsedStrats[stratDistribution[k][0]] = strat;
						}
					}
					stratsDistribution[team] = teamUsedStrats;
				}
			}
			var teamRow;
			var teamsToUse = footCats[catID]['editions'][editionID]['teams'];
			if (catType == 0) {
				var offGroups = [];
				var matchsProcessedInOffGroups = [];
				for (var k = 0; k < 16; k++) {
					offGroups.push([]);
					matchsProcessedInOffGroups.push(0);
					for (var l = 0; l < 5; l++) offGroups[k].push(getTeamRow(teamsToUse,k*5+l+1));
				}
				var groups = [];
				var matchsProcessedInGroups = [];
				for (var k = 0; k < 8; k++) {
					groups.push([]);
					matchsProcessedInGroups.push(0);
					for (var l = 0; l < 4; l++) groups[k].push(getTeamRow(null));
				}
				var grid = [];
				var blankMatch = [
					{'team':{'type':'blank'},'temp':true},
					{'team':{'type':'blank'},'temp':true}
				];
				for (var k = 0; k < 4; k++) {
					grid.push([]);
					matchsNumber = Math.pow(2,3-k);
					for (var l = 0; l < matchsNumber; l++) {
						grid[k].push($.extend(true,[],blankMatch));
						if (k == 3) grid[k].push($.extend(true,[],blankMatch));
					}
				}
			}
			else {
				var leaderboard = [];
				for (var k = 0; k < 20; k++) leaderboard.push(getTeamRow(teamsToUse,k+1));
			}
			var matchs = footCats[catID]['editions'][editionID]['matchs'];
			var matchsToUse = $.extend(true,[],matchs);
			if (randomization > 0) {
				matchsToUse = getRandomizedMatchsToUse(catType,matchs,randomization);
				footCats[catID]['editions'][editionID]['hasBeenRandomized'] = true;
			}
			var matchsRecap = [];
			var matchToUse;
			var matchIndex;
			var match;
			var penalties = {};
			if (footCats[catID]['editions'][editionID]['penalties'] !== undefined) penalties = footCats[catID]['editions'][editionID]['penalties'];
			if (catType == 0) {
				var offGroupMatchOrder = [
					[[0,4],[1,3]],
					[[3,0],[2,1]],
					[[0,2],[4,3]],
					[[1,0],[2,4]],
					[[4,1],[3,2]]
				];
				for (k in offGroupMatchOrder) {
					matchsRecap.push([]);
					for (l in offGroups) {
						for (m in offGroupMatchOrder[k]) {
							matchToUse = matchsToUse[k*16*2+l*2+m*1];
							addGroupMatch(offGroups[l],offGroupMatchOrder[k][m],matchToUse,matchsRecap[k]);
							if (matchToUse !== undefined && matchToUse !== null) matchsProcessedInOffGroups[l]++;
						}
					}
				}
				for (k in offGroups) {
					if (matchsProcessedInOffGroups[k] > 0) {
						for (l in offGroups[k]) {
							offGroups[k][l]['db'] = offGroups[k][l]['bp']-offGroups[k][l]['bc'];
							offGroups[k][l]['pts'] = offGroups[k][l]['g']*3+offGroups[k][l]['n'];
						}
					}
					for (l in offGroups[k]) if (penalties[offGroups[k][l]['team']['name']] !== undefined) offGroups[k][l]['pts'] -= penalties[offGroups[k][l]['team']['name']][0];
					if (matchsProcessedInOffGroups[k] > 0) {
						offGroups[k] = offGroups[k].orderByKeyDesc('bp').orderByKeyDesc('db').orderByKeyDesc('pts');
						if (matchsProcessedInOffGroups[k] > 1) {
							if (k%2 == 0) {
								groups[k/2][0]['team'] = offGroups[k][0]['team'];
								if (k < 8) groups[k/2+4][2]['team'] = offGroups[k][1]['team'];
								else groups[k/2-4][2]['team'] = offGroups[k][1]['team'];
							}
							else {
								groups[(k-1)/2][1]['team'] = offGroups[k][0]['team'];
								if (k < 8) groups[(k-1)/2+4][3]['team'] = offGroups[k][1]['team'];
								else groups[(k-1)/2-4][3]['team'] = offGroups[k][1]['team'];
							}
							if (matchsProcessedInOffGroups[k] == 10) {
								if (k%2 == 0) {
									delete groups[k/2][0]['temp'];
									if (k < 8) delete groups[k/2+4][2]['temp'];
									else delete groups[k/2-4][2]['temp'];
								}
								else {
									delete groups[(k-1)/2][1]['temp'];
									if (k < 8) delete groups[(k-1)/2+4][3]['temp'];
									else delete groups[(k-1)/2-4][3]['temp'];
								}
							}
						}
					}
				}
				var groupMatchsOrder = [
					[[0,3],[1,2]],
					[[2,0],[3,1]],
					[[0,1],[2,3]]
				];
				var teamsIndexes;
				for (var k = 0; k < 2; k++) {
					for (l in groupMatchsOrder) {
						matchsRecap.push([]);
						for (m in groups) {
							for (n in groupMatchsOrder[l]) {
								matchToUse = matchsToUse[160+k*48+l*16+m*2+n*1];
								teamsIndexes = groupMatchsOrder[l][n];
								if (k == 1) teamsIndexes = [teamsIndexes[1],teamsIndexes[0]];
								addGroupMatch(groups[m],teamsIndexes,matchToUse,matchsRecap[5+k*3+l*1]);
								if (matchToUse !== undefined && matchToUse !== null) matchsProcessedInGroups[m]++;
							}
						}
					}
				}
				for (k in groups) {
					if (matchsProcessedInGroups[k] > 0) {
						for (l in groups[k]) {
							groups[k][l]['db'] = groups[k][l]['bp']-groups[k][l]['bc'];
							groups[k][l]['pts'] = groups[k][l]['g']*3+groups[k][l]['n'];
						}
					}
					for (l in groups[k]) if (penalties[groups[k][l]['team']['name']] !== undefined) groups[k][l]['pts'] -= penalties[groups[k][l]['team']['name']][1];
					if (matchsProcessedInGroups[k] > 0) {
						groups[k] = groups[k].orderByKeyDesc('bp').orderByKeyDesc('db').orderByKeyDesc('pts');
						if (matchsProcessedInGroups[k] > 1) {
							if (k%2 == 0) {
								grid[0][k/2][0]['team'] = groups[k][0]['team'];
								grid[0][k/2+4][1]['team'] = groups[k][1]['team'];
							}
							else {
								grid[0][(k-1)/2+4][0]['team'] = groups[k][0]['team'];
								grid[0][(k-1)/2][1]['team'] = groups[k][1]['team'];
							}
							if (matchsProcessedInGroups[k] == 12) {
								if (k%2 == 0) {
									delete grid[0][k/2][0]['temp'];
									delete grid[0][k/2+4][1]['temp'];
								}
								else {
									delete grid[0][(k-1)/2+4][0]['temp'];
									delete grid[0][(k-1)/2][1]['temp'];
								}
							}
						}
					}
				}
				matchIndex = 255;
				for (var k = 0; k < 3; k++) {
					for (var l = 0; l < 2; l++) {
						matchsRecap.push([]);
						for (m in grid[k]) {
							matchIndex++;
							matchToUse = matchsToUse[matchIndex];
							match = [
								{'team':$.extend(true,{},grid[k][m][0]['team'])},
								{'team':$.extend(true,{},grid[k][m][1]['team'])}
							];
							if (l == 1) {
								match = [match[1],match[0]];
								var previousMatch = matchsRecap[11+k*2][m];
								if (previousMatch[0]['forfeit'] !== undefined || previousMatch[1]['forfeit'] !== undefined) {
									match[0]['temp'] = true;
									match[1]['temp'] = true;
									matchsRecap[11+k*2+1].push(match);
									continue;
								}
							}
							if (matchToUse === undefined || matchToUse === null) {
								match[0]['temp'] = true;
								match[1]['temp'] = true;
							}
							else {
								if (!isNaN(matchToUse[0]) && !isNaN(matchToUse[1])) {
									match[0]['goals0'] = matchToUse[0];
									match[1]['goals0'] = matchToUse[1];
									if (l == 0) {
										grid[k][m][0]['goals0'] = matchToUse[0];
										grid[k][m][1]['goals0'] = matchToUse[1];
									}
									else {
										grid[k][m][0]['goals1'] = matchToUse[1];
										grid[k][m][1]['goals1'] = matchToUse[0];
										match[0]['previousGoals'] = grid[k][m][1]['goals0'];
										match[1]['previousGoals'] = grid[k][m][0]['goals0'];
										if (matchToUse[2] !== undefined && matchToUse[2] !== null && matchToUse[3] !== undefined && matchToUse[3] !== null) {
											grid[k][m][0]['trb'] = matchToUse[3];
											grid[k][m][1]['trb'] = matchToUse[2];
											match[0]['trb'] = matchToUse[2];
											match[1]['trb'] = matchToUse[3];
										}
										delete grid[k][m][0]['isLooser'];
										delete grid[k][m][1]['isLooser'];
									}
									setMatchGoalsSum(match);
									setMatchGoalsSum(grid[k][m]);
									if (grid[k][m][0]['goalsSum'] > grid[k][m][1]['goalsSum']) grid[k+1][(m-m%2)/2][m%2]['team'] = grid[k][m][0]['team'];
									else if (grid[k][m][0]['goalsSum'] < grid[k][m][1]['goalsSum']) grid[k+1][(m-m%2)/2][m%2]['team'] = grid[k][m][1]['team'];
									else grid[k+1][(m-m%2)/2][m%2]['team'] = {'type':'blank'};
									if (l == 1) delete grid[k+1][(m-m%2)/2][m%2]['temp'];
								}
								else if (matchToUse[0] == 'F' && matchToUse[1] == null || matchToUse[0] == null && matchToUse[1] == 'F') {
									if (l == 1) {
										delete grid[k][m][0]['isLooser'];
										delete grid[k][m][1]['isLooser'];
										delete grid[k][m][0]['goals0'];
										delete grid[k][m][1]['goals0'];
									}
									if (matchToUse[0] == 'F') {
										match[0]['isLooser'] = true;
										match[0]['forfeit'] = true;
										grid[k][m][l]['isLooser'] = true;
										grid[k][m][l]['forfeit'] = true;
										grid[k+1][(m-m%2)/2][m%2]['team'] = grid[k][m][1-l]['team'];
									}
									if (matchToUse[1] == 'F') {
										match[1]['isLooser'] = true;
										match[1]['forfeit'] = true;
										grid[k][m][1-l]['isLooser'] = true;
										grid[k][m][1-l]['forfeit'] = true;
										grid[k+1][(m-m%2)/2][m%2]['team'] = grid[k][m][l]['team'];
									}
									delete grid[k+1][(m-m%2)/2][m%2]['temp'];
								}
								else {
									match[0]['temp'] = true;
									match[1]['temp'] = true;
								}
								if (matchToUse[4] !== undefined) match[0]['stats'] = matchToUse[4];
							}
							matchsRecap[11+k*2+l*1].push(match);
						}
					}
				}
				if (matchsToUse[281] !== undefined) {
					for (var k = 0; k < 2; k++) {
						if (grid[2][k][0]['goalsSum'] < grid[2][k][1]['goalsSum']) grid[3][1][k]['team'] = grid[2][k][0]['team'];
						else if (grid[2][k][0]['goalsSum'] > grid[2][k][1]['goalsSum']) grid[3][1][k]['team'] = grid[2][k][1]['team'];
					}
					if (matchsToUse[282] !== undefined) delete grid[3][1][0]['temp'];
					if (matchsToUse[283] !== undefined) delete grid[3][1][1]['temp'];
				}
				var finalMatch;
				for (var k = 0; k < 2; k++) {
					matchToUse = matchsToUse[k+284];
					finalMatch = grid[3][1-k];
					if (matchToUse !== undefined) {
						grid[3][1-k][0]['goals0'] = matchToUse[0];
						grid[3][1-k][1]['goals0'] = matchToUse[1];
						if (matchToUse[2] !== undefined && matchToUse[2] !== null && matchToUse[3] !== undefined && matchToUse[3] !== null) {
							grid[3][1-k][0]['trb'] = matchToUse[2];
							grid[3][1-k][1]['trb'] = matchToUse[3];
						}
						setMatchGoalsSum(grid[3][1-k]);
						finalMatch = $.extend(true,[],finalMatch);
						if (matchToUse[4] !== undefined) finalMatch[0]['stats'] = matchToUse[4];
					}
					matchsRecap.push([finalMatch]);
				}
				if (matchsToUse[285] !== undefined) {
					if (grid[3][0][0]['goalsSum'] > grid[3][0][1]['goalsSum']) footCats[catID]['editions'][editionID]['winner'] = grid[3][0][0]['team'];
					else if (grid[3][0][0]['goalsSum'] < grid[3][0][1]['goalsSum']) footCats[catID]['editions'][editionID]['winner'] = grid[3][0][1]['team'];
				}
				footCats[catID]['editions'][editionID]['offGroups'] = offGroups;
				footCats[catID]['editions'][editionID]['groups'] = groups;
				footCats[catID]['editions'][editionID]['grid'] = grid;
			}
			else {
				for (var k = 0; k < 2; k++) {
					for (l in championshipMatchsOrder) {
						matchsRecap.push([]);
						for (m in championshipMatchsOrder[l]) {
							matchIndex = k*190+l*10+m*1;
							if (l > 14) matchIndex = k*190+150+(l-15)*8+m*1;
							matchToUse = matchsToUse[matchIndex];
							teamsIndexes = [championshipMatchsOrder[l][m][0],championshipMatchsOrder[l][m][1]];
							if (k == 0) teamsIndexes.reverse();
							addGroupMatch(leaderboard,teamsIndexes,matchToUse,matchsRecap[k*20+l*1]);
						}
					}
				}
				for (k in leaderboard) {
					leaderboard[k]['db'] = leaderboard[k]['bp']-leaderboard[k]['bc'];
					leaderboard[k]['pts'] = leaderboard[k]['g']*3+leaderboard[k]['n'];
				}
				for (k in leaderboard) if (penalties[leaderboard[k]['team']['name']] !== undefined) leaderboard[k]['pts'] -= penalties[leaderboard[k]['team']['name']][0];
				footCats[catID]['editions'][editionID]['leaderboard'] = leaderboard.orderByKeyDesc('bp').orderByKeyDesc('db').orderByKeyDesc('pts');
				var firstTeamIsWinner = true;
				for (var k = 1; k < 20; k++) if (leaderboard[k]['pts']+(38-leaderboard[k]['mj'])*3 > leaderboard[0]['pts']) firstTeamIsWinner = false;
				if (firstTeamIsWinner) footCats[catID]['editions'][editionID]['winner'] = leaderboard[0]['team'];
			}
			footCats[catID]['editions'][editionID]['stratsDistribution'] = stratsDistribution;
			footCats[catID]['editions'][editionID]['matchsToUse'] = matchsToUse;
			footCats[catID]['editions'][editionID]['matchsRecap'] = matchsRecap;
			footCats[catID]['editions'][editionID]['editionIsOver'] = (catType == 0 && matchs[285] !== undefined || catType == 1 && (matchs[379] !== undefined || footCats[catID]['editions'][editionID]['winner'] !== undefined));
		}

		function getTeamIconSrc(team) {
			return (team['type'] == 'normal' && footTeams[team['name']] !== undefined ? addPathPartIfNotURL('/medias/scenarios/foot/icons/teams/',footTeams[team['name']]['icon']) : (team['type'] == 'blank' ? 'https://www.gstatic.com/onebox/sports/logos/crest_48dp.png' : '/medias/scenarios/foot/icons/teams/_generic.png'));
		}

		function getTeamNameHTML(team) {
			return (team['type'] == 'normal' ? team['name'] : (team['type'] == 'blank' ? '<i>À venir</i>' : '<i>'+team['name']+'</i>'));
		}

		function getTeamNameAndIcon(team,temp=false,match=null,teamIndex=null) {
			return '<div class="foot-team-name-and-icon'+(temp ? ' foot-team-name-and-icon-temp' : '')+(match !== null && match[teamIndex]['isLooser'] !== undefined ? ' foot-match-box-team-looser' : '')+' flx flx-ac"><div class="foot-box-team-icon-dv"><img src="'+getTeamIconSrc(team)+'"></div><div class="foot-box-team-name hide-scrollbar">'+getTeamNameHTML(team)+'</div>'+(match === null ? '' : ('<div class="flx flx-ac">'+(match[0]['stats'] !== undefined && match[0]['stats']['redCards'] !== undefined && match[0]['stats']['redCards'][teamIndex].length > 0 ? '<img src="https://ssl.gstatic.com/onebox/sports/soccer_timeline/red-card-right.svg"/>' : '')+(match[0]['goals0'] !== undefined ? match[teamIndex]['goals0']+(match[teamIndex]['goals1'] !== undefined ? ' '+match[teamIndex]['goals1'] : '')+(match[teamIndex]['trb'] !== undefined ? '('+match[teamIndex]['trb']+')' : '') : (match[teamIndex]['forfeit'] !== undefined ? '<span class="foot-match-forfeit-text">FORFAIT</span>' : ''))+'</div>'))+'</div>';
		}

		function getGroupTableHTML(group,title) {
			var HTML = '<div class="foot-box foot-box-group"><div>'+title+'</div><table><tr><th colspan="3"></th><th class="foot-cup-group-table-team-name">Équipe</th><th>MJ</th><th>G</th><th>N</th><th>P</th><th>BP</th><th>BC</th><th>DB</th><th>Pts</th></tr>';
			for (j in group) HTML += '<tr><td></td><td></td><td>'+(parseInt(j)+1)+'</td><td class="foot-cup-group-table-team-name">'+getTeamNameAndIcon(group[j]['team'],group[j]['temp'] === true)+'</td><td>'+group[j]['mj']+'</td><td>'+group[j]['g']+'</td><td>'+group[j]['n']+'</td><td>'+group[j]['p']+'</td><td>'+group[j]['bp']+'</td><td>'+group[j]['bc']+'</td><td>'+group[j]['db']+'</td><td>'+group[j]['pts']+'</td></tr>';
			return HTML+'</table></div>';
		}

		function getCatContentTeamAttributionClass(team) {
			return (team['type'] != 'blank' ? ' foot-cat-content-contain-team-'+team['id'] : '');
		}

		function getMatchBoxHTML(match,matchRecapDayIndex=null,matchRecapIndex=null) {
			var matchIsDefined = (match[0]['goals0'] !== undefined || match[0]['forfeit'] !== undefined || match[1]['forfeit'] !== undefined);
			var HTML = '<div '+(matchRecapDayIndex !== null ? 'id="foot-match-recap-'+matchRecapDayIndex+'-'+matchRecapIndex+'"' : '')+' class="foot-box foot-box-selectable'+(matchRecapDayIndex !== null ? getCatContentTeamAttributionClass(match[0]['team']) : '')+(matchRecapDayIndex !== null ? getCatContentTeamAttributionClass(match[1]['team']) : '')+(matchIsDefined ? ' foot-box-match-defined' : '')+'">';
			if (matchIsDefined) for (var n = 0; n < 2; n++) HTML += getTeamNameAndIcon(match[n]['team'],match[n]['temp'] === true,match,n);
			else for (var n = 0; n < 2; n++) HTML += getTeamNameAndIcon(match[n]['team'],match[n]['temp'] === true);
			return HTML+'</div>';
		}

		function getMatchsRecapDayHTML(title,matchsRecapDay,matchRecapDayIndex) {
			var HTML = '<div class="foot-matchs-separation flx flx-jc flx-ac';
			for (j in matchsRecapDay) for (var k = 0; k < 2; k++) HTML += getCatContentTeamAttributionClass(matchsRecapDay[j][k]['team']);
			HTML += '"><div></div><div>'+title+'</div><div></div></div><div class="flx flx-jc flx-ww">';
			for (j in matchsRecapDay) HTML += getMatchBoxHTML(matchsRecapDay[j],matchRecapDayIndex,j);
			return HTML+'</div>';
		}

		function getFootPersoPP(footPersoName) {
			var HTML = '<div';
			if (footPersoName != '') {
				footPersosMatchIndex++;
				footPersosMatch[footPersoName] = {'id':footPersosMatchIndex};
				HTML += ' id="foot-perso-pp-'+footPersosMatchIndex+'"';
			}
			return HTML+' class="foot-perso-pp-dv"><img class="foot-perso-pp" src="'+(footPersoName == '' || footPersos[footPersoName] === undefined ? '/medias/scenarios/foot/blank-pp.jpg' : footPersos[footPersoName]['pp'])+'"></div>';
		}

		function getFootPersoName(footPersoName) {
			return (footPersoName == '' ? '???' : footPersoName);
		}

		function getFootPersoDescriptionInList(footPerso) {
			return '<div class="flx flx-ac">'+(footPerso === undefined ? '' : getFootPersoPP(footPerso['name'])+'<div class="foot-perso-infos"><div class="foot-perso-name hide-scrollbar">'+getFootPersoName(footPerso['name'])+'</div><div class="foot-perso-position">'+jobs[footPerso['job']]+'</div></div>')+'</div>';
		}

		function getFootPersosListsHTML(list) {
			var txt = '';
			var len = list[0].length;
			if (list[1].length > len) len = list[1].length;
			for (var i = 0; i < len; i++) txt += '<div class="flx flx-jb">'+getFootPersoDescriptionInList(list[0][i])+getFootPersoDescriptionInList(list[1][i])+'</div>';
			return txt;
		}

		function addPersoPPAdd(footPersoName,type) {
			$('#foot-perso-pp-'+footPersosMatch[footPersoName]['id']).append(ppAdds[type]);
		}

		function addMatchsEventListeners() {
			$('#foot-cat-content-matchs > div > div.foot-box').click(function(){
				var matchRecapId = this.id.replace('foot-match-recap-','').split('-').map(mID => parseInt(mID));
				var matchRecapIdString = matchRecapId.join('-');
				var isRegularMatch = (currentCatType != 2);
				var matchRecap = (isRegularMatch ? footCats[currentCatID]['editions'][currentEditionID]['matchsRecap'][matchRecapId[0]][matchRecapId[1]] : footCats[currentCatID]['matchsRecap'][matchRecapId[1]]);
				if (!isRegularMatch) var friendlyMatch = footCats[currentCatID]['matchs'][matchRecapId[1]];
				var matchRecapStats = (isRegularMatch ? matchRecap[0]['stats'] : friendlyMatch['stats']);
				var matchHasStats = (matchRecapStats !== undefined);
				var matchsRecapHeaderText = 'Match amical';
				if (matchHasStats && matchRecapStats['name'] !== undefined) matchsRecapHeaderText = matchRecapStats['name'];
				else if (isRegularMatch) {
					matchsRecapHeaderText = footCats[currentCatID]['name']+' '+footCats[currentCatID]['editions'][currentEditionID]['name']+' - ';
					var isCupMatch = (currentCatType == 0);
					if (isCupMatch) matchsRecapHeaderText += cupPhaseTitles[matchRecapId[0]];
					else matchsRecapHeaderText += 'Journée '+(matchRecapId[0]+1);
				}
				if (matchHasStats && matchRecapStats['stadium'] !== undefined) matchsRecapHeaderText += '<br>'+matchRecapStats['stadium'];
				$('#foot-match-stats-score, #foot-match-stats-scores-lists, #foot-match-stats-red-cards-lists, #foot-match-stats-persos,  #foot-match-stats-field-team-0, #foot-match-stats-field-team-1, #foot-match-stats-substitues, #foot-match-stats-coachs, #foot-match-stats-staff, #foot-match-stats-referees, #foot-match-stats-referees-var, #foot-match-stats-notes').addClass('hidden');
				$('#foot-match-stats-aggregate, #foot-match-stats-forfeit-message, #foot-match-stats-probabilities, #foot-match-stats-field-team-icon-0, #foot-match-stats-field-team-icon-1, #foot-match-stats-field-0, #foot-match-stats-field-1, #foot-match-stats-substitues-team-icon-0, #foot-match-stats-substitues-team-icon-1, #foot-match-stats-substitues .foot-persos-lists, #foot-match-stats-coachs-team-icon-0, #foot-match-stats-coachs-team-icon-1, #foot-match-stats-coachs .foot-persos-lists, #foot-match-stats-staff-team-icon-0, #foot-match-stats-staff-team-icon-1, #foot-match-stats-staff .foot-persos-lists, #foot-match-stats-notes-content').html('');
				$('#foot-match-stats-header').html(matchsRecapHeaderText);
				for (var i = 0; i < 2; i++) {
					$('#foot-match-stats-team-name-'+i).html(getTeamNameHTML(matchRecap[i]['team']));
					$('#foot-match-stats-team-icon-'+i).attr('src',getTeamIconSrc(matchRecap[i]['team']));
				}
				if (isRegularMatch) {
					var isPlayoffMatch = isCupMatch && [12,14,16,17,18].includes(matchRecapId[0]);
					if (isPlayoffMatch && matchRecapId[0] < 17) {
						var previousMatch = footCats[currentCatID]['editions'][currentEditionID]['matchsRecap'][matchRecapId[0]-1][matchRecapId[1]];
						if (previousMatch[0]['goals0'] !== undefined) {
							var aggregate = [previousMatch[1]['goals0'],previousMatch[0]['goals0']];
							if (matchRecap[0]['goals0'] !== undefined) {
								aggregate[0] += matchRecap[0]['goals0'];
								aggregate[1] += matchRecap[1]['goals0'];
							}
							$('#foot-match-stats-aggregate').html('Cumul des scores : '+aggregate[0]+' à '+aggregate[1]);
						}
					}
				}
				if (matchRecap[0]['goals0'] !== undefined) {
					$('#foot-match-stats-score-team-0').html(matchRecap[0]['goals0']+(matchRecap[0]['trb'] !== undefined ? ' ('+matchRecap[0]['trb']+')' : ''));
					$('#foot-match-stats-score-team-1').html((matchRecap[1]['trb'] !== undefined ? '('+matchRecap[1]['trb']+') ' : '')+matchRecap[1]['goals0']);
					$('#foot-match-stats-score').removeClass('hidden');
				}
				if (matchRecap[0]['forfeit'] !== undefined && matchRecap[1]['forfeit'] !== undefined) $('#foot-match-stats-forfeit-message').html('Les deux équipes ont déclaré forfait.');
				else if (matchRecap[0]['forfeit'] !== undefined) $('#foot-match-stats-forfeit-message').html(getTeamNameHTML(matchRecap[0]['team'])+' a déclaré forfait.');
				else if (matchRecap[1]['forfeit'] !== undefined) $('#foot-match-stats-forfeit-message').html(getTeamNameHTML(matchRecap[1]['team'])+' a déclaré forfait.');
				var res;
				var matchHasSpecs = false;
				if (isRegularMatch && footCats[currentCatID]['editions'][currentEditionID]['matchsSpecs'] !== undefined || !isRegularMatch) {
					var matchSpecs = (isRegularMatch ? footCats[currentCatID]['editions'][currentEditionID]['matchsSpecs'][matchRecapIdString] : friendlyMatch['specs']);
					matchHasSpecs = (matchSpecs !== undefined);
				}
				if (matchHasSpecs && matchSpecs['probabilities'] !== undefined) {
					var probabilities = matchSpecs['probabilities'];
					res = '';
					for (i in probabilities) {
						if (probabilities[i][1] == null) probabilities[i][1] = 100-probabilities[i][2]-probabilities[i][3];
						if (probabilities[i][2] == null) probabilities[i][2] = 100-probabilities[i][1]-probabilities[i][3];
						if (probabilities[i][3] == null) probabilities[i][3] = 100-probabilities[i][1]-probabilities[i][2];
						res += '<div><div class="flx flx-jc"><img class="foot-match-stats-probability-operator-logo" src="'+addPathPartIfNotURL('/medias/scenarios/foot/operator-logos/',footBetOperators[probabilities[i][0]]['icon'])+'"></div><div class="foot-match-stats-probability-names flx flx-jb"><div class="hide-scrollbar">'+getTeamNameHTML(matchRecap[0]['team'])+'</div><div>'+(isPlayoffMatch ? 'Prolongations' : 'Match nul')+'</div><div class="hide-scrollbar">'+getTeamNameHTML(matchRecap[1]['team'])+'</div></div><div class="foot-match-stats-probability-prcs flx flx-jb"><div>'+(1/(probabilities[i][1]/100)).round(2)+' ('+probabilities[i][1].round(0)+'%)</div><div>'+(1/(probabilities[i][2]/100)).round(2)+' ('+probabilities[i][2].round(0)+'%)</div><div>'+(1/(probabilities[i][3]/100)).round(2)+' ('+probabilities[i][3].round(0)+'%)</div></div><div class="foot-match-stats-probability-bar flx"><div style="width: '+probabilities[i][1].round(2)+'%;"></div><div style="width: '+probabilities[i][2].round(2)+'%;"></div><div style="width: '+probabilities[i][3].round(2)+'%;"></div></div></div>';
					}
					$('#foot-match-stats-probabilities').html(res);
				}
				var matchTeam;
				var footTeam;
				var footTeamStratsDistribution;
				var footTeamIcon;
				var strat;
				var stratIndex;
				var matchHasPersosStats = false;
				var substitues = [[],[]];
				var substitutionsTypes = ['substitution_out','substitution_in'];
				var matchHasSubstitutons = false;
				var coachs = [[],[]];
				var staffs = [[],[]];
				footPersosMatch = {};
				footPersosMatchIndex = 0;
				var footPersoName;
				if (isRegularMatch && footCats[currentCatID]['editions'][currentEditionID]['usedStrats'] !== undefined || !isRegularMatch && friendlyMatch['usedStrats'] !== undefined) {
					for (var i = 0; i < 2; i++) {
						matchTeam = matchRecap[i]['team'];
						footTeam = footTeams[matchTeam['name']];
						if (isRegularMatch) footTeamStratsDistribution = footCats[currentCatID]['editions'][currentEditionID]['stratsDistribution'][matchTeam['name']];
						if (matchTeam['type'] == 'normal' && footTeam !== undefined && footTeam['usualStrats'] !== undefined && (isRegularMatch && footTeamStratsDistribution !== undefined || !isRegularMatch && friendlyMatch['usedStrats'][i] !== null)) {
							matchHasPersosStats = true;
							footTeamIconImgTag = '<img src="'+getTeamIconSrc(matchTeam)+'">';
							strat = (isRegularMatch ? footTeam['usualStrats'][footTeamStratsDistribution[matchRecapId[0]]] : footTeam['usualStrats'][friendlyMatch['usedStrats'][i]]);
							$('#foot-match-stats-field-team-icon-'+i).html(footTeamIconImgTag);
							$('#foot-match-stats-field-team-name-'+i).html(getTeamNameHTML(matchTeam));
							$('#foot-match-stats-field-team-strat-'+i).html(strat['starters'].slice(1).map(footPlayers => footPlayers.length).join('-'));
							res = '<div class="foot-schema">';
							for (j in strat['starters']) {
								res += '<div class="foot-field-row-with-'+strat['starters'][j].length+'-persos">';
								for (k in strat['starters'][j]) res += '<div>'+getFootPersoPP(strat['starters'][j][k])+'<div class="foot-field-perso-name-dv flx flx-jc"><div class="foot-perso-name hide-scrollbar">'+getFootPersoName(strat['starters'][j][k])+'</div></div></div>';
								res += '</div>';
							}
							res += '</div>';
							$('#foot-match-stats-field-'+i).html(res);
							if (strat['substitues'] !== undefined) {
								$('#foot-match-stats-substitues-team-icon-'+i).html(footTeamIconImgTag);
								for (j in strat['substitues']) for (k in strat['substitues'][j]) substitues[i].push({'name':strat['substitues'][j][k],'job':j*1+1,'order':0});
								matchHasSubstitutons = (matchHasStats && matchRecapStats['substitutions'] !== undefined);
								if (matchHasSubstitutons) {
									for (j in matchRecapStats['substitutions']) {
										footPersoName = matchRecapStats['substitutions'][j][1];
										for (var k = 0; k < 2; k++) for (l in substitues[k]) if (substitues[k][l]['name'] == footPersoName) substitues[k][l]['order'] = 100-j*1;
									}
									substitues.map(teamSubstitues => teamSubstitues.orderByKeyDesc('order'));
								}
							}
							if (strat['coach'] !== undefined) {
								coachs[i].push({'name':strat['coach'],'job':0});
								$('#foot-match-stats-coachs-team-icon-'+i).html(footTeamIconImgTag);
							}
							if (strat['STAFF'] !== undefined) {
								for (j in strat['STAFF']) for (k in strat['STAFF'][j]) staffs[i].push({'name':strat['STAFF'][j][k],'job':13+j*1});
								$('#foot-match-stats-staff-team-icon-'+i).html(footTeamIconImgTag);
							}
							$('#foot-match-stats-field-team-'+i).removeClass('hidden');
						}
					}
				}
				if (substitues[0].length > 0 || substitues[1].length > 0) {
					$('#foot-match-stats-substitues .foot-persos-lists').html(getFootPersosListsHTML(substitues));
					if (matchHasSubstitutons) {
						for (j in matchRecapStats['substitutions']) {
							for (var k = 0; k < 2; k++) {
								footPersoName = matchRecapStats['substitutions'][j][k];
								if (footPersosMatch[footPersoName] !== undefined) addPersoPPAdd(footPersoName,substitutionsTypes[k]);
							}
						}
					}
					$('#foot-match-stats-substitues').removeClass('hidden');
				}
				if (coachs[0].length == 1 || coachs[1].length == 1) {
					$('#foot-match-stats-coachs .foot-persos-lists').html(getFootPersosListsHTML(coachs));
					$('#foot-match-stats-coachs').removeClass('hidden');
				}
				if (staffs[0].length > 0 || staffs[1].length > 0) {
					$('#foot-match-stats-staff .foot-persos-lists').html(getFootPersosListsHTML(staffs));
					$('#foot-match-stats-staff').removeClass('hidden');
				}
				if (matchHasSpecs && matchSpecs['referees'] !== undefined) {
					var referees = [
						[
							{'job':5,'name':matchSpecs['referees'][0]},
							{'job':7,'name':matchSpecs['referees'][2]}
						],
						[
							{'job':6,'name':matchSpecs['referees'][1]},
							{'job':8,'name':matchSpecs['referees'][3]}
						]
					];
					$('#foot-match-stats-referees .foot-persos-lists').html(getFootPersosListsHTML(referees));
					$('#foot-match-stats-referees').removeClass('hidden');
					if (matchSpecs['refereesVAR'] !== undefined) {
						referees = [
							[
								{'job':9,'name':matchSpecs['refereesVAR'][0]},
								{'job':11,'name':matchSpecs['refereesVAR'][2]}
							],
							[
								{'job':10,'name':matchSpecs['refereesVAR'][1]},
								{'job':12,'name':matchSpecs['refereesVAR'][3]}
							]
						];
						$('#foot-match-stats-referees-var .foot-persos-lists').html(getFootPersosListsHTML(referees));
						$('#foot-match-stats-referees-var').removeClass('hidden');
					}
				}
				if (matchHasStats) {
					if (matchRecapStats['goals'] !== undefined && (matchRecapStats['goals'][0].length > 0 || matchRecapStats['goals'][1].length > 0)) {
						var matchHasValidGoals = true;
						for (var i = 0; i < 2; i++) {
							for (j in matchRecapStats['goals'][i]) {
								for (k in matchRecapStats['goals'][i][j][1]) {
									if (matchRecapStats['goals'][i][j][1][k].length == 0) {
										matchHasValidGoals = false;
										break;
									}
								}
							}
						}
						if (matchHasValidGoals) {
							var footPersoGoals;
							var footPersoTrueGoals;
							for (var i = 0; i < 2; i++) {
								for (j in matchRecapStats['goals'][i]) {
									footPersoName = matchRecapStats['goals'][i][j][0];
									footPersoGoals = matchRecapStats['goals'][i][j][1];
									if (footPersosMatch[footPersoName] !== undefined) {
										footPersoTrueGoals = 0;
										for (var k = 0; k < footPersoGoals.length; k++) if (footPersoGoals[k][1] !== "CSC") footPersoTrueGoals++;
										if (footPersoTrueGoals > 0) {
											addPersoPPAdd(footPersoName,'but');
											if (footPersoTrueGoals > 1) {
												addPersoPPAdd(footPersoName,'but_nb');
												$('#foot-perso-pp-'+footPersosMatch[footPersoName]['id']).addClass('foot-perso-has-scored-plus');
												$('#foot-perso-pp-'+footPersosMatch[footPersoName]['id']).find('.foot-perso-pp-add-score-number').html(footPersoTrueGoals);
											}
										}
									}
								}
							}
							for (var i = 0; i < 2; i++) $('#foot-match-stats-scores-list-team-'+i).html(matchRecapStats['goals'][i].map(footPersoGoals => footPersoGoals[0]+' '+footPersoGoals[1].map(goal => goal[1] === undefined ? goal[0]+'\'' : goal[0]+'\' ('+goal[1]+')').join(', ')).join('<br/>'));
							$('#foot-match-stats-scores-lists').removeClass('hidden');
						}
					}
					if (matchRecapStats['redCards'] !== undefined && (matchRecapStats['redCards'][0].length > 0 || matchRecapStats['redCards'][1].length > 0)) {
						for (var i = 0; i < 2; i++) {
							for (j in matchRecapStats['redCards'][i]) if (footPersosMatch[matchRecapStats['redCards'][i][j][0]] !== undefined) addPersoPPAdd(matchRecapStats['redCards'][i][j][0],'carton_rouge');
							$('#foot-match-stats-red-cards-list-team-'+i).html(matchRecapStats['redCards'][i].map(redCard => redCard[0]+' '+redCard[1]+'\'').join('<br/>'));
						}
						$('#foot-match-stats-red-cards-lists').removeClass('hidden');
					}
				}
				var matchNotes = footMatchsNotes[currentCatID+'-'+currentEditionID+'-'+matchRecapId[0]+'-'+matchRecapId[1]];
				if (matchNotes !== undefined) {
					$('#foot-match-stats-notes').removeClass('hidden');
					$('#foot-match-stats-notes-content').html(getEscapedLinesStr(matchNotes.replace(/\r/g,'')).replace(/^(<br\/>)+|(<br\/>)+$/g,''));
				}
				if (matchHasPersosStats) $('#foot-match-stats-persos').removeClass('hidden');
				$('#foot-cat-content-match-stats-dv-dv-dv').removeClass('hidden');
				$('#foot-cat-content-match-stats').scrollTo(0);
				// Thanks to tfe and Igor Raush
				// https://stackoverflow.com/questions/3656592/how-to-programmatically-disable-page-scrolling-with-jquery
				// lock scroll position, but retain settings for later
				scrollPosition = [
					self.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft,
					self.pageYOffset || document.documentElement.scrollTop  || document.body.scrollTop
				];
				var html = jQuery('html'); // it would make more sense to apply this to body, but IE7 won't have that
				html.data('scroll-position', scrollPosition);
				html.data('previous-overflow', html.css('overflow'));
				html.css('overflow', 'hidden');
				window.scrollTo(scrollPosition[0], scrollPosition[1]);
			});
		}

		function setCatEditionHTML(catID,editionID) {
			var catType = footCats[catID]['type'];
			var editionIsOver = footCats[catID]['editions'][editionID]['editionIsOver'];
			var hasBeenRandomized = footCats[catID]['editions'][editionID]['hasBeenRandomized'];
			makedTabs = [];
			if (catType == 0) {
				$('#foot-championship-leaderboard-dv').html('');
				$('#foot-cat-content-display-buttons > button').addClass('hidden');
				$('#foot-cat-content-display-cup-grid-dv, #foot-cat-content-display-cup-groups, #foot-cat-content-display-cup-off-groups').removeClass('hidden');
			}
			else {
				$('#foot-cup-grid-dv-sep, #foot-cup-groups-sep, #foot-cup-off-groups-sep').addClass('hidden');
				$('#foot-cup-grid, #foot-cup-groups, #foot-cup-off-groups').html('');
				$('#foot-cat-content-display-buttons > button').addClass('hidden');
				$('#foot-cat-content-display-championship-leaderboard-dv').removeClass('hidden');
			}
			var matchsRecap = footCats[catID]['editions'][editionID]['matchsRecap'];
			var catContentTeams = footCats[catID]['editions'][editionID]['teams'];
			var catContentTeamsPlayedMatchs = {};
			for (i in catContentTeams) catContentTeamsPlayedMatchs[catContentTeams[i]] = 0;
			if (catType == 0) {
				var cupFirstPhases = ['offGroups','groups'];
				var phaseGroups;
				for (i in cupFirstPhases) {
					phaseGroups = footCats[catID]['editions'][editionID][cupFirstPhases[i]];
					for (j in phaseGroups) for (k in phaseGroups[j]) if (phaseGroups[j][k]['team']['type'] == 'normal') catContentTeamsPlayedMatchs[phaseGroups[j][k]['team']['name']] += phaseGroups[j][k]['mj'];
				}
				for (var i = 11; i < 19; i++) for (j in matchsRecap[i]) for (var k = 0; k < 2; k++) if (matchsRecap[i][j][k]['team']['type'] == 'normal') catContentTeamsPlayedMatchs[matchsRecap[i][j][k]['team']['name']]++;
			}
			else {
				var leaderboard = footCats[catID]['editions'][editionID]['leaderboard'];
				for (i in leaderboard) if (leaderboard[i]['team']['type'] == 'normal') catContentTeamsPlayedMatchs[leaderboard[i]['team']['name']] = leaderboard[i]['mj'];
			}
			var catContentTeamsOrderedList = [];
			for (i in catContentTeams) catContentTeamsOrderedList.push({'id':i,'type':'normal','name':catContentTeams[i],'playedMatchs':catContentTeamsPlayedMatchs[catContentTeams[i]]});
			catContentTeamsOrderedList = catContentTeamsOrderedList.orderByKeyDesc('playedMatchs');
			var res = '';
			for (i in catContentTeamsOrderedList) res += '<div id="foot-cat-content-team-'+catContentTeamsOrderedList[i]['id']+'" class="foot-box foot-box-selectable">'+getTeamNameAndIcon(catContentTeamsOrderedList[i])+'</div>';
			$('#foot-cat-content-teams').html(res);
			$('#foot-cat-content-teams > div.foot-box').click(function(){
				$('#foot-cat-content-teams > div.foot-box.selected').removeClass('selected');
				$(this).addClass('selected');
				$('#foot-cat-content-matchs :is(.foot-box, .foot-matchs-separation).hidden').removeClass('hidden');
				$('#foot-cat-content-matchs :is(.foot-box, .foot-matchs-separation):not(.foot-cat-content-contain-team-'+this.id.replace('foot-cat-content-team-','')+')').addClass('hidden');
				$('#foot-cat-content-matchs, #foot-cat-content-matchs-sep').removeClass('hidden');
				focusCatContentMatchs();
				footScrollTo('#foot-cat-content-matchs');
				matchsWasFiltered = true;
			});
			res = '';
			if (catType == 0) for (i in matchsRecap) res += getMatchsRecapDayHTML(cupPhaseTitles[i],matchsRecap[i],i);
			else for (i in matchsRecap) res += getMatchsRecapDayHTML('Journée '+(parseInt(i)+1),matchsRecap[i],i);
			$('#foot-cat-content-matchs').html(res);
			$('#foot-cat-content-display-all, #foot-cat-content-display-cat-content-teams, #foot-cat-content-display-cat-content-matchs').removeClass('hidden');
			displayCatContentTab(editionIsOver || hasBeenRandomized ? 'all' : 'cat-content-matchs');
			if (footCats[catID]['editions'][editionID]['winner'] !== undefined) $('#foot-cup-winner').html('<div class="foot-box foot-box-selectable flx flx-dc flx-ac"><img src="'+addPathPartIfNotURL('/medias/scenarios/foot/icons/',footCats[catID]['icon'])+'"><div>Vainqueur</div>'+getTeamNameAndIcon(footCats[catID]['editions'][editionID]['winner'])+'</div>');
			addMatchsEventListeners();
		}

		function setCatContentTabHTML(tab) {
			if (!makedTabs.includes(tab)) {
				var res = '';
				switch (tab) {
					case 'cup-grid-dv':
						var grid = footCats[currentCatID]['editions'][currentEditionID]['grid'];
						var gridSeperationsNums = [4,2,1];
						for (i in grid) {
							res += '<div class="flx flx-dc flx-ja">';
							for (j in grid[i]) res += getMatchBoxHTML(grid[i][j]);
							res += '</div>';
							if (i < 3) {
								res += '<div class="foot-grid-separation-1 foot-grid-separation-1-'+(parseInt(i)+1)+' flx flx-dc flx-ja">';
								for (var j = 0; j < gridSeperationsNums[i]; j++) res += '<div></div>';
								res += '</div><div class="foot-grid-separation-2 flx flx-dc flx-ja">';
								for (var j = 0; j < gridSeperationsNums[i]; j++) res += '<div></div>';
								res += '</div>';
							}
						}
						$('#foot-cup-grid').html(res);
						break;
					case 'cup-groups':
						var groups = footCats[currentCatID]['editions'][currentEditionID]['groups'];
						for (i in groups) res += getGroupTableHTML(groups[i],'Groupe '+tablesLetters[i]);
						$('#foot-cup-groups').html(res);
						break;
					case 'cup-off-groups':
						var offGroups = footCats[currentCatID]['editions'][currentEditionID]['offGroups'];
						for (i in offGroups) res += getGroupTableHTML(offGroups[i],'Groupe '+tablesLetters[i]);
						$('#foot-cup-off-groups').html(res);
						break;
					case 'championship-leaderboard-dv':
						var leaderboard = footCats[currentCatID]['editions'][currentEditionID]['leaderboard'];
						$('#foot-championship-leaderboard-dv').html(getGroupTableHTML(leaderboard,'Classement'));
						break;
				}
				makedTabs.push(tab);
			}
		}

		function focusCatContentMatchs() {
			if ($('#foot-cat-content-matchs .foot-box.foot-box-match-defined:not(.hidden)').length == 0) $('#foot-cat-content-matchs').scrollTo(0);
			else $('#foot-cat-content-matchs').scrollTo($('#foot-cat-content-matchs .foot-box.foot-box-match-defined:not(.hidden)').last(),{'offset':-(($('#foot-cat-content-matchs .foot-box').height()+35)*2+5)});
		}

		function footScrollTo(selector='#foot-cat-content-scroll-attach-point') {
			smoothlyScrollTo($(selector),null,$(window).height()/2-200);
		}

		function displayCatContentTab(tab) {
			$('#foot-cat-content-display-buttons > button, #foot-cat-content-teams > div.foot-box.selected').removeClass('selected');
			$('#foot-cat-content-display-'+tab).addClass('selected');
			$('#foot-cup-grid-dv, #foot-cup-groups, #foot-cup-off-groups, #foot-championship-leaderboard-dv, #foot-cat-content-teams, #foot-cat-content-matchs, #foot-cat-content > .foot-matchs-separation').addClass('hidden');
			if (tab == 'all') {
				var catContentTabs = [['cup-grid-dv','cup-groups','cup-off-groups'],['championship-leaderboard-dv']][currentCatType];
				for (i in catContentTabs) setCatContentTabHTML(catContentTabs[i]);
				$((currentCatType == 0 ? '#foot-cup-grid-dv-sep, #foot-cup-grid-dv, #foot-cup-groups-sep, #foot-cup-groups, #foot-cup-off-groups-sep, #foot-cup-off-groups' : '#foot-championship-leaderboard-dv')+', #foot-cat-content-teams-sep, #foot-cat-content-teams, #foot-cat-content-matchs-sep, #foot-cat-content-matchs').removeClass('hidden');
			}
			else {
				setCatContentTabHTML(tab);
				$('#foot-'+tab).removeClass('hidden');
			}
			if (matchsWasFiltered && (tab == 'cat-content-matchs' || tab == 'all')) {
				$('#foot-cat-content-matchs :is(.foot-box, .foot-matchs-separation).hidden').removeClass('hidden');
				focusCatContentMatchs();
				matchsWasFiltered = false;
			}
		}

		function getTeamNameTxt(team) {
			if (team['type'] == 'blank') return '???';
			return team['name'];
		}

		function isTeamWithStrat(team) {
			return (team['type'] == 'normal' && footCats[currentCatID]['editions'][currentEditionID]['usedStrats'][team['name']] !== undefined);
		}

		function resetCatContent() {
			$('#foot-cat-content').addClass('hidden');
			$('#foot-cup-winner, #foot-cup-grid, #foot-cup-groups, #foot-cat-content-teams, #foot-cat-content-matchs, #foot-comments').html('');
			$('#foot-editions > div').removeClass('selected');
			$('#header-returnpage').off();
		}


// ------------------ TODO ------------------
// seperate hilightTypes 'recap' and 'vainqueur', no 'fin_du_match'

		// function getAffTime(time) {
		// 	time = Math.trunc(time);
		// 	if (time > times[timeIndex][0]) return times[timeIndex][0]+'+'+(time-times[timeIndex][0])+'\'';
		// 	return time+'\'';
		// }

		// function getAffTimeHighlightBox(time) {
		// 	if ([0,2,4,6,9].includes(timeIndex)) return '';
		// 	if (timeIndex == 8) return 'Séance de tirs au but';
		// 	return getAffTime(time);
		// }

		// function getHighlightTimeSeparationHTML(title,time,showing3Lines) {

		// }

		// function getHighlightTimeSeparation(time) {
		// 	var txt = '<div class="foot-highlight-time';
		// 	if ([1,2,4,6,8].includes(timeIndex)) txt += ' foot-highlight-time-showing-3-lines';
		// 	txt += '">';
		// 	if (timeIndex > 0) txt += '<img src="/src/icons/scenarios/foot/stopwatch_icon.svg">';
		// 	txt += '<div class="foot-highlight-time-title flx flx-jc flx-ac"><div></div><span>'+times[timeIndex][1].toUpperCase()+'</span><div></div></div>';
		// 	if (timeIndex > 1) {
		// 		if ([1,3,5,7].includes(timeIndex)) time = times[timeIndex-1][0];
		// 		txt += '<span>'+getAffTime(time)+'</span>';
		// 		if ([2,4,6,8].includes(timeIndex)) txt += getInlineScoreHTML(2,false);
		// 	}
		// 	else if (timeIndex > 0) txt += '<span>'+matchs[currentMatchID]['kick-offTime']+'</span><span>'+matchs[currentMatchID]['stadiumName']+'</span>';
		// 	txt += '</div>';
		// 	return txt;
		// }

		// function getPersoTeamIndex(footPersoName) {
		// 	return (matchs[currentMatchID]['players'][0].includes(footPersoName) ? 0 : 1);
		// }

		// function getFootPersoDescriptionInBox(footPersoName) {
		// 	var teamIndex = getPersoTeamIndex(footPersoName);
		// 	return '<div class="foot-highlight-box-inner foot-highlight-box-inner-perso-description"><div><div>'+footPersoName+'</div><div><img src="'+footTeams[matchs[currentMatchID]['teams'][teamIndex]]['icon']+'"><span>'+matchs[currentMatchID]['teams'][teamIndex]+' · '+jobs[footPersos[footPersoName]['job']]+'</span></div></div><div><img class="foot-perso-pp" src="/medias/scenarios/foot/pps/'+footPersos[footPersoName]['pp']+'"></div></div>';
		// }

		// function getScoreTeamNameHTML(teamIndex,scored,showIcon,showName=true) {
		// 	if (!showName) return '<div></div>';
		// 	if (showIcon) return '<div class="foot-score-team-name-with-icon flx flx-dc flx-ac"><img src="'+footTeams[matchs[currentMatchID]['teams'][teamIndex]]['icon']+'"><div>'+matchs[currentMatchID]['teams'][teamIndex]+'</div></div>';
		// 	return '<div'+(scored ? ' class="foot-score-scored"' : '')+'>'+matchs[currentMatchID]['teams'][teamIndex]+'</div>';
		// }

		// function getScoreNbHTML(nb,duringTrb,scored) {
		// 	return '<div class="foot-score-nb'+(duringTrb ? ' foot-score-nb-trb' : '')+(scored ? ' foot-score-scored' : '')+'">'+(duringTrb ? '(' : '')+nb+(duringTrb ? ')' : '')+'</div>';
		// }

		// function getInlineScoreHTML(scoreTeamIndex,duringTrb,showIcons,scoreType=0,showNames=true) {
		// 	var affScore = (scoreType == 0 ? score : (scoreType == 1 ? previousScore : aggregate));
		// 	return '<div class="foot-score'+(showIcons ? ' foot-score-containing-icons' : '')+' flx flx-jc flx-ac">'+getScoreTeamNameHTML(0,scoreTeamIndex == 0,showIcons,showNames)+getScoreNbHTML(affScore[0],false,scoreTeamIndex == 0)+(duringTrb ? getScoreNbHTML(trbScore[0],true,scoreTeamIndex == 0) : '')+'<div class="foot-score-separation">-</div>'+(duringTrb ? getScoreNbHTML(trbScore[1],true,scoreTeamIndex == 1) : '')+getScoreNbHTML(affScore[1],false,scoreTeamIndex == 1)+getScoreTeamNameHTML(1,scoreTeamIndex == 1,showIcons,showNames)+'</div>';
		// }

		// function getAggregateScore(scoreTeamIndex,duringTrb,showIcons) {
		// 	if (hasPreviousScore) return getInlineScoreHTML(2,false,false,1,false)+getInlineScoreHTML(2,false,false,0,false)+getInlineScoreHTML(2,false,true,2);
		// 	return getInlineScoreHTML(2,false,true);
		// }

		// function addScoreOrRedCardInLists(footPersoName,time,type) {
		// 	var teamIndex = getPersoTeamIndex(footPersoName);
		// 	var affTime = getAffTimeHighlightBox(time);


		// 	if (type == 'redCard') {
		// 		if (!redCardInLists) $('#foot-match-red-cards-lists').removeClass('hidden');
		// 		$('#foot-match-red-cards-list-team-'+teamIndex).append('<div>'+footPersoName+' '+affTime+'</div>');
		// 	}
		// 	else {
		// 		if (!scoreInLists) $('#foot-match-scores-lists').removeClass('hidden');
		// 		affTime += (type == 'normal' ? '' : (type == 'penalty' ? ' (P)' : ' (CSC)'));

		// 		if (type == 'penalty') type = 'normal';

		// 		// console.log('footPersoName',footPersoName);
		// 		// console.log('footPersosMatch',footPersosMatch);
		// 		// console.log('type',type);

		// 		if (footPersosMatch[footPersoName]['score'][type]['nb'] > 1) footPersosMatch[footPersoName]['score'][type]['$'].append(', '+affTime);
		// 		else {
		// 			var persoSelector = 'foot-perso-scoreinlist'+(type == 'ownGoal' ? 'og' : '')+'-'+footPersosMatch['id'];
		// 			$('#foot-match-scores-list-team-'+(type == 'ownGoal' ? 1-teamIndex : teamIndex)).append('<div id="'+persoSelector+'">'+footPersoName+' '+affTime+'</div>');
		// 			footPersosMatch[footPersoName]['score'][type]['$'] = $('#'+persoSelector);
		// 		}


		// 	}



		// }

		// function getHighlightBox(highlight) {
		// 	var highlightType = highlight['type'];
		// 	var highlightTime = highlight['time'];
		// 	var txt = '<div class="foot-highlight-box beenadded';
		// 	if (highlightType == 'remplacement') txt += ' foot-highlight-box-inner-substitution';
		// 	if (['var_but','var_penalty','var_mais'].includes(highlightType)) txt += ' foot-highlight-box-var';
		// 	if (['recap','vainqueur'].includes(highlightType)) txt += ' flx flx-dc flx-jc flx-ac';
		// 	if (highlightType == 'recap') txt += ' foot-highlight-box-containing-recap-score';
		// 	if (highlightType == 'va_tirer' && matchPoint) txt += ' foot-highlight-box-trb-match-point';
		// 	// if (highlightType == 'fin_du_match') {
		// 	// 	var matchHasAWinner = (aggregate[0] != aggregate[1]);
		// 	// 	txt += ' foot-highlight-box-match-over'+(matchHasAWinner ? ' foot-highlight-box-match-over-winner' : ' foot-highlight-box-containing-recap-score');
		// 	// }
		// 	txt += '">';

		// 	if (highlightType == 'but') {
		// 		txt += '<div class="foot-highlight-box-inner foot-highlight-box-inner-up-goal'+(!highlight['scored'] ? ' foot-highlight-box-inner-up-goal-missed' : '')+'"><img src="'+(!highlight['scored'] && (highlight['scoreType'] == 'penalty' || timeIndex == 8) ? '/src/icons/scenarios/foot/9dceb08e628e5683ca8b0fa57c439c1a' : 'https://ssl.gstatic.com/onebox/sports/game_feed/goal_icon')+'.svg"><span>'+(highlight['scored'] ? 'BUUUUT !!!' : (highlight['scoreType'] == 'penalty' || timeIndex == 8 ? 'MANQUÉ !!!' : 'But contre son camp'))+'</span><span>'+getAffTimeHighlightBox(highlightTime)+(highlight['scoreType'] == 'penalty' ? ' (Penalty)' : '')+'</span>'+getInlineScoreHTML(highlight['scoreTeamIndex'],timeIndex == 8)+'</div>';
		// 	}
		// 	if (!['but','recap','vainqueur'].includes(highlightType)) {
		// 		txt += '<div class="foot-highlight-box-inner foot-highlight-box-inner-up"><div><img src="'+highlightTypes[highlightType]['icon']+'"><span>'+highlightTypes[highlightType]['title']+'</span></div><div>';
		// 		if (!['tirs_au_but','tirage_au_sort','trb_recap'].includes(highlightType)) txt += '<span>'+getAffTimeHighlightBox(highlightTime)+'</span>';
		// 		txt += '</div></div>';
		// 	}

		// 	if (highlightType == 'remplacement') txt += '<div class="foot-highlight-box-inner foot-highlight-box-inner-substitution-text foot-highlight-box-inner-substitution-text-in">ENTRÉE</div>';

		// 	if (highlightType == 'va_tirer' && matchPoint) txt += '<div class="foot-highlight-box-inner">BALLE DE MATCH</div>';

		// 	if (['carton_jaune','carton_rouge','carton_jaune_rouge','remplacement','occasion','var_but','var_penalty','var_mais','but','va_tirer'].includes(highlightType)) txt += getFootPersoDescriptionInBox(highlight['perso']);

		// 	if (highlightType == 'remplacement') txt += '<div class="foot-highlight-box-inner foot-highlight-box-inner-substitution-text foot-highlight-box-inner-substitution-text-out">SORTIE</div>'+getFootPersoDescriptionInBox(highlight['perso2']);

		// 	if (highlightType == 'decision') {
		// 		txt += '<div class="foot-highlight-box-inner foot-highlight-box-inner-showing-main-icon"><img src="/src/icons/scenarios/foot/';
		// 		if (highlight['accepted']) txt += 'decision-accepted-icon.svg"><div class="foot-highlight-box-decision foot-highlight-box-decision-accepted">ACCEPTÉ';
		// 		else txt += 'crossed-arms-stop-gesture-black-3135545-transformed.svg"><div class="foot-highlight-box-decision foot-highlight-box-decision-refused">REFUSÉ';
		// 		txt += '</div></div>';
		// 	}

		// 	if (highlightType == 'temps_additionnel') txt += '<div class="foot-highlight-box-inner foot-highlight-box-inner-announce-time">'+highlight['nb']+'\'</div>';

		// 	if (highlightType == 'tirs_au_but') txt += '<div class="foot-highlight-box-inner foot-highlight-box-inner-showing-main-icon"><img src="/src/icons/scenarios/foot/soccer-goal-881047.svg"><div>'+highlight['comment']+'</div></div>';

		// 	if (highlightType == 'tirage_au_sort') txt += '<div class="foot-highlight-box-inner foot-highlight-box-inner-draw-result"><img src="'+footTeams[matchs[currentMatchID]['teams'][highlight['firstShootTeam']]]['icon']+'"><div>CÔTÉ<br/>'+(highlight['side'] == 0 ? 'GAUCHE' : 'DROIT')+'</div></div>';

		// 	if (highlightType == 'recap') txt += '<div>Récapitulatif des scores</div>'+getAggregateScore();

		// 	// if (highlightType == 'trb_recap') {
		// 	// 	txt += '<div class="foot-highlight-box-inner foot-highlight-box-inner-trb-result"><div class="foot-score"><span>'+trbScore[0]+'</span><span>-</span><span>'+trbScore[1]+'</span></div><div class="foot-highlight-trb-rows"><div><div>'+matchs[currentMatchID]['teams'][0]+'</div><img src="'+footTeams[matchs[currentMatchID]['teams'][0]]['icon']+'"><img src="'+footTeams[matchs[currentMatchID]['teams'][1]]['icon']+'"><div>'+matchs[currentMatchID]['teams'][1]+'</div></div>';
		// 	// 	var recap = highlight['recap'];
		// 	// 	for (j in recap) {
		// 	// 		txt += '<div><div>';
		// 	// 		if (recap[j][0][0] !== undefined) txt += '<div>'+recap[j][0][0]+'</div><div>'+recap[j][0][2]+'</div>';
		// 	// 		txt += '</div><div class="foot-highlight-trb-pen-dv">';
		// 	// 		if (recap[j][0][0] !== undefined) txt += '<img src="http://ssl.gstatic.com/onebox/sports/game_feed/pens_'+(recap[j][0][1] ? 'goal' : 'miss')+'_icon.svg">';
		// 	// 		txt += '</div><div class="foot-highlight-trb-pen-dv">';
		// 	// 		if (recap[j][1][0] !== undefined) txt += '<img src="http://ssl.gstatic.com/onebox/sports/game_feed/pens_'+(recap[j][1][1] ? 'goal' : 'miss')+'_icon.svg">';
		// 	// 		txt += '</div><div>';
		// 	// 		if (recap[j][1][0] !== undefined) txt += '<div>'+recap[j][1][0]+'</div><div>'+recap[j][1][2]+'</div>';
		// 	// 		txt += '</div></div>';
		// 	// 	}
		// 	// 	txt += '</div><div class="foot-highlight-trb-first-shoot-message">Première équipe à avoir tiré : '+matchs[currentMatchID]['teams'][highlight['firstShootTeam']]+'</div></div>';
		// 	// }

		// 	// if (highlightType == 'fin_du_match') {
		// 	// 	if (matchHasAWinner) {
		// 	// 		var winnerTeamIndex = (aggregate[0] > aggregate[1] ? 0 : 1);
		// 	// 		txt += '<div>Vainqueur du match</div>'+getScoreTeamNameHTML(winnerTeamIndex,false,true);
		// 	// 	}
		// 	// 	else txt += '<div>Match nul</div>'+getAggregateScore();
		// 	// 	timeIndex = 9;
		// 	// }

		// 	if (!['recap','tirs_au_but','trb_recap','fin_du_match'].includes(highlightType) && highlight['comment'] != '') {
		// 		if (highlightType != 'commentaire') txt += '<div class="foot-highlight-box-hr"></div>';
		// 		txt += '<div class="foot-highlight-box-inner">'+highlight['comment'];
		// 		if (highlight['schema'] !== undefined) {
		// 			var schema = highlight['schema'];
		// 			txt += '<div class="foot-field-in-box"><div class="foot-schema">';
		// 			for (j in schema) {
		// 				txt += '<div>';
		// 				for (k in schema[j]) {
		// 					txt += '<div><div>';
		// 					for (l in schema[j][k]) if (schema[j][k] != '') txt += '<img class="foot-perso-pp" src="/medias/scenarios/foot/pps/'+footPersos[schema[j][k][l]]['pp']+'" title="'+schema[j][k][l]+'">';
		// 					txt += '</div></div>';
		// 				}
		// 				txt += '</div>';
		// 			}
		// 			txt += '</div></div>';
		// 		}
		// 		txt += '</div>';
		// 	}
		// 	txt += '</div>';
		// 	return txt;
		// }

		// function changeMatch() {
		// 	$('#foot-match-name').html(matchs[currentMatchID]['name']);
		// 	$('#foot-view').css({
		// 		'background' : 'linear-gradient(-45deg,rgba(0,0,0,0.5),rgba(0,0,0,0.5)),url("/medias/scenarios/foot/posters/'+matchs[currentMatchID]['background']+'")',
		// 		'background-size' : 'cover',
		// 		'background-position' : '50% 50%'
		// 	});

		// 	hasPreviousScore = (matchs[currentMatchID]['previousScore'] !== undefined);
		// 	previousScore = (hasPreviousScore? matchs[currentMatchID]['previousScore'] : [0,0]);
		// 	aggregate = [previousScore[0],previousScore[1]];
		// 	$('#foot-score-team-0, #foot-score-team-1').html('0');

		// 	var teamName, teamIcon, stratTxt;
		// 	var strats = matchs[currentMatchID]['strats'];
		// 	for (var i = 0; i < 2; i++) {
		// 		teamName = matchs[currentMatchID]['teams'][i];
		// 		teamIcon = footTeams[teamName]['icon'];
		// 		stratTxt = [];
		// 		for (var j = 1; j < strats[i].length; j++) stratTxt.push(strats[i][j].length);
		// 		$('#foot-field-team-name-'+i).html('<div><img src="'+teamIcon+'"><div class="foot-field-team-name-text">'+teamName+'</div></div><div>'+stratTxt.join('-')+'</div>');
		// 		$('.foot-team-name-'+i).html(teamName);
		// 		$('.foot-team-icon-'+i).attr('src',teamIcon);
		// 		if (hasPreviousScore) $('#foot-previous-score-team-'+i).html(previousScore[i]);
		// 	}

		// 	if (hasPreviousScore) $('#foot-previous-score').removeClass('hidden');
		// 	else $('#foot-previous-score').addClass('hidden');

		// 	footPersosMatch = {};
		// 	footPersosMatchIndex = 0;



		// 	var res;
		// 	for (i in strats) {
		// 		res = '<div class="foot-schema">';
		// 		for (j in strats[i]) {
		// 			res += '<div>';
		// 			for (k in strats[i][j]) res += '<div>'+getFootPersoPP(strats[i][j][k])+'<div class="foot-perso-name">'+strats[i][j][k]+'</div></div>';
		// 			res += '</div>';
		// 		}
		// 		res += '</div>';
		// 		$('#foot-field-'+i).html(res);
		// 	}

		// 	var substituesForLists = [];
		// 	for (i in matchs[currentMatchID]['substitues'][0]) {
		// 		substituesForLists.push(matchs[currentMatchID]['substitues'][0][i]);
		// 		substituesForLists.push(matchs[currentMatchID]['substitues'][1][i]);
		// 	}
		// 	$('#foot-substitues .foot-persos-lists').html(getFootPersosListsHTML(substituesForLists));
		// 	$('#foot-coachs .foot-persos-lists').html(getFootPersosListsHTML(matchs[currentMatchID]['coachs']));
		// 	$('#foot-referees .foot-persos-lists').html(getFootPersosListsHTML(matchs[currentMatchID]['referees']));
		// 	$('#foot-referees-var .foot-persos-lists').html(getFootPersosListsHTML(matchs[currentMatchID]['refereesVAR']));

		// 	presentationIDsIndex = -1;
		// 	scoreInLists = false;
		// 	redCardInLists = false;
		// 	matchPoint = false;


		// 	score = [0,0];
		// 	trbScore = [0,0];
		// 	timeIndex = 0;
		// 	var matchNeedsAWinner = (matchs[currentMatchID]['matchNeedsAWinner'] === true);


		// 	highlights = [];

		// 	var highlightsToAdd = $.extend(true,[],matchs[currentMatchID]['highlights']);
		// 	var highlight;
		// 	for (i in highlightsToAdd) {

		// 		highlight = highlightsToAdd[i];

		// 		// if ()

		// 		if (highlight['type'] == 'temps') {
		// 			timeIndex++;

		// 		}

		// 		if (highlight['type'] == 'but' && highlight['scoreType'] == 'penalty') {


		// 			highlights.push({'time':highlight['time'],'type':'va_tirer','perso':highlight['perso'],'comment':highlight['beforeShootComment']});
		// 			highlight['time'] += highlight['beforeShootTime'];

		// 		}

		// 		highlights.push(highlight);
		// 	}

		// 	score = [0,0];
		// 	trbScore = [0,0];


		// 	// highlights = matchs[currentMatchID]['highlights'];

		// 	$('#foot-highlights').html('');


		// 	timeIndex = 0;
		// 	highlightIndex = -1;




		// 	$('#foot-view-header, #foot-infos > div').addClass('hidden');
		// 	$('#foot-before-match-buttons, #foot-before-match-brand').removeClass('hidden');
		// 	$('#foot-view-no-display, #foot-match-scores-lists, #foot-match-red-cards-lists').addClass('hidden');
		// }

		// function addSmoothlyHighlightHTML(HTML) {
		// 	$('#foot-highlights').append(HTML);
		// 	$('#foot-highlights > :last-child')[0].scrollIntoView({block:'nearest',behavior:'smooth'});
		// 	setTimeout(function(){$('#foot-highlights > div.beenadded').removeClass('beenadded');},400);
		// }

		// function addHighlight(playMatch) {
		// 	res = '';
		// 	var highlight = highlights[highlightIndex];
		// 	var scored = false;

		// 	switch (highlight['type']) {
		// 		case 'temps':
		// 			timeIndex++;
		// 			res += getHighlightTimeSeparation(highlight['time']);
		// 			break;
		// 		case 'carton_jaune':

		// 			if (footPersosMatch[highlight['perso']]['carton_jaune'] === undefined) {

		// 				addPersoPPAdd(highlight['perso'],'carton_jaune');

		// 				footPersosMatch[highlight['perso']]['carton_jaune'] = true;




		// 			}
		// 			else {

		// 				$('#foot-perso-pp-'+footPersosMatch[highlight['perso']]['id']).find('.foot-perso-pp-add-card').remove();
		// 				addPersoPPAdd(highlight['perso'],'carton_jaune_rouge');

		// 				highlight['type'] = 'carton_jaune_rouge';


		// 				addScoreOrRedCardInLists(highlight['perso'],highlight['time'],'redCard');


		// 			}

		// 			res += getHighlightBox(highlight);


		// 			break;
		// 		case 'carton_rouge':

		// 			addPersoPPAdd(highlight['perso'],'carton_rouge');
		// 			addScoreOrRedCardInLists(highlight['perso'],highlight['time'],'redCard');
		// 			res += getHighlightBox(highlight);

		// 			break;
		// 		case 'remplacement':

		// 			addPersoPPAdd(highlight['perso'],'substitution_out');
		// 			addPersoPPAdd(highlight['perso2'],'substitution_in');

		// 			res += getHighlightBox(highlight);
		// 			break;
		// 		case 'but':

		// 			var teamIndex = getPersoTeamIndex(highlight['perso']);
		// 			var scoreTeamIndex = teamIndex;
		// 			var scoreType = highlight['scoreType'];
		// 			if (scoreType === undefined) {
		// 				scoreType = 'normal';
		// 				highlight['scored'] = true;
		// 			}
		// 			if (scoreType == 'ownGoal') {
		// 				scoreTeamIndex = 1-teamIndex;
		// 				highlight['scored'] = false;
		// 			}


		// 			if (footPersosMatch[highlight['perso']]['score'] === undefined) footPersosMatch[highlight['perso']]['score'] = {'normal':{'nb':0},'ownGoal':{'nb':0}};



		// 			if (highlight['scored'] || scoreType == 'ownGoal') footPersosMatch[highlight['perso']]['score'][(scoreType == 'ownGoal' ? 'ownGoal' : 'normal')]['nb']++;

		// 			if (scoreType != 'ownGoal') {

		// 				if (footPersosMatch[highlight['perso']]['score']['normal']['nb'] == 1) addPersoPPAdd(highlight['perso'],'but');
		// 				else {

		// 					var $persoPP = $('#foot-perso-pp-'+footPersosMatch[highlight['perso']]['id']);

		// 					$persoPP.addClass('foot-perso-has-scored-plus');

		// 					addPersoPPAdd(highlight['perso'],'but_nb');
		// 					footPersosMatch[highlight['perso']]['score']['$ppNb'] = $persoPP.find('.foot-perso-pp-add-score-number');


		// 					footPersosMatch[highlight['perso']]['score']['$ppNb'].html(footPersosMatch[highlight['perso']]['score']['normal']['nb']);


		// 				}



		// 			}


		// 			if (highlight['scored'] || scoreType == 'ownGoal') {

		// 				score[scoreTeamIndex]++;
		// 				aggregate[scoreTeamIndex]++;
		// 				$('#foot-score-team-'+scoreTeamIndex).html(score[scoreTeamIndex]);


		// 				addScoreOrRedCardInLists(highlight['perso'],highlight['time'],scoreType);

		// 			}


		// 			if (scoreType == 'penalty') {
		// 				// res += getHighlightBox({'time':highlight['time'],'type':'va_tirer','perso':highlight['perso'],'comment':highlight['beforeShootComment']});
		// 				// highlight['time'] += 0.5;
		// 				if (!highlight['scored']) scoreTeamIndex = 2;
		// 			}


		// 			highlight['scoreTeamIndex'] = scoreTeamIndex;
		// 			res += getHighlightBox(highlight);




		// 			// if (highlight['scored']) {


		// 			// 	if (footPersosMatch[highlight['perso']]['score'] === undefined) {
		// 			// 		footPersosMatch[highlight['perso']]['score'] = 1;




		// 			// 	}
		// 			// 	else {

		// 			// 		footPersosMatch[highlight['perso']]['score']++;

		// 			// 		if (footPersosMatch[highlight['perso']]['score'] == 2) {


		// 			// 		}



		// 			// 	}



		// 			// }
		// 			// else {
		// 			// }






		// 			break;
		// 		// case 'tirs_au_but':
		// 		// 	res += getHighlightBox({type:'tirs_au_but','comment':highlight['beginComment']});



		// 		// 	res += getHighlightBox({type:'tirage_au_sort','firstShootTeam':highlight['firstShootTeam'],...highlight['tirage_au_sort']});
		// 		// 	var shootOrder = highlight['shootOrder'];
		// 		// 	var nbShoots = Math.round((shootOrder[0].length+shootOrder[1].length)/2);
		// 		// 	var teamOrder = (highlight['firstShootTeam'] == 0 ? [0,1] : [1,0]);
		// 		// 	var recap = [];
		// 		// 	var recapLine;
		// 		// 	var teamIndex;
		// 		// 	var shoot;
		// 		// 	var scored;
		// 		// 	var recapLineScoreTxt;
		// 		// 	var matchPoint;



		// 		// 	for (var j = 0; j < nbShoots; j++) {
		// 		// 		recapLine = [[],[]];
		// 		// 		for (k in teamOrder) {
		// 		// 			teamIndex = teamOrder[k];
		// 		// 			shoot = shootOrder[teamIndex][j];
		// 		// 			if (shoot === undefined) continue;
		// 		// 			scored = shoot[1];
		// 		// 			matchPoint = false;
		// 		// 			res += getHighlightBox({'type':'va_tirer','perso':shoot[0],'matchPoint':matchPoint,'comment':shoot[2]});




		// 		// 			if (scored) trbScore[teamIndex]++;

		// 		// 			res += getHighlightBox({'type':'but','perso':shoot[0],'scored':scored,'comment':shoot[3]});




		// 		// 			recapLineScoreTxt = '';
		// 		// 			if (scored && teamIndex == 0) recapLineScoreTxt += '<span>';
		// 		// 			recapLineScoreTxt += trbScore[0];
		// 		// 			if (scored && teamIndex == 0) recapLineScoreTxt += '</span>';
		// 		// 			recapLineScoreTxt += ' - ';
		// 		// 			if (scored && teamIndex == 1) recapLineScoreTxt += '<span>';
		// 		// 			recapLineScoreTxt += trbScore[1];
		// 		// 			if (scored && teamIndex == 1) recapLineScoreTxt += '</span>';
		// 		// 			recapLine[teamOrder[k]] = [shoot[0],shoot[1],(shoot[1] ? 'But' : 'Manqué')+' ('+recapLineScoreTxt+')'];
		// 		// 		}
		// 		// 		recap.push(recapLine);
		// 		// 	}


		// 		// 	res += getHighlightBox({'type':'trb_recap','recap':recap,'firstShootTeam':highlight['firstShootTeam']});
		// 		// 	// $('#foot-view-header-trb').html('Tirs au but: '+recapLineScoreTxt);


		// 		// 	break;
		// 		default:
		// 			res += getHighlightBox(highlight);
		// 			break;
		// 	}

		// 	if (playingMatch) addSmoothlyHighlightHTML(res);
		// 	else $('#foot-highlights').append(res);
		// }

		// function showMatch(playMatch) {

		// 	playingMatch = playMatch;
		// 	if (playingMatch) $('#foot-view').addClass('foot-playing-match');
		// 	else $('#foot-view').removeClass('foot-playing-match');

		// 	if (playingMatch) {
		// 		$('#foot-before-match-buttons, #foot-before-match-brand, #foot-next-btn').addClass('hidden');
		// 		$('#foot-view-header, #foot-infos > div, #foot-highlights-dv').removeClass('hidden');

		// 		addSmoothlyHighlightHTML(getHighlightTimeSeparation(0));

		// 		$('#foot-next-btn').removeClass('hidden');
		// 	}
		// 	else {
		// 		$('#foot-before-match-buttons, #foot-before-match-brand').addClass('hidden');
		// 		$('#foot-view-header, #foot-infos > div, #foot-highlights-dv').removeClass('hidden');

		// 		$('#foot-highlights').html(getHighlightTimeSeparation(0));
		// 		for (i in highlights) {
		// 			highlightIndex++;
		// 			addHighlight();
		// 		}
		// 	}

		// }

// ------------------ TODO ------------------


		serverResponse = function(opt) {
			if (opt[0] == 0) {
				for (i in persos) footPersos[persos[i]['name-resp']] = {'pp':addPathPartIfNotURL('/medias/pps/',persos[i]['pp'])};
				for (footPerso in opt[1][0]) footPersos[footPerso] = {'pp':addPathPartIfNotURL('/medias/pps/force/',opt[1][0][footPerso]['pp'])};
				footTeams = opt[1][1];
				opt[1][2] = opt[1][2].orderByKeyDesc('order').reverse();
				var catID;
				for (i in opt[1][2]) {
					catID = opt[1][2][i]['id'];
					footCats[catID] = opt[1][2][i];
					$('#foot-cats').append('<div id="foot-cat-'+catID+'" class="foot-box foot-box-selectable flx flx-ac"><img src="'+addPathPartIfNotURL('/medias/scenarios/foot/icons/',footCats[catID]['icon'])+'"><div>'+footCats[catID]['name']+'</div></div>');
					if (footCats[catID]['type'] == 0 || footCats[catID]['type'] == 1) {
						footCats[catID]['editionsOrdered'] = $.extend(true,[],footCats[catID]['editions']).orderByKeyDesc('order').reverse();
						var editions = {};
						var editionID;
						for (j in footCats[catID]['editions']) {
							editionID = footCats[catID]['editions'][j]['id'];
							editions[editionID] = footCats[catID]['editions'][j];
						}
						footCats[catID]['editions'] = editions;
						for (j in editions) setCatEditionStats(catID,j);
					}
					else if (footCats[catID]['type'] == 2) {
						var matchs = footCats[catID]['matchs'];
						var matchsRecap = [];
						var match;
						for (j in matchs) {
							match = [
								{'team':{'type':'normal','name':matchs[j]['teams'][0]}},
								{'team':{'type':'normal','name':matchs[j]['teams'][1]}}
							];
							if (matchs[j]['score'] !== undefined) {
								if (!isNaN(matchs[j]['score'][0]) && !isNaN(matchs[j]['score'][1])) {
									for (var k = 0; k < 2; k++) match[k]['goals0'] = matchs[j]['score'][k];
									if (!isNaN(matchs[j]['score'][2]) && !isNaN(matchs[j]['score'][3])) for (var k = 0; k < 2; k++) match[k]['trb'] = matchs[j]['score'][k+2];
									setMatchGoalsSum(match);
								}
								else {
									if (matchs[j]['score'][0] == 'F') {
										match[0]['isLooser'] = true;
										match[0]['forfeit'] = true;
									}
									if (matchs[j]['score'][1] == 'F') {
										match[1]['isLooser'] = true;
										match[1]['forfeit'] = true;
									}
								}
							}
							matchsRecap.push(match);
						}
						footCats[catID]['matchsRecap'] = matchsRecap;
					}
				}
				footBetOperators = opt[1][3];
				footMatchsNotes = opt[1][4];
				$('#foot-cats > div').click(function(){
					currentCatID = parseInt(this.id.replace('foot-cat-',''));
					currentCatType = footCats[currentCatID]['type'];
					currentEditionID = 0;
					if (true) {
					// if ([0,1,2].includes(currentCatType)) {
						// $('#matchs-thumbnails').html('');
						// $('#foot-view').addClass('hidden');
						var res = '';
						if (currentCatType == 2) {
							$('#foot-editions, #foot-cup-winner, #foot-cup-grid, #foot-cup-groups, #foot-cup-off-groups, #foot-cat-content-teams').html('');
							$('#foot-cat-content-display-buttons, #foot-cat-content > .foot-matchs-separation, #foot-cat-content-manage').addClass('hidden');
							$('#foot-cat-content-matchs').html(getMatchsRecapDayHTML('',footCats[currentCatID]['matchsRecap'],0));
							$('#foot-cat-content .foot-matchs-separation').addClass('hidden');
							addMatchsEventListeners();
							$('#foot-cat-content, #foot-cat-content-matchs').removeClass('hidden');
							prepareComments('foot-comments',3,currentCatID,0,0,true,false,true);
						}
						else {
							$('#foot-cat-content-display-buttons, #foot-cat-content-manage').removeClass('hidden');
							for (i in footCats[currentCatID]['editionsOrdered']) res += '<div id="foot-cat-'+currentCatID+'-'+footCats[currentCatID]['editionsOrdered'][i]['id']+'" class="foot-box foot-box-selectable flx flx-ac"><div>'+footCats[currentCatID]['editionsOrdered'][i]['name']+'</div></div>';
							$('#foot-editions').html(res);
							$('#foot-editions > div').click(function(){
								currentEditionIDAnc = currentEditionID;
								currentEditionID = this.id.replace('foot-cat-','').split('-')[1];
								$('#foot-cup-winner').html('');
								if (footCats[currentCatID]['editions'][currentEditionID]['hasBeenRandomized'] === true) {
									delete footCats[currentCatID]['editions'][currentEditionID]['winner'];
									setCatEditionStats(currentCatID,currentEditionID);
								}
								setCatEditionHTML(currentCatID,currentEditionID);
								$('#foot-cat-content').removeClass('hidden');
								focusCatContentMatchs();
								if (currentEditionID != currentEditionIDAnc) prepareComments('foot-comments',3,currentCatID,currentEditionID,0,true,false,true);
								$('#foot-editions > div').removeClass('selected');
								$(this).addClass('selected');
								$('#header-returnpage').click(function(e){e.preventDefault();resetCatContent();});
								matchsWasFiltered = false;
								footScrollTo();
							});
							footScrollTo('#foot-editions');
							resetCatContent();
						}
					}
					$('#foot-cats > div').removeClass('selected');
					$(this).addClass('selected');
				});
			}
		};

		$('#foot-cat-content-display-buttons > button').click(function(){
			displayCatContentTab(this.id.replace('foot-cat-content-display-',''));
			footScrollTo();
		});
		$('#foot-cat-content-randomize').click(function(){
			setCatEditionStats(currentCatID,currentEditionID,1);
			setCatEditionHTML(currentCatID,currentEditionID);
		});
		$('#foot-cat-content-randomize-all').click(function(){
			setCatEditionStats(currentCatID,currentEditionID,2);
			setCatEditionHTML(currentCatID,currentEditionID);
		});
		$('#foot-cat-content-copy').click(function(){
			var teams = footCats[currentCatID]['editions'][currentEditionID]['teams'];
			var matchsToUse = footCats[currentCatID]['editions'][currentEditionID]['matchsToUse'];
			var matchsToUseRandomCompleted = getRandomizedMatchsToUse(currentCatType,matchsToUse,1);
			var matchsRecap = footCats[currentCatID]['editions'][currentEditionID]['matchsRecap'];
			var lastMatchIndex = (currentCatType == 0 ? 285 : 379);
			var matchIndex = -1;
			var teamsLen = 0;
			for (i in teams) teamsLen++;
			var teamsCt = 0;
			var teamsDistribution = (currentCatType == 0 ? [16,5] : [1,20]);
			var teamIndex;
			var txt = '\t\t\t\t\t"teams" : {\n';
			for (var i = 0; i < teamsDistribution[0]; i++) {
				if (currentCatType == 0) txt += '\t\t\t\t\t\t// -- Groupe '+tablesLetters[i]+' --\n';
				for (var j = 0; j < teamsDistribution[1]; j++) {
					teamIndex = i*teamsDistribution[1]+j*1+1;
					txt += '\t\t\t\t\t\t'+(teams[teamIndex] === undefined ? '// ' : '')+'"'+teamIndex+'" : "';
					if (teams[teamIndex] !== undefined) {
						txt += teams[teamIndex];
						teamsCt++;
					}
					txt += '"'+(teams[teamIndex] === undefined && (i != teamsDistribution[0]-1 || j != teamsDistribution[1]-1) || teamsCt < teamsLen ? ',' : '')+'\n';
				}
				if (i < teamsDistribution[0]-1) txt += '\n';
			}
			txt += '\t\t\t\t\t},\n\t\t\t\t\t"matchs" : [\n';
			var match;
			var matchIsNotPlayedYet;
			var goals;
			for (i in matchsRecap) {
				txt += '\t\t\t\t\t\t// -- '+(currentCatType == 0 ? cupPhaseTitles[i] : 'Journée '+(parseInt(i)+1))+' --\n\n';
				for (j in matchsRecap[i]) {
					matchIndex++;
					match = matchsToUse[matchIndex];
					matchIsNotPlayedYet = (match === undefined);
					txt += '\t\t\t\t\t\t// '+(matchIsNotPlayedYet ? '// ' : '')+getTeamNameTxt(matchsRecap[i][j][0]['team'])+' - '+getTeamNameTxt(matchsRecap[i][j][1]['team'])+'\n\t\t\t\t\t\t';
					if (matchIsNotPlayedYet) {
						match = matchsToUseRandomCompleted[matchIndex];
						txt += '// ';
					}
					if (footCats[currentCatID]['editions'][currentEditionID]['usedStrats'] !== undefined && match !== null && !isNaN(match[0]) && !isNaN(match[1]) && matchsRecap[i][j][0]['stats'] === undefined && (isTeamWithStrat(matchsRecap[i][j][0]['team']) || isTeamWithStrat(matchsRecap[i][j][1]['team'])) && (matchIsNotPlayedYet || !matchIsNotPlayedYet && (match[0] > 0 || match[1] > 0))) {
						if (match[2] === undefined) match.push(null);
						if (match[3] === undefined) match.push(null);
						goals = [[],[]];
						if (!matchIsNotPlayedYet) for (var k = 0; k < 2; k++) for (var l = 0; l < match[k]; l++) goals[k].push(['',[[]]]);
						match.push({"goals":goals});
					}
					txt += JSON.stringify(match)+(matchIndex < lastMatchIndex ? (matchIsNotPlayedYet || matchsToUse[matchIndex+1] !== undefined ? ',' : '')+'\n\n' : '\n');
				}
			}
			txt += '\t\t\t\t\t]\n';
			copyToClipboard(txt);
		});
		$('#foot-cat-content-match-stats-bkg').click(function(){
			$('#foot-cat-content-match-stats-dv-dv-dv').addClass('hidden');
			// Thanks to tfe and Igor Raush
			// https://stackoverflow.com/questions/3656592/how-to-programmatically-disable-page-scrolling-with-jquery
			// un-lock scroll position
			var html = jQuery('html');
			scrollPosition = html.data('scroll-position');
			html.css('overflow', html.data('previous-overflow'));
			window.scrollTo(scrollPosition[0], scrollPosition[1]);
		});


// ------------------ TODO ------------------

		// $('#foot-play-match').click(function(){showMatch(true)});
		// $('#foot-show-results').click(function(){showMatch(false)});
		// $('#foot-button-fullscreen').click(function(){$('#foot-view')[0].requestFullscreen();});
		// $('#foot-infos').click(function(){
		// 	presentationIDsIndex++;
		// 	$('#foot-'+presentationIDs[presentationIDsIndex%presentationIDsLen])[0].scrollIntoView({block:'nearest',behavior:'smooth'});
		// });
		// $('#foot-next-btn').click(function(){
		// 	highlightIndex++;
		// 	if (highlights[highlightIndex] === undefined) return;
		// 	addHighlight();
		// });

		var footPersos = {};
		var footTeams;
		var footCats = {};
		var footBetOperators;
		var footMatchsNotes;
		var currentCatID;
		var currentCatType;
		var currentEditionID;
		var currentEditionIDAnc;
		var footPersosMatch;
		var footPersosMatchIndex;
		var makedTabs;
		var matchsWasFiltered;
		// var matchs = {};
		// var currentMatchID;

		var championshipMatchsOrder = [[[9,10],[8,11],[7,12],[6,13],[5,14],[4,15],[3,16],[2,17],[1,18],[0,19]],[[19,9],[10,8],[11,7],[12,6],[13,5],[14,4],[15,3],[16,2],[17,1],[18,0]],[[9,18],[8,19],[7,10],[6,11],[5,12],[4,13],[3,14],[2,15],[1,16],[0,17]],[[17,9],[18,8],[19,7],[10,6],[11,5],[12,4],[13,3],[14,2],[15,1],[16,0]],[[9,16],[8,17],[7,18],[6,19],[5,10],[4,11],[3,12],[2,13],[1,14],[0,15]],[[15,9],[16,8],[17,7],[18,6],[19,5],[10,4],[11,3],[12,2],[13,1],[14,0]],[[9,14],[8,15],[7,16],[6,17],[5,18],[4,19],[3,10],[2,11],[1,12],[0,13]],[[13,9],[14,8],[15,7],[16,6],[17,5],[18,4],[19,3],[10,2],[11,1],[12,0]],[[9,12],[8,13],[7,14],[6,15],[5,16],[4,17],[3,18],[2,19],[1,10],[0,11]],[[11,9],[12,8],[13,7],[14,6],[15,5],[16,4],[17,3],[18,2],[19,1],[10,0]],[[14,15],[13,16],[12,17],[11,18],[10,19],[4,5],[3,6],[2,7],[1,8],[0,9]],[[19,14],[15,13],[16,12],[17,11],[18,10],[9,4],[5,3],[6,2],[7,1],[8,0]],[[14,18],[13,19],[12,15],[11,16],[10,17],[4,8],[3,9],[2,5],[1,6],[0,7]],[[17,14],[18,13],[19,12],[15,11],[16,10],[7,4],[8,3],[9,2],[5,1],[6,0]],[[14,16],[13,17],[12,18],[11,19],[10,15],[4,6],[3,7],[2,8],[1,9],[0,5]],[[18,17],[19,16],[13,12],[14,11],[8,7],[9,6],[3,2],[4,1]],[[16,18],[15,19],[11,13],[10,14],[6,8],[5,9],[1,3],[0,4]],[[17,16],[18,15],[12,11],[13,10],[7,6],[8,5],[2,1],[3,0]],[[18,19],[15,17],[13,14],[10,12],[8,9],[5,7],[3,4],[0,2]],[[17,19],[16,15],[12,14],[11,10],[7,9],[6,5],[2,4],[1,0]]];
		var cupPhaseTitles = [
			'Barrages - J1',
			'Barrages - J2',
			'Barrages - J3',
			'Barrages - J4',
			'Barrages - J5',
			'Phase de groupes - J1',
			'Phase de groupes - J2',
			'Phase de groupes - J3',
			'Phase de groupes - J4',
			'Phase de groupes - J5',
			'Phase de groupes - J6',
			'Huitièmes de finale - Manche 1/2',
			'Huitièmes de finale - Manche 2/2',
			'Quarts de finale - Manche 1/2',
			'Quarts de finale - Manche 2/2',
			'Demi-finales - Manche 1/2',
			'Demi-finales - Manche 2/2',
			'Match pour la troisième place',
			'Finale'
		];
		var randomScores = {'distribution':{0:150,1:440,2:400,3:350,4:120,5:40,6:15,7:5,8:3},'res':[]};
		for (i in randomScores['distribution']) for (var j = 0; j < randomScores['distribution'][i]; j++) randomScores['res'].push(i);
		randomScores['resLen'] = randomScores['res'].length;
		var tablesLetters = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P'];

		var jobs = ['Sélectionneur','Gardien de but','Défenseur','Milieu de terrain','Attaquant','Arbitre central','Arbitre assistant','Arbitre assistant','Quatrième arbitre','Chef opérationnel','Adjoint n°1','Adjoint n°2','Adjoint n°3','Assistant stratégie','Secouriste'];
		var ppAdds = {
			'but' : '<img class="foot-perso-pp-add foot-perso-pp-add-score" src="https://ssl.gstatic.com/onebox/sports/soccer_timeline/soccer-ball.svg">',
			'but_nb' : '<div class="foot-perso-pp-add foot-perso-pp-add-score-number"></div>',
			'substitution_out' : '<div class="foot-perso-pp-add foot-perso-pp-add-substitution foot-perso-pp-add-substitution-out"><img src="https://ssl.gstatic.com/onebox/sports/soccer_timeline/substitution-out.svg"></div>',
			'substitution_in' : '<div class="foot-perso-pp-add foot-perso-pp-add-substitution foot-perso-pp-add-substitution-in"><img src="https://ssl.gstatic.com/onebox/sports/soccer_timeline/substitution-in.svg"></div>',
			'carton_jaune' : '<img class="foot-perso-pp-add foot-perso-pp-add-card" src="https://ssl.gstatic.com/onebox/sports/soccer_timeline/yellow-card-right.svg">',
			'carton_rouge' : '<img class="foot-perso-pp-add foot-perso-pp-add-card" src="https://ssl.gstatic.com/onebox/sports/soccer_timeline/red-card-right.svg">',
			'carton_jaune_rouge' : '<img class="foot-perso-pp-add foot-perso-pp-add-card" src="https://ssl.gstatic.com/onebox/sports/game_feed/second_yellow_card_icon.svg">'
		};

		// var times = [
		// 	[  0,'Avant match'],
		// 	[ 45,'Coup d\'envoi'],
		// 	[ 45,'Mi-temps'],
		// 	[ 90,'Seconde mi-temps'],
		// 	[ 90,'Temps réglementaire'],
		// 	[105,'Polongation'],
		// 	[105,'Mi-temps de la prolongation'],
		// 	[120,'Seconde prolongation'],
		// 	[120,'Fin de la prolongation']
		// ];
		// var timeIndex;

		// var highlightTypes = {
		// 	'commentaire' : {'title':'COMMENTAIRES','icon':'https://ssl.gstatic.com/onebox/sports/game_feed/commentary_icon.svg'},
		// 	'carton_jaune' : {'title':'CARTON JAUNE','icon':'https://ssl.gstatic.com/onebox/sports/game_feed/yellow_card_icon.svg'},
		// 	'carton_rouge' : {'title':'CARTON ROUGE','icon':'https://ssl.gstatic.com/onebox/sports/game_feed/red_card_icon.svg'},
		// 	'carton_jaune_rouge' : {'title':'CARTON ROUGE','icon':'https://ssl.gstatic.com/onebox/sports/game_feed/second_yellow_card_icon.svg'},
		// 	'remplacement' : {'title':'REMPLACEMENT','icon':'https://ssl.gstatic.com/onebox/sports/game_feed/substitution_icon.svg'},
		// 	'occasion' : {'title':'OCCASION','icon':'https://ssl.gstatic.com/onebox/sports/game_feed/goal_icon.svg'},
		// 	'var_but' : {'title':'VÉRIFICATION DE BUT','icon':'/src/icons/scenarios/foot/VAR_System_Logo.svg'},
		// 	'var_penalty' : {'title':'POTENTIEL PENALTY','icon':'/src/icons/scenarios/foot/VAR_System_Logo.svg'},
		// 	'var_mais' : {'title':'POTENTIEL MAÏS','icon':'/src/icons/scenarios/foot/VAR_System_Logo.svg'},
		// 	'decision' : {'title':'DÉCISION PRISE','icon':'/src/icons/scenarios/foot/whistle-icon-12.svg'},
		// 	'but' : {'title':'BUUUUT !!!','icon':'https://ssl.gstatic.com/onebox/sports/game_feed/goal_icon.svg'},
		// 	'temps_additionnel' : {'title':'TEMPS ADDITIONNEL ANNONCÉ','icon':'/src/icons/scenarios/foot/stopwatch_icon.svg'},
		// 	'tirs_au_but' : {'title':'TIRS AU BUT','icon':'/src/icons/scenarios/foot/soccer-goal-881047.svg'},
		// 	'tirage_au_sort' : {'title':'TIRAGE AU SORT','icon':'/src/icons/scenarios/foot/coin-toss-98997.svg'},
		// 	'va_tirer' : {'title':'S\'APPRÊTE À TIRER','icon':'/src/icons/scenarios/foot/soccer-goal-881047.svg'},
		// 	'trb_recap' : {'title':'RÉCAPITULATIF DES TIRS AU BUT','icon':'/src/icons/scenarios/foot/soccer-goal-881047.svg'}
		// };
		// var highlightIndex;

		// var presentationIDs = [
		// 	'field-team-name-1',
		// 	'substitues',
		// 	'coachs',
		// 	'referees',
		// 	'referees-var',
		// 	'field-team-name-0'
		// ];
		// const presentationIDsLen = presentationIDs.length;
		// var presentationIDsIndex;

		// var highlights;
		// var hasPreviousScore;
		// var score;
		// var previousScore;
		// var trbScore;
		// var aggregate;
		// var scoreInLists;
		// var redCardInLists;
		// var matchPoint;
		// var playingMatch;

		var scrollPosition;

// ------------------ TODO ------------------


		serverComments(0);
	});
	</script>
</head>
<body>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../header.php'; ?>
	<div id="pg">
		<div class="flx flx-jc flx-ac pgtlt">
			<img src="/src/icons/menu/white/93-939160_soccer-ball-png-file-soccer-ball-svg.svg"/>
			<h1>Foot</h1>
			<img src="/src/icons/menu/white/93-939160_soccer-ball-png-file-soccer-ball-svg.svg"/>
		</div>
		<div id="foot-cats" class="foot-contain-cats-boxes flx flx-jc flx-ww"></div>
		<div id="foot-editions" class="foot-contain-cats-boxes flx flx-jc flx-ww"></div>
		<div id="foot-cat-content" class="hidden">
			<div id="foot-cat-content-display-buttons" class="flx flx-ae hide-scrollbar">
				<button id="foot-cat-content-display-cat-content-matchs">Matchs</button>
				<button id="foot-cat-content-display-cat-content-teams">Équipes</button>
				<button id="foot-cat-content-display-cup-grid-dv">Phase finale</button>
				<button id="foot-cat-content-display-cup-groups">Phase de groupes</button>
				<button id="foot-cat-content-display-cup-off-groups">Barrages</button>
				<button id="foot-cat-content-display-championship-leaderboard-dv">Classement</button>
				<button id="foot-cat-content-display-all">Tout</button>
			</div>
			<div id="foot-cat-content-scroll-attach-point"></div>
			<div id="foot-cup-winner" class="foot-contain-match-boxes flx flx-jc"></div>
			<div id="foot-cup-grid-dv-sep" class="foot-matchs-separation flx flx-jc flx-ac"><div></div><div>Phase finale</div><div></div></div>
			<div id="foot-cup-grid-dv"><div id="foot-cup-grid" class="foot-contain-match-boxes flx"></div></div>
			<div id="foot-cup-groups-sep" class="foot-matchs-separation flx flx-jc flx-ac"><div></div><div>Phase de groupes</div><div></div></div>
			<div id="foot-cup-groups" class="foot-contain-cup-groups flx flx-jc flx-ww"></div>
			<div id="foot-cup-off-groups-sep" class="foot-matchs-separation flx flx-jc flx-ac"><div></div><div>Barrages</div><div></div></div>
			<div id="foot-cup-off-groups" class="foot-contain-cup-groups flx flx-jc flx-ww"></div>
			<div id="foot-championship-leaderboard-dv" class="flx flx-jc"></div>
			<div id="foot-cat-content-teams-sep" class="foot-matchs-separation flx flx-jc flx-ac"><div></div><div>Équipes</div><div></div></div>
			<div id="foot-cat-content-teams" class="foot-contain-match-boxes flx flx-jc flx-ww"></div>
			<div id="foot-cat-content-matchs-sep" class="foot-matchs-separation flx flx-jc flx-ac"><div></div><div>Matchs</div><div></div></div>
			<div id="foot-cat-content-matchs" class="foot-contain-match-boxes"></div>
			<div id="foot-cat-content-manage" class="flx flx-je">
				<button id="foot-cat-content-randomize">Randomiser</button>
				<button id="foot-cat-content-randomize-all">Randomiser tout</button>
				<button id="foot-cat-content-copy">Copier</button>
			</div>
			<div id="foot-cat-content-match-stats-dv-dv-dv" class="hidden">
				<div id="foot-cat-content-match-stats-dv-dv">
					<div id="foot-cat-content-match-stats-bkg" class="fillrelativeparent"></div>
					<div id="foot-cat-content-match-stats-dv" class="fillrelativeparent flx flx-jc flx-ac">
						<div id="foot-cat-content-match-stats" class="foot-box hide-scrollbar">
							<div id="foot-match-stats-header"></div>
							<div id="foot-match-stats-teams-names" class="flx flx-jc flx-ac">
								<div id="foot-match-stats-team-name-0" class="foot-box-team-name hide-scrollbar"></div>
								<div class="foot-box-team-icon-dv"><img id="foot-match-stats-team-icon-0"></div>
								<div>VS</div>
								<div class="foot-box-team-icon-dv"><img id="foot-match-stats-team-icon-1"></div>
								<div id="foot-match-stats-team-name-1" class="foot-box-team-name hide-scrollbar"></div>
							</div>
							<div id="foot-match-stats-aggregate" class="foot-cat-content-match-stats-head-text"></div>
							<div id="foot-match-stats-forfeit-message" class="foot-cat-content-match-stats-head-text"></div>
							<div id="foot-match-stats-score" class="flx flx-jc">
								<div id="foot-match-stats-score-team-0"></div>
								<div>-</div>
								<div id="foot-match-stats-score-team-1"></div>
							</div>
							<div id="foot-match-stats-scores-lists" class="foot-match-stats-lists flx">
								<div id="foot-match-stats-scores-list-team-0"></div>
								<div><img src="https://ssl.gstatic.com/onebox/sports/soccer_timeline/soccer-ball.svg"></div>
								<div id="foot-match-stats-scores-list-team-1"></div>
							</div>
							<div id="foot-match-stats-red-cards-lists" class="foot-match-stats-lists flx">
								<div id="foot-match-stats-red-cards-list-team-0"></div>
								<div><img src="https://ssl.gstatic.com/onebox/sports/soccer_timeline/red-card-right.svg"></div>
								<div id="foot-match-stats-red-cards-list-team-1"></div>
							</div>
							<div class="flx flx-dc flx-ac"><div id="foot-match-stats-probabilities"></div></div>
							<div class="flx flx-jc">
								<div id="foot-match-stats-persos">
									<div id="foot-match-stats-field-team-0">
										<div id="foot-match-stats-field-team-header-0" class="foot-field-team-header flx flx-ac">
											<div id="foot-match-stats-field-team-icon-0" class="foot-box-team-icon-dv"></div>
											<div id="foot-match-stats-field-team-name-0" class="hide-scrollbar"></div>
											<div id="foot-match-stats-field-team-strat-0"></div>
										</div>
										<div id="foot-match-stats-field-0" class="foot-field foot-field-top"></div>
									</div>
									<div id="foot-match-stats-field-team-1">
										<div id="foot-match-stats-field-1" class="foot-field foot-field-bottom"></div>
										<div id="foot-match-stats-field-team-header-1" class="foot-field-team-header flx flx-ac">
											<div id="foot-match-stats-field-team-icon-1" class="foot-box-team-icon-dv"></div>
											<div id="foot-match-stats-field-team-name-1" class="hide-scrollbar"></div>
											<div id="foot-match-stats-field-team-strat-1"></div>
										</div>
									</div>
									<div id="foot-match-stats-substitues">
										<div class="foot-persos-lists-title flx flx-jb flx-ac">
											<div id="foot-match-stats-substitues-team-icon-0" class="foot-box-team-icon-dv"></div>
											<div>Remplaçants</div>
											<div id="foot-match-stats-substitues-team-icon-1" class="foot-box-team-icon-dv"></div>
										</div>
										<div class="foot-persos-lists"></div>
									</div>
									<div id="foot-match-stats-coachs">
										<div class="foot-persos-lists-title flx flx-jb flx-ac">
											<div id="foot-match-stats-coachs-team-icon-0" class="foot-box-team-icon-dv"></div>
											<div>Seléctionneurs</div>
											<div id="foot-match-stats-coachs-team-icon-1" class="foot-box-team-icon-dv"></div>
										</div>
										<div class="foot-persos-lists"></div>
									</div>
									<div id="foot-match-stats-staff">
										<div class="foot-persos-lists-title flx flx-jb flx-ac">
											<div id="foot-match-stats-staff-team-icon-0" class="foot-box-team-icon-dv"></div>
											<div>STAFF</div>
											<div id="foot-match-stats-staff-team-icon-1" class="foot-box-team-icon-dv"></div>
										</div>
										<div class="foot-persos-lists"></div>
									</div>
									<div id="foot-match-stats-referees">
										<div class="foot-persos-lists-title">Arbitres</div>
										<div class="foot-persos-lists"></div>
									</div>
									<div id="foot-match-stats-referees-var">
										<div class="foot-persos-lists-title">VAR</div>
										<div class="foot-persos-lists"></div>
									</div>
								</div>
							</div>
							<div id="foot-match-stats-notes">
								<div>Notes</div>
								<div id="foot-match-stats-notes-content"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
<!--
		<div id="matchs-thumbnails" class="flx flx-jc flx-ww"></div>
		<div id="foot-view" class="flx flx-dc flx-ac hidden">
			<div id="foot-view-header">
				<div>
					<div class="foot-score foot-score-containing-icons flx flx-jc flx-ac">
						<div class="foot-score-team-name-with-icon flx flx-dc flx-ac">
							<img class="foot-team-icon-0">
							<span class="foot-team-name-0"></span>
						</div>
						<div id="foot-score-team-0" class="foot-score-nb"></div>
						<div id="foot-view-header-time">
							<div id="foot-view-header-time-text">-</div>
							<div><div></div></div>
						</div>
						<div id="foot-score-team-1" class="foot-score-nb"></div>
						<div class="foot-score-team-name-with-icon flx flx-dc flx-ac">
							<img class="foot-team-icon-1">
							<span class="foot-team-name-1"></span>
						</div>
					</div>
					<div id="foot-previous-score" class="flx flx-jc">
						<div class="foot-score flx flx-jc flx-ac">
							<div></div>
							<div id="foot-previous-score-team-0" class="foot-score-nb"></div>
							<div>-</div>
							<div id="foot-previous-score-team-1" class="foot-score-nb"></div>
							<div></div>
						</div>
					</div>
					<div id="foot-view-header-trb"></div>
				</div>
				<div id="foot-match-name"></div>
				<div id="foot-match-scores-and-red-cards-lists" class="hide-scrollbar">
					<div id="foot-match-scores-lists" class="flx">
						<div id="foot-match-scores-list-team-0"></div>
						<div><img src="https://ssl.gstatic.com/onebox/sports/soccer_timeline/soccer-ball.svg"></div>
						<div id="foot-match-scores-list-team-1"></div>
					</div>
					<div id="foot-match-red-cards-lists" class="flx">
						<div id="foot-match-red-cards-list-team-0"></div>
						<div><img src="https://ssl.gstatic.com/onebox/sports/soccer_timeline/red-card-right.svg"></div>
						<div id="foot-match-red-cards-list-team-1"></div>
					</div>
				</div>
			</div>
			<div id="foot-view-content" class="flx">
				<div id="foot-infos" class="hide-scrollbar">
					<div id="foot-field-team-0">
						<div id="foot-field-team-name-0" class="foot-field-team-name"></div>
						<div id="foot-field-0" class="foot-field foot-field-top"></div>
					</div>
					<div id="foot-field-team-1">
						<div id="foot-field-1" class="foot-field foot-field-bottom"></div>
						<div id="foot-field-team-name-1" class="foot-field-team-name"></div>
					</div>
					<div id="foot-substitues">
						<div class="foot-persos-lists-title flx flx-jb flx-ac">
							<img class="foot-team-icon-0">
							<div>Remplaçants</div>
							<img class="foot-team-icon-1">
						</div>
						<div class="foot-persos-lists"></div>
					</div>
					<div id="foot-coachs">
						<div class="foot-persos-lists-title flx flx-jb flx-ac">
							<img class="foot-team-icon-0">
							<div>Seléctionneurs</div>
							<img class="foot-team-icon-1">
						</div>
						<div class="foot-persos-lists"></div>
					</div>
					<div id="foot-referees">
						<div class="foot-persos-lists-title">Arbitres</div>
						<div class="foot-persos-lists"></div>
					</div>
					<div id="foot-referees-var">
						<div class="foot-persos-lists-title">VAR</div>
						<div class="foot-persos-lists"></div>
					</div>
				</div>
				<div id="foot-highlights-dv" class="flx flx-dc hide-scrollbar">
					<div id="foot-highlights" class="hide-scrollbar"></div>
					<div class="flx flx-jc"><button id="foot-next-btn">Suite</button></div>
				</div>
			</div>
			<div id="foot-before-match-buttons" class="fillrelativeparent flx flx-dc flx-jc flx-ac">
				<button id="foot-play-match">Jouer le match</button>
				<button id="foot-show-results">Résultats</button>
			</div>
			<div id="foot-before-match-brand">
				<div>
					<div id="foot-before-match-brand-bar"></div>
					<div id="foot-before-match-brand-infos" class="flx flx-jc flx-ac">
						<img class="foot-team-icon-0">
						<div class="foot-score flx flx-jc flx-ac">
							<div class="foot-team-name-0"></div>
							<div>-</div>
							<div class="foot-team-name-1"></div>
						</div>
						<img class="foot-team-icon-1">
					</div>
				</div>
			</div>
			<div id="foot-button-fullscreen" class="buttonlike buttonlike-fullscreen"></div>
			<div id="foot-view-no-display" class="fillrelativeparent flx flx-jc flx-ac"><i>Sélectionner un match</i></div>
		</div>
 -->
 		<div id="foot-comments"></div>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/../footer.php'; ?>
	</div>
</body>
</html>
