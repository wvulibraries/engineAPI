<?php

class time {
	
	/**
	 * converts given input to either seconds or human readable time string.
	 * @param  integer|string  $time      if time is an integer, returns human 
	 *                                    readable string. If time is string returns 
	 *                                    seconds since midnight.
	 * @param  boolean|string $precision See description of toSeconds and/or toTime
	 * @return int|float|string          See description of toSeconds and/or toTime  
	 */
	public function convert($time,$precision=FALSE) {
	
		if (is_int($time) || is_float($time)) return $this->toTime($time,$precision);
		if (is_string($time)) return $this->toSeconds($time,$precision);
	
		return FALSE;
	}
	
	/**
	 * converts human readable time to seconds. Must be able to be parsed by date_parse
	 * 24:00 is converted to 12am of the SAME day (hour zero) not 12AM of the following day. If 
	 * 12am of the following day is required get time for 11:59:59 and add 1 second to result. 
	 *
	 * @param  string  $string    time description as string
	 * @param  boolean $precision maintains fractional seconds if TRUE
	 * @return int|float             time in seconds since midnight
	 */
	public function toSeconds($string,$precision=FALSE) {
		
		$timeInfo = date_parse($string);

		if ($timeInfo['hour'] == 24) { 
			$timeInfo['warning_count'] = 0;
			$timeInfo['hour']          = 0;
		}
		
		if ($timeInfo['error_count'] > 0 || $timeInfo['warning_count'] > 0) {
			return FALSE;
		} 
		
		if ($timeInfo['hour'] === FALSE || $timeInfo['minute'] === FALSE) {
			return FALSE;
		}
		
		$seconds = ($timeInfo['hour'] * 60 * 60) +  ($timeInfo['minute'] * 60);
		$seconds = (is_int($timeInfo['second']))?$seconds + $timeInfo['second']:$seconds;
		$seconds = ($precision === TRUE && !is_bool($timeInfo['fraction']))?$seconds + $timeInfo['fraction']:$seconds;
		
		return $seconds;
		
	}
	
	/**
	 * Converts seconds since midnight into human readable string. 
	 * @param  integer $seconds     seconds since midnight
	 * @param  boolean|string $tweelveHour If true, returns 12 hour time, if false (default) returns 24 hour time. If a string, expected to be valud date() format string, returns result
	 * @return string               gmdate() return. 
	 */
	public function toTime($seconds, $tweelveHour = FALSE) {
		
		if ($tweelveHour === TRUE) {
			$convert = "g:i:sa";
		}
		else if ($tweelveHour === FALSE) {
			$convert = "H:i:s";
		}
		else {
			$convert = $tweelveHour;
		}

		return gmdate($convert,$seconds);
		
	}

}

?>