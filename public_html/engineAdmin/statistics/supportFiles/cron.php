<?
// Disable php timeout settings
ini_set("max_execution_time", "0");
ini_set("max_input_time", "0");
set_time_limit(0);

$time = explode(' ', microtime());
$time = $time[1] + $time[0];
$startTime = $time;

// Launch engine
$engineDir = "/home/library/phpincludes/engineAPI/engine";
include($engineDir ."/engine.php");
$engine = new EngineCMS();

print "Initializing variables...\n";
ob_flush();

// Read previous stopping point
$sql = sprintf("SELECT value FROM %s WHERE name='%s' LIMIT 1",
	$engineDB->escape("engineConfig"),
	$engineDB->escape("lastLogProcessed")
	);
$engineDB->sanitize = FALSE;
$sqlResult          = $engineDB->query($sql);
$row                = mysql_fetch_array($sqlResult['result'], MYSQL_NUM);

$lastRowProcessed = $row[0];
// $lastRowProcessed = '2525000'; // for testing

// Find latest log entry
$sql = sprintf("SELECT ID FROM %s ORDER BY ID DESC LIMIT 1",
	$engineDB->escape("log")
	);
$engineDB->sanitize = FALSE;
$sqlResult          = $engineDB->query($sql);
$row                = mysql_fetch_array($sqlResult['result'], MYSQL_NUM);

$latestLogEntry = $row[0]; // used in sql to make sure all processing stops at same place
$currentTime    = time();  // used in loops to make sure all processing stops at the same place


// Find date of first log entry after $lastRowProcessed
$sql = sprintf("SELECT date FROM %s WHERE ID>'%s' ORDER BY ID ASC LIMIT 1",
	$engineDB->escape("log"),
	$engineDB->escape($lastRowProcessed)
	);
$engineDB->sanitize = FALSE;
$sqlResult          = $engineDB->query($sql);
$row                = mysql_fetch_array($sqlResult['result'], MYSQL_NUM);

$dateNextLogEntry = $row[0];
$start            = strtotime(date("Y-m-01 00:00:00",$dateNextLogEntry));


// Get list of sites
$sql = sprintf("SELECT DISTINCT site FROM %s WHERE date>='%s' AND ID<='%s'",
	$engineDB->escape("log"),
	$engineDB->escape($start),
	$engineDB->escape($latestLogEntry)
	);
$engineDB->sanitize = FALSE;
$sqlResult          = $engineDB->query($sql);

$sites = array();
if ($sqlResult['result']) {
	while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
		$sites[] = $row['site'];
	}
}


// 
// Begin processing useragents
// 
print "Processing user agents...\n";
ob_flush();

$bc = new Browscap('/tmp/browsecap_cache/');
$useragentStr = array();
$useragents   = array();

$sql = sprintf("SELECT DISTINCT useragent FROM %s WHERE date>='%s' AND ID<='%s'",
	$engineDB->escape("log"),
	$engineDB->escape($start),
	$engineDB->escape($latestLogEntry)
	);
$engineDB->sanitize = FALSE;
$sqlResult          = $engineDB->query($sql);

if ($sqlResult['result']) {
	while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
		$row['useragent'] = trim($row['useragent']);
		$browser = $bc->getBrowser($row['useragent']);
		
		$nonHuman = 0;
		if ($browser->Crawler==1 || $browser->isSyndicationReader==1) {
			$nonHuman = 1;
		}
		
		if (!isset($browser->Parent)) {
			$browser->Parent = "Unknown";
			$nonHuman = 1;
		}
		
		if (!isset($browser->Platform) || $browser->Platform == 'unknown') {
			$browser->Platform = "Unknown";
		}
		
		if (is_empty($browser->isMobileDevice)) {
			$browser->isMobileDevice = 0;
		}

		$useragentStr[] = $row['useragent'];
		$index = array_pop(array_keys($useragentStr));

		$useragents[$index]['browser']  = $browser->Parent;
		$useragents[$index]['os']       = $browser->Platform;
		$useragents[$index]['isMobile'] = $browser->isMobileDevice;
		$useragents[$index]['nonHuman'] = $nonHuman;
	}
}
// 
// End processing useragents
// 



// 
// Begin processing overall visits, hits, and pages
// 
print "Processing overall visits, hits, and pages...\n";
ob_flush();

