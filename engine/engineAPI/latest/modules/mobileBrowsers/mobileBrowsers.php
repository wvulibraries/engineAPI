<?php
/**
 * EngineAPI mobileBrowsers module
 * @package EngineAPI\modules\mobileBrowsers
 */
class mobileBrowsers {
	/**
	 * Store the UA results for parsing
	 * @var string
	 */
	private static $uaResult;

	/**
	 * Class constructor
	 * @return bool
	 */
	private static function construct() {
		if(!isnull(self::$uaResult) && self::$uaResult !== FALSE) return(TRUE);
		require_once(dirname(__FILE__)."/tobie-ua-parser-php/UAParser.php");
		self::$uaResult = UA::parse();

		if(self::$uaResult === FALSE) return(FALSE);
		return(TRUE);
	}

	/**
	 * Returns TRUE if the UserAgent is a mobile browser
	 * @return bool
	 */
	public static function isMobileBrowser() {
		self::construct();
		if(isnull(self::$uaResult) || self::$uaResult === FALSE) return(NULL);
		return self::$uaResult->isMobile;
	}

	/**
	 * Returns TRUE if the UserAgent is a tablet browser
	 * @return bool
	 */
	public static function isTabletBrowser() {
		self::construct();
		if(isnull(self::$uaResult) || self::$uaResult === FALSE) return(NULL);
		return self::$uaResult->isTablet;
	}

	/**
	 * Returns what kind of devide the UserAgent is
	 * If valid UA, returns 'tablet', 'mobile', or 'desktop'
	 *
	 * @return mixed
	 */
	public static function deviceIs() {
		self::construct();
		if (self::$uaResult === FALSE || isnull(self::$uaResult)) return(self::$uaResult);
		switch(true){
			case self::$uaResult->isMobile:
				return "tablet";
			case self::$uaResult->isTablet:
				return "mobile";
			default:
				return "desktop";
		}
	}

	/**
	 * Returns debug text for display
	 *
	 * @return string
	 */
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