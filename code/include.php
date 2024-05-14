<meta charset="utf-8" name="viewport" content="width=device-width"/>
	<link rel="stylesheet" href="/src/style.css"/>
	<style type="text/css">
		@media screen and (min-width: 601px) {
			body {
				background:linear-gradient(-45deg,rgba(0,0,0,0.25),rgba(0,0,0,0.25)),url("<?php echo '/medias/wallpapers/'.$_SESSION['wallpapers']['list'][rand(0,$_SESSION['wallpapers']['lastIndex'])]; ?>");
				background-size: cover;
				background-attachment:fixed;
				background-position: 50% 0%;
			}
		}
	</style>
