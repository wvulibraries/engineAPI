<?
global $engine;
global $engineDB;

if (isset($engine->cleanPost['HTML']['site'])) {

	if ($engine->cleanPost['HTML']['site'] == "all") {
		sessionSet("engineStatsSite",NULL);
	}
	else {
		sessionSet("engineStatsSite",$engine->cleanPost['HTML']['site']);
	}

}
?>

<form method="post">
	<select name="site">
		<option value="all">All Sites</option>
		<?
		$sql = sprintf("SELECT DISTINCT site FROM %s ORDER BY site",
			$engineDB->escape("logHits")
			);
		$engineDB->sanitize = FALSE;
		$sqlResult          = $engineDB->query($sql);
		
		if ($sqlResult['result']) {
			while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
				if (sessionGet("engineStatsSite") == $row['site']) {
					print '<option value="'.$row['site'].'" selected="selected">'.$row['site'].'</option>';
				}
				else {
					print '<option value="'.$row['site'].'">'.$row['site'].'</option>';
				}
			}
		}
		
		?>
	</select>

	{engine name="insertCSRF"}
	<input type="submit" name="submitChangeSite" value="Change Site" />
</form>
<hr />
