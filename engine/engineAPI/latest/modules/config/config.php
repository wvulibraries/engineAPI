<?php
/**
 * EngineAPI config module
 * @package EngineAPI\modules\config
 */
class config {

	const NULL_VALUE = '%eapi%1ee6ba19c95e25f677e7963c6ce293b4%api%';

	/**
	 * @var array The array of variables being stored
	 */
	protected $variables = array();

	public function __construct($filename=NULL){
		if($filename) self::loadConfig($filename);
	}

	/**
	 * Load a config file and return the variables
	 * @param string $file
	 * @return array
	 */
	public static function loadFile($file) {
		switch(pathinfo($file, PATHINFO_EXTENSION)){
			case 'php':
				// Store variables before including $file
				$varsBefore = array_keys(get_defined_vars());

				// Bring in the file
				require $file;

				// Store variables after including $file
				$varsAfter  = array_keys(get_defined_vars());

				// Remove 'varsBefore' from the list since we're only using it as a helper
				unset($varsAfter[ array_search('varsBefore', $varsAfter) ]);

				// Return the differences
				return compact(array_diff($varsAfter, $varsBefore));
				break;

			case 'yaml':
				// TODO
				return array();
				break;

			case 'xml':
				// TODO
				return array();
				break;
		}
		return NULL;
	}

	/**
	 * Load config from file or array
	 *
	 * This will recursivly merge with the current config allowing config items to be overwritten
	 * Can be given filepath or a raw array
	 *
	 * @param string|array $config
	 * @return bool
	 */
	public function loadConfig($config){
		try{
			// If we're given a filename, load it
			if (is_string($config)) {
				$filename = $config;

				// Make sure $filename is an actual file
				if (!is_readable($filename)) throw new Exception("Given string '$config' is not a valid file path!");

				// Load the file
				$config = self::loadFile($filename);
				if (isnull($config)) throw new Exception("Failed to load config from '$filename'!");
			}

			// If we don't have an array, abort!
			if (!is_array($config)) throw new Exception("Unsupported input! (must be filepath or array)");

			// Merge the given config with the current config
			$this->variables = array_merge_recursive_overwrite($this->variables, $config);
			return TRUE;

		}catch(Exception $e){
			errorHandle::newError(__METHOD__."() ".$e->getMessage(), errorHandle::DEBUG);
			return FALSE;
		}
	}

	/**
	 * Create a config object
	 * @return self
	 */
	public static function getInstance() {
		return new self;
	}

	/**
	 * Returns whether a variable is set or not
	 * @param string $name
	 * @return bool
	 */
	public function is_set($name) {
		// Check if variable exists
		if (isset($this->variables[$name])) {
			// If set, return true
			return TRUE;
		}

		// Not set, return false
		return FALSE;
	}

	/**
	 * Set the value of a variable
	 * @param string $name  The variable to set
	 * @param string $value The value being set
	 * @param bool   $null  Whether to set a null value (default: false)
	 * @return bool
	 */
	public function set($name, $value, $null=FALSE) {
		// If $name is an array of more than 1 item, treat it as an array
		if (is_array($name) === TRUE && count($name) > 1) {
			$arrayLen = count($name);
			$count    = 0;

			// Loop through the array, and set the variables
			foreach ($name as $V) {
				$count++;
				if ($count == 1) {
					$this->variables[$V] = array();
					$prevTemp = &$this->variables[$V];
				}
				else if ($count == $arrayLen) {
					if ($prevTemp[$V] = $value) {
						return TRUE;
					}
				}
				else {
					$prevTemp[$V] = array();
					$prevTemp = &$prevTemp[$V];
				}
			}

			return TRUE;
		}

		// If $value is null, and $null is true, store as constant NULL_VALUE
		if (isnull($value) && $null === TRUE) {
			$this->variables[$name] = self::NULL_VALUE;
			return TRUE;
		}

		// Set the variable
		if (isset($value)) {
			$this->variables[$name] = $value;
			return TRUE;
		}

		// Return false on failure
		return FALSE;
	}

	/**
	 * Get the value of a variable
	 * @param string $name    The variable to get
	 * @param string $default The default value, if not set (default: '')
	 * @return string
	 */
	public function get($name, $default='') {
		/*
		 * @TODO: private ACLs need to be put into place
		 * should only return a type if it is called from self:: or from the
		 * correct class
		 */

		// If $name is an array, check to see if an exact match exists
		if (is_array($name)) {
			$arrayLen = count($name);
			$count    = 0;

			foreach ($name as $V) {
				$count++;
				// First iteration
				if ($count == 1) {
					if (isset($this->variables[$V])) {
						$prevTemp = &$this->variables[$V];
					}
					else {
						return NULL;
					}
				}
				// All but first iteration
				else {
					if (!isset($prevTemp[$V])) {
						return NULL;
					}
					else {
						$prevTemp = &$prevTemp[$V];
					}

					if ($count == $arrayLen) {
						return $prevTemp;
					}
				}
			}

			// $name array not found, return default value
			return $default;
		}

		// Check if variable exists
		if (array_key_exists($name, $this->variables)) {
			// If the value is the NULL_VALUE constant, replace it with PHP null
			if ($this->variables[$name] == self::NULL_VALUE) {
				return NULL;
			}

			// Return the variable
			return $this->variables[$name];
		}

		// Variable doesn't exist, return default value
		return $default;
	}

	/**
	 * Remove a variable
	 * @param string The name of the variable to remove
	 * @return bool
	 */
	public function remove($name) {
		// Check if variable exists
		if (array_key_exists($name, $this->variables)) {
			// Remove variable and return true
			unset($this->variables[$name]);
			return TRUE;
		}

		// Variable doesn't exist, return false
		return FALSE;
	}

	/**
	 * Get or set a variable
	 * If no $value is passed, get the variable; if $value is passed, set the variable
	 * @param string $name  The variable to get/set
	 * @param string $value If provided, the value to set
	 * @param bool   $null  Whether to set a null value (default: false)
	 * @return bool|string
	 */
	public function variable($name, $value=NULL, $null=FALSE) {
		// Get variable if only $name is passed
		if (isnull($value) && $null === FALSE) {
			return $this->get($name);
		}

		// Set variable
		return $this->set($name, $value, $null);
	}

	/**
	 * Return all of the variables set
	 * @return array
	 */
	public function export() {
		return $this->variables;
	}

}
?>
