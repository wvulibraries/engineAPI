<?
$time = explode(' ', microtime());
$time = $time[1] + $time[0];
$start = $time;

include("header.php");


$year  = (isset($engine->cleanGet['MYSQL']['y'])&&!is_empty($engine->cleanGet['MYSQL']['y']))?$engine->cleanGet['MYSQL']['y']:NULL;
$month = (isset($engine->cleanGet['MYSQL']['m'])&&!is_empty($engine->cleanGet['MYSQL']['m']))?$engine->cleanGet['MYSQL']['m']:NULL;
$page  = (isset($engine->cleanGet['MYSQL']['p'])&&!is_empty($engine->cleanGet['MYSQL']['p']))?$engine->cleanGet['MYSQL']['p']:NULL;

if (isnull($year) || isnull($month)) {

	$sql = sprintf("SELECT year,month FROM %s ORDER BY year ASC, month ASC LIMIT 1",
		$engineDB->escape("logHits")
		);
	$engineDB->sanitize = FALSE;
	$sqlResult          = $engineDB->query($sql);
	
	if ($sqlResult['result']) {
		$row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC);
	
		$minYear  = $row['year'];
		$minMonth = $row['month'];
	}
	
	$sql = sprintf("SELECT year,month FROM %s ORDER BY year DESC, month DESC LIMIT 1",
		$engineDB->escape("logHits")
		);
	$engineDB->sanitize = FALSE;
	$sqlResult          = $engineDB->query($sql);
	
	if ($sqlResult['result']) {
		$row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC);
	
		$maxYear  = $row['year'];
		$maxMonth = $row['month'];
	}
	
	$monthStart = strtotime($minYear."-".$minMonth."-01");
	$monthEnd   = strtotime('-1 second',strtotime('+1 month',strtotime($maxYear."-".$maxMonth."-01")));
	
	$dateRange = date("F Y",$monthStart).' - '.date("F Y",$monthEnd);
	$linkToAll = NULL;

	$numDays = 0;
	$maxNumDays = 0;
	for($i=$monthStart; $i<$monthEnd; $i=strtotime('+1 month',$i)) {
		$numDays += date('t',$i);

		if ($maxNumDays < date('t',$i)) {
			$maxNumDays = date('t',$i);
		}
	}
	
}
else {

	$monthStart = strtotime($year."-".$month."-01");
	$monthEnd   = strtotime('-1 second',strtotime('+1 month',$monthStart));

	$dateRange = date("F Y",$monthStart);
	$linkToAll = '<a href="'.$_SERVER['PHP_SELF'].'?p='.$page.'">View All</a>';
	
	if ($year == date("Y") && $month == date("m")) {
		$numDays = date("d"); // if current month, use today as number of days in month
	}
	else {
		$numDays = date('t',$monthStart);
	}

	$maxNumDays = $numDays;

}

$numHours = $numDays * 24;


$sql = sprintf("SELECT SUM(mobilevisits) AS totalMobileVisits, SUM(nonmobilevisits) AS totalNonmobileVisits, SUM(mobilehits) AS totalMobileHits, SUM(nonmobilehits) AS totalNonmobileHits, MAX(mobilehits) AS maxMobileHits, MAX(nonmobilehits) AS maxNonmobileHits FROM %s WHERE UNIX_TIMESTAMP(CONCAT(year,'-',month,'-',day,' 00:00:00'))>='%s' AND UNIX_TIMESTAMP(CONCAT(year,IF(month<10,CONCAT('0',month),month),IF(day<10,CONCAT('0',day),day)))<'%s' AND resource='%s'",
	$engineDB->escape("logHits"),
	$engineDB->escape($monthStart),
	$engineDB->escape($monthEnd),
	$engineDB->escape($page)
	);
$engineDB->sanitize = FALSE;
$sqlResult          = $engineDB->query($sql);

