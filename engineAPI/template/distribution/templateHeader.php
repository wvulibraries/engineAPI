<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

	<title>{local var="pageTitle"}</title>

	<!-- importStyles.css HAS to be linked using import. This is for     -->
	<!-- legacy web browsers. DO NOT CHANGE                              -->
	<style type="text/css" media="all">
	@import url({engine var="CSSURL"}/importStyles.css);
	@import url({engine var="CSSURL"}/oldBrowsers.css);
	</style>

	<!-- Standard 'functions'; Bold, Italic, etc ... -->
	<link rel="stylesheet" href="{engine var="CSSURL"}/standard.css" type="text/css" media="screen" />

	<!-- Specific css file for this Template -->
	<link rel="stylesheet" href="{engine var="CSSURL"}/customcss/distribution.css" type="text/css" media="screen" />
	
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
			<div id="banner">
				<div id="bannertext">Site Title</div>
			</div>
		</div>

		<div id="content">

			<div id="left">
				<?php recurseInsert("leftnav.php","php") ?>
			</div>

			<div id="right">

				<div id="breadcrumbs">
					<a href="{engine var="WEBROOT"}" class="breadCrumbLink">Home</a> 
					<span class="breadCrumbSpacer">&rsaquo;</span> 
					{engine name="function" function="breadCrumbs" type="hierarchical" spacer="&rsaquo;" displayNum="5" titlecase="true"}
				</div>
