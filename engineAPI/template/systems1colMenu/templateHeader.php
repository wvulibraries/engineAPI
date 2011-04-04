<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<!-- Copyright 2010 WVU Libraries            -->
<!-- Scott Blake                             -->

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

	<title>WVU Libraries: {local var="pageTitle"}</title>

	<!-- importStyles.css HAS to be linked using import. This is for     -->
	<!-- legacy web browsers. DO NOT CHANGE                              -->
	<style type="text/css" media="all">
	@import url(http://www.libraries.wvu.edu/css/importStyles.css);
	</style>

	<!-- we don't support older browsers anymore, this styles the content -->
	<!-- that the older browsers see (content in .skipLink)               -->
	<link rel="stylesheet" href="http://www.libraries.wvu.edu/css/oldBrowsers.css" type="text/css" media="screen" />

	<!-- Dropdown Menu style sheet                                     -->
	<!-- Broke this out since it was almost identical to the menus on  -->
	<!-- on the interior pages. interior.css handles the differences   -->
	<link rel="stylesheet" href="http://www.libraries.wvu.edu/css/dropdownMenus.css" type="text/css" media="screen" />

	<!-- Main style sheet for the interior pages -->
	<link rel="stylesheet" href="http://www.libraries.wvu.edu/css/interior.css" type="text/css" media="screen" />
	
	<!-- Handles the links in the header image -->
	<link rel="stylesheet" href="http://www.libraries.wvu.edu/css/interiorHeader.css" type="text/css" media="screen" />
	
	<!-- Standard 'functions'; Bold, Italic, etc ... -->
	<link rel="stylesheet" href="http://www.libraries.wvu.edu/css/standard.css" type="text/css" media="screen" />

	<!-- Table Style Sheet -->
	<!-- Uncomment the line below for 'standard' table styling -->
	<!-- <link rel="stylesheet" href="http://www.libraries.wvu.edu/css/tables.css" type="text/css" media="screen" /> -->

	<!-- Internet Explorer 6 Style Sheet                               -->
	<!-- Style sheet to handle bugs specific to IE6                    -->
	<!--[if IE 6]>
	<link rel="stylesheet" href="http://www.libraries.wvu.edu/css/ie6.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="http://www.libraries.wvu.edu/css/ie6int.css" type="text/css" media="screen" />
	<script src="http://www.libraries.wvu.edu/javascript/iehover.js" type="text/javascript"></script>
	<![endif]-->

	<!-- Internet Explorer 7 Style Sheet                               -->
	<!-- Style sheet to handle bugs specific to IE7                    -->
	<!--[if IE 7]>
	<link rel="stylesheet" href="http://www.libraries.wvu.edu/css/ie7.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="http://www.libraries.wvu.edu/css/ie7int.css" type="text/css" media="screen" />
	<![endif]-->

	<!-- Internet Explorer Style Sheet                                  -->

	<!-- This style sheet is used to fix bugs common to all IE versions -->
	<!-- iehover.js is a work around to IEs :hover bug                  -->
	<!--[if IE]>
	<link rel="stylesheet" href="http://www.libraries.wvu.edu/css/ieint.css" type="text/css" media="screen" />
	<![endif]-->

	<!-- Dirty Hack to fix a windows font size problem with the menu bar -->
	<!-- Not applied to Safari 3 beta on Windows                         -->
	<script src="http://www.libraries.wvu.edu/javascript/winfont.js" type="text/javascript"></script>
	<script src="http://www.libraries.wvu.edu/javascript/winFox.js" type="text/javascript"></script>

	<!-- Couple of fixes for Safari 2                                   -->
	<!-- (not worrying about safari 3 until its out of beta, not gonna  -->
	<!-- chase bugs till they are done)                                 -->
	<script src="http://www.libraries.wvu.edu/javascript/safari.js" type="text/javascript"></script>

	<!-- Specific css file for this Template -->
	<link rel="stylesheet" href="/css/customcss/systems1colMenu.css" type="text/css" media="screen" />
	
	<script src="{engine var="engineListObjJS"}" type="text/javascript"></script>
	<script src="{engine var="convert2TextJS"}"  type="text/javascript"></script>
	<script src="{engine var="selectBoxJS"}"     type="text/javascript"></script>
	<script src="{engine var="jquery"}"          type="text/javascript"></script>
	<script src="{engine var="jqueryDate"}"      type="text/javascript"></script>
	<script src="{engine var="jqueryCookie"}"    type="text/javascript"></script>
	<script src="{engine var="engineWYSIWYGJS"}" type="text/javascript"></script>
	<script src="{engine var="tiny_mce_JS"}"     type="text/javascript"></script>
	
	<script>
	$.extend(DateInput.DEFAULT_OPTS, {
	  stringToDate: function(string) {
	    var matches;
	    if (matches = string.match(/^(\d{2,2})\/(\d{2,2})\/(\d{4,4})$/)) {
	      return new Date(matches[3], matches[1] - 1, matches[2]);
	    } else {
	      return null;
	    };
	  },

	  dateToString: function(date) {
	    var month = (date.getMonth() + 1).toString();
	    var dom = date.getDate().toString();
	    if (month.length == 1) month = "0" + month;
	    if (dom.length == 1) dom = "0" + dom;
	    return month + "/" + dom + "/" + date.getFullYear();
	  }
	});
	</script>
	
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

				<a href="http://www.wvu.edu/">
					<img id="logo" src="/images/interior/wvu_logo.gif" alt="West Virginia University" />
				</a>

				<ul>

					<li class="first" id="headSiteMap">
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
					<li id="headerLibrariesHome">
						<a href="http://www.libraries.wvu.edu" title="WVU Libraries Home">Libraries Home</a>
						&nbsp;&middot;&nbsp;
					</li>
					<li class="last" id="headerWVUHome">
						<a href="http://www.wvu.edu" title="WVU Homepage">WVU Home</a>
					</li>
				</ul> <!-- headerImageMap -->
				
				<div id="banner">
					<div id="bannertext">WVU Libraries<br />&nbsp; &nbsp; &nbsp; Systems Office</div>
				</div>

			
		</div> <!-- header -->

		<div id="content">
			
			<div id="top">
				<a id="menuLink" onclick="$('#left').toggle('slow');">Navigation Menu</a>

				<div id="breadcrumbs">
					<a href="{engine var="WEBROOT"}" class="breadCrumbLink">Libraries Home</a> 
					<span class="breadCrumbSpacer">&rsaquo;</span> 
					{engine name="function" function="breadCrumbs" type="hierarchical" spacer="&rsaquo;" displayNum="5" titlecase="true"}
				</div>
			</div>

			<div id="left">
				<?php recurseInsert("leftnav.php","php") ?>
			</div>
			
			<div id="right" onclick="$('#left').hide('slow');">