foreach ($sites as $site) {
	for ($start=strtotime(date("Y-m-d H:00:00",$dateNextLogEntry)); $start<$currentTime; $start+=3600) {
		
		$end = strtotime('+1 hour',$start);
		
		$mobileHits    = 0;
		$nonmobileHits = 0;
		$mobileips    = array();
		$nonmobileips = array();
		$ips          = array();
		$resources    = array();
		$data         = array();

		$sql = sprintf("SELECT useragent, ip, TRIM(TRAILING '/' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(resource,'?',1),'index.',1)) AS resource FROM %s WHERE site='%s' AND date>='%s' AND date<'%s' AND ID<='%s' GROUP BY resource",
			$engineDB->escape("log"),
			$engineDB->escape($site),
			$engineDB->escape($start),
			$engineDB->escape($end),
			$engineDB->escape($latestLogEntry)
			);
		$engineDB->sanitize = FALSE;
		$sqlResult          = $engineDB->query($sql);
		
		if (!$sqlResult['result']) {
			continue;
		}


		while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {

			$index = array_search($row['useragent'], $useragentStr);
			if ($index === FALSE) {
				continue;
			}

			// if ($useragents[$index]['nonHuman'] == 1) {
			// 	continue;
			// }

			if ($useragents[$index]['isMobile'] == 1) {
				$mobileHits++;
				if (!in_array($row['ip'],$ips)) {
					$mobileips[] = $row['ip'];
				}
			}
			else {
				$nonmobileHits++;
				if (!in_array($row['ip'],$ips)) {
					$nonmobileips[] = $row['ip'];
				}
			}

			if (!in_array($row['ip'],$ips)) {
				$ips[] = $row['ip'];
			}

			$data[$row['resource']]['mobileVisits']    = count($mobileips);
			$data[$row['resource']]['nonmobileVisits'] = count($nonmobileips);
			$data[$row['resource']]['pages']           = count($resources);
			$data[$row['resource']]['mobileHits']      = $mobileHits;
			$data[$row['resource']]['nonmobileHits']   = $nonmobileHits;

		}

		foreach ($data as $resource => $V) {

			// Check to see if record exists to determine whether to update or insert
			$sql = sprintf("SELECT ID FROM %s WHERE site='%s' AND year='%s' AND month='%s' AND day='%s' AND hour='%s' AND resource='%s'",
				$engineDB->escape("logHits"),
				$engineDB->escape($site),
				date("Y",$start),
				date("m",$start),
				date("d",$start),
				date("H",$start),
				$engineDB->escape($resource)
				);
			$engineDB->sanitize = FALSE;
			$sqlResult          = $engineDB->query($sql);
			
			if ($sqlResult['affectedRows'] > 0) {
				
				$row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC);
				
				$sql = sprintf("UPDATE %s SET site='%s', mobilevisits='%s', nonmobilevisits='%s', mobilehits='%s', nonmobilehits='%s' WHERE ID='%s'",
					$engineDB->escape("logHits"),
					$engineDB->escape($site),
					$engineDB->escape($V['mobileVisits']),
					$engineDB->escape($V['nonmobileVisits']),
					$engineDB->escape($V['mobileHits']),
					$engineDB->escape($V['nonmobileHits']),
					$engineDB->escape($row['ID'])
					);

				$engineDB->sanitize = FALSE;
				$sqlResult2         = $engineDB->query($sql);
				
			}
			else {
				
				$sql = sprintf("INSERT INTO %s (site, year, month, day, hour, mobilevisits, nonmobilevisits, mobilehits, nonmobilehits, resource) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
					$engineDB->escape("logHits"),
					$engineDB->escape($site),
					date("Y",$start),
					date("m",$start),
					date("d",$start),
					date("H",$start),
					$engineDB->escape($V['mobileVisits']),
					$engineDB->escape($V['nonmobileVisits']),
					$engineDB->escape($V['mobileHits']),
					$engineDB->escape($V['nonmobileHits']),
					$engineDB->escape($resource)
					);
				$engineDB->sanitize = FALSE;
				$sqlResult2         = $engineDB->query($sql);
				
			}
			
		}
		
	}
}
// 
// End processing overall visits, hits, and pages
// 


// 
// Begin processing URLs, entry pages, and referring pages
// 
print "Processing URLs, entry pages, and referring pages...\n";
ob_flush();

foreach ($sites as $site) {
	for ($start=strtotime(date("Y-m-01 00:00:00",$dateNextLogEntry)); $start<$currentTime; $start=strtotime('+1 month',$start)) {
		
		$end = strtotime('+1 month',$start);
		
		// Delete any old records
		$sql = sprintf("DELETE FROM %s WHERE site='%s' AND year='%s' AND month='%s'",
			$engineDB->escape("logURLs"),
			$engineDB->escape($site),
			date("Y",$start),
			date("m",$start)
			);
		$engineDB->sanitize = FALSE;
		$sqlResult          = $engineDB->query($sql);
		
		// Create list from log table
		$sql = sprintf("SELECT useragent, referrer, COUNT(ID) AS hits, TRIM(TRAILING '/' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(resource,'?',1),'index.',1)) AS page FROM %s WHERE site='%s' AND date>='%s' AND date<'%s' AND ID<='%s' GROUP BY page, referrer, useragent ORDER BY hits DESC",
			$engineDB->escape("log"),
			$engineDB->escape($site),
			$engineDB->escape($start),
			$engineDB->escape($end),
			$engineDB->escape($latestLogEntry)
			);
		$engineDB->sanitize = FALSE;
		$sqlResult          = $engineDB->query($sql);
		
		if ($sqlResult['result']) {
			while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
				
				$index = array_search($row['useragent'], $useragentStr);
				if ($index === FALSE) {
					continue;
				}

				// if ($useragents[$index]['nonHuman'] == 1) {
				// 	continue;
				// }

				$mobileHits    = 0;
				$nonmobileHits = 0;

				if ($useragents[$index]['isMobile'] == 1) {
					$mobileHits += $row['hits'];
				}
				else {
					$nonmobileHits += $row['hits'];
				}

				// Insert List into condensed "logURLs" table
				$sql = sprintf("INSERT INTO %s (site, year, month, mobilehits, nonmobilehits, url, referrer) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s')",
					$engineDB->escape("logURLs"),
					$engineDB->escape($site),
					date("Y",$start),
					date("m",$start),
					$engineDB->escape($mobileHits),
					$engineDB->escape($nonmobileHits),
					$engineDB->escape($row['page']),
					$engineDB->escape($row['referrer'])
					);
				$engineDB->sanitize = FALSE;
				$sqlResult2         = $engineDB->query($sql);
		
			}
		}
		
	}
}
// 
// End processing URLs, entry pages, and referring pages
// 



