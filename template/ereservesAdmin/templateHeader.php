<!DOCTYPE html>

<!-- Copyright 2008 WVU Libraries            -->
<!-- Michael Bond                            -->

<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

	<title>eReserves Admin: {local var="pageTitle"}</title>

	<link rel="stylesheet" href="/css/admin.css" type="text/css" media="Screen" />
	<link rel="stylesheet" href="/css/main.css" type="text/css" media="Screen" />
	<link rel="stylesheet" href="/css/date_input.css" type="text/css">
	
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
	});</script>
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


