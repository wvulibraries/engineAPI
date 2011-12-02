<!DOCTYPE html>

<!-- Copyright 2008 WVU Libraries            -->
<!-- Michael Bond                            -->

<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

	<title>eReserves Admin: {local var="pageTitle"}</title>

	<link rel="stylesheet" href="/css/admin.css" type="text/css" media="Screen" />
	<link rel="stylesheet" href="/css/main.css" type="text/css" media="Screen" />
	<link rel="stylesheet" href="/css/ui-smoothness/jquery.ui.css" type="text/css">
	<style>
		.ui-timepicker-div .ui-widget-header { margin-bottom: 8px; }
		.ui-timepicker-div dl { text-align: left; }
		.ui-timepicker-div dl dt { height: 25px; }
		.ui-timepicker-div dl dd { margin: -25px 10px 10px 65px; }
		.ui-timepicker-div td { font-size: 90%; }
		.ui-tpicker-grid-label { background: none; border: none; margin: 0; padding: 0; }
	</style>

	<script type="text/javascript" src="{engine var="engineListObjJS"}"></script>
	<script type="text/javascript" src="{engine var="convert2TextJS"}"></script>
	<script type="text/javascript" src="{engine var="selectBoxJS"}"></script>
	<script type="text/javascript" src="{engine var="jquery"}"></script>
	<script type="text/javascript" src="{engine var="jqueryCookie"}"></script>
	<script type="text/javascript" src="{engine var="engineWYSIWYGJS"}"></script>
	<script type="text/javascript" src="{engine var="tiny_mce_JS"}"></script>
	<script type="text/javascript" src="{engine var="engineInc"}/jquery.ui.js"></script>
	<script type="text/javascript" src="{engine var="engineInc"}/jquery.ui.touch.js"></script>
	<script type="text/javascript" src="{engine var="engineInc"}/jquery.ui.timepicker.js"></script>

	<?php recurseInsert("headerIncludes.php","php")	?>

</head>

<body>

<header>
	<h1>eReserves Admin</h1>
</header>

<section>
<nav>
	<?php recurseInsert("leftnav.php","php") ?>
</nav>

<section id="mainContent">
	<div id="errorDisplay">
	</div>