// 
// Begin processing browsers
// 
print "Processing browsers and operating systems...\n";
ob_flush();

foreach ($sites as $site) {
	for ($start=strtotime(date("Y-m-01 00:00:00",$dateNextLogEntry)); $start<$currentTime; $start=strtotime('+1 month',$start)) {
		
		$end = strtotime('+1 month',$start);
		
		// Delete any old records
		$sql = sprintf("DELETE FROM %s WHERE site='%s' AND year='%s' AND month='%s'",
			$engineDB->escape("logBrowsers"),
			$engineDB->escape($site),
			date("Y",$start),
			date("m",$start)
			);
		$engineDB->sanitize = FALSE;
		$sqlResult          = $engineDB->query($sql);
		
		// Create list from log table
		$sql = sprintf("SELECT useragent, ip, TRIM(TRAILING '/' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(resource,'?',1),'index.',1)) AS resource FROM %s WHERE site='%s' AND date>='%s' AND date<'%s' AND ID<='%s'",
			$engineDB->escape("log"),
			$engineDB->escape($site),
			$engineDB->escape($start),
			$engineDB->escape($end),
			$engineDB->escape($latestLogEntry)
			);
		$engineDB->sanitize = FALSE;
		$sqlResult          = $engineDB->query($sql);
		
		if ($sqlResult['result']) {
			$list = array();
			while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
				
				$index = array_search($row['useragent'], $useragentStr);
				if ($index === FALSE) {
					continue;
				}

				if (!array_key_exists($row['resource'],$list)) {
					$list[$row['resource']] = array();
				}
				
				if (!array_key_exists($index,$list[$row['resource']])) {
					$list[$row['resource']][$index] = array();
					$list[$row['resource']][$index]['onCampus']  = 0;
					$list[$row['resource']][$index]['offCampus'] = 0;
				}
				
				if (onCampus($row['ip'])) {
					$list[$row['resource']][$index]['onCampus']++;
				}
				else {
					$list[$row['resource']][$index]['offCampus']++;
				}
				
				$list[$row['resource']][$index]['browser']  = $useragents[$index]['browser'];
				$list[$row['resource']][$index]['os']       = $useragents[$index]['os'];
				$list[$row['resource']][$index]['isMobile'] = $useragents[$index]['isMobile'];
				$list[$row['resource']][$index]['nonHuman'] = $useragents[$index]['nonHuman'];
				
			}
			
			foreach ($list as $resource => $userAgents) {
				foreach ($userAgents as $array) {
					
					// Insert List into condensed "logBrowsers" table
					$sql = sprintf("INSERT INTO %s (site, year, month, resource, browser, nonHuman, onCampusCount, offCampusCount, os, mobile) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
						$engineDB->escape("logBrowsers"),
						$engineDB->escape($site),
						date("Y",$start),
						date("m",$start),
						$engineDB->escape($resource),
						$engineDB->escape($array['browser']),
						$engineDB->escape($array['nonHuman']),
						$engineDB->escape($array['onCampus']),
						$engineDB->escape($array['offCampus']),
						$engineDB->escape($array['os']),
						$engineDB->escape($array['isMobile'])
						);
					$engineDB->sanitize = FALSE;
					$sqlResult          = $engineDB->query($sql);

				}
			}

					
		}
		
	}
}
// 
// End processing browsers
// 


// write last processed log entry
$sql = sprintf("UPDATE %s SET value='%s' WHERE name='%s' LIMIT 1",
	$engineDB->escape("engineConfig"),
	$latestLogEntry,
	$engineDB->escape("lastLogProcessed")
	);
$engineDB->sanitize = FALSE;
$sqlResult          = $engineDB->query($sql);

$time = explode(' ', microtime());
$time = $time[1] + $time[0];
$finish = $time;
$total_time = round(($finish - $startTime), 4);
print "Finished in ".$total_time." seconds.\n";
?>
