<?php
/**
 * EngineAPI date module
 * Helper functions for engine template tags
 * @package EngineAPI\modules\date
 */
class date {
	public $pattern  = "/\{date\s+(.+?)\}/";
	public $function = "date::templateMatches";

	private $months = array(
				array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12),
				array("", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"),
				array("", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec")
			);

	function __construct() {
		templates::defTempPatterns($this->pattern,$this->function,$this);
	}

	/**
	 * Template Date
	 *
	 * @param $matches
	 *        Matches as passed by the template handler
	 *          - time: unix time, Option, current time if not provided
	 *          - format: Format string to pass to date()
	 * @return bool|string
	 */
	public static function templateMatches($matches) {
		$attPairs = attPairs($matches[1]);

		if (isset($attPairs['time'])) {
			if (!validate::getInstance()->integer($attPairs['time'])) {
				errorHandle::newError(__METHOD__."() - ".$attPairs['time'], errorHandle::DEBUG);
				return(FALSE);
			}
			return(date($attPairs['format'],$attPairs['time']));
		}

		return(date($attPairs['format']));
	}

	/** 
	 * $options is not html_sanitized. Assumed to be safe.
	 */
	private function buildAttributes($options) {

		$output = "";

		foreach ($options as $attr=>$value) {
			$output .= sprintf(' %s="%s" ',$attr,$value);
		}

		return $output;

	}

	public function getCurrentMonth() {

		$localtime = localtime(time(), true);
		return $localtime['tm_mon'] + 1;

	}

	public function getCurrentYear() {

		$localtime = localtime(time(), true);
		return $localtime['tm_year'] + 1900;

	}

	public function getCurrentDay() {
		
		$localtime = localtime(time(), true);
		return $localtime['tm_mday'];

	}

	public function getCurrentHour() {
		$localtime = localtime(time(), true);
		return $localtime['tm_hour'];
	}

	public function getCurrentMinute() {
		$localtime = localtime(time(), true);
		return $localtime['tm_min'] + 1;
	}

	/**
	 * Provides a select dropdown suitable for framing
	 * @param  int $monthAs 0 = render month as number, 1 = Month Spelled out, 2 short month
	 * @param  bool|int $selectCurrentMonth if true, ads a select attribute to the current month, If integer, selects that integer. 
	 * @param  array $options associative array, each index in the array becomes an attribute for the select box         
	 *                        with the value of the index being the value of the attribute
	 * 
	 * @return string
	 */
	public function dropdownMonthSelect($monthAs=0,$selectCurrentMonth=TRUE,$options=array()) {

		$currentMonth = ($selectCurrentMonth === TRUE)?$this->getCurrentMonth():$selectCurrentMonth;

		$output = sprintf('<select %s>',$this->buildAttributes($options));
		for($I=1;$I<=12;$I++) {
			$output .= sprintf('<option value="%s" %s>%s</option>',
				($I < 10)?"0".$I:$I,
				($I == $currentMonth)?"selected":"",
				$this->months[$monthAs][$I]);
		}
		$output .= "</select>";

		return $output;

	}

	public function dropdownDaySelect($selectCurrentDay=TRUE,$options=array()) {

		$currentDay = ($selectCurrentDay === TRUE)?$this->getCurrentDay():$selectCurrentDay;

		$output = sprintf('<select %s>',$this->buildAttributes($options));
		for ($I=1;$I<=31;$I++) {
			$output .= sprintf('<option value="%s" %s>%s</option>',
				($I < 10)?"0".$I:$I,
				($I == $currentDay)?"selected":"",
				$I
				);
		}
		$output .= "</select>";

		return $output;

	}

	/**
	 * returns a select dropdown of years, suitable for printing
	 * @param  integer $start             years from current to start the list at (can be negative)
	 * @param  integer $end               Years from current to end the list at (can be negative), must be greater than $start
	 * @param  boolean $selectCurrentYear Select the current year, if it is in the list
	 * @param  array   $options           associative array, each index in the array becomes an attribute for the select box         
	 *                                    with the value of the index being the value of the attribute
	 * @return string                     
	 */
	public function dropdownYearSelect($start=0,$end=1,$selectCurrentYear=TRUE,$options=array()) {

		$currentYear = ($selectCurrentYear === TRUE)?$this->getCurrentYear():$selectCurrentYear;
		$start = $currentYear + $start;
		$end   = $currentYear + $end;

		$output = sprintf('<select %s>',$this->buildAttributes($options));
		for ($I=$start;$I<=$end;$I++) {
			$output .= sprintf('<option value="%s" %s>%s</option>',
				$I,
				($I == $currentYear)?"selected":"",
				$I
				);
		}
		$output .= "</select>";

		return $output;

	}

	// $hour12 select 12 vs 24 hour time
	public function dropdownHourSelect($hour12=TRUE,$selectCurrentHour=TRUE,$options=array()) {

		$currentHour = ($selectCurrentHour === TRUE)?$this->getCurrentHour():$selectCurrentHour;

		$output = sprintf('<select %s>',$this->buildAttributes($options));
		for($I=0;$I<=23;$I++) {
			$output .= sprintf('<option value="%s" %s>%s</option>',
				($I < 10)?"0".$I:$I,
				($I == $currentHour)?"selected":"",
				($hour12 === FALSE)?$I:(($I==12)?"12pm":(($I>=13)?($I-12)."pm":(($I == 0)?"12am":$I."am"))));
		}
		$output .= "</select>";

		return $output;

	}

	public function dropdownMinuteSelect($increment=1,$selectCurrentMinute=TRUE,$options=array()) {

		$currentMinute = ($selectCurrentMinute === TRUE)?$this->getCurrentMinute():$selectCurrentMinute;

		$output = sprintf('<select %s>',$this->buildAttributes($options));
		for($I=0;$I<60;$I += $increment) {
			$output .= sprintf('<option value="%s" %s>%s</option>',
				($I < 10)?"0".$I:$I,
				($I == $currentMinute)?"selected":"",
				$I);
		}
		$output .= "</select>";

		return $output;

	}

	/**
	 * Generate an HTML date dropdown menu
	 *
	 * @param $attPairs
	 *          - formname:     The name of the form element. Will be usd 3 times followed by _month, _day, and _year
	 *          - makearray:    Add [] onto the end of the formname
	 *          - disabled:     Make the selects disabled
	 *          - prompts:      Include prompts
	 *          - separator:    String used to separator month/day/year values. defaults to " / "
	 *          - monthdformat: How the month is displayed. "month" or "mon" or "m". Default "m"
	 *          - monthvformat: How the month is valued. "month" or "mon" or "m". Default "m"
	 *          - startyear:    4 digit year, start of the year range. Default = 2000
	 *          - endyear:      4 digit year, end of the year range. Default = 2015
	 *          - defaultyear:  Year that is selected if no date is given. Default = current year
	 *          - setdate:      Unix time stamp to set the dropdown too
	 * @return string
	 *         The generated HTML
	 */
	public function dateDropDown($attPairs){
		$monArray = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
		$monthArray = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
		$mArray = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12");

		$localtime = localtime(time(), true);
		$cyear     = $localtime['tm_year'] + 1900;
		$cmon      = $localtime['tm_mon'] + 1;
		$cday      = $localtime['tm_mday'];


		// Set the Defaults
		if(!isset($attPairs['makearray'])) {
            $attPairs['makearray'] = FALSE;
		}

        if(!isset($attPairs['disabled'])){
            $attPairs['disabled'] = FALSE;
        }

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

		$output = sprintf('<select name="%s_month%s"%s>', $attPairs['formname'], ($attPairs['makearray'] ? '[]' : ''), ($attPairs['disabled'] ? ' disabled="disabled"' : ''));
        if($attPairs['prompts']) $output .= '<option>Month</option>';
		for($I=0;$I<=11;$I++) {
			$output .= "<option value=\"".$monValue[$I]."\"";
			$output .= ($smon == $monValue[$I])?" selected=\"selected\"":"";
			$output .= ">".$monDisplay[$I]."</option>";
		}
		$output .= "</select>";

		$output .= $separator;

        $output .= sprintf('<select name="%s_day%s"%s>', $attPairs['formname'], ($attPairs['makearray'] ? '[]' : ''), ($attPairs['disabled'] ? ' disabled="disabled"' : ''));
        if($attPairs['prompts']) $output .= '<option>Day</option>';
		for($I=1;$I<=31;$I++) {
			$output .= "<option value=\"$I\"";
			if (isset($sday)) {
				$output .= ($sday == $I)?"selected=\"selected\"":"";
			}
			$output .= ">$I</option>";
		}
		$output .= "</select>";

		$output .= $separator;

        $output .= sprintf('<select name="%s_year%s"%s>', $attPairs['formname'], ($attPairs['makearray'] ? '[]' : ''), ($attPairs['disabled'] ? ' disabled="disabled"' : ''));
        if($attPairs['prompts']) $output .= '<option>Year</option>';
		for($I=$startYear;$I<=$endYear;$I++) {
			$output .= "<option value=\"$I\"";
			if (isset($defaultYear) and !$attPairs['prompts']) {
				$output .= ($defaultYear == $I)?"selected=\"selected\"":"";
			}
			$output .= ">$I</option>";
		}
		$output .= "</select>";

		return($output);

	}

	/**
	 * Generate HTML time dropdown menu
	 *
	 * @param $attPairs
	 *   - formname:    The name of the form element. Will be usd 3 times followed by _hour, _minute, and _ampm
	 *   - makearray:   Add [] onto the end of the formname
	 *   - disabled:    Make the selects disabled
	 *   - prompts:     Include prompts
	 *   - separator:   String used to separator hour and minute values. defaults to " : "
	 *   - hourformat:  12 or 24 hour format. Default: "12"
	 *   - mininterval: How the minutes are spaced. Default is "1" (01,02,03,...) - "15" would be (00,15,30,45)
	 *   - settime:     Unix time stamp to set the dropdown too
	 * @return string
	 */
	public function timeDropDown($attPairs) {
		$localtime = localtime(time(), true);
		$chour     = $localtime['tm_hour'];
		$cmin      = $localtime['tm_min'];


		// Set the Defaults
        if(!isset($attPairs['makearray'])) {
            $attPairs['makearray'] = FALSE;
        }
        if(!isset($attPairs['disabled'])) {
            $attPairs['disabled'] = FALSE;
        }

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
		$sampm = "am";
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

        $output = sprintf('<select name="%s_hour%s"%s>', $attPairs['formname'], ($attPairs['makearray'] ? '[]' : ''), ($attPairs['disabled'] ? ' disabled="disabled"' : ''));
        if($attPairs['prompts']) $output .= '<option>Hour</option>';
		for($I=$startHour;$I<=$endHour;$I++) {
			$output .= "<option value=\"".$I."\"";
			if (isset($shour) and !$attPairs['prompts']) {
				$output .= ($shour == $I)?" selected=\"selected\"":"";
			}
			$output .= ">".(($I<10)?"0".$I:$I)."</option>";
		}
		$output .= "</select>";

		$output .= $separator;

        $output .= sprintf('<select name="%s_min%s"%s>', $attPairs['formname'], ($attPairs['makearray'] ? '[]' : ''), ($attPairs['disabled'] ? ' disabled="disabled"' : ''));
        if($attPairs['prompts']) $output .= '<option>Min</option>';
		for($I=0;$I<60;$I+=$mininterval) {
			if(isset($minArray[$I])){
				$output .= "<option value=\"$I\"";
				if (isset($smin) and !$attPairs['prompts']) {
					$output .= ($smin == $I)?"selected=\"selected\"":"";
				}
				$output .= ">$minArray[$I]</option>";
			}
		}
		$output .= "</select>";

		if($hourformat == 12) {
			$output .= "&nbsp;&nbsp;";

            $output .= sprintf('<select name="%s_ampm%s"%s>', $attPairs['formname'], ($attPairs['makearray'] ? '[]' : ''), ($attPairs['disabled'] ? ' disabled="disabled"' : ''));
            if($attPairs['prompts']) $output .= '<option></option>';
			$output .= "<option value=\"am\"".(($sampm == "am" and !$attPairs['prompts'])?" selected=\"selected\"":"").">am</option>";
			$output .= "<option value=\"pm\"".(($sampm == "pm" and !$attPairs['prompts'])?" selected=\"selected\"":"").">pm</option>";
			$output .= "</select>";
		}

		return($output);
	}
}
?>