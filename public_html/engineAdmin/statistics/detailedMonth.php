<?
$time = explode(' ', microtime());
$time = $time[1] + $time[0];
$start = $time;

include("header.php");


$year  = (isset($engine->cleanGet['MYSQL']['y']))?$engine->cleanGet['MYSQL']['y']:date("Y");
$month = (isset($engine->cleanGet['MYSQL']['m']))?$engine->cleanGet['MYSQL']['m']:date("m");

$sqlSiteYearMonth  = is_empty(sessionGet("engineStatsSite")) ? NULL : "site='".sessionGet("engineStatsSite")."' AND ";
$sqlSiteYearMonth .= "year='".$year."' AND month='".$month."'";

$monthStart = strtotime($year."-".$month."-01");
$monthEnd   = strtotime('-1 second',strtotime('+1 month',$monthStart));

if ($year == date("Y") && $month == date("m")) {
	$numDays = date("d"); // if current month, use today as number of days in month
}
else {
	$numDays = date('t',$monthStart);
}

$numHours = $numDays * 24;



$sql = sprintf("SELECT SUM(mobilevisits) AS totalMobileVisits, SUM(nonmobilevisits) AS totalNonmobileVisits, SUM(mobilehits) AS totalMobileHits, SUM(nonmobilehits) AS totalNonmobileHits, COUNT(resource) AS totalPages, MAX(mobilehits) AS maxMobileHits, MAX(nonmobilehits) AS maxNonmobileHits FROM %s WHERE %s",
	$engineDB->escape("logHits"),
	$sqlSiteYearMonth
	);
$engineDB->sanitize = FALSE;
$sqlResult          = $engineDB->query($sql);

if ($sqlResult['result']) {
	$row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC);
	
	$totalMobileVisits       = $row['totalMobileVisits'];
	$totalNonmobileVisits    = $row['totalNonmobileVisits'];
	$totalMobileHits         = $row['totalMobileHits'];
	$totalNonmobileHits      = $row['totalNonmobileHits'];
	$totalPages              = $row['totalPages'];
	$maxMobileHitsPerHour    = $row['maxMobileHits'];
	$maxNonmobileHitsPerHour = $row['maxNonmobileHits'];
}


$maxMobileVisits        = 0;
$maxNonmobileVisits     = 0;
$maxMobileHitsPerDay    = 0;
$maxNonmobileHitsPerDay = 0;
$maxPages               = 0;

$sql = sprintf("SELECT SUM(mobilevisits) AS mobilevisits, SUM(nonmobilevisits) AS nonmobilevisits, SUM(mobilehits) AS mobilehits, SUM(nonmobilehits) AS nonmobilehits, COUNT(resource) AS pages FROM %s WHERE %s GROUP BY day",
	$engineDB->escape("logHits"),
	$sqlSiteYearMonth
	);
$engineDB->sanitize = FALSE;
$sqlResult          = $engineDB->query($sql);

if ($sqlResult['result']) {
	while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
		
		if ($maxMobileVisits < $row['mobilevisits']) {
			$maxMobileVisits = $row['mobilevisits'];
		}
		
		if ($maxNonmobileVisits < $row['nonmobilevisits']) {
			$maxNonmobileVisits = $row['nonmobilevisits'];
		}
		
		if ($maxMobileHitsPerDay < $row['mobilehits']) {
			$maxMobileHitsPerDay = $row['mobilehits'];
		}
		
		if ($maxNonmobileHitsPerDay < $row['nonmobilehits']) {
			$maxNonmobileHitsPerDay = $row['nonmobilehits'];
		}
		
		if ($maxPages < $row['pages']) {
			$maxPages = $row['pages'];
		}
		
	}
}
?>
<!-- Page Content Goes Below This Line -->

<h1>Detailed Stats</h1>

<h2>Month: <?= date("F Y",$monthStart) ?></h2>

<br />