if ($sqlResult['result']) {
	$row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC);
	
	$totalMobileVisits       = $row['totalMobileVisits'];
	$totalNonmobileVisits    = $row['totalNonmobileVisits'];
	$totalMobileHits         = $row['totalMobileHits'];
	$totalNonmobileHits      = $row['totalNonmobileHits'];
	$maxMobileHitsPerHour    = $row['maxMobileHits'];
	$maxNonmobileHitsPerHour = $row['maxNonmobileHits'];
}


$maxMobileVisits        = 0;
$maxNonmobileVisits     = 0;
$maxMobileHitsPerDay    = 0;
$maxNonmobileHitsPerDay = 0;

$sql = sprintf("SELECT SUM(mobilevisits) AS mobilevisits, SUM(nonmobilevisits) AS nonmobilevisits, SUM(mobilehits) AS mobilehits, SUM(nonmobilehits) AS nonmobilehits FROM %s WHERE UNIX_TIMESTAMP(CONCAT(year,'-',month,'-',day,' 00:00:00'))>='%s' AND UNIX_TIMESTAMP(CONCAT(year,IF(month<10,CONCAT('0',month),month),IF(day<10,CONCAT('0',day),day)))<'%s' AND resource='%s' GROUP BY day",
	$engineDB->escape("logHits"),
	$engineDB->escape($monthStart),
	$engineDB->escape($monthEnd),
	$engineDB->escape($page)
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
		
	}
}
?>
<!-- Page Content Goes Below This Line -->

<h1>Detailed Stats</h1>

<h2>Page:  <?= htmlSanitize($page) ?></h2>
<h2>Range: <?= $dateRange.(!isnull($linkToAll)?(" (".$linkToAll.")"):"") ?></h2>

<br />

<a name="monthlyStats"></a>
<table>
 	<thead>
		<tr>
			<th colspan="3" class="sorttable_nosort">Monthly Statistics for "<?= htmlSanitize($page) ?>"<br /><?= $dateRange ?></th>
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
	</tbody>
</table>

<br /><br />

