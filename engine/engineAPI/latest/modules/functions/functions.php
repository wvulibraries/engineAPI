<?php

class functions {
	
	private static $functionExtensions = array();

	private static $classInstance;

	function __construct() {}

	public static function getInstance() {
		if (!isset(self::$classInstance)) { 
			self::$classInstance = new self();
		}

		return self::$classInstance;
	}

	/**
	 * Set function extensions
	 *
	 * @param string|array $function
	 * @param string|array $newFunction
	 * @param string $stage identifier of when this callback will be called. 
	 *                      Used by the calling function to allow the calling function to 
	 *                      have multiple Extensions. 
	 * @return bool
	 */
	public function setFunctionExtension($function,$newFunction,$stage="after") {

		$class       = (is_array($function))?$function[0]:NULL;
		$function    = (is_array($function))?$function[1]:$function;
		$newClass    = (is_array($newFunction))?$newFunction[0]:NULL;
		$newFunction = (is_array($newFunction))?$newFunction[1]:$newFunction;

		// check if the function/method exists

		if (isnull($newClass) && functionExists($newFunction) === FALSE) {
			return FALSE;
		}
		else if (isnull($newClass) && $newFunction == "recurseInsert") {
			// can't define the system recurseInsert as the function.
			return FALSE;
		}
		else if (!isnull($newClass) && functionExists($newClass,$newFunction) === FALSE) {
			return FALSE;
		}

		$functionIndex = $function.((isnull($class))?"":"::".$class);

		$temp             = array();
		$temp['class']    = $newClass;
		$temp['function'] = $newFunction;

		if (!isset(self::$functionExtensions[$functionIndex][$stage])) {
			self::$functionExtensions[$functionIndex][$stage] = array();
		}

		self::$functionExtensions[$functionIndex][$stage][] = $temp;

		return TRUE;
	}

	/**
	 * Get function extensions
	 *
	 * @param string $function
	 * @param string|null $class
	 * @return array|bool
	 */
	public function getFunctionExtension($function,$class=NULL) {
		$functionIndex = $function.((isnull($class))?"":"::".$class);
		if (array_key_exists($functionIndex,self::$functionExtensions)) {
			return self::$functionExtensions[$functionIndex];
		}
		return FALSE;
	}

	/**
	 * Execute something...
	 * @param string $function
	 * @param string $params
	 * @param string $stage identifier of when this callback will be called. 
	 *                      Used by the calling function to allow the calling function to 
	 *                      have multiple Extensions. 
	 * @return bool
	 */
	public function execFunctionExtension($function,$params,$stage="after") {

		if (!is_array($params)) {
			return FALSE;
		}

		$class     = (is_array($function))?$function[0]:NULL;
		$function  = (is_array($function))?$function[1]:$function;


		$functions = $this->getFunctionExtension($function,$class);

		if (!is_array($functions) || count($functions) < 1) {
			return FALSE;
		}

		if (!array_key_exists($stage, $functions)) {
			return FALSE;
		}

		$output = FALSE;



		// return FALSE;
		foreach($functions[$stage] as $I=>$function) {
			if (array_key_exists('class',$function) && !isnull($function['class'])) {
				$obj    = new $function['class'];
				$output = $obj->$function['function']($params);
			}
			else {
				$output = $function['function']($params);
			}
			if ($output !== FALSE) {
				break;
			}
		}

		return $output;

	}

}

?>