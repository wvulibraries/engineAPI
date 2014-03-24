<?php

class time {
	
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

}

?>