<a name="dailyStats"></a>
<table class="sortable">
	<thead>
 		<tr>
			<th colspan="9" class="sorttable_nosort">Daily Statistics for "<?= htmlSanitize($page) ?>"<br /><?= $dateRange ?></th>
		</tr>
		<tr>
			<th rowspan="2">Day</th>
			<th colspan="4" class="sorttable_nosort">Hits</th>
			<th colspan="4" class="sorttable_nosort">Visits</th>
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
		
		$totalVisits = 0;
		$totalHits   = 0;
		
		for ($i=1; $i <= $maxNumDays; $i++) {
			
			$sql = sprintf("SELECT SUM(mobilevisits) AS mobilevisits, SUM(nonmobilevisits) AS nonmobilevisits, SUM(mobilehits) AS mobilehits, SUM(nonmobilehits) AS nonmobilehits FROM %s WHERE UNIX_TIMESTAMP(CONCAT(year,IF(month<10,CONCAT('0',month),month),IF(day<10,CONCAT('0',day),day)))>='%s' AND UNIX_TIMESTAMP(CONCAT(year,IF(month<10,CONCAT('0',month),month),IF(day<10,CONCAT('0',day),day)))<'%s' AND day='%s' AND resource='%s'",
				$engineDB->escape("logHits"),
				$engineDB->escape($monthStart),
				$engineDB->escape($monthEnd),
				$engineDB->escape($i),
				$engineDB->escape($page)
				);
			$engineDB->sanitize = FALSE;
			$sqlResult          = $engineDB->query($sql);
			
			if ($sqlResult['result']) {
				$row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC);
				
				$mobilehits[$i]    = $row['mobilehits'];
				$nonmobilehits[$i] = $row['nonmobilehits'];
				$hits[$i] = $mobilehits[$i] + $nonmobilehits[$i];

				$mobilevisits[$i]    = $row['mobilevisits'];
				$nonmobilevisits[$i] = $row['nonmobilevisits'];
				$visits[$i] = $mobilevisits[$i] + $nonmobilevisits[$i];

				$totalHits   += $hits[$i];
				$totalVisits += $visits[$i];
			}
			
		}
		
		for ($i=1; $i <= $maxNumDays; $i++) {
			
			$mobilehits[$i]      = is_empty($mobilehits[$i])      ? 0 : $mobilehits[$i];
			$nonmobilehits[$i]   = is_empty($nonmobilehits[$i])   ? 0 : $nonmobilehits[$i];
			$hits[$i]            = is_empty($hits[$i])            ? 0 : $hits[$i];
			$mobilevisits[$i]    = is_empty($mobilevisits[$i])    ? 0 : $mobilevisits[$i];
			$nonmobilevisits[$i] = is_empty($nonmobilevisits[$i]) ? 0 : $nonmobilevisits[$i];
			$visits[$i]          = is_empty($visits[$i])          ? 0 : $visits[$i];
			
			$percentHits   = ($totalHits>0)   ? ($hits[$i]/$totalHits)     : 0;
			$percentVisits = ($totalVisits>0) ? ($visits[$i]/$totalVisits) : 0;

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
			<th colspan="7" class="sorttable_nosort">Hourly Statistics for "<?= htmlSanitize($page) ?>"<br /><?= $dateRange ?></th>
		</tr>
		<tr>
			<th rowspan="2">Hour</th>
			<th colspan="3" class="sorttable_nosort">Mobile Hits</th>
			<th colspan="3" class="sorttable_nosort">Non-Mobile Hits</th>
		</tr>
		<tr>
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
		
		$totalMobileHits    = 0;
		$totalNonmobileHits = 0;
		
		for ($i=0; $i < 24; $i++) {
			
			$sql = sprintf("SELECT SUM(mobilehits) AS totalMobileHits, SUM(nonmobilehits) AS totalNonmobileHits FROM %s WHERE UNIX_TIMESTAMP(CONCAT(year,IF(month<10,CONCAT('0',month),month),IF(day<10,CONCAT('0',day),day)))>='%s' AND UNIX_TIMESTAMP(CONCAT(year,IF(month<10,CONCAT('0',month),month),IF(day<10,CONCAT('0',day),day)))<'%s' AND hour='%s' AND resource='%s'",
				$engineDB->escape("logHits"),
				$engineDB->escape($monthStart),
				$engineDB->escape($monthEnd),
				$engineDB->escape($i),
				$engineDB->escape($page)
				);
			$engineDB->sanitize = FALSE;
			$sqlResult          = $engineDB->query($sql);
			
			if ($sqlResult['result']) {
				$row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC);
				
				$totalMobileHits    += $mobileHits[$i]    = $row['totalMobileHits'];
				$totalNonmobileHits += $nonmobileHits[$i] = $row['totalNonmobileHits'];
				
			}
			
		}
		
		for ($i=0; $i < 24; $i++) {
			
			$mobileHits[$i]    = is_empty($mobileHits[$i])    ? 0 : $mobileHits[$i];
			$nonmobileHits[$i] = is_empty($nonmobileHits[$i]) ? 0 : $nonmobileHits[$i];
			
			$percentMobile    = ($totalMobileHits>0)    ? ($mobileHits[$i]/$totalMobileHits)       : 0;
			$percentNonmobile = ($totalNonmobileHits>0) ? ($nonmobileHits[$i]/$totalNonmobileHits) : 0;

			print "<tr>";
			print "<td>".$i."</td>";
			print "<td>".round($mobileHits[$i]/24)."</td>";
			print "<td>".$mobileHits[$i]."</td>";
			print "<td>".number_format($percentMobile*100,2)."%</td>";
			print "<td>".round($nonmobileHits[$i]/24)."</td>";
			print "<td>".$nonmobileHits[$i]."</td>";
			print "<td>".number_format($percentNonmobile*100,2)."%</td>";
			print "</tr>";
			
		}
		?>
	</tbody>
