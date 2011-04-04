<?php

class stats {
	
	private $startTime = null;
	private $endTime   = null;
	
	public $stats = array();
	
	public $startTimeReadable = null;
	public $endTimeReadable   = null;
	
	function __construct($smon,$sday,$syear,$shour,$emon,$eday,$eyear,$ehour) {
		$this->startTime = mktime($shour,0,0,$smon,$sday,$syear);
		$this->endTime   = mktime($ehour,0,0,$emon,$eday,$eyear);
		
		global $engineDB;
		
		$query = sprintf("select * from log where date > %s and date < %s",
			$engineDB->escape($this->startTime),
			$engineDB->escape($this->endTime));
		
		$this->sanitize = FALSE;
		$results        = $engineDB->query($query);
	}
	
	function buildStatsArray($results) {
		while ($row = mysql_fetch_row($results)) {
			$this->stats["raw"][$row[0]]["id"]          = $row[0];
			$this->stats["raw"][$row[0]]["date"]        = $row[1];
			$this->stats["raw"][$row[0]]["ip"]          = $row[2];
			$this->stats["raw"][$row[0]]["referrer"]    = $row[3];
			$this->stats["raw"][$row[0]]["resource"]    = $row[4];
			$this->stats["raw"][$row[0]]["useragent"]   = $row[5];
			$this->stats["raw"][$row[0]]["function"]    = $row[6];
			$this->stats["raw"][$row[0]]["type"]        = $row[7];
			$this->stats["raw"][$row[0]]["message"]     = $row[8];
			$this->stats["raw"][$row[0]]["querystring"] = $row[9];
			
			if($row["type"] != "access") {
				continue;
			}
			
			// Total access
			$this->stats["counts"]["access"]["total"]++;
			
			// Total Count for each URL
			$this->stats["counts"]["resources"][$this->stats["raw"][$row[0]]["resource"]]["total"]++;
			
			// Length of array stats["counts"]["resources"][$row["resource"]]["unique"] Unique hits for each URL. 
			// Value of stats["counts"]["resources"][$row["resource"]]["unique"][$row["ip"]] == hits from each IP.
			$this->stats["counts"]["resources"][$this->stats["raw"][$row[0]]["resource"]]["unique"][$this->stats["raw"][$row[0]]["ip"]]++;
			
		}
		
		return;
	}
	
}

?>