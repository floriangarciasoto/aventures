<?php
session_start();
if (!isset($_SESSION['user'])) {
	header('Location: /?page='.urlencode($_SERVER['REQUEST_URI']));
	exit;
}
function processPOSTRequest($type,&$responseData) {
	if (in_array($type,[1,2,3,4,5,6,7])) {
		if (!isset($_POST['map']) || $_POST['map'] != intval($_POST['map']).'') return -1;
	}
	if ($type == 1) {
		if (!isset($_POST['name']) || $_POST['name'] == '' || !isset($_POST['background']) || $_POST['background'] == '') return -1;
	}
	if ($type == 4) {
		if (!isset($_POST['pp']) || $_POST['pp'] == '') return -1;
	}
	if ($type == 4 || $type == 5) {
		if (!isset($_POST['left']) || $_POST['left'] != floatval($_POST['left']).'' || !isset($_POST['top']) || $_POST['top'] != floatval($_POST['top']).'') return -1;
	}
	if ($type == 5) {
		if (!isset($_POST['icon']) || $_POST['icon'] != intval($_POST['icon']).'') return -1;
	}
	if ($type == 6 || $type == 7) {
		if (!isset($_POST['iconsnumber']) || $_POST['iconsnumber'] != intval($_POST['iconsnumber']).'') return -1;
		$iconsNumber = intval($_POST['iconsnumber']);
		for ($i = 0; $i < $iconsNumber; $i++) if (!isset($_POST['icon'.$i]) || $_POST['icon'.$i] != intval($_POST['icon'.$i]).'') return -1;
	}
	if ($type == 7) {
		if (!isset($_POST['map-dst']) || $_POST['map-dst'] != intval($_POST['map-dst']).'') return -1;
	}
	require $_SERVER['DOCUMENT_ROOT'].'/../lib/db.php';
	$db = new DB();
	if ($type == 0) {
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/os.php';
		if ($_SESSION['user']['canAccessScenariosOnlyPages']) {
			$siteSection = 'scenarios';
			$ppsFolders = ['scenarios','force','jdr'];
		}
		else {
			$siteSection = 'jdr';
			$ppsFolders = ['jdr','force'];
		}
		$responseData = array($siteSection,$db->get("SELECT * FROM `jdr-maps` WHERE `scenarios`=?",[$_SESSION['user']['scenarios']]),getFilesListFromFolder('/medias/'.$siteSection.'/maps/'),getpps($ppsFolders));
	}
	if ($type == 1) {
		require $_SERVER['DOCUMENT_ROOT'].'/../lib/misc.php';
		if ($_POST['background'] == '0' && (!isset($_POST['backgroundurl']) || $_POST['backgroundurl'] == '' || !isImgURL($_POST['backgroundurl']))) return -1;
		if ($_POST['background'] != '0' && !file_exists($_SERVER['DOCUMENT_ROOT'].'/medias/jdr/maps/'.$_POST['background'])) return -1;
		$background = $_POST['background'];
		if ($_POST['background'] == '0') $background = $_POST['backgroundurl'];
		if ($_POST['map'] == 0) $db->req("INSERT INTO `jdr-maps` (`scenarios`, `name`, `background`) VALUES (?, ?, ?)",[$_SESSION['user']['scenarios'],$_POST['name'],$background]);
		else $db->req("UPDATE `jdr-maps` SET `name`=?, `background`=? WHERE `id`=?",[$_POST['name'],$background,$_POST['map']]);
	}
	if ($type == 2) {
		$responseData = $db->get("SELECT * FROM `jdr-maps-undermaps` WHERE `map`=?",[$_POST['map']]);
	}
	if ($type == 4) {
		$db->req("INSERT INTO `jdr-maps-icons`(`map`, `pp`, `left`, `top`) VALUES (?, ?, ?, ?)",[$_POST['map'],$_POST['pp'],$_POST['left'],$_POST['top']]);
	}
	if ($type == 5) {
		$db->req("UPDATE `jdr-maps-icons` SET `left`=?, `top`=? WHERE `id`=?",[$_POST['left'],$_POST['top'],$_POST['icon']]);
	}
	if ($type == 6 || $type == 7) {
		$iconsSQLList = '';
		for ($i = 0; $i < $iconsNumber; $i++) $iconsSQLList .= $_POST['icon'.$i].', ';
		$iconsSQLList = preg_replace('/, $/','',$iconsSQLList);
	}
	if ($type == 6) {
		$db->reqDirect("DELETE FROM `jdr-maps-icons` WHERE `id` IN (".$iconsSQLList.")");
	}
	if ($type == 7) {
		$db->req("UPDATE `jdr-maps-icons` SET `map`=?, `left`=50, `top`=50 WHERE `id` IN (".$iconsSQLList.")",[$_POST['map-dst']]);
	}
	if (in_array($type,[3,4,5,6,7])) {
		$responseData = $db->get("SELECT * FROM `jdr-maps-icons` WHERE `map`=?",[$_POST['map']]);
	}
	$db->close();
	return 0;
}
if (isset($_POST['type']) && in_array($_POST['type'],[0,1,2,3,4,5,6,7])) {
	$responseData = '';
	if (processPOSTRequest($_POST['type'],$responseData) == 0) echo json_encode([$_POST['type'],$responseData]);
	exit;
} ?>
<!DOCTYPE html>
<html>
<head>
	<title>Cartes - aventures.ddns.net</title>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../include-js.php'; ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.imagesloaded/5.0.0/imagesloaded.pkgd.min.js" integrity="sha512-kfs3Dt9u9YcOiIt4rNcPUzdyNNO9sVGQPiZsub7ywg6lRW5KuK1m145ImrFHe3LMWXHndoKo2YRXWy8rnOcSKg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js" integrity="sha256-eTyxS0rkjpLEo16uXTS0uVCS4815lc40K2iVpWDvdSY=" crossorigin="anonymous"></script>
	<script src="/src/lib/modified/jquery.ui.touch-punch.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js" integrity="sha512-UXumZrZNiOwnTcZSHLOfcTs0aos2MzBWHXOHOuB0J/R44QB0dwY5JgfbvljXcklVf65Gc4El6RjZ+lnwd2az2g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script src="https://cdn.jsdelivr.net/npm/jquery-hammerjs@2.0.0/jquery.hammer.min.js" integrity="sha512-ZMtoQf/73QPoGjDKJzpTUR0kspqZq/q2jvtfq27ur8DSOzqx4jDakMwA5pxkSoyG/GnjVl5CS+j0WqLAPffIQQ==" crossorigin="anonymous"></script>
	<script type="text/javascript">
	$(document).ready(function(){
		function isValidAttrAttribution(attrAttribution) {
			return (attrAttribution !== undefined && attrAttribution !== false);
		}

		function applyMapPosition() {
			// TODO: Coeffs same force
			// console.log(map['dimensions'],map['position']);
			if (map['position']['left'] > map['position']['leftLimit']) map['position']['left'] = map['position']['leftLimit'];
			if (map['position']['top'] > map['position']['topLimit']) map['position']['top'] = map['position']['topLimit'];
			if (map['position']['left']+map['dimensions']['width'] < map['position']['leftLimit']+map['dimensions']['initialWidth']) map['position']['left'] += map['position']['leftLimit']+map['dimensions']['initialWidth']-(map['position']['left']+map['dimensions']['width']);
			if (map['position']['top']+map['dimensions']['height'] < map['position']['topLimit']+map['dimensions']['initialHeight']) map['position']['top'] += map['position']['topLimit']+map['dimensions']['initialHeight']-(map['position']['top']+map['dimensions']['height']);
			if (map['position']['left'] < 0 && map['dimensions']['displayWidth']-map['position']['left']-map['dimensions']['width'] > 0) map['position']['left'] = map['dimensions']['displayWidth']-map['dimensions']['width'];
			if (map['position']['top'] < 0 && map['dimensions']['displayHeight']-map['position']['top']-map['dimensions']['height'] > 0) map['position']['top'] = map['dimensions']['displayHeight']-map['dimensions']['height'];
			if (map['position']['left'] > 0) {
				if (map['dimensions']['width'] > map['dimensions']['displayWidth']) map['position']['left'] = 0;
				else map['position']['left'] = (map['dimensions']['displayWidth']-map['dimensions']['width'])/2;
			}
			if (map['position']['top'] > 0) {
				if (map['dimensions']['height'] > map['dimensions']['displayHeight']) map['position']['top'] = 0;
				else map['position']['top'] = (map['dimensions']['displayHeight']-map['dimensions']['height'])/2;
			}
			if (displayRatioIsLandscape && !displayRatioWasLandscape) $('#map-elems-dv').css('width','unset');
			if (!displayRatioIsLandscape && displayRatioWasLandscape) $('#map-elems-dv').css('height','unset');
			var cssObj = {
				'left' : map['position']['left'],
				'top' : map['position']['top']
			}
			cssObj[map['position']['scaleChanger']] = map['position']['scale']+'%';
			$('#map-elems-dv').css(cssObj);
		}

		function zoomOnMap(zoomingIn,xPos,yPos) {
			if (zoomingIn) map['position']['scale'] *= 1.5;
			else {
				if (map['position']['scale'] == 100) return;
				map['position']['scale'] /= 1.5;
				if (map['position']['scale'] < 100) map['position']['scale'] = 100;
			}
			var leftCoeff = 2-(map['dimensions']['width']-map['dimensions']['displayWidth']/2+map['position']['left'])/map['dimensions']['width']*2;
			var topCoeff = 2-(map['dimensions']['height']-map['dimensions']['displayHeight']/2+map['position']['top'])/map['dimensions']['height']*2;
			map['dimensions']['ancWidth'] = map['dimensions']['width'];
			map['dimensions']['ancHeight'] = map['dimensions']['height'];
			map['dimensions']['width'] = map['dimensions']['initialWidth']*map['position']['scale']/100;
			map['dimensions']['height'] = map['dimensions']['initialHeight']*map['position']['scale']/100;
			var leftOffset = map['dimensions']['displayWidth']/2*(1-(xPos-$('#map-display').offset().left)/map['dimensions']['displayWidth']*2);
			var topOffset = map['dimensions']['displayHeight']/2*(1-(yPos-$('#map-display').offset().top)/map['dimensions']['displayHeight']*2);
			if (zoomingIn) {
				leftOffset *= map['dimensions']['ancWidth']/map['dimensions']['width'];
				topOffset *= map['dimensions']['ancHeight']/map['dimensions']['height'];
			}
			else {
				leftOffset *= -map['dimensions']['width']/map['dimensions']['ancWidth'];
				topOffset *= -map['dimensions']['height']/map['dimensions']['ancHeight'];
			}
			// TODO: Coeffs same force
			// console.log(topOffset,leftOffset);
			map['position']['left'] += ((map['dimensions']['ancWidth']-map['dimensions']['width'])/2)*leftCoeff+leftOffset;
			map['position']['top'] += ((map['dimensions']['ancHeight']-map['dimensions']['height'])/2)*topCoeff+topOffset;
			applyMapPosition();
		}

		function adjustIconsSlHeight() {
			if (!$('#map-icons-sl-dv').hasClass('hidden')) {
				var height = parseFloat(($('#map-display').height()-$('#map-panel-modif .map-panel-content > div:last').height()-30)/2);
				if (!$('#map-display').is(':fullscreen')) height -= 50;
				height *= 2;
				height -= 70;
				if (height < 110) height = 110;
				$('#map-icons-sl').css('height',height+'px');
			}
		}

		function adjustElemsDiv(mapOpening=false) {
			map['dimensions'] = {
				'displayWidth' : $('#map-display').width(),
				'displayHeight' : $('#map-display').height()
			};
			displayRatioIsLandscape = map['dimensions']['displayWidth']/map['dimensions']['displayHeight'] >= map['aspect-ratio'];
			if (mapOpening) displayRatioWasLandscape = !displayRatioIsLandscape;
			if (displayRatioIsLandscape) {
				map['dimensions']['initialWidth'] = map['dimensions']['displayHeight']*map['aspect-ratio'];
				map['dimensions']['initialHeight'] = map['dimensions']['displayHeight'];
				map['position']['leftLimit'] = (map['dimensions']['displayWidth']-map['dimensions']['initialWidth'])/2;
				map['position']['topLimit'] = 0;
				if (!displayRatioWasLandscape) {
					map['position']['scaleChanger'] = 'height';
					$('#map-elems-dv').removeClass('width100');
				}
			}
			else {
				map['dimensions']['initialWidth'] = map['dimensions']['displayWidth'];
				map['dimensions']['initialHeight'] = map['dimensions']['displayWidth']/map['aspect-ratio'];
				map['position']['leftLimit'] = 0;
				map['position']['topLimit'] = (map['dimensions']['displayHeight']-map['dimensions']['initialHeight'])/2;
				if (displayRatioWasLandscape) {
					map['position']['scaleChanger'] = 'width';
					$('#map-elems-dv').addClass('width100');
				}
			}
			if (mapOpening) {
				map['dimensions']['width'] = map['dimensions']['initialWidth'];
				map['dimensions']['height'] = map['dimensions']['initialHeight'];
				map['position']['left'] = map['position']['leftLimit'];
				map['position']['top'] = map['position']['topLimit'];
			}
			else {
				map['dimensions']['width'] = map['dimensions']['initialWidth']*map['position']['scale']/100;
				map['dimensions']['height'] = map['dimensions']['initialHeight']*map['position']['scale']/100;
				// TODO: Recenter map after display change
				// if (displayRatioIsLandscape !== displayRatioWasLandscape) {

				// }
			}
			applyMapPosition();
			displayRatioWasLandscape = displayRatioIsLandscape;
			adjustIconsSlHeight();
		}

		function resizeMapPanelOverlay(id) {
			var side = 'left';
			if ($('#'+id).hasClass('map-panel-right')) side = 'right';
			$('#'+id).css(side,'-'+$('#'+id+' .map-panel-content').width()+'px');
		}

		function toggleMapPanelOverlay(id,closeAnyway=false) {
			$('#'+id).addClass('map-panel-animated');
			if (isValidAttrAttribution($('#'+id).attr('style')) && !closeAnyway) $('#'+id+', #'+id+' .map-panel-content').removeAttr('style');
			else {
				resizeMapPanelOverlay(id);
				setTimeout(function(){$('#'+id+' .map-panel-content').css('height',0);},500);
			}
			setTimeout(function(){$('#'+id).removeClass('map-panel-animated');},500);
		}

		function moveIcons(iconsToBePlaced) {
			for (i in iconsToBePlaced) $('#map-icon-'+i).css({'left':map['currentIcons'][i]['left']+'%','top':map['currentIcons'][i]['top']+'%'});
		}

		function commandDeletion(id,order) {
			setTimeout(function(){$('#map-icon-'+id).addClass('invisible');},order*250);
			setTimeout(function(){$('#map-icon-'+id).remove();},(order+1)*250);
		}

		function getBackgroundURL(bkg) {
			if (!isImgURL(bkg)) return '/medias/'+siteSection+'/maps/'+bkg;
			return bkg;
		}

		function getIconsURI(obj) {
			var n = -1;
			for (i in map['selectedIcons']) {
				n++;
				obj['icon'+n] = i;
			}
			obj['iconsnumber'] = n+1;
			return obj;
		}

		function changeMap(id) {
			$('#maps-thumbnails > div').removeClass('selected');
			if (id == 0) {
				$('#map-no-display, #map-loading-message').removeClass('hidden');
				$('#map-elems-dv').removeAttr('style');
			}
			else {
				$('#map-thumbnail-'+id).addClass('selected');
				var background = getBackgroundURL(maps[id]['background']);
				// Thanks to Kristoffer
				// https://stackoverflow.com/questions/5438715/getting-auto-size-of-img-before-adding-it-to-dom-using-jquery
				var img = new Image();
				$(img).bind('load',function(){
					$('#map-elems-dv').css('aspect-ratio',img.width+' / '+img.height);
					map = {
						'id' : id,
						'aspect-ratio' : img.width/img.height,
						'dimensions' : {},
						'position' : {
							'scaleChanger' : 'height',
							'scale' : 100,
							'left' : 0,
							'top' : 0
						},
						'currentIcons' : {},
						'selectedIcons' : {}
					};
					$('#map-elems-dv').css({
						'background' : 'url("'+background+'")',
						'background-size' : 'cover'
					});
					$('#map-elems-dv').imagesLoaded(function(){
						adjustElemsDiv(true);
						$('#map-loading-message').addClass('hidden');
						firstMovement = true;
						server(2,{'map':map['id']});
					});
				});
				img.src = background;
				$('#map-elems > img').remove();
				if (firstMapOpening) {
					// Thanks to Louis Ameline
					// https://stackoverflow.com/questions/8189840/get-mouse-wheel-events-in-jquery
					$('#map-display')
						.on('wheel',function(e){
							if (e.originalEvent.deltaY !== 0) {
								e.preventDefault();
								zoomOnMap(e.originalEvent.deltaY < 0,e.pageX,e.pageY);
							}
						})
						.hammer({recognizers:[[Hammer.Pinch,{enable:true}]]}).on('pinchend',function(e){if (e.gesture.scale != 1) zoomOnMap(e.gesture.scale > 1,e.gesture.center.x,e.gesture.srcEvent.pageY);});
					$('#map-elems-dv').draggable({
						stop : function(){
							map['position']['left'] = $(this).position().left;
							map['position']['top'] = $(this).position().top;
							applyMapPosition();
						}
					});
					$(window).resize(function(){adjustElemsDiv();});
					$('#map-elems').droppable({
						scope : 'new-icon',
						drop : function(ev,ui) {
							var l = ((ui.offset.left-$(this).offset().left)/$(this).width()*100).round(4);
							var t = ((ui.offset.top-$(this).offset().top)/$(this).height()*100).round(4);
							server(4,{'map':map['id'],'pp':pps[parseInt(ui.draggable.attr('ppn'))],'left':l,'top':t});
						}
					});
					$('#map-unselect-dv').hammer().on('tap',function(){
						$('#map-elems > img.blink').removeClass('blink');
						map['selectedIcons'] = {};
					})
					$('#map-display div.map-panel-content, #map-icons-sl').on('wheel',function(e){e.stopPropagation();});
					$('#map-display .map-panel').on('mouseenter',function(){$(this).addClass('map-panel-no-hide');});
					$('#map-display .map-button-display-panel').click(function(){toggleMapPanelOverlay($(this.parentNode.parentNode).attr('id'));});
					$('#map-button-reload').click(function(){server(3,{'map':map['id']});});
					$('#map-icons-add, #map-icons-sl-ok').click(function(){
						$('#map-icons-add, #map-icons-sl-dv').toggleClass('hidden');
						adjustIconsSlHeight();
					});
					$('#map-icons-del').click(function(){if (!$.isEmptyObject(map['selectedIcons'])) server(6,getIconsURI({'map':map['id']}));});
					$('#map-sl-move-ok').click(function(){if ($('#map-sl-move').val() != 0 && !$.isEmptyObject(map['selectedIcons'])) server(7,getIconsURI({'map':map['id'],'map-dst':$('#map-sl-move').val()}));});
					$('#map-button-fullscreen').click(function(){$('#map-display')[0].requestFullscreen();});
					setTimeout(function(){
						$('#map-display .map-panel:not(.map-panel-no-hide)').each(function(){toggleMapPanelOverlay($(this).attr('id'),true);});
						$('#map-display .map-panel').off();
					},2000);
					firstMapOpening = false;
				}
				var mapSlMoveId = $('#map-sl-move').val();
				var IdIsInNewMapSl = false;
				var res = '<option value="0">-Carte-</option>';
				for (i in maps) {
					if (i != id) {
						res += '<option value="'+i+'">'+maps[i]['name']+'</option>';
						if (i == mapSlMoveId) IdIsInNewMapSl = true;
					}
				}
				$('#map-sl-move').html(res);
				if (IdIsInNewMapSl) $('#map-sl-move').val(mapSlMoveId);
				else $('#map-sl-move').val(0);
				if (isValidAttrAttribution($('#map-panel-modif').attr('style'))) resizeMapPanelOverlay('map-panel-modif');
				$('#map-no-display').addClass('hidden');
			}
		}

		serverResponse = function(opt) {
			if (opt[0] == 0) {
				siteSection = opt[1][0];
				for (i in opt[1][1]) maps[opt[1][1][i]['id']] = opt[1][1][i];
				for (i in maps) $('#map-modif-sl, #map-sl').append('<option value="'+i+'">'+maps[i]['name']+'</option>');
				for (i in maps) $('#maps-thumbnails').append('<div id="map-thumbnail-'+i+'" style="background: url(&quot;'+getBackgroundURL(maps[i]['background'])+'&quot;) 50% 50% / cover;"><div class="flx flx-jc"><div>'+maps[i]['name']+'</div></div></div>');
				$('#maps-thumbnails > div').click(function(){
					var id = $(this).attr('id').replace('map-thumbnail-','');
					changeMap(id);
					$('#map-sl').val(id);
					$('#map-display')[0].scrollIntoView({block:'center',behavior:'smooth'});
				});
				for (i in opt[1][2]) $('#background').append(getSelectOptionsHTMLFromImagesList(opt[1][2]));
				pps = getPpsPathsListFromObj(opt[1][3]);
				var res = '';
				var n = -1;
				for (i in opt[1][3]) {
					res += '<div class="rollable"><div>'+i+'</div><div>';
					for (j in opt[1][3][i]) {
						res += '<div class="rollable"><div>'+j+'</div><div class="flx flx-ww">';
						for (k in opt[1][3][i][j]) {
							n++;
							res += '<img class="icon" ppn="'+n+'" src="/medias/pps/'+pps[n]+'" title="'+pps[n]+'"/>';
						}
						res += '</div></div>';
					}
					res += '</div></div>';
				}
				$('#map-icons-sl').html(res);
				$('#map-icons-sl .rollable > div:first-child').click(function(){
					$rollable = $(this.parentNode);
					if ($rollable.hasClass('show-content')) {
						$rollable.removeClass('show-content');
						$rollable.find('.rollable').removeClass('show-content');
					}
					else $rollable.addClass('show-content');
				});
				$('#map-icons-sl img').draggable({
					cursorAt : {'left':-4.375,'top':-4.375},
					helper : 'clone',
					scope : 'new-icon',
					appendTo : '#map-elems',
					start : function(){$('#map-elems-dv').addClass('map-overlaid');},
					stop : function(){$('#map-elems-dv').removeClass('map-overlaid');}
				});
				// Thanks to bolmaster2
				// https://stackoverflow.com/questions/4817029/whats-the-best-way-to-detect-a-touch-screen-device-using-javascript
				if ('ontouchstart' in window || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0) {
					$('#map-icons-sl img').draggable('disable');
					$('#map-icons-sl img').hammer().on('tap',function(){
						if ($(this).hasClass('blink')) {
							$(this).draggable('disable');
							$(this).removeClass('blink');
						}
						else {
							$('#map-icons-sl img.blink').draggable('disable');
							$('#map-icons-sl img.blink').removeClass('blink');
							$(this).draggable('enable');
							$(this).addClass('blink');
						}
					});
				}
			}
			if (opt[0] == 1) window.location.replace('.');
			if (opt[0] == 2) {
				for (i in opt[1]) $('#map-undermaps-dv').append('<img src="'+getBackgroundURL(opt[1][i]['background'])+'" style="width:'+opt[1][i]['width']+'%;left:'+opt[1][i]['left']+'%;top:'+opt[1][i]['top']+'%;"/>');
				server(3,{'map':map['id']});
			}
			if (['3','4','5','6','7'].includes(opt[0])) {
				var newIconsDisposition = {};
				for (i in opt[1]) newIconsDisposition[opt[1][i]['id']] = opt[1][i];
				var isIn;
				var addedIcons = false;
				var deletedIcons = [];
				var iconsToBePlaced = {};
				for (i in newIconsDisposition) {
					isIn = false;
					for (j in map['currentIcons']) {
						if (i == j) {
							isIn = true;
							if (newIconsDisposition[i]['left'] != map['currentIcons'][j]['left'] || newIconsDisposition[i]['top'] != map['currentIcons'][j]['top']) iconsToBePlaced[i] = true;
							break;
						}
					}
					if (!isIn) {
						addedIcons = true;
						$('#map-elems').append('<img id="map-icon-'+i+'" class="icon beenAdded invisible" icon-number="'+i+'" src="/medias/pps/'+newIconsDisposition[i]['pp']+'"/>');
						iconsToBePlaced[i] = true;
					}
				}
				for (i in map['currentIcons']) {
					isIn = false;
					for (j in newIconsDisposition) {
						if (i == newIconsDisposition[j]['id']) {
							isIn = true;
							break;
						}
					}
					if (!isIn) {
						deletedIcons.push(i);
						$('#map-icon-'+i).addClass('willbedeleted');
					}
				}
				map['currentIcons'] = newIconsDisposition;
				if (firstMovement) setTimeout(function(){moveIcons(iconsToBePlaced);},750);
				else moveIcons(iconsToBePlaced);
				if (addedIcons) {
					var timeToWait = 500+100;
					if (firstMovement) timeToWait += 750;
					setTimeout(function(){$('#map-elems > img.invisible').removeClass('invisible');},100);
					setTimeout(function(){
						$('#map-elems > img.beenAdded')
							.draggable({
								cursorAt : {'left':-4.375,'top':-4.375},
								start : function(){$(this).addClass('icon-no-anim');},
								stop : function(){
									// Thanks to Luka
									// https://stackoverflow.com/questions/37910467/jquery-draggable-convert-position-to-percentage
									var $this = $(this);
									var eWidth = $this.width();
									var eHeight = $this.height();
									var dWidth = $this.parent().width();
									var dHeight = $this.parent().height();
									var l = $this.position().left;
									var t = $this.position().top;
									if (l < -eWidth/2) l = -eWidth/2;
									if (t < -eHeight/2) t = -eHeight/2;
									if (l > dWidth-eWidth/2) l = dWidth-eWidth/2;
									if (t > dHeight-eHeight/2) t = dHeight-eHeight/2;
									l = ((l+eWidth/2)/dWidth*100).round(4);
									t = ((t+eHeight/2)/dHeight*100).round(4);
									$this.css({'left':l+'%','top':t+'%'});
									server(5,{'map':map['id'],'icon':$this.attr('icon-number'),'left':l,'top':t});
									setTimeout(function(){$this.removeClass('icon-no-anim');},250);
								}
							})
							.hammer().on('tap',function(e){
								if ($(this).hasClass('blink')) delete map['selectedIcons'][$(this).attr('icon-number')];
								else map['selectedIcons'][$(this).attr('icon-number')] = null;
								var blinkImgsSelector = '';
								for (i in map['selectedIcons']) blinkImgsSelector += '#map-icon-'+i+', ';
								$('#map-elems > img.blink').removeClass('blink');
								setTimeout(function(){$(blinkImgsSelector.replace(/, $/,'')).addClass('blink');},0);
							})
							.removeClass('beenAdded');
					},timeToWait);
				}
				if (deletedIcons.length > 0) {
					$('#map-elems > img.icon.willbedeleted.blink').removeClass('blink');
					setTimeout(function(){
						for (i in deletedIcons) {
							commandDeletion(deletedIcons[i],parseInt(i));
							if (map['selectedIcons'][deletedIcons[i]] !== undefined) delete map['selectedIcons'][deletedIcons[i]];
						}
					},0);
				}
				firstMovement = false;
			}
		}

		$('#modif-open-btn, #modif-close-btn').click(function(){$('#modif-open-btn, #modif-fld').toggleClass('hidden');});
		$('#map-modif-sl').change(function(){
			var id = $(this).val();
			if (id == 0) {
				$('#name, #backgroundurl').val('');
				$('#background').val(0);
			}
			else {
				$('#name').val(maps[id]['name']);
				if (isImgURL(maps[id]['background'])) {
					$('#background').val(0);
					$('#backgroundurl').val(maps[id]['background']);
				}
				else {
					$('#background').val(maps[id]['background']);
					$('#backgroundurl').val('');
				}
			}
		});
		$('#modif-send-btn').click(function(){
			server(1,{
				'map' : $('#map-modif-sl').val(),
				'name' : $('#name').val(),
				'background' : $('#background').val(),
				'backgroundurl' : $('#backgroundurl').val()
			});
		});

		$('#map-sl').change(function(){changeMap($(this).val());});

		var siteSection;
		var maps = {};
		var pps = [];
		var map;
		var firstMovement;
		var firstMapOpening = true;
		var displayRatioIsLandscape;
		var displayRatioWasLandscape;

		server(0);
	});
	</script>