</table>

<br /><br />


<?
$sql = sprintf("SELECT SUM(mobilehits) AS totalMobileHits, SUM(nonmobilehits) AS totalNonmobileHits, COUNT(DISTINCT referrer) AS totalURLs FROM %s WHERE UNIX_TIMESTAMP(CONCAT(year,IF(month<10,CONCAT('0',month),month),'01'))>='%s' AND UNIX_TIMESTAMP(CONCAT(year,IF(month<10,CONCAT('0',month),month),'01'))<'%s' AND referrer!='NULL' AND url='%s'",
	$engineDB->escape("logURLs"),
	$engineDB->escape($monthStart),
	$engineDB->escape($monthEnd),
	$engineDB->escape($page)
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
				<?= 'Top 10 of <a href="allReferringPages.php?y='.$year.'&m='.$month.'&p='.$page.'">'.$totalURLs.' Referring Pages</a> for "'.htmlSanitize($page).'"<br />'.$dateRange ?>
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
		$sql = sprintf("SELECT SUM(mobilehits) AS mobilehits, SUM(nonmobilehits) AS nonmobilehits, referrer FROM %s WHERE UNIX_TIMESTAMP(CONCAT(year,IF(month<10,CONCAT('0',month),month),'01'))>='%s' AND UNIX_TIMESTAMP(CONCAT(year,IF(month<10,CONCAT('0',month),month),'01'))<'%s' AND referrer!='NULL' AND url='%s' GROUP BY referrer ORDER BY SUM(mobilehits+nonmobilehits) DESC LIMIT 10",
			$engineDB->escape("logURLs"),
			$engineDB->escape($monthStart),
			$engineDB->escape($monthEnd),
			$engineDB->escape($page)
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

$sql = sprintf("SELECT browser, nonHuman, SUM(onCampusCount) AS onCampusCount, SUM(offCampusCount) AS offCampusCount, (SUM(onCampusCount)+SUM(offCampusCount)) AS total FROM %s WHERE UNIX_TIMESTAMP(CONCAT(year,IF(month<10,CONCAT('0',month),month),'01'))>='%s' AND UNIX_TIMESTAMP(CONCAT(year,IF(month<10,CONCAT('0',month),month),'01'))<'%s' AND resource='%s' GROUP BY browser ORDER BY total DESC",
	$engineDB->escape("logBrowsers"),
	$engineDB->escape($monthStart),
	$engineDB->escape($monthEnd),
	$engineDB->escape($page)
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
					<?= 'Top 10 of <a href="allBrowsers.php?y='.$year.'&m='.$month.'&p='.$page.'">'.count($output).' Browsers</a> for "'.htmlSanitize($page).'"<br />'.$dateRange ?>
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

$sql = sprintf("SELECT os, nonHuman, SUM(onCampusCount) AS onCampusCount, SUM(offCampusCount) AS offCampusCount, (SUM(onCampusCount)+SUM(offCampusCount)) AS total FROM %s WHERE UNIX_TIMESTAMP(CONCAT(year,IF(month<10,CONCAT('0',month),month),'01'))>='%s' AND UNIX_TIMESTAMP(CONCAT(year,IF(month<10,CONCAT('0',month),month),'01'))<'%s' AND resource='%s' GROUP BY os ORDER BY total DESC",
	$engineDB->escape("logBrowsers"),
	$engineDB->escape($monthStart),
	$engineDB->escape($monthEnd),
	$engineDB->escape($page)
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
					<?= 'Top 5 of <a href="allOSs.php?y='.$year.'&m='.$month.'&p='.$page.'">'.count($output).' Operating Systems</a> for "'.htmlSanitize($page).'"<br />'.$dateRange ?>
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