<a name="monthlyStats"></a>
<table>
 	<thead>
		<tr>
			<th colspan="3" class="sorttable_nosort">Monthly Statistics for <?= date("F Y",$monthStart) ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Total Mobile Visits</td>
			<td colspan="2"><?= $totalMobileVisits ?></td>
		</tr>
		<tr>
			<td>Total Non-Mobile Visits</td>
			<td colspan="2"><?= $totalNonmobileVisits ?></td>
		</tr>
		<tr>
			<td>Total Mobile Hits</td>
			<td colspan="2"><?= $totalMobileHits ?></td>
		</tr>
		<tr>
			<td>Total Non-Mobile Hits</td>
			<td colspan="2"><?= $totalNonmobileHits ?></td>
		</tr>
		<tr>
			<td>Total Pages</td>
			<td colspan="2"><?= $totalPages ?></td>
		</tr>
		
		<tr>
			<th>&nbsp;</th>
			<th>Avg</th>
			<th>Max</th>
		</tr>
		<tr>
			<td>Mobile Visits per Day</td>
			<td><?= round($totalMobileVisits/$numDays) ?></td>
			<td><?= $maxMobileVisits ?></td>
		</tr>
		<tr>
			<td>Non-Mobile Visits per Day</td>
			<td><?= round($totalNonmobileVisits/$numDays) ?></td>
			<td><?= $maxNonmobileVisits ?></td>
		</tr>
		<tr>
			<td>Mobile Hits per Hour</td>
			<td><?= round($totalMobileHits/$numHours) ?></td>
			<td><?= $maxMobileHitsPerHour ?></td>
		</tr>
		<tr>
			<td>Non-Mobile Hits per Hour</td>
			<td><?= round($totalNonmobileHits/$numHours) ?></td>
			<td><?= $maxNonmobileHitsPerHour ?></td>
		</tr>
		<tr>
			<td>Mobile Hits per Day</td>
			<td><?= round($totalMobileHits/$numDays) ?></td>
			<td><?= $maxMobileHitsPerDay ?></td>
		</tr>
		<tr>
			<td>Non-Mobile Hits per Day</td>
			<td><?= round($totalNonmobileHits/$numDays) ?></td>
			<td><?= $maxNonmobileHitsPerDay ?></td>
		</tr>
		<tr>
			<td>Pages per Day</td>
			<td><?= round($totalPages/$numDays) ?></td>
			<td><?= $maxPages ?></td>
		</tr>
	</tbody>
</table>

<br /><br />

