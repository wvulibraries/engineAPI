<?php

class time {
	
	public function convert($time,$precision=FALSE) {
	
		if (is_int($time) || is_float($time)) return $this->toTime($time,$precision);
		if (is_string($time)) return $this->toSeconds($time,$precision);
	
		return FALSE;
	}
	
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