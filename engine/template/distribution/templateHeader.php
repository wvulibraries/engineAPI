<!DOCTYPE html>
<html lang="en">
<head>

	<title>{local var="pageTitle"}</title>
	<meta charset="UTF-8">

	<link rel="stylesheet" type="text/css" href="/javascript/distribution/bootstrap/css/bootstrap.min.css">
	<style type="text/css">
		body {
			padding-top: 60px; /* This must be after boostrap and before responsive bootstrap css */
		}
	</style>
	<link rel="stylesheet" type="text/css" href="/javascript/distribution/bootstrap/css/bootstrap-responsive.min.css">
	<script type="text/javascript" src="/javascript/distribution/jquery-1.9.1.min.js"></script>
	<?php recurseInsert("headerIncludes.php","php") ?>
</head>

<body>

	<div class="navbar navbar-inverse navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container-fluid">
				<button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="brand" href="{local var="siteRoot"}">{local var="pageHeader"}</a>
				<div class="nav-collapse collapse">
					<?php recurseInsert("topnav.php","php") ?>
				</div>
			</div>
		</div>
	</div>

	<div class="container-fluid">
		<div class="row-fluid">
