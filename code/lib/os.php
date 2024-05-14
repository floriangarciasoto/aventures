<?php
function getFilesListFromFolder($path) {
	$fileslist = array_diff(scandir($_SERVER['DOCUMENT_ROOT'].$path),['.','..']);
	natsort($fileslist);
	return array_values($fileslist);
}
function getRecursiveFilesListFromFolder($path) {
	$files = [];
	$fileslist = getFilesListFromFolder($path);
	foreach ($fileslist as $value) {
		if (is_dir($_SERVER['DOCUMENT_ROOT'].$path.'/'.$value)) $files[$value] = getRecursiveFilesListFromFolder($path.'/'.$value);
		else array_push($files,$value);
	}
	return $files;
}
function getpps($ppsFolders) {
	$pps = array();
	foreach ($ppsFolders as $value) $pps[$value] = getRecursiveFilesListFromFolder('/medias/pps/'.$value);
	return $pps;
}
?>