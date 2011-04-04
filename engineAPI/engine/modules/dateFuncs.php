<?php

// Template Date
// $attPairs['time'] == unix time, Option, current time if not provided
// $attPairs['format'] for date();
function tempDate($attPairs) {

	if (isset($attPairs['time'])) {
		return(date($attPairs['format'],$attPairs['time']));
	}

	return(date($attPairs['format']));
	
}

// formname : the name of the form element. Will be usd 3 times followed by _month, _day, and _year
// separator : string used to separator month/day/year values. defaults to " / "
// monthdformat : How the month is displayed. "month" or "mon" or "m". Default "m" 
// monthvformat : How the month is valued. "month" or "mon" or "m". Default "m" 
// startyear : 4 digit year, start of the year range. Default = 2000
// endyear : 4 digit year, end of the year range. Default = 2015
// defaultyear : year that is selected if no date is given. Default = current year
// setdate : unix time stamp to set the dropdown too
function dateDropDown($attPairs,$engine=null) {
	
	$monArray = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"); 
	$monthArray = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
	$mArray = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12");
	
	$localtime = localtime(time(), true);
	$cyear     = $localtime['tm_year'] + 1900;
	$cmon      = $localtime['tm_mon'] + 1;
	$cday      = $localtime['tm_mday']; 
	
	
	// Set the Defaults
	$separator = "&nbsp;/&nbsp;";
	if(!empty($attPairs['separator'])) {
		$separator = $attPairs['separator'];
	}
	
	$startYear = 2000;
	if(!empty($attPairs['startyear'])) {
		$startYear = $attPairs['startyear'];
	}
	
	$endYear = 2015;
	if(!empty($attPairs['endyear'])) {
		$endYear = $attPairs['endyear'];
	}
	
	$defaultYear = $cyear;
	if(!empty($attPairs['defaultyear'])) {
		$defaultYear = $attPairs['defaultyear'];
	}
	
	switch(empty($attPairs['monthdformat'])?"":$attPairs['monthdformat']) {

		case "mon":
		    $monDisplay = $monArray;
		    break;
		case "month":
		    $monDisplay = $monthArray;
		    break;
		case "m":
		default:
		    $monDisplay = $mArray;
	}
	
	switch(empty($attPairs['monthvformat'])?"":$attPairs['monthvformat']) {

		case "mon":
		    $monValue = $monArray;
		    break;
		case "month":
		    $monValue = $monthArray;
		    break;
		case "m":
		default:
		    $monValue = $mArray;
	}
	
	// Set up passed in values
	$smon = "";
	$sday = "";
	if(!empty($attPairs['setdate'])) {
		$setdate     = localtime($attPairs['setdate'], true);
		$sday        = $setdate['tm_mday'];
		$smon        = $setdate['tm_mon'] + 1;
		$defaultYear = $setdate['tm_year'] + 1900;
	}

	
	
	$output = "<select name=\"".$attPairs['formname']."_month\">";
	for($I=0;$I<=11;$I++) {
		$output .= "<option value=\"".$monValue[$I]."\"";
		$output .= ($smon == $monValue[$I])?" selected=\"selected\"":"";
		$output .= ">".$monDisplay[$I]."</option>";
	}
	$output .= "</select>";
	
	$output .= $separator; 
	
	$output .= "<select name=\"".$attPairs['formname']."_day\">";
	for($I=1;$I<=31;$I++) {
		$output .= "<option value=\"$I\"";
		if (isset($sday)) {
			$output .= ($sday == $I)?"selected=\"selected\"":"";
		}
		$output .= ">$I</option>";
	}
	$output .= "</select>";
	
	$output .= $separator;
	
	$output .= "<select name=\"".$attPairs['formname']."_year\">";
	for($I=$startYear;$I<=$endYear;$I++) {
		$output .= "<option value=\"$I\"";
		if (isset($defaultYear)) {
			$output .= ($defaultYear == $I)?"selected=\"selected\"":"";
		}
		$output .= ">$I</option>";
	}
	$output .= "</select>";
	
	return($output);
	
}


// formname : the name of the form element. Will be usd 3 times followed by _hour, _minute, and _ampm
// separator : string used to separator hour and minute values. defaults to " : "
// hourformat : 12 or 24 hour format. Default: "12"
// mininterval : How the minutes are spaced. Default is "1" (01,02,03,...) - "15" would be (00,15,30,45)
// settime : unix time stamp to set the dropdown too
function timeDropDown($attPairs,$engine=null) {
	
	$localtime = localtime(time(), true);
	$chour     = $localtime['tm_hour'];
	$cmin      = $localtime['tm_min'];
	
	
	// Set the Defaults
	$separator = "&nbsp;:&nbsp;";
	if(!empty($attPairs['separator'])) {
		$separator = $attPairs['separator'];
	}
	
	$hourformat = "12";
	if(!empty($attPairs['hourformat'])) {
		$hourformat = $attPairs['hourformat'];
	}
	
	$mininterval = "1";
	if(!empty($attPairs['mininterval'])) {
		$mininterval = $attPairs['mininterval'];
	}
	
	
	// Create Arrays
	if($hourformat == 12) {
		$startHour = 1;
		$endHour = 12;
	}
	else {
		$startHour = 0;
		$endHour = 23;
	}
	
	for($I=0;$I<60;$I++) {
		if($I % $mininterval == 0) {
			$minArray[$I] = (($I<10)?"0".$I:$I);
		}
	}
	
		
	// Set up passed in values
	$shour = "";
	$smin  = "";
	if(!empty($attPairs['settime'])) {
		$settime = localtime($attPairs['settime'], true);
		$shour   = $settime['tm_hour'];
		$smin    = $mininterval * round($settime['tm_min']/$mininterval);

		if($hourformat == 12) {
			if($shour > 12) {
				$shour -= 12;
				$sampm = "pm";
			}
			else {
				$sampm = "am";
			}
		}
	}
	
		
	
	$output = "<select name=\"".$attPairs['formname']."_hour\">";
	for($I=$startHour;$I<=$endHour;$I++) {
		$output .= "<option value=\"".$I."\"";
		if (isset($shour)) {
			$output .= ($shour == $I)?" selected=\"selected\"":"";
		}
		$output .= ">".(($I<10)?"0".$I:$I)."</option>";
	}
	$output .= "</select>";
	
	$output .= $separator; 
	
	$output .= "<select name=\"".$attPairs['formname']."_min\">";
	for($I=0;$I<60;$I+=$mininterval) {
		if(isset($minArray[$I])){
			$output .= "<option value=\"$I\"";
			if (isset($smin)) {
				$output .= ($smin == $I)?"selected=\"selected\"":"";
			}
			$output .= ">$minArray[$I]</option>";
		}
	}
	$output .= "</select>";
	
	if($hourformat == 12) {
		$output .= "&nbsp;&nbsp;";
			
		$output .= "<select name=\"".$attPairs['formname']."_ampm\">";
		$output .= "<option value=\"am\"".(($sampm == "am")?" selected=\"selected\"":"").">am</option>";
		$output .= "<option value=\"pm\"".(($sampm == "pm")?" selected=\"selected\"":"").">pm</option>";
		$output .= "</select>";
	}
	
	return($output);
	
}
?>