<a name="dailyStats"></a>
<table class="sortable">
	<thead>
 		<tr>
			<th colspan="11" class="sorttable_nosort">Daily Statistics for <?= date("F Y",$monthStart) ?></th>
		</tr>
		<tr>
			<th rowspan="2">Day</th>
			<th colspan="4" class="sorttable_nosort">Hits</th>
			<th colspan="4" class="sorttable_nosort">Visits</th>
			<th rowspan="2" colspan="2">Pages</th>
		</tr>
		<tr>
			<th>Mobile</th>
			<th>Non-Mobile</th>
			<th colspan="2">Total</th>
			<th>Mobile</th>
			<th>Non-Mobile</th>
			<th colspan="2">Total</th>
		</tr>
	</thead>
	<tbody>
		<?
		$visits = array();
		$hits   = array();
		$pages  = array();
		
		for ($i=1; $i <= $numDays; $i++) {
			$visits[$i]          = 0;
			$hits[$i]            = 0;
			$pages[$i]           = 0;
			$mobilehits[$i]      = 0;
			$nonmobilehits[$i]   = 0;
			$mobilevisits[$i]    = 0;
			$nonmobilevisits[$i] = 0;
		}

		$totalVisits = 0;
		$totalHits   = 0;
		$totalPages  = 0;
		
			
		$sql = sprintf("SELECT day, SUM(mobilevisits) AS mobilevisits, SUM(nonmobilevisits) AS nonmobilevisits, SUM(mobilehits) AS mobilehits, SUM(nonmobilehits) AS nonmobilehits, COUNT(resource) AS pages FROM %s WHERE %s GROUP BY day",
			$engineDB->escape("logHits"),
			$sqlSiteYearMonth
			);
		$engineDB->sanitize = FALSE;
		$sqlResult          = $engineDB->query($sql);
		
		if ($sqlResult['result']) {
			while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
				
				$mobilehits[$row['day']]    = $row['mobilehits'];
				$nonmobilehits[$row['day']] = $row['nonmobilehits'];
				$hits[$row['day']] = $mobilehits[$row['day']] + $nonmobilehits[$row['day']];

				$mobilevisits[$row['day']]    = $row['mobilevisits'];
				$nonmobilevisits[$row['day']] = $row['nonmobilevisits'];
				$visits[$row['day']] = $mobilevisits[$row['day']] + $nonmobilevisits[$row['day']];

				$totalHits   += $hits[$row['day']];
				$totalVisits += $visits[$row['day']];
				$totalPages  += $pages[$row['day']]  = $row['pages'];

			}
		}
		
		for ($i=1; $i <= $numDays; $i++) {
			
			$mobilehits[$i]      = is_empty($mobilehits[$i])      ? 0 : $mobilehits[$i];
			$nonmobilehits[$i]   = is_empty($nonmobilehits[$i])   ? 0 : $nonmobilehits[$i];
			$hits[$i]            = is_empty($hits[$i])            ? 0 : $hits[$i];
			$mobilevisits[$i]    = is_empty($mobilevisits[$i])    ? 0 : $mobilevisits[$i];
			$nonmobilevisits[$i] = is_empty($nonmobilevisits[$i]) ? 0 : $nonmobilevisits[$i];
			$visits[$i]          = is_empty($visits[$i])          ? 0 : $visits[$i];
			$pages[$i]           = is_empty($pages[$i])           ? 0 : $pages[$i];
			
			$percentHits   = ($totalHits>0)   ? ($hits[$i]/$totalHits)     : 0;
			$percentVisits = ($totalVisits>0) ? ($visits[$i]/$totalVisits) : 0;
			$percentPages  = ($totalPages>0)  ? ($pages[$i]/$totalPages)   : 0;

			print "<tr>";
			print "<td>".$i."</td>";
			print "<td>".$mobilehits[$i]."</td>";
			print "<td>".$nonmobilehits[$i]."</td>";
			print "<td>".$hits[$i]."</td>";
			print "<td>".number_format($percentHits*100,2)."%</td>";
			print "<td>".$mobilevisits[$i]."</td>";
			print "<td>".$nonmobilevisits[$i]."</td>";
			print "<td>".$visits[$i]."</td>";
			print "<td>".number_format($percentVisits*100,2)."%</td>";
			print "<td>".$pages[$i]."</td>";
			print "<td>".number_format($percentPages*100,2)."%</td>";
			print "</tr>";
			
		}
		?>
	</tbody>
</table>

<br /><br />

