function getTextCodesFromFilesList(list) {
	return list.map(file => ':'+file.replace(/\.[^\.]*$/,'')+':');
}

function addPathPartIfNotURL(path,str) {
	if (!isImgURL(str)) return path+str;
	return str;
}

function getReplacedHashtagsWithHTMLTags(str) {
	return str.replace(/#[A-Z-a-z][^ \n]*/g,match => '<span class="hashtag">'+match+'</span>');
}

function getReplacedURIsWithHTMLTags(str) {
	return str.replace(/https?:\/\/[^ \n<>]*\.(jpg|jpeg|png|gif|webp)(\?[^ \n<>]*)?(?=\b)/gi,match => '<div><img src="'+match+'"/></div>')
		.replace(/https?:\/\/[^ \n<>]*\.(mp4|webm|avi|mpeg)(\?[^ \n<>]*)?(?=\b)/gi,match => '<div><video src="'+match+'" muted autoplay loop></video></div>')
		.replace(/(?<!")https?:\/\/[^ \n<>]*(?!")/gi,match => '<a href="'+match+'">'+match+'</a>');
}

// Thanks to Stephen M. Harris
// https://stackoverflow.com/questions/5069464/replace-multiple-strings-at-once
function replaceBulk(str,searchArray,replaceArray) {
	var i, regex = [], map = {};
	for (i=0; i<searchArray.length; i++) {
		regex.push(searchArray[i].replace(/([-[\]{}()*+?.\\^$|#,])/g,'\\$1'));
		map[searchArray[i]] = replaceArray[i];
	}
	regex = regex.join('|');
	str = str.replace(new RegExp(regex,'g'),function(matched){
		return map[matched];
	});
	return str;
}

function getFormatedPersoNameByID(id) {
	var name = replaceBulk(getEscapedLineStr(persos[id]['name']),searchArrayOnName,replaceArrayOnName);
	if (persos[id]['certified'] == 1) return name+' <img class="certified-account-icon" src="/src/icons/iconmonstr-check-mark-4.svg" title="Compte certifié"/>';
	return name;
}

function setPersos(opt) {
	persos = {};
	nbPersos = 0;
	for (i in opt) {
		persos[opt[i]['id']] = opt[i];
		nbPersos++;
	}
	searchArrayOnName = [];
	var name;
	for (i in persos) {
		name = persos[i]['name'].replace(regexCustomEmojis,'');
		persos[i]['name-sl'] = name.replace(/^ +| +$/g,'').replace(/ {2,}/g,' ');
		name = name.replace(regexpUnicodeModified,'').replace(/^ +| +$/g,'').replace(/ {2,}/g,' ');
		persos[i]['name-resp'] = name;
		name = getEscapedLineStr(name);
		searchArrayOnName.push('@'+name);
	}
	searchArrayOnName = searchArrayOnName.sort().reverse();
	replaceArrayOnName = $.extend(true,[],searchArrayOnName).map(at => '<span class="at">'+at+'</span>');
	searchArrayOnName = searchArrayOnName.concat(customEmojisSearchArray);
	replaceArrayOnName = replaceArrayOnName.concat(customEmojisReplaceArray);
	searchArrayOnText = searchArrayOnName.concat(soundsForCommentsSearchArray).concat(videosForCommentsSearchArray);
	replaceArrayOnText = replaceArrayOnName.concat(soundsForCommentsReplaceArray).concat(videosForCommentsReplaceArray);
}

function setPersosOptions() {
	var persosArray = [];
	for (i in persos) persosArray.push(persos[i]);
	ownLastUsedPersosIndexes = persosArray.filter(perso => perso['group'] == 0 || perso['group'] == myGroupID).sort((a,b) => a['last-used'] < b['last-used'] ? 1 : (a['last-used'] > b['last-used'] ? -1 : 0)).map(perso => perso['id']);
	myLastUsedPersoId = ownLastUsedPersosIndexes[0];
	persosOptions = '';
	for (i in ownLastUsedPersosIndexes) persosOptions += '<option value="'+ownLastUsedPersosIndexes[i]+'">'+persos[ownLastUsedPersosIndexes[i]]['name-sl']+'</option>';
}

function setBansDef() {
	bansDef = bans.filter(ban => ban['banisher'] == 0).map(ban => ban['banned']);
}

function getPersoPPURLByID(id) {
	return addPathPartIfNotURL('/medias/pps/',persos[id]['pp']);
}

function getPreparedObjToSend(selectorID) {
	var objToSend = {
		'selectorid' : selectorID,
		'page' : comments[selectorID]['page'],
		'content' : comments[selectorID]['content'],
		'subcontent' : comments[selectorID]['subContent'],
		'nbpersos' : nbPersos,
		'nbbans' : nbBans
	};
	if (comments[selectorID]['updated'] !== undefined) objToSend['updated'] = comments[selectorID]['updated'];
	if (comments[selectorID]['notLoadedCommentsNums'] !== undefined) objToSend['notloadedcommentsnums'] = comments[selectorID]['notLoadedCommentsNums'].join('-');
	if (imAdmin) objToSend['nbconvs'] = nbConvs;
	return objToSend;
}

function spinButton($button) {
	$button.css('rotate','360deg');
	setTimeout(function(){
		$button.css('transition-duration','unset');
		$button.css('rotate','unset');
		setTimeout(function(){$button.removeAttr('style');},100);
	},400);
}

function showComments(selectorID) {
	$('#'+selectorID).html('<div class="comments-nb-dv flx flx-jc"><span class="comments-nb"></span> commentaires</div><div class="comment-0 comment comment-send beenadded"><div class="comment-edit flx"><div><img class="pp" src="'+getPersoPPURLByID(myLastUsedPersoId)+'"/></div><div class="comment-texts"><div><select class="comment-author">'+persosOptions+'</select></div><div><textarea class="comment-textarea maxedwidth" rows="5" placeholder="Commentaire ..."></textarea></div></div></div><div class="comment-manage comment-edit flx flx-je"><span class="comment-cancel">ANNULER</span><span class="comment-post">POSTER</span></div><div class="comment-show flx flx-jc"><button class="comment-modif">Poster un commentaire</button></div></div><div class="comments-dv"><div class="comments-list"></div><div class="comments-buttons-dv flx flx-je flx-ac"><div id="'+selectorID+'-comments-update" class="comments-update buttonlike buttonlike-refresh"></div></div></div>');
	comments[selectorID]['$updateButton'] = $('#'+selectorID+'-comments-update');
	comments[selectorID]['$updateButton'].click(function(){
		var selectorID = this.parentNode.parentNode.parentNode.id;
		serverComments(5,getPreparedObjToSend(selectorID));
	});
	if (imAdmin) {
		$('#'+selectorID+' .comments-buttons-dv').prepend('<div class="comments-manage-controls flx flx-je flx-ac"><button class="comments-manage-ok">OK</button><div class="comments-convsl-dv"><select id="'+selectorID+'-comments-convsl" class="comments-convsl"></select></div><select class="comments-manage-type"><option value="0">Cacher</option><option value="1">Supprimer</option><option value="2">Envoyer</option><option value="3">Ban def</option></select><div class="comments-unselect buttonlike buttonlike-comments-unselect"></div></div><div class="comments-manage buttonlike buttonlike-comments-manage"></div>');
		$('#'+selectorID+' .comments-manage').click(function(){
			var selectorID = this.closest('.comments').id;
			$('#'+selectorID).toggleClass('selecting-comments');
			comments[selectorID]['selectingComments'] = !comments[selectorID]['selectingComments'];
			spinButton($(this));
		});
		comments[selectorID]['$manageTypeSl'] = $('#'+selectorID+' .comments-manage-type');
		$('#'+selectorID+' .comments-unselect').click(function(){if (confirm('Désélectionner les commentaires ?')) $(this.closest('.comments')).find('.comments-list .comment.selected').removeClass('selected comment-banned-showanyway');});
		comments[selectorID]['$manageTypeSl'].change(function(){
			if ($(this).val() == 2) $(this.parentNode).addClass('comments-manage-controls-with-convs');
			else $(this.parentNode).removeClass('comments-manage-controls-with-convs');
		});
		$('#'+selectorID+' .comments-manage-ok').click(function(){
			var selectorID = this.closest('.comments').id;
			var manageType = comments[selectorID]['$manageTypeSl'].val();
			if (confirm(manageType == 0 ? 'Cacher les commentaires sélectionnés ?' : (manageType == 1 ? 'Supprimer les messages sélectionnés ?' : (manageType == 2 ? 'Déplacer les messages sélectionnés ?' : 'Bannir définitivement les auteurs des commentaires sélectionnés ?')))) {
				var objToSend = getPreparedObjToSend(selectorID);
				var persosToBanIDs = {};
				objToSend['managetype'] = manageType;
				objToSend['len'] = 0;
				$('#'+selectorID+' .comments-list .comment.selected').each(function(){
					var commentId = this.id.replace(selectorID+'-comment-','').split('-');
					if (manageType == 3) persosToBanIDs[persos[comments[selectorID]['comments'][commentId[0]][commentId[1]]['author']]['id']] = true;
					else {
						objToSend['len']++;
						objToSend['comment'+objToSend['len']+'num'] = commentId[0];
						objToSend['comment'+objToSend['len']+'resp'] = commentId[1];
					}
				});
				if (manageType == 2) {
					var convVal = $('#'+selectorID+'-comments-convsl').val().split('-');
					objToSend['destinationpage'] = convVal[0];
					if (convVal[1] != '0') objToSend['conv'] = convVal[1];
				}
				if (manageType == 3) {
					var j = 0;
					for (i in persosToBanIDs) {
						j++;
						objToSend['for'+j] = i;
					}
					objToSend['len'] = j;
				}
				serverComments(7,objToSend);
			}
		});
	}
	serverComments(comments[selectorID]['isLong'] === undefined ? 5 : 1,getPreparedObjToSend(selectorID));
}

function addShowCommentsEventListeners() {
	$('#pg .comments .comments-aff-btn.beenadded').click(function(){showComments(this.parentNode.parentNode.id);});
	$('#pg .comments .comments-aff-btn.beenadded').removeClass('beenadded');
}

function prepareComments(selectorID,page,content,subContent,authorAccountID=0,showDirectly=false,isScrollable=true,isLong=false) {
	$('#'+selectorID).addClass('comments');
	if (isScrollable) $('#'+selectorID).addClass('comments-scrollable hide-scrollbar');
	comments[selectorID] = {
		'page' : page,
		'content' : content,
		'subContent' : subContent,
		'authorAccountID' : authorAccountID,
		'isMyContent' : false,
		'banisherName' : '',
		'firstCommentsFilling' : true,
		'bans' : [],
		'consideredNbBans' : 0,
		'comments' : {'$':{}},
		'commentsNb' : -1,
		'commentsIDs' : [],
		'hasNewComments' : false,
		'selectingComments' : false
	};
	if (authorAccountID != 0) {
		comments[selectorID]['isMyContent'] = (persos[authorAccountID]['group'] == myGroupID);
		comments[selectorID]['banisherName'] = getEscapedLineStr(persos[authorAccountID]['name-resp']);
	}
	if (isLong) {
		comments[selectorID]['isLong'] = true;
		longCommentsSelectorID = selectorID;
		longCommentsAdding = false;
	}
	if (imAdmin) comments[selectorID]['consideredNbConvs'] = 0;
	if (showDirectly) showComments(selectorID);
	else $('#'+selectorID).html('<div class="flx flx-jc"><button class="comments-aff-btn beenadded">Afficher les commentaires</button></div>');
}

function updateComments(selectorID,commentsNums,commentsFromOpt,newCommentsNb,persosChanged,addAtBottom) {
	var currentComments = comments[selectorID]['comments'];
	var newCommentsOrdered = commentsFromOpt.orderByKeyDesc('updated');
	var newCommentsOrder = [];
	var newComments = {};
	comments[selectorID]['hasNewComments'] = false;
	if (newCommentsOrdered.length > 0) {
		for (i in newCommentsOrdered) if (!newCommentsOrder.includes(newCommentsOrdered[i]['num'])) newCommentsOrder.push(newCommentsOrdered[i]['num']);
		if (!addAtBottom) comments[selectorID]['updated'] = newCommentsOrdered[0]['updated'];
		var commentList;
		for (i in newCommentsOrder) {
			newComments[newCommentsOrder[i]] = {};
			commentList = newCommentsOrdered.filter(comment => comment.num == newCommentsOrder[i]);
			for (j in commentList) newComments[newCommentsOrder[i]][commentList[j]['resp']] = commentList[j];
		}
		var newCommentsOrderNewOnly = newCommentsOrder.filter(num => currentComments[num] === undefined);
		var res = '';
		for (i in newCommentsOrderNewOnly) {
			currentComments[newCommentsOrderNewOnly[i]] = {};
			res += '<div id="'+selectorID+'-comment-list-'+newCommentsOrderNewOnly[i]+'"><div class="comment-list"></div><div id="'+selectorID+'-comment-'+newCommentsOrderNewOnly[i]+'" class="comment comment-send beenadded"><div class="comment-edit flx"><div><img class="pp" src="'+getPersoPPURLByID(myLastUsedPersoId)+'"/></div><div class="comment-texts"><div><select class="comment-author">'+persosOptions+'</select></div><div><textarea class="comment-textarea maxedwidth" rows="5" placeholder="Réponse ..."></textarea></div></div></div><div class="comment-edit comment-manage flx flx-je"><span class="comment-cancel">ANNULER</span><span class="comment-post">POSTER</span></div><div class="comment-show"><button class="comment-modif">Répondre</button></div></div><div class="comment-list-arrows-dv beenadded"><img class="comment-list-arrow-up" src="/src/icons/right-arrow-svgrepo-com.svg"><img class="comment-list-arrow-down" src="/src/icons/right-arrow-svgrepo-com.svg"></div></div></div>';
		}
		if (addAtBottom) $('#'+selectorID+' .comments-list').append(res);
		else $('#'+selectorID+' .comments-list').prepend(res);
		for (i in newCommentsOrderNewOnly) currentComments['$'][newCommentsOrderNewOnly[i]] = {'container':$('#'+selectorID+'-comment-list-'+newCommentsOrderNewOnly[i]),'list':$('#'+selectorID+'-comment-list-'+newCommentsOrderNewOnly[i]+' > div.comment-list')};
		var newCommentsOrderList, commentFromMe;
		for (i in newComments) {
			newCommentsOrderList = {};
			for (j in newComments[i]) if (currentComments[i][j] === undefined) newCommentsOrderList[j] = true;
			if (!$.isEmptyObject(newCommentsOrderList)) {
				res = '';
				for (j in newCommentsOrderList) {
					comments[selectorID]['hasNewComments'] = true;
					commentFromMe = (persos[newComments[i][j]['author']]['group'] == myGroupID || persos[newComments[i][j]['author']]['group'] == 0);
					res += '<div id="'+selectorID+'-comment-'+i+'-'+j+'" class="comment beenadded"><div class="comment-content flx"><div><img class="pp"/></div><div class="comment-texts"><div class="comment-show comment-acname customemoji-container"></div><div class="comment-show comment-text formated-text colored-text-container customemoji-container"></div><div class="comment-show comment-newiconlight"></div>';
					if (commentFromMe) res += '<div class="comment-edit"><select class="comment-author">'+persosOptions+'</select></div><div class="comment-edit"><textarea class="comment-textarea maxedwidth" rows="5" placeholder="Réponse ..."></textarea></div>';
					res += '</div></div><div class="comment-show comment-manage comment-manage-existing flx flx-je">';
					if (comments[selectorID]['isMyContent'] && newComments[i][j]['author'] != comments[selectorID]['authorAccountID']) res += '<span class="comment-ban">BANNIR</span>';
					if (commentFromMe) res += '<span class="comment-modif">MODIFIER</span>';
					res += '<span class="comment-resp">RÉPONDRE</span></div>';
					if (commentFromMe) res += '<div class="comment-edit comment-manage flx flx-je"><span class="comment-cancel">ANNULER</span><span class="comment-post">POSTER</span></div>';
					var bannedName = getEscapedLineStr(persos[newComments[i][j]['author']]['name-resp']);
					res += '<div class="comment-ban-message-dv"><div class="comment-ban-hideonanyway"><span class="comment-ban-message">Le commentaire de '+bannedName+' a été caché pour non respect des règles de la communauté</span><span class="comment-ban-by-author-message">'+bannedName+' a été banni par '+comments[selectorID]['banisherName']+'</span><span class="comment-ban-def-message">'+bannedName+' a été banni définitivement.</span></div><div><span class="comment-toggle-banned comment-ban-hideonanyway">Voir le commentaire</span><span class="comment-toggle-banned comment-ban-showonanyway">Cacher</span></div></div>';
					if (comments[selectorID]['isMyContent'] || imAdmin) res += '<div class="comment-select-dv fillrelativeparent"></div>';
					res += '</div>';
				}
				currentComments['$'][i]['list'].append(res);
				for (j in newCommentsOrderList) {
					currentComments[i][j] = {'author':'','comment':'','hidden':0};
					currentComments['$'][i][j] = {
						'container' : $('#'+selectorID+'-comment-'+i+'-'+j),
						'pp' : $('#'+selectorID+'-comment-'+i+'-'+j+' .pp'),
						'acname' : $('#'+selectorID+'-comment-'+i+'-'+j+' .comment-acname'),
						'text' : $('#'+selectorID+'-comment-'+i+'-'+j+' .comment-text'),
						'acsl' : $('#'+selectorID+'-comment-'+i+'-'+j+' .comment-author'),
						'textedit' : $('#'+selectorID+'-comment-'+i+'-'+j+' .comment-textarea')
					};
					comments[selectorID]['commentsIDs'].push(i+'-'+j);
				}
			}
		}
	}
	else newComments = $.extend(true,{},currentComments);
	for (i in newComments) {
		for (j in newComments[i]) {
			if (newComments[i][j]['hidden'] == 0 && comments[selectorID]['bans'].includes(newComments[i][j]['author'])) newComments[i][j]['hidden'] = 2;
			if (bansDef.includes(newComments[i][j]['author'])) newComments[i][j]['hidden'] = 3;
			if (newComments[i][j]['comment'] != currentComments[i][j]['comment'] || newComments[i][j]['author'] != currentComments[i][j]['author'] || newComments[i][j]['hidden'] != currentComments[i][j]['hidden']) {
				if (newComments[i][j]['author'] != currentComments[i][j]['author']) {
					currentComments['$'][i][j]['pp'].attr('src',addPathPartIfNotURL('/medias/pps/',persos[newComments[i][j]['author']]['pp']));
					if (newComments[i][j]['author'] == comments[selectorID]['authorAccountID']) currentComments['$'][i][j]['acname'].html('<span class="comment-author-name" title="Auteur du contenu">'+replaceBulk(getEscapedLineStr(persos[newComments[i][j]['author']]['name']),searchArrayOnName,replaceArrayOnName)+' &checkmark;</span>');
					else currentComments['$'][i][j]['acname'].html(getFormatedPersoNameByID(newComments[i][j]['author']));
					currentComments['$'][i][j]['acsl'].val(newComments[i][j]['author']);
				}
				if (newComments[i][j]['comment'] != currentComments[i][j]['comment']) {
					if (newComments[i][j]['comment'].replace(regexpUnicodeModified,'').replace(regexCustomEmojis,'').replace(/ /g,'') == '') currentComments['$'][i][j]['text'].addClass('comment-onlyemojis');
					else currentComments['$'][i][j]['text'].removeClass('comment-onlyemojis');
					currentComments['$'][i][j]['text'].html(getReplacedHashtagsWithHTMLTags(getReplacedURIsWithHTMLTags(replaceBulk(getEscapedLinesStr(newComments[i][j]['comment']),searchArrayOnText,replaceArrayOnText))));
					currentComments['$'][i][j]['textedit'].html(newComments[i][j]['comment']);
				}
				if (newComments[i][j]['hidden'] != currentComments[i][j]['hidden']) {
					if (newComments[i][j]['hidden'] == 0) currentComments['$'][i][j]['container'].removeClass('comment-banned comment-banned-by-author comment-banned-def');
					else {
						currentComments['$'][i][j]['container'].addClass('comment-banned');
						if (newComments[i][j]['hidden'] == 2) {
							currentComments['$'][i][j]['container'].addClass('comment-banned-by-author');
							currentComments['$'][i][j]['container'].removeClass('comment-banned-def');
						}
						else if (newComments[i][j]['hidden'] == 3) {
							currentComments['$'][i][j]['container'].addClass('comment-banned-def');
							currentComments['$'][i][j]['container'].removeClass('comment-banned-by-author');
						}
						else currentComments['$'][i][j]['container'].removeClass('comment-banned-by-author comment-banned-def');
					}
				}
				currentComments[i][j] = newComments[i][j];
			}
		}
	}
	if (newCommentsNb != comments[selectorID]['commentsNb']) {
		$('#'+selectorID+' .comments-nb').html(newCommentsNb);
		if (newCommentsNb == 0) $('#'+selectorID).addClass('comments-empty');
		else $('#'+selectorID).removeClass('comments-empty');
		comments[selectorID]['commentsNb'] = newCommentsNb;
	}
	if (persosChanged) {
		$('#pg .comments .comment:not(.beenadded) .comment-author').each(function(){
			var val = $(this).val();
			$(this).html(persosOptions);
			$(this).val(val);
		});
	}
	if (postedNewComment) {
		$('#pg .comments .comment-send').each(function(){
			var $authorSelect = $(this).find('.comment-author');
			var val = $authorSelect.val();
			if (!$(this).hasClass('comment-inedit')) val = lastUsedPersoId;
			$authorSelect.html(persosOptions);
			$authorSelect.val(val);
			$(this).find('.pp').attr('src',getPersoPPURLByID(val));
		});
	}
	if (!addAtBottom && !comments[selectorID]['firstCommentsFilling']) {
		$('#'+selectorID+' .comments-list .comment-list .comment-new').removeClass('comment-new');
		$('#'+selectorID+' .comments-list .comment-list .beenadded').addClass('comment-new');
		commentsNums = commentsNums.map(commentID => commentID['num']+'-'+commentID['resp']);
		var commentsIDsToDelete = comments[selectorID]['commentsIDs'].filter(commentID => !commentsNums.includes(commentID)).reverse();
		if (commentsIDsToDelete.length > 0) {
			var commentId;
			for (i in commentsIDsToDelete) {
				commentId = commentsIDsToDelete[i].split('-');
				currentComments['$'][commentId[0]][commentId[1]]['container'].remove();
				delete currentComments[commentId[0]][commentId[1]];
				delete currentComments['$'][commentId[0]][commentId[1]];
			}
			for (i in currentComments) {
				if (i == '$') break;
				if ($.isEmptyObject(currentComments[i])) {
					delete currentComments[i];
					currentComments['$'][i]['container'].remove();
					delete currentComments['$'][i];
				}
			}
			comments[selectorID]['commentsIDs'] = commentsNums;
		}
	}
	addCommentsEventListeners();
}

function addCommentsToLongComments() {
	longCommentsAdding = true;
	serverComments(2,getPreparedObjToSend(longCommentsSelectorID));
}

function smoothlyScrollTo($target,onEnd,offset=0) {
	$('html, body').animate({'scrollTop':$target.offset().top-$(window).height()/2+offset},750,'swing',onEnd);
}

function addCommentsEventListeners() {
	$('#pg .comments-list .comment.beenadded .comment-texts .comment-show').click(function(){$(this.parentNode.parentNode.parentNode).toggleClass('comment-showing-manage');});
	$('#pg .comments .comment.beenadded :is(.comment-modif, .comment-cancel)').click(function(){$(this.parentNode.parentNode).toggleClass('comment-inedit');});
	$('#pg .comments-list .comment.beenadded .comment-resp').click(function(){
		var selectorID = this.closest('.comments').id;
		var commentNode = this.parentNode.parentNode;
		$(commentNode).removeClass('comment-showing-manage');
		var commentId = commentNode.id.replace(selectorID+'-comment-','').split('-');
		var commentTextareaTarget = $('#'+selectorID+'-comment-'+commentId[0]+' .comment-textarea');
		var respondCommentListSelector = '#'+selectorID+'-comment-'+commentId[0];
		var persoToRespond = persos[comments[selectorID]['comments'][commentId[0]][commentId[1]]['author']];
		commentTextareaTarget.val('@'+persoToRespond['name-resp']+' ');
		if (persoToRespond['id'] == myLastUsedPersoId) {
			$(respondCommentListSelector+' .comment-author').val(ownLastUsedPersosIndexes[1]);
			$(respondCommentListSelector+' .pp').attr('src',addPathPartIfNotURL('/medias/pps/',persos[ownLastUsedPersosIndexes[1]]['pp']));
		}
		$(respondCommentListSelector).addClass('comment-inedit');
		smoothlyScrollTo(commentTextareaTarget,function(){
			commentTextareaTarget[0].setSelectionRange(10000,10000);
			commentTextareaTarget.focus();
		});
	});
	$('#pg .comments .comment.beenadded .comment-post').click(function(){
		var selectorID = this.closest('.comments').id;
		var commentNode = this.parentNode.parentNode;
		var commentId = commentNode.id.replace(selectorID+'-comment-','').split('-');
		lastUsedPersoId = $(commentNode).find(' .comment-author').val();
		lastUsedCommentSelector = $(commentNode);
		var objToSend = getPreparedObjToSend(selectorID);
		objToSend['author'] = lastUsedPersoId;
		objToSend['comment'] = $(commentNode).find('.comment-textarea').val();
		if (commentId[0] != 0) objToSend['num'] = commentId[0];
		postedNewComment = (commentId[1] === undefined);
		if (!postedNewComment) objToSend['resp'] = commentId[1];
		serverComments(4,objToSend);
	});
	$('#pg .comments .comment.beenadded .comment-author').change(function(){$(this.parentNode.parentNode.parentNode).find('.pp').attr('src',getPersoPPURLByID($(this).val()));});
	$('#pg .comments .comment.beenadded .comment-ban').click(function(){
		var selectorID = this.closest('.comments').id;
		var commentNode = this.parentNode.parentNode;
		var commentId = commentNode.id.replace(selectorID+'-comment-','').split('-');
		var persoToBanID = comments[selectorID]['comments'][commentId[0]][commentId[1]]['author'];
		if (confirm('Souhaitez vous bannir '+persos[persoToBanID]['name-sl']+' ?')) {
			var objToSend = getPreparedObjToSend(selectorID);
			objToSend['by'] = comments[selectorID]['authorAccountID'];
			objToSend['for'] = persoToBanID;
			serverComments(6,objToSend);
			$(commentNode).removeClass('comment-showing-manage comment-inedit');
		}
	});
	$('#pg .comments .comment.beenadded .comment-select-dv').click(function(){
		var selectorID = this.closest('.comments').id;
		var commentNode = this.parentNode;
		var commentId = commentNode.id.replace(selectorID+'-comment-','').split('-');
		var $comment = $(commentNode);
		var $commentSelection = $comment;
		var commentIsSelected = $comment.hasClass('selected');
		var commentListLen = 0;
		for (i in comments[selectorID]['comments'][commentId[0]]) commentListLen++;
		if (commentListLen > 1 && $(this.parentNode.previousSibling).length == 0 && confirm((commentIsSelected ? 'Dés' : 'S')+'électionner tous les commentaires de ce fil de commentaires ?')) $commentSelection = $(this.parentNode.parentNode).find('.comment');
		if (commentIsSelected) {
			$commentSelection.removeClass('selected');
			$commentSelection.filter('.comment-banned').removeClass('comment-banned-showanyway');
		}
		else {
			$commentSelection.addClass('selected');
			$commentSelection.filter('.comment-banned').addClass('comment-banned-showanyway');
		}
	});
	$('#pg .comments .comment.beenadded .comment-toggle-banned').click(function(){$(this.parentNode.parentNode.parentNode).toggleClass('comment-banned-showanyway');});
	$('#pg .comments .comment-list-arrows-dv.beenadded .comment-list-arrow-up').click(function(){smoothlyScrollTo($(this.parentNode.parentNode.previousSibling).find('.comment-list .comment:first'));});
	$('#pg .comments .comment-list-arrows-dv.beenadded .comment-list-arrow-down').click(function(){
		var $targetComment = $(this.parentNode.parentNode.nextSibling).find('.comment-list .comment:first');
		if ($targetComment.length == 0) $targetComment = $(this.parentNode.parentNode).find('.comment-send');
		smoothlyScrollTo($targetComment);
	});
	$('#pg .comments .comment.beenadded, #pg .comments .comment-list-arrows-dv').removeClass('beenadded');
}

serverComments = function(type,sendata={}) {
	var URI = '';
	for (i in sendata) URI += '&'+encodeURIComponent(i)+'='+encodeURIComponent(sendata[i]);
	// console.log('Try to contact server with type :',type);
	// if (URI != '') console.log('Data sent to the server :',URI);
	$.post({
		url : '/ajax/comments/',
		data : 'type='+type+URI,
		success : function(opt) {
			// console.log('Response from server :',opt);
			if (opt[0] == 0) {
				customEmojisSearchArray = getTextCodesFromFilesList(opt[1][0][0]).sort().reverse();
				customEmojisReplaceArray = opt[1][0][0].map(emoji => '<span class="customemoji"><img src="/medias/comments/custom-emojis/'+emoji+'"/></span>').sort().reverse();
				soundsForCommentsSearchArray = getTextCodesFromFilesList(opt[1][0][1]).sort().reverse();
				videosForCommentsSearchArray = getTextCodesFromFilesList(opt[1][0][2]).sort().reverse();
				soundsForCommentsReplaceArray = opt[1][0][1].map(sound => '<div><audio src="/medias/comments/sounds/'+sound+'" controls></audio></div>').sort().reverse();
				videosForCommentsReplaceArray = opt[1][0][2].map(video => '<div><video src="/medias/comments/videos/'+video+'" controls></video></div>').sort().reverse();
				myGroupID = opt[1][1];
				imAdmin = opt[1][2];
				setPersos(opt[1][3]);
				setPersosOptions();
				server(0);
			}
			if (['1','2','4','5','6','7'].includes(opt[0])) var selectorID = opt[1][0];
			if (opt[0] == 1) {
				if (opt[1][1].length > 0) {
					comments[selectorID]['notLoadedCommentsNums'] = opt[1][1].map(num => num['num']);
					$('#'+selectorID).append('<div id="comments-long-observer-target"></div>');
					longCommentsObserver = new IntersectionObserver(function(){if (!longCommentsAdding) addCommentsToLongComments();},{rootMargin:bottomPageOffset+'px',threshold:1.0});
					longCommentsObserver.observe($('#comments-long-observer-target')[0]);
				}
				else serverComments(5,getPreparedObjToSend(selectorID));
			}
			if (opt[0] == 2) {
				if (opt[1][1] !== undefined) {
					updateComments(selectorID,[],opt[1][1],opt[1][2],false,true);
					var newNotLoadedCommentNum = opt[1][1][0]['num'];
					var sliceNb = 0;
					for (i in comments[selectorID]['notLoadedCommentsNums']) {
						sliceNb++;
						if (comments[selectorID]['notLoadedCommentsNums'][i] == newNotLoadedCommentNum) break;
					}
					comments[selectorID]['notLoadedCommentsNums'] = comments[selectorID]['notLoadedCommentsNums'].slice(sliceNb);
					if (comments[selectorID]['notLoadedCommentsNums'].length == 0) {
						delete comments[selectorID]['notLoadedCommentsNums'];
						longCommentsObserver.unobserve($('#comments-long-observer-target')[0]);
					}
				}
				setTimeout(function(){
					longCommentsAdding = false;
					if (window.innerHeight + Math.ceil(window.pageYOffset) + bottomPageOffset + 100 >= document.body.offsetHeight && comments[selectorID]['notLoadedCommentsNums'] !== undefined) addCommentsToLongComments();
				},100);
			}
			if (opt[0] == 4) {
				lastUsedCommentSelector.removeClass('comment-inedit comment-showing-manage');
				if (postedNewComment) {
					persos[lastUsedPersoId]['last-used'] = moment().format('YYYY-MM-DD HH:mm:ss');
					setPersosOptions();
					lastUsedCommentSelector.find('.comment-textarea').val('');
				}
			}
			if (opt[0] == 7) $('#'+opt[1][0]+' .comments-list .comment.selected').removeClass('selected');
			if (['4','5','6','7'].includes(opt[0])) {
				var persosChanged = (opt[1][4] !== false);
				if (persosChanged) {
					setPersos(opt[1][4]);
					setPersosOptions();
				}
				if (opt[1][5] !== false) {
					bans = opt[1][5];
					nbBans = bans.length;
				}
				if (imAdmin && opt[1][6] !== false) {
					convs = opt[1][6];
					nbConvs = convs.length;
				}
				if (comments[selectorID]['consideredNbBans'] < nbBans && comments[selectorID]['page'] != 6) {
					comments[selectorID]['bans'] = bans.filter(ban => ban['banisher'] == comments[selectorID]['authorAccountID']).map(ban => ban['banned']);
					setBansDef();
					comments[selectorID]['consideredNbBans'] = nbBans;
				}
				if (comments[selectorID]['consideredNbConvs'] != nbConvs) {
					var $convsSl = $('#'+selectorID+'-comments-convsl');
					var convVal = $convsSl.val();
					if (convVal === null) convVal = '5-0';
					var commentText;
					var optionsHTML = '<option value="5-0">-Nouvelle conversation-</option>';
					for (i in convs) {
						commentText = convs[i]['comment'];
						if (commentText.length == 30) commentText += '...';
						optionsHTML += '<option value="'+convs[i]['page']+'-'+convs[i]['num']+'">'+convs[i]['page']+' - '+convs[i]['num']+' - '+persos[convs[i]['author']]['name-sl']+' : '+commentText+'</option>';
					}
					$convsSl.html(optionsHTML);
					$convsSl.val(convVal);
				}
				updateComments(selectorID,opt[1][1],opt[1][2],opt[1][3],persosChanged,false);
				postedNewComment = false;
				if (opt[0] == 5 && !comments[selectorID]['firstCommentsFilling']) {
					spinButton(comments[selectorID]['$updateButton']);
					if (comments[selectorID]['hasNewComments']) $('#'+selectorID+' .comments-list .comment-list .comment-new').first()[0].scrollIntoView({block:'center',behavior:'smooth'});
				}
			}
			if (['2','4','5','6','7'].includes(opt[0]) && comments[selectorID]['firstCommentsFilling']) comments[selectorID]['firstCommentsFilling'] = false;
		},
		dataType : 'json'
	});
}

var persos = {};
var nbPersos;
var bans;
var nbBans = 0;
var bansDef = [];
var convs;
var nbConvs = 0;

var myGroupID;
var persosOptions;
var ownLastUsedPersosIndexes;
var lastUsedPersoId;
var myLastUsedPersoId;
var imAdmin;
var longCommentsSelectorID;
var longCommentsObserver;
var longCommentsAdding;
var bottomPageOffset = 100;

var searchArrayOnName;
var replaceArrayOnName;
var searchArrayOnText;
var replaceArrayOnText;
var customEmojisSearchArray;
var customEmojisReplaceArray;
var soundsForCommentsSearchArray;
var soundsForCommentsReplaceArray;
var videosForCommentsSearchArray;
var videosForCommentsReplaceArray;

var comments = {};
var postedNewComment = false;
var lastUsedCommentSelector;

var regexCustomEmojis = /:[^: ]*:/g;
// Thanks to Rimas Kudelis
// https://stackoverflow.com/questions/43242440/javascript-regular-expression-for-unicode-emoji/45138005
var regexpUnicodeModified = /\p{RI}\p{RI}|\p{Emoji}(\p{EMod}+|\u{FE0F}\u{20E3}?|[\u{E0020}-\u{E007E}]+\u{E007F})?(\u{200D}\p{Emoji}(\p{EMod}+|\u{FE0F}\u{20E3}?|[\u{E0020}-\u{E007E}]+\u{E007F})?)+|\p{EPres}(\p{EMod}+|\u{FE0F}\u{20E3}?|[\u{E0020}-\u{E007E}]+\u{E007F})?|\p{Emoji}(\p{EMod}+|\u{FE0F}\u{20E3}?|[\u{E0020}-\u{E007E}]+\u{E007F})/gu;
