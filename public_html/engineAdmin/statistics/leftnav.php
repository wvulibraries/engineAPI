<?
print '<ul>';
print '<li><a href="index.php">Summary by Month<br />(Past Year)</a></li>';
print '<li><a href="index.php?action=all">Summary by Month<br />(All Months)</a></li>';

if (strpos($_SERVER['PHP_SELF'],"detailedMonth") !== FALSE) {
	print '<li class="noBorder">&nbsp;</li>';
	print '<li><a href="#monthlyStats">Monthly Statistics</a></li>';
	print '<li><a href="#dailyStats">Daily Statistics</a></li>';
	print '<li><a href="#hourlyStats">Hourly Statistics</a></li>';
	print '<li><a href="#URLs">Top URLs</a></li>';
	print '<li><a href="#entryPages">Top Entry Pages</a></li>';
	print '<li><a href="#referringPages">Top Referring Pages</a></li>';
	print '<li><a href="#browsers">Top Browsers</a></li>';
	print '<li><a href="#OSs">Top Operating Systems</a></li>';
}

else if (strpos($_SERVER['PHP_SELF'],"detailedPage") !== FALSE) {
	print '<li class="noBorder">&nbsp;</li>';
	print '<li><a href="#monthlyStats">Monthly Statistics</a></li>';
	print '<li><a href="#dailyStats">Daily Statistics</a></li>';
	print '<li><a href="#hourlyStats">Hourly Statistics</a></li>';
	print '<li><a href="#referringPages">Top Referring Pages</a></li>';
	print '<li><a href="#browsers">Top Browsers</a></li>';
	print '<li><a href="#OSs">Top Operating Systems</a></li>';
}

print '</ul>';
?>