<?php 

class config {
	
	private static $variables = array();

	public function __construct($engineDir,$site="default") {

		// setup private config variables 
		require_once $engineDir."/config/defaultPrivate.php";
		if($site != "default" && $site != "defaultPrivate"){
			$siteConfigFile = $engineDir."/config/".$site."Private.php";
			require_once $siteConfigFile;
		}
		self::$variables['private'] = $engineVarsPrivate;
		unset($engineVarsPrivate);

		// setup $engineVars
		require_once $engineDir."/config/default.php";
		if($site != "default" && $site != "defaultPrivate"){
			$siteConfigFile = $engineDir."/config/".$site.".php";
			require_once $siteConfigFile;
		}
		self::$variables['engine'] = $engineVars;
		unset($engineVars);

		self::$variables['local'] = array();

		self::set("engine","engineDir",$engineDir);

	}

	public static function isset($type,$name) {

		if (isset(self::$variables[$type][$name])) return TRUE;

		return FALSE;

	}

	public static function set($type,$name,$value,$null=FALSE) {

		if (is_array($name) === TRUE && count($variable) > 1) {
			$arrayLen = count($name);
			$count    = 0;

			foreach ( $name as $V ) { 
				$count++;
				if ($count == 1) { 
					self::$variables[$type][$V] = array(); 
					$prevTemp = &self::$variables[$type][$V]; 
				} 
				else { 
					if ($count == $arrayLen) {
                                        // $prevTemp[$V] = $value;
						if ($prevTemp[$V] = $value) {
							return TRUE;
						}
					}
					else {
						$prevTemp[$V] = array(); 
						$prevTemp = &$prevTemp[$V]; 
					}
				} 
			}

			return TRUE;
		}

		if (isnull($value) && $null === TRUE) {
			self::$variables[$type][$name] = "%eapi%1ee6ba19c95e25f677e7963c6ce293b4%api%";
			return TRUE;
		}
		
		if(isset($value)) {
			self::$variables[$type][$name] = $value;
			return TRUE;
		}
		
		return FALSE;

	}

	public static function get($type,$name,$default="") {

		// @TODO private ACLs need to be put into place
		// @TODO should only return a type if it is called from self:: or from the correct 
		// 		 class

		if (is_array($name)) {
			$arrayLen = count($name);
			$count    = 0;

			foreach ( $name as $V ) { 
				$count++;
				if ($count == 1) { 
					if (isset(self::$variables[$type][$V])) {
						$prevTemp = &self::$variables[$type][$V];
					} 
					else {
						return(NULL);
					}

				} 
				else { 
					if (!isset($prevTemp[$V])) {
						return(NULL);
					}
					else {
						$prevTemp = &$prevTemp[$V]; 
					}
					if ($count == $arrayLen) {
						return($prevTemp);
					}
				} 
			}
			return $default;
		}

		if (array_key_exists($name,self::$variables[$type])) {
			if (self::$variables[$type][$name] == "%eapi%1ee6ba19c95e25f677e7963c6ce293b4%api%") {
				return NULL;
			}
			return self::$variables[$type][$name];
		}
		
		return $default;

	}

	public static function remove($type,$name) {
		
		if (array_key_exists($name,self::$variables[$type])) {
			unset(self::$variables[$type][$name]);
			return TRUE;
		}
		
		return FALSE ;
		
	}

	public static function variable($type,$name,$value=NULL,$null=FALSE) {
		if (isnull($value) && $null === FALSE) {
			return self::get($type,$name);
		}
		
		return self::add($type,$name,$value,$null);
	}

	public static function export($type) {

		if ($type == "private") return FALSE;

		return self::$variables[$type];
	}

}

?>