<a name="hourlyStats"></a>
<table class="sortable">
	<thead>
		<tr>
			<th colspan="10" class="sorttable_nosort">Hourly Statistics for <?= date("F Y",$monthStart) ?></th>
		</tr>
		<tr>
			<th rowspan="2">Hour</th>
			<th colspan="3" class="sorttable_nosort">Mobile Hits</th>
			<th colspan="3" class="sorttable_nosort">Non-Mobile Hits</th>
			<th colspan="3" class="sorttable_nosort">Pages</th>
		</tr>
		<tr>
			<th>Avg</th>
			<th colspan="2">Total</th>
			<th>Avg</th>
			<th colspan="2">Total</th>
			<th>Avg</th>
			<th colspan="2">Total</th>
		</tr>
	</thead>
	<tbody>
		<?
		$mobileHits    = array();
		$nonmobileHits = array();
		$pages         = array();
		
		for ($i=0; $i < 24; $i++) {
			$mobileHits[$i]    = 0;
			$nonmobileHits[$i] = 0;
			$pages[$i]         = 0;
		}

		$totalMobileHits    = 0;
		$totalNonmobileHits = 0;
		$totalPages         = 0;
		
			
		$sql = sprintf("SELECT hour, SUM(mobilehits) AS totalMobileHits, SUM(nonmobilehits) AS totalNonmobileHits, COUNT(resource) AS totalPages FROM %s WHERE %s GROUP BY hour",
			$engineDB->escape("logHits"),
			$sqlSiteYearMonth
			);
		$engineDB->sanitize = FALSE;
		$sqlResult          = $engineDB->query($sql);
		
		if ($sqlResult['result']) {
			while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
			
				$totalMobileHits    += $mobileHits[$row['hour']]    = $row['totalMobileHits'];
				$totalNonmobileHits += $nonmobileHits[$row['hour']] = $row['totalNonmobileHits'];
				$totalPages         += $pages[$row['hour']]         = $row['totalPages'];
			
			}
		}
		
		for ($i=0; $i < 24; $i++) {
			
			$mobileHits[$i]    = is_empty($mobileHits[$i])    ? 0 : $mobileHits[$i];
			$nonmobileHits[$i] = is_empty($nonmobileHits[$i]) ? 0 : $nonmobileHits[$i];
			$pages[$i]         = is_empty($pages[$i])         ? 0 : $pages[$i];
			
			$percentMobile    = ($totalMobileHits>0)    ? ($mobileHits[$i]/$totalMobileHits)       : 0;
			$percentNonmobile = ($totalNonmobileHits>0) ? ($nonmobileHits[$i]/$totalNonmobileHits) : 0;
			$percentPages     = ($totalPages>0)         ? ($pages[$i]/$totalPages)                 : 0;

			print "<tr>";
			print "<td>".$i."</td>";
			print "<td>".round($mobileHits[$i]/24)."</td>";
			print "<td>".$mobileHits[$i]."</td>";
			print "<td>".number_format($percentMobile*100,2)."%</td>";
			print "<td>".round($nonmobileHits[$i]/24)."</td>";
			print "<td>".$nonmobileHits[$i]."</td>";
			print "<td>".number_format($percentNonmobile*100,2)."%</td>";
			print "<td>".round($pages[$i]/24)."</td>";
			print "<td>".$pages[$i]."</td>";
			print "<td>".number_format($percentPages*100,2)."%</td>";
			print "</tr>";
			
		}
		?>
	</tbody>
</table>

<br /><br />


<?
$sql = sprintf("SELECT SUM(mobilehits) AS totalMobileHits, SUM(nonmobilehits) AS totalNonmobileHits, COUNT(DISTINCT url) AS totalURLs FROM %s WHERE %s",
	$engineDB->escape("logURLs"),
	$sqlSiteYearMonth
	);
$engineDB->sanitize = FALSE;
$sqlResult          = $engineDB->query($sql);
$row                = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC);

