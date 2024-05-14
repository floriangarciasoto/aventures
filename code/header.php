<header class="flx flx-jb flx-ac">
	<span class="flx flx-js">
		<a href="/?logout"><div class="buttonlike buttonlike-logout" title="Se déconnecter"></div></a>
		<?php if ($_SERVER['REQUEST_URI'] != '/') { ?>
		<a id="header-returnpage" href="/"><div class="buttonlike buttonlike-returnpage"></div></a>
		<?php } ?>
	</span>
	<a id="header-sitename" href="/" title="Accueil">
		<span>A</span>
		<span>v</span>
		<span>e</span>
		<span>n</span>
		<span>t</span>
		<span>u</span>
		<span>r</span>
		<span>e</span>
		<span>s</span>
	</a>
	<span class="flx flx-je"><a id="header-imgarea" href="/settings/" title="Paramètres d'utilisateur"><img id="header-img" src="/medias/pps/<?php echo $_SESSION['user']['pp']; ?>"/></a></span>
</header>
