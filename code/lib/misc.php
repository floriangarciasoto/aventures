<?php
function isURL($str) {
	return (preg_match('/^https*:\/\//',$str) == 1);
}
function isImgURL($str) {
	return (isURL($str) && preg_match('/\.(jpe?g|png|gif|webp)(|\?.*)$/',$str) == 1);
}
function getStringFromTextData($path) {
	return file_get_contents($_SERVER['DOCUMENT_ROOT'].'/../data/txt/'.$path.'.txt');
}
function getPageJSONData($path) {
	return json_decode(preg_replace('/\/\/ .*/','',file_get_contents($_SERVER['DOCUMENT_ROOT'].'/../data/json/'.$path.'.json')));
}
// WILL BE DELETED :
function addPathPartIfNotURL($pathPart,$str) {
	if (!isImgURL($str)) return $pathPart.$str;
	return $str;
}
function getEscapedAngleBrackets($txt) {
	return str_replace(['<','>'],['&lt;','&gt;'],$txt);
}
function getLines($txt) {
	return str_replace("\n",'<br/>',$txt);
}
function getSinceDate($SQLResult) {
	$sinceWords = [['years','an'],['months','moi'],['weeks','semaine'],['days','jour'],['hours','heure'],['minutes','minute']];
	foreach ($sinceWords as $key => $value) {
		$since = $SQLResult[$value[0]];
		if ($since > 0) {
			if ($since > 1 || $key == 1) return 'Il y a '.$since.' '.$value[1].'s';
			return 'Il y a '.$since.' '.$value[1];
		}
	}
	return 'À l\'instant';
}
?>