$totalHits = $row['totalMobileHits'] + $row['totalNonmobileHits'];
$totalURLs = $row['totalURLs'];
?>
<a name="URLs"></a>
<table class="sortable">
	<thead>
		<tr>
			<th colspan="6" class="sorttable_nosort">
				<?= 'Top 10 of <a href="allURLs.php?y='.$year.'&m='.$month.'">'.$totalURLs.' URLs</a> for '.date("F Y",$monthStart) ?>
			</th>
		</tr>
		<tr>
			<th rowspan="2">#</th>
			<th colspan="4" class="sorttable_nosort">Hits</th>
			<th rowspan="2" class="sorttable_alpha">URL</th>
		</tr>
		<tr>
			<th>Mobile</th>
			<th>Non-Mobile</th>
			<th colspan="2">Total</th>
		</tr>
	</thead>
	<tbody>
		<?
		$sql = sprintf("SELECT SUM(mobilehits) AS mobilehits, SUM(nonmobilehits) AS nonmobilehits, url FROM %s WHERE %s GROUP BY url ORDER BY SUM(mobilehits+nonmobilehits) DESC LIMIT 10",
			$engineDB->escape("logURLs"),
			$sqlSiteYearMonth
			);
		$engineDB->sanitize = FALSE;
		$sqlResult          = $engineDB->query($sql);
		
		if ($sqlResult['result']) {
			for ($i=1; $row=mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC); $i++) {
				
				if ($row['url'] == "NULL") {
					$url = "/";
				}
				else {
					$url = $row['url'];
				}

				print "<tr>";
				print "<td>".$i."</td>";
				print "<td>".$row['mobilehits']."</td>";
				print "<td>".$row['nonmobilehits']."</td>";
				print "<td>".($row['mobilehits']+$row['nonmobilehits'])."</td>";
				print "<td>".number_format(($row['mobilehits']+$row['nonmobilehits'])/$totalHits*100,2)."%</td>";
				print '<td><a href="detailedPage.php?y='.$year.'&m='.$month.'&p='.$url.'">'.$url.'</a></td>';
				print "</tr>";
				
			}
		}
		?>
	</tbody>
</table>

<br /><br />


<?
$sql = sprintf("SELECT SUM(mobilehits) AS totalMobileHits, SUM(nonmobilehits) AS totalNonmobileHits, COUNT(DISTINCT url) AS totalURLs FROM %s WHERE %s AND referrer='NULL'",
	$engineDB->escape("logURLs"),
	$sqlSiteYearMonth
	);
$engineDB->sanitize = FALSE;
$sqlResult          = $engineDB->query($sql);
$row                = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC);

$totalHits = $row['totalMobileHits'] + $row['totalNonmobileHits'];
$totalURLs = $row['totalURLs'];
?>
<a name="entryPages"></a>
<table class="sortable">
	<thead>
		<tr>
			<th colspan="6" class="sorttable_nosort">
				<?= 'Top 10 of <a href="allEntryPages.php?y='.$year.'&m='.$month.'">'.$totalURLs.' Entry Pages</a> for '.date("F Y",$monthStart) ?>
			</th>
		</tr>
		<tr>
			<th rowspan="2">#</th>
			<th colspan="4" class="sorttable_nosort">Hits</th>
			<th rowspan="2" class="sorttable_alpha">URL</th>
		</tr>
		<tr>
			<th>Mobile</th>
			<th>Non-Mobile</th>
			<th colspan="2">Total</th>
		</tr>
	</thead>
	<tbody>
		<?
		$sql = sprintf("SELECT SUM(mobilehits) AS mobilehits, SUM(nonmobilehits) AS nonmobilehits, url FROM %s WHERE %s AND referrer='NULL' GROUP BY url ORDER BY SUM(mobilehits+nonmobilehits) DESC LIMIT 10",
			$engineDB->escape("logURLs"),
			$sqlSiteYearMonth
			);
		$engineDB->sanitize = FALSE;
		$sqlResult          = $engineDB->query($sql);
		
		if ($sqlResult['result']) {
			for ($i=1; $row=mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC); $i++) {
				
				if ($row['url'] == "NULL") {
					$url = "/";
				}
				else {
					$url = $row['url'];
				}

				print "<tr>";
				print "<td>".$i."</td>";
				print "<td>".$row['mobilehits']."</td>";
				print "<td>".$row['nonmobilehits']."</td>";
				print "<td>".($row['mobilehits']+$row['nonmobilehits'])."</td>";
				print "<td>".number_format(($row['mobilehits']+$row['nonmobilehits'])/$totalHits*100,2)."%</td>";
				print '<td><a href="detailedPage.php?y='.$year.'&m='.$month.'&p='.$url.'">'.$url.'</a></td>';
				print "</tr>";
				
			}
		}
		?>
	</tbody>
