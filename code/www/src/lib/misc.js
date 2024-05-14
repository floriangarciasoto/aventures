server = function(type,sendata={}) {
	var URI = '';
	for (i in sendata) URI += '&'+encodeURIComponent(i)+'='+encodeURIComponent(sendata[i]);
	// console.log('Try to contact server with type :',type);
	// if (URI != '') console.log('Data sent to the server :',URI);
	$.post({
		url : 'index.php',
		data : 'type='+type+URI,
		success : serverResponse,
		dataType : 'json'
	});
}
serverResponse = function(opt) {
	// console.log('Response from server :',opt);
}

getPpsPathsListFromObj = function(pps) {
	var lst = [];
	for (i in pps) for (j in pps[i]) for (k in pps[i][j]) lst.push(i+'/'+j+'/'+pps[i][j][k]);
	return lst;
}
getSelectOptionsHTMLFromImagesList = function(lst) {
	var selectOptionsHTML = '';
	for (i in lst) selectOptionsHTML += '<option value="'+lst[i]+'">'+lst[i]+'</option>';
	return selectOptionsHTML;
}

isURL = function(str) {
	return (str.search(/^https*:\/\/|^data\:/) != -1);
}
isImgURL = function(str) {
	return (isURL(str) && str.search(/\.(jpe?g|png|gif|webp|svg)(|\?.*)$|^data\:/) != -1);
}
getEscapedLineStr = function(str) {
	return str.replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
getEscapedLinesStr = function(str) {
	return str.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br/>');
}

Object.defineProperty(Array.prototype,'orderByKeyDesc',{
	enumerable : false,
	value : function(key) {
		return this.sort((a,b) => a[key] < b[key] ? 1 : (a[key] > b[key] ? -1 : 0));
	}
});

// Thanks to Lavamantis and Peter Mortensen
// https://stackoverflow.com/questions/11832914/how-to-round-to-at-most-2-decimal-places-if-necessary
Number.prototype.round = function(places) {
	return +(Math.round(this+'e+'+places)+'e-'+places);
}

copyToClipboard = function(txt) {
	var tmp = $("<textarea/>");
	$("body").append(tmp);
	tmp.val(txt).select();
	document.execCommand("copy");
	tmp.remove();
}
