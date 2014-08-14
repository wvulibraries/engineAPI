<?php

class templates {

	/**
	 * The currently selected template
	 * @var string
	 */
	private static $template = "";

	private static $moduleTemplateEngine = array();

	private $enginevars;

	function __construct() {
		$this->set_enginevars(enginevars::getInstance());

		if (!is_empty($this->enginevars->get("templateDefault"))) {
			self::load($this->enginevars->get("templateDefault"));
		}


	}

	public function set_enginevars($enginevars) {
		$this->enginevars = $enginevars;
	}

	/**
	 * sets the current template
	 * @param  string $template template to be loaded. Must be the directory name of the 
	 *                          template
	 * @return BOOL           	TRUE on success, FALSE otherwise.
	 */
	public static function load($template) {
		$enginevars = enginevars::getInstance();
		
		if (file_exists($enginevars->get("tempDir").DIRECTORY_SEPARATOR.$template)) {

			self::$template = $enginevars->get("tempDir").DIRECTORY_SEPARATOR.$template;
			$enginevars->set("currentTemplate", self::$template);
			return TRUE;

		}

		return FALSE;
		
	}

	/**
	 * returns the current template
	 * @return [type] [description]
	 */
	public static function currentTemplate() {
		return self::$template;
	}
	
	/**
	 * returns the name of the current tample
	 * @return string name of the current template
	 */
	public static function name() {
		return basename(self::$template);
	}

	/**
	 * includes the specified file from the template
	 * @param  string $file file to be included. May be "header" or "footer" to include these
	 *                      required template files
	 * @return BOOL       [description]
	 */
	public static function display($file) {

		switch($file) {
			case "header":
				$file = "templateHeader.php";
				break;
			case "footer":
				$file = "templateFooter.php";
				break;
		}

		$file = self::$template.DIRECTORY_SEPARATOR.$file;

		if (is_readable($file)) {
			include $file;
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Define Template Object Pattern
	 *
	 * @param string $pattern
	 * @param string $function
	 * @param string $object
	 * @return bool Always TRUE
	 */
	public static function defTempPatterns($pattern,$function,$object,$class=NULL) {
		$class            = (is_string($class))?$class:get_class($object);
		$temp             = array();
		$temp['pattern']  = $pattern;
		$temp['function'] = $function;
		$temp['object']   = $object;
		self::$moduleTemplateEngine[$class][] = $temp;
		return(TRUE);
	}

	/**
	 * Redefine Template Object Pattern
	 *
	 * @see self::defTempPatterns()
	 * @param string $oldPattern
	 * @param string $newPattern
	 * @param string $function
	 * @param string $object
	 * @return bool Always TRUE
	 */
	public static function reDefTempPattern($oldPattern,$newPattern,$function,$object) {
		foreach (self::$moduleTemplateEngine as $class=>$V) {
			foreach (self::$moduleTemplateEngine[$class] as $I => $plugin) {
				if ($plugin['pattern'] == $oldPattern) {
					unset(self::$moduleTemplateEngine[$class][$I]);
					break;
				}
			}
		}
		self::defTempPatterns($newPattern,$function,$object);
		return TRUE;
	}

	/**
	 * Um...
	 * This function should be combined with defTempPatterns
	 * if $pattern, $function, $class already exist the object should be updated
	 *
	 * @param string $pattern
	 * @param string $function
	 * @param string $object
	 * @return bool Always TRUE
	 */
	public static function reDefTempPatternObject($pattern,$function,$object) {
		$class = get_class($object);
		foreach (self::$moduleTemplateEngine[$class] as $I => $plugin) {
			if ($plugin['pattern'] == $pattern) {
				self::$moduleTemplateEngine[$class][$I]['object'] = $object;
			}
		}
		return TRUE;
	}

	/**
	 * Retrieve Template Object
	 *
	 * @param string $className
	 * @return mixed
	 */
	public static function retTempObj($className) {
		return self::$moduleTemplateEngine[$className][0]['object'];
	}

	/**
	 * Returns the moduleTemplateEngine array for processing
	 * @return array $moduleTemplateEngine
	 */
	public static function getTemplatePatterns() {
		return self::$moduleTemplateEngine;
	}

}

?>