</table>

<br /><br />


<?
$sql = sprintf("SELECT SUM(mobilehits) AS totalMobileHits, SUM(nonmobilehits) AS totalNonmobileHits, COUNT(DISTINCT referrer) AS totalURLs FROM %s WHERE %s AND referrer!='NULL'",
	$engineDB->escape("logURLs"),
	$sqlSiteYearMonth
	);
$engineDB->sanitize = FALSE;
$sqlResult          = $engineDB->query($sql);
$row                = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC);

$totalHits = $row['totalMobileHits'] + $row['totalNonmobileHits'];
$totalURLs = $row['totalURLs'];
?>
<a name="referringPages"></a>
<table class="sortable">
	<thead>
		<tr>
			<th colspan="6" class="sorttable_nosort">
				<?= 'Top 10 of <a href="allReferringPages.php?y='.$year.'&m='.$month.'">'.$totalURLs.' Referring Pages</a> for '.date("F Y",$monthStart) ?>
			</th>
		</tr>
		<tr>
			<th rowspan="2">#</th>
			<th colspan="4" class="sorttable_nosort">Hits</th>
			<th rowspan="2" class="sorttable_alpha">URL</th>
		</tr>
		<tr>
			<th>Mobile</th>
			<th>Non-Mobile</th>
			<th colspan="2">Total</th>
		</tr>
	</thead>
	<tbody>
		<?
		$sql = sprintf("SELECT SUM(mobilehits) AS mobilehits, SUM(nonmobilehits) AS nonmobilehits, referrer FROM %s WHERE %s AND referrer!='NULL' GROUP BY referrer ORDER BY SUM(mobilehits+nonmobilehits) DESC LIMIT 10",
			$engineDB->escape("logURLs"),
			$sqlSiteYearMonth
			);
		$engineDB->sanitize = FALSE;
		$sqlResult          = $engineDB->query($sql);
		
		if ($sqlResult['result']) {
			for ($i=1; $row=mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC); $i++) {
				
				print "<tr>";
				print "<td>".$i."</td>";
				print "<td>".$row['mobilehits']."</td>";
				print "<td>".$row['nonmobilehits']."</td>";
				print "<td>".($row['mobilehits']+$row['nonmobilehits'])."</td>";
				print "<td>".number_format(($row['mobilehits']+$row['nonmobilehits'])/$totalHits*100,2)."%</td>";
				print "<td>".$row['referrer']."</td>";
				print "</tr>";
				
			}
		}
		?>
	</tbody>
</table>

<br /><br />


<?
$output     = array();
$totalCount = 0;

$sql = sprintf("SELECT browser, nonHuman, SUM(onCampusCount) AS onCampusCount, SUM(offCampusCount) AS offCampusCount, (SUM(onCampusCount)+SUM(offCampusCount)) AS total FROM %s WHERE %s GROUP BY browser ORDER BY total DESC",
	$engineDB->escape("logBrowsers"),
	$sqlSiteYearMonth
	);
$engineDB->sanitize = FALSE;
$sqlResult          = $engineDB->query($sql);

