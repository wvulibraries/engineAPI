<?
$time = explode(' ', microtime());
$time = $time[1] + $time[0];
$start = $time;

include("header.php");
?>

<!-- Page Content Goes Above This Line -->

<?
$year  = (isset($engine->cleanGet['MYSQL']['y'])&&!is_empty($engine->cleanGet['MYSQL']['y']))?$engine->cleanGet['MYSQL']['y']:NULL;
$month = (isset($engine->cleanGet['MYSQL']['m'])&&!is_empty($engine->cleanGet['MYSQL']['m']))?$engine->cleanGet['MYSQL']['m']:NULL;
$page  = (isset($engine->cleanGet['MYSQL']['p'])&&!is_empty($engine->cleanGet['MYSQL']['p']))?$engine->cleanGet['MYSQL']['p']:NULL;

$sqlSite = is_empty(sessionGet("engineStatsSite")) ? NULL : "WHERE site='".sessionGet("engineStatsSite")."'";

if (isnull($year) || isnull($month)) {

	$sql = sprintf("SELECT year,month FROM %s %s ORDER BY year ASC, month ASC LIMIT 1",
		$engineDB->escape("logHits"),
		$sqlSite
		);
	$engineDB->sanitize = FALSE;
	$sqlResult          = $engineDB->query($sql);
	
	if ($sqlResult['result']) {
		$row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC);
	
		$minYear  = $row['year'];
		$minMonth = $row['month'];
	}
	
	$sql = sprintf("SELECT year,month FROM %s %s ORDER BY year DESC, month DESC LIMIT 1",
		$engineDB->escape("logHits"),
		$sqlSite
		);
	$engineDB->sanitize = FALSE;
	$sqlResult          = $engineDB->query($sql);
	
	if ($sqlResult['result']) {
		$row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC);
	
		$maxYear  = $row['year'];
		$maxMonth = $row['month'];
	}
	
	$monthStart = strtotime($minYear."-".$minMonth."-01");
	$monthEnd   = strtotime('-1 second +1 month',strtotime($maxYear."-".$maxMonth."-01"));
	
	$dateRange = date("F Y",$monthStart).' - '.date("F Y",$monthEnd);
	
}
else {

	$monthStart = strtotime($year."-".$month."-01");
	$monthEnd   = strtotime('-1 second +1 month',$monthStart);

	$dateRange = date("F Y",$monthStart);

}

$sqlPage = !isnull($page)?("AND resource='".$page."'"):NULL;
$sqlSite = is_empty(sessionGet("engineStatsSite")) ? NULL : "site='".sessionGet("engineStatsSite")."' AND ";

$output     = array();
$totalCount = 0;

$sql = sprintf("SELECT os, nonHuman, SUM(onCampusCount) AS onCampusCount, SUM(offCampusCount) AS offCampusCount, (SUM(onCampusCount)+SUM(offCampusCount)) AS total FROM %s WHERE %s UNIX_TIMESTAMP(CONCAT(year,'-',month,'-01 00:00:00'))>='%s' AND UNIX_TIMESTAMP(CONCAT(year,'-',month,'-01 00:00:00'))<'%s' %s GROUP BY os ORDER BY total DESC",
	$engineDB->escape("logBrowsers"),
	$sqlSite,
	$engineDB->escape($monthStart),
	$engineDB->escape($monthEnd),
	$sqlPage
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
					<?= 'Top Operating Systems '.(!isnull($page)?'for "'.htmlSanitize($page).'"':' ').'<br />'.date("F Y",$monthStart) ?>
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
				
			}
		}
		?>
	</tbody>
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
