<?php

class mobileBrowsers {

	private static $uaResult = NULL;
	
	private static function construct() {
		
		if (!isnull(self::$uaResult) && self::$uaResult !== FALSE) {
			return(TRUE);
		}
		
		require_once(dirname(__FILE__)."/dmolsen-ua-parser-php/UAParser.php");
		self::$uaResult = UA::parse();
		
		if (self::$uaResult === FALSE) {
			return(FALSE);
		}
		
		return(TRUE);
	}

	public static function isMobileBrowser() {
		
		self::construct();

		if (isnull(self::$uaResult) || self::$uaResult === FALSE) {
			return(NULL);
		}

		return self::$uaResult->isMobile;
	}
	
	public static function isTabletBrowser() {
		
		self::construct();
		
		if (isnull(self::$uaResult) || self::$uaResult === FALSE) {
			return(NULL);
		}

		return self::$uaResult->isTablet;
	}
	
	public static function deviceIs() {
		
		self::construct();
			
		if (self::$uaResult === FALSE || isnull(self::$uaResult)) {
			return(self::$uaResult);
		}
		
		$mobile = self::$uaResult->isMobile;
		$tablet = self::$uaResult->isTablet;
		
		if ($tablet === TRUE) {
			return("tablet");
		}
		if ($mobile === TRUE) {
			return("mobile");
		}
		
		return("desktop");
		
	}
	
	public static function prettyPrint() {
		
		self::construct();
		
		if (isnull(self::$uaResult) || self::$uaResult === FALSE) {
			return(errorHandle::errorMsg("Browser not recognized."));
		}
		
		$output  = "";
		$output .= "<table>";
		foreach(self::$uaResult as $key => $value) {
			$output .= '<tr>';
			$output .= '<td>'.$key."</td><td> ".$value."</td>";
			$output .= '</tr>';
		}
		$output .=  "</table>";
		
		return($output);
		
	}
}

?>