if ($sqlResult['result']) {
	while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
		
		$totalCount += $row['total'];
		
		if ($row['nonHuman'] == 1) {
			continue;
		}
		
		if (array_key_exists($row['browser'],$output)) {
			$output[$row['browser']]['onCampus']  += $row['onCampusCount'];
			$output[$row['browser']]['offCampus'] += $row['offCampusCount'];
			$output[$row['browser']]['total']     += $row['total'];
		}
		else {
			$output[$row['browser']]['onCampus']   = $row['onCampusCount'];
			$output[$row['browser']]['offCampus']  = $row['offCampusCount'];
			$output[$row['browser']]['total']      = $row['total'];
		}
						
	}
	?>			
	<a name="browsers"></a>
	<table class="sortable">
		<thead>
	 		<tr>
				<th colspan="6" class="sorttable_nosort">
					<?= 'Top 10 of <a href="allBrowsers.php?y='.$year.'&m='.$month.'">'.count($output)." Browsers</a> for ".date("F Y",$monthStart) ?>
				</th>
			</tr>
	 		<tr>
				<th>#</th>
				<th>On Campus</th>
				<th>Off Campus</th>
				<th colspan="2">Total</th>
				<th class="sorttable_alpha">Browser</th>
			</tr>
		</thead>
		<tbody>
			<?
			$i = 1;
			foreach ($output as $browser => $count) {
				
				print "<tr>";
				print "<td>".$i++."</td>";
				print "<td>".$count['onCampus']."</td>";
				print "<td>".$count['offCampus']."</td>";
				print "<td>".$count['total']."</td>";
				print "<td>".number_format($count['total']/$totalCount*100,2)."%</td>";
				print "<td>".$browser."</td>";
				print "</tr>";
				
				if ($i > 10) {
					break;
				}
				
			}
			?>
		</tbody>
	</table>
	<?
}
?>

<br /><br />


<?
$output     = array();
$totalCount = 0;

$sql = sprintf("SELECT os, nonHuman, SUM(onCampusCount) AS onCampusCount, SUM(offCampusCount) AS offCampusCount, (SUM(onCampusCount)+SUM(offCampusCount)) AS total FROM %s WHERE %s GROUP BY os ORDER BY total DESC",
	$engineDB->escape("logBrowsers"),
	$sqlSiteYearMonth
	);
$engineDB->sanitize = FALSE;
$sqlResult          = $engineDB->query($sql);

if ($sqlResult['result']) {
	while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
		
		$totalCount += $row['total'];
		
		if ($row['nonHuman'] == 1) {
			continue;
		}
		
		if (array_key_exists($row['os'],$output)) {
			$output[$row['os']]['onCampus']  += $row['onCampusCount'];
			$output[$row['os']]['offCampus'] += $row['offCampusCount'];
			$output[$row['os']]['total']     += $row['total'];
		}
		else {
			$output[$row['os']]['onCampus']   = $row['onCampusCount'];
			$output[$row['os']]['offCampus']  = $row['offCampusCount'];
			$output[$row['os']]['total']      = $row['total'];
		}
						
	}
	?>			
	<a name="OSs"></a>
	<table class="sortable">
		<thead>
	 		<tr>
				<th colspan="6" class="sorttable_nosort">
					<?= 'Top 5 of <a href="allOSs.php?y='.$year.'&m='.$month.'">'.count($output)." Operating Systems</a> for ".date("F Y",$monthStart) ?>
				</th>
			</tr>
	 		<tr>
				<th>#</th>
				<th>On Campus</th>
				<th>Off Campus</th>
				<th colspan="2">Total</th>
				<th class="sorttable_alpha">Operating System</th>
			</tr>
		</thead>
		<tbody>
			<?
			$i = 1;
			foreach ($output as $os => $count) {
				
				print "<tr>";
				print "<td>".$i++."</td>";
				print "<td>".$count['onCampus']."</td>";
				print "<td>".$count['offCampus']."</td>";
				print "<td>".$count['total']."</td>";
				print "<td>".number_format($count['total']/$totalCount*100,2)."%</td>";
				print "<td>".$os."</td>";
				print "</tr>";
				
				if ($i > 5) {
					break;
				}
				
			}
			?>
		</tbody>
	</table>
	<?
}
?>

<!-- Page Content Goes Above This Line -->

<?
$engine->eTemplate("include","footer");

if (debugNeeded("time")) {
	$time = explode(' ', microtime());
	$time = $time[1] + $time[0];
	$finish = $time;
	$total_time = round(($finish - $start), 4);
	print '<p>Page generated in '.$total_time.' seconds.</p>'."\n";
}
?>