<?
$time = explode(' ', microtime());
$time = $time[1] + $time[0];
$start = $time;

include("header.php");
?>

<!-- Page Content Goes Below This Line -->

<table class="sortable">
	<thead>
		<tr>
			<th colspan="9" class="sorttable_nosort">Summary by Month</th>
		</tr>
		<tr>
			<th rowspan="3">Month</th>
			<th colspan="3" class="sorttable_nosort">Daily Avg</th>
			<th colspan="5" class="sorttable_nosort">Monthly Totals</th>
		</tr>
		<tr>
			<th rowspan="2" class="sorttable_numeric">Visits</th>
			<th rowspan="2" class="sorttable_numeric">Hits</th>
			<th rowspan="2" class="sorttable_numeric">Pages</th>
			<th colspan="2" class="sorttable_nosort">Mobile</th>
			<th colspan="2" class="sorttable_nosort">Non-Mobile</th>
			<th rowspan="2" class="sorttable_numeric">Pages</th>
		</tr>
		<tr>
			<th class="sorttable_numeric">Visits</th>
			<th class="sorttable_numeric">Hits</th>
			<th class="sorttable_numeric">Visits</th>
			<th class="sorttable_numeric">Hits</th>
		</tr>
	</thead>
	<tbody>
		<?
		$totalMobileVisits    = NULL;
		$totalNonmobileVisits = NULL;
		$totalMobileHits      = NULL;
		$totalNonmobileHits   = NULL;
		$totalPages           = NULL;
		$action = isset($engine->cleanGet['MYSQL']['action'])?$engine->cleanGet['MYSQL']['action']:'year';

		$minTime = strtotime('-1 year +1 month',strtotime(date("Y")."-".date("m")."-01"));

		if ($action == 'all') {
			$sql = sprintf("SELECT UNIX_TIMESTAMP(CONCAT(year,IF(month<10,CONCAT('0',month),month),IF(day<10,CONCAT('0',day),day))) AS minTime FROM %s ORDER BY year ASC, month ASC LIMIT 1",
				$engineDB->escape("logHits")
				);
			$engineDB->sanitize = FALSE;
			$sqlResult          = $engineDB->query($sql);
			
			if ($sqlResult['result']) {
				$row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC);
				$minTime = $row['minTime'];
			}
		}
		
		$monthStart = strtotime(date("Y")."-".date("m")."-01");
		$monthEnd   = strtotime('-1 second',strtotime('+1 month',$monthStart));

		while ($monthEnd >= $minTime) { 
			
			$year  = date('Y',$monthStart);
			$month = date('m',$monthStart);
			
			if ($year == date("Y") && $month == date("m")) {
				$numDays = date("d"); // if current month, use "today" as number of days in month
			}
			else {
				$numDays = date('t',$monthStart);
			}
			
			$sql = sprintf("SELECT SUM(mobilevisits) AS mobilevisits, SUM(nonmobilevisits) AS nonmobilevisits, SUM(mobilehits) AS mobilehits, SUM(nonmobilehits) AS nonmobilehits, COUNT(resource) AS pages FROM %s WHERE year='%s' AND month='%s'",
				$engineDB->escape("logHits"),
				$engineDB->escape($year),
				$engineDB->escape($month)
				);
			$engineDB->sanitize = FALSE;
			$sqlResult          = $engineDB->query($sql);
			
			if ($sqlResult['result']) {
				$row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC);
				
				$totalMobileVisits    += $mobileVisits    = $row['mobilevisits'];
				$totalNonmobileVisits += $nonmobileVisits = $row['nonmobilevisits'];
				$totalMobileHits      += $mobileHits      = $row['mobilehits'];
				$totalNonmobileHits   += $nonmobileHits   = $row['nonmobilehits'];
				$totalPages           += $pages           = $row['pages'];
			}
			
			if ($mobileHits > 0 || $nonmobileHits > 0) {
				
				print '<tr>';
				print '<td><a href="detailedMonth.php?y='.$year.'&m='.$month.'">'.date('M Y',$monthStart).'</a></td>';
				print '<td>'.round(($mobileVisits+$nonmobileVisits)/$numDays).'</td>';
				print '<td>'.round(($mobileHits+$nonmobileHits)/$numDays).'</td>';
				print '<td>'.round($pages/$numDays).'</td>';
				print '<td>'.$mobileVisits.'</td>';
				print '<td>'.$mobileHits.'</td>';
				print '<td>'.$nonmobileVisits.'</td>';
				print '<td>'.$nonmobileHits.'</td>';
				print '<td>'.$pages.'</td>';
				print '</tr>';
				
			}
			
			$monthStart = strtotime('-1 month',$monthStart);
			$monthEnd   = strtotime('-1 second +1 month',$monthStart);
			
		}
		?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="4">Totals</td>
			<td><?= $totalMobileVisits    ?></td>
			<td><?= $totalMobileHits      ?></td>
			<td><?= $totalNonmobileVisits ?></td>
			<td><?= $totalNonmobileHits   ?></td>
			<td><?= $totalPages           ?></td>
		</tr>
	</tfoot>
</table>

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
