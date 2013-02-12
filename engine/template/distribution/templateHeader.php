<!DOCTYPE html>
<html lang="en">
<head>

	<title>{local var="pageTitle"}</title>
	<meta charset="UTF-8">

	<link rel="stylesheet" type="text/css" href="/css/distribution/bootstrap.min.css">
	<style type="text/css">
		body {
			padding-top: 60px; /* This must be after boostrap and before responsive bootstrap css */
		}
	</style>
	<link rel="stylesheet" type="text/css" href="/css/distribution/bootstrap-responsive.min.css">

	<?php recurseInsert("headerIncludes.php","php") ?>

</head>

<body>

	<div class="navbar navbar-inverse navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container-fluid">
				<a class="brand" href="#">{local var="pageHeader"}</a>
			</div>
		</div>
	</div>

	<div class="container-fluid">
		<div class="row-fluid">
			<div class="span3">
				<div class="well sidebar-nav">
					<?php recurseInsert("leftnav.php","php") ?>
				</div>
			</div>

			<div class="span9">