</head>
<body>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/../header.php'; ?>
	<div id="pg">
		<div class="flx flx-jc flx-ac pgtlt">
			<img src="/src/icons/menu/white/treasure-map.svg"/>
			<h1>Cartes</h1>
			<img src="/src/icons/menu/white/treasure-map.svg"/>
		</div>
		<div class="flx flx-jc">
			<button id="modif-open-btn">Gérer les cartes</button>
			<fieldset id="modif-fld" class="fldct hidden">
				<legend>Carte</legend>
				<div><select id="map-modif-sl"><option value="0">-Nouvelle carte-</option></select></div>
				<div><input type="text" id="name" placeholder="Nom de la carte ..."/></div>
				<div>
					<div>Fond :</div>
					<div>
						<select id="background"><option value="0">-URL-</option></select><br/>
						<input type="text" id="backgroundurl" placeholder="https://..."/>
					</div>
				</div>
				<div><button id="modif-close-btn">Annuler</button> <button id="modif-send-btn">OK</button></div>
			</fieldset>
		</div>
		<div id="maps-thumbnails" class="flx flx-jc flx-ww"></div>
		<div id="map-display" class="flx flx-ac">
			<div id="map-loading-message" class="fillrelativeparent flx flx-jc flx-ac"><i>Chargement ...</i></div>
			<div id="map-elems-dv">
				<div id="map-elems" class="fill">
					<div id="map-undermaps-dv" class="fill"></div>
					<div id="map-unselect-dv" class="fillrelativeparent"></div>
				</div>
			</div>
			<div id="map-panel-modif" class="map-panel map-panel-right flx">
				<div class="map-panel-icons flx flx-dc flx-jc flx-ac">
					<div id="map-button-reload"><img class="fill" src="/src/icons/Refresh_font_awesome.svg"/></div>
					<div class="map-button-display-panel"><img class="fill" src="/src/icons/buttons/white/Ic_settings_48px.svg"/></div>
				</div>
				<div class="map-panel-content hide-scrollbar">
					<div class="flx flx-jc">
						<button id="map-icons-add">Ajouter une icône</button>
						<div id="map-icons-sl-dv" class="hidden">
							<div id="map-icons-sl" class="rollables-dv rollables-min rollables-pps hide-scrollbar"></div>
							<div class="flx flx-jc"><button id="map-icons-sl-ok">OK</button></div>
						</div>
					</div>
					<div class="flx flx-dc flx-ac">
						<div><button id="map-icons-del">Supprimer</button></div>
						<div class="linked-inputs flx flx-dc">
							<select id="map-sl-move"></select>
							<button id="map-sl-move-ok">Déplacer</button>
						</div>
					</div>
				</div>
			</div>
			<div id="map-button-fullscreen" class="buttonlike buttonlike-fullscreen"></div>
			<div id="map-no-display" class="fillrelativeparent flx flx-jc flx-ac"><i>Sélectionner une carte</i></div>
			<div id="map-panel-maps" class="map-panel map-panel-left flx">
				<div class="map-panel-content"><select id="map-sl"><option value="0">-Carte-</option></select></div>
				<div class="map-panel-icons flx flx-dc flx-jc flx-ac"><div class="map-button-display-panel"><img src="/src/icons/menu/white/treasure-map.svg"/></div></div>
			</div>
		</div>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/../footer.php'; ?>
	</div>
</body>
</html>
