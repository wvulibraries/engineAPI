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
	$monthEnd   = strtotime('-1 second +1 month',strtotime($maxYear."-".$maxMonth."-01"));
	
	$dateRange = date("F Y",$monthStart).' - '.date("F Y",$monthEnd);
	
}
else {

	$monthStart = strtotime($year."-".$month."-01");
	$monthEnd   = strtotime('-1 second +1 month',$monthStart);

	$dateRange = date("F Y",$monthStart);

}

$sqlPage = !isnull($page)?("AND url='".$page."'"):NULL;


$sql = sprintf("SELECT SUM(mobilehits) AS totalMobileHits, SUM(nonmobilehits) AS totalNonmobileHits, COUNT(DISTINCT referrer) AS totalURLs FROM %s WHERE UNIX_TIMESTAMP(CONCAT(year,'-',month,'-01 00:00:00'))>='%s' AND UNIX_TIMESTAMP(CONCAT(year,'-',month,'-01 00:00:00'))<'%s' AND referrer!='NULL' %s",
	$engineDB->escape("logURLs"),
	$engineDB->escape($monthStart),
	$engineDB->escape($monthEnd),
	$sqlPage
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
				<?= 'Top Referring Pages '.(!isnull($page)?'for "'.htmlSanitize($page).'"':' ').'<br />'.date("F Y",$monthStart) ?>
			</th>
		</tr>
		<tr>
			<th rowspan="2">#</th>
			<th colspan="4">Hits</th>
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
		$sql = sprintf("SELECT SUM(mobilehits) AS mobilehits, SUM(nonmobilehits) AS nonmobilehits, referrer FROM %s WHERE UNIX_TIMESTAMP(CONCAT(year,'-',month,'-01 00:00:00'))>='%s' AND UNIX_TIMESTAMP(CONCAT(year,'-',month,'-01 00:00:00'))<'%s' AND referrer!='NULL' %s GROUP BY referrer ORDER BY SUM(mobilehits+nonmobilehits) DESC",
			$engineDB->escape("logURLs"),
			$engineDB->escape($monthStart),
			$engineDB->escape($monthEnd),
			$sqlPage
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
