<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<!-- Copyright 2008 WVU Libraries            -->
<!-- Michael Bond                            -->

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

	<title>WVU Libraries: {local var="pageTitle"}</title>

	<!-- DO NOT CHANGE                              -->
	<style type="text/css" media="all">
	@import url(/css/importStyles.css);
	</style>

	<!-- we don't support older browsers anymore, this styles the content -->
	<!-- that the older browsers see (content in .skipLink)               -->
	<link rel="stylesheet" href="/css/oldBrowsers.css" type="text/css" media="screen" />

	<!-- Dropdown Menu style sheet                                     -->
	<!-- Broke this out since it was almost identical to the menus on  -->
	<!-- on the interior pages. interior css handles the differences   -->
	<link rel="stylesheet" href="/css/dropdownMenus.css" type="text/css" media="screen" />

	<!-- Main style sheet for the interior pages -->
	<link rel="stylesheet" href="/css/interior.css" type="text/css" media="screen" />
	
	<!-- Handles the links in the header image -->
	<link rel="stylesheet" href="/css/interiorHeader.css" type="text/css" media="screen" />

	<!-- Standard 'functions'; Bold, Italic, etc ... -->
	<link rel="stylesheet" href="/css/standard.css" type="text/css" media="screen" />

	<!-- Table Style Sheet -->
	<!-- Uncomment the line below for 'standard' table styling -->
	<!-- <link rel="stylesheet" href="/css/tables.css" type="text/css" media="screen" /> -->

	<!-- Defines the header image for the current page                       -->
	<!-- I like the idea of using ./images/banner.gif for all interior pages -->
	<!-- and symlinks to handle not duplicating ... but I think that might   -->
	<!-- be too  difficult to manage                                         -->
	<style type="text/css" media="all">
	#headerImageMap {
		background-image: url("{engine name="include" type="url" file="images/banner.gif"}");
	}
	</style>

	<!-- Internet Explorer 6 Style Sheet                               -->
	<!-- Style sheet to handle bugs specific to IE6                    -->
	<!--[if IE 6]>
	<link rel="stylesheet" href="/css/ie6.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="/css/ie6int.css" type="text/css" media="screen" />
	<script src="/javascript/iehover.js" type="text/javascript"></script>
	<![endif]-->

	<!-- Internet Explorer 7 Style Sheet                               -->
	<!-- Style sheet to handle bugs specific to IE7                    -->
	<!--[if IE 7]>
	<link rel="stylesheet" href="/css/ie7.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="/css/ie7int.css" type="text/css" media="screen" />
	<![endif]-->

	<!-- Internet Explorer Style Sheet                                  -->

	<!-- This style sheet is used to fix bugs commont o all IE versions -->
	<!-- iehover.js is a work around to IEs :hover bug                  -->
	<!--[if IE]>
	<link rel="stylesheet" href="/css/ieint.css" type="text/css" media="screen" />
	<![endif]-->

	<!-- Dirty Hack to fix a windows font size problem with the menu bar -->
	<!-- Not applied to Safari 3 beta on Windows                         -->
	<script src="/javascript/winfont.js" type="text/javascript"></script>
	<script src="/javascript/winFox.js" type="text/javascript"></script>

	<!-- Couple of fixes for Safari 2                                   -->
	<!-- (not worrying about safari 3 until its out of beta, not gonna  -->
	<!-- chase bugs till they are done)                                 -->
	<script src="/javascript/safari.js" type="text/javascript"></script>

	<?php recurseInsert("headerIncludes.php","php")	?>

</head>
<body>

	<div class="skipLink">
		<p>

			<br />
			<strong>
				This site will work and look better in a modern Web browser that supports Web
				Standards such as XHTML and CSS. Please visit the 
				<a href="/browserupgrade/"> WVU Libraries Web Browser Upgrade page for more information </a>
				on upgrading your web browser. 
				<br /><br />
				WVU Libraries strongly recommends upgrading your web browser, but will continue 
				to provide a site that will be functional in all web browsers. 
			</strong>
			<br />

		</p>
	</div>

	<div id="mainDiv">
		<div id="header">
			<div id="headerImageMap">
				<a href="http://www.wvu.edu" title="WVU Homepage" id="headerWVULogo"></a>

				<ul id="headerImageMapList">

					<li id="headSiteMap">
						<a href="http://www.wvu.edu/siteindex/" title="WVU Sitemap">A-Z Site Index</a> 
						&nbsp;&middot;&nbsp;			
					</li>
					<li id="headCampusMap">
						<a href="http://www.wvu.edu/CampusMap/" title="Campus Map">Campus Map</a>
						&nbsp;&middot;&nbsp;
					</li>
					<li id="headDirectory">

						<a href="http://directory.wvu.edu" title="Campus Map">Directory</a>
						&nbsp;&middot;&nbsp;
					</li>
					<li id="headerContactUs">
						<a href="http://www.libraries.wvu.edu/contactus" title="Contact WVU Libraries">Contact Us</a>
						&nbsp;&middot;&nbsp;
					</li>
					<li id="headerHours">

						<a href="http://www.libraries.wvu.edu/hours" title="WVU Library Hours">Hours</a> 
						&nbsp;&middot;&nbsp;
					</li>
					<li id="headerWVUHome">
						<a href="http://www.wvu.edu" title="WVU Homepage">WVU Home</a>
					</li>
				</ul> <!-- headerImageMap -->
				
				<a href="/" id="headerLibaryHome" title="WVU Libraries Home"></a>

				
			</div>
			
			<?php recurseInsert("dropdown.php","php") ?>

		</div> <!-- header -->

		<div id="content">

