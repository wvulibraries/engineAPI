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

	/**
	 * Class constructor
	 */
	public function __construct() {
	}

	/**
	 * Load a config file and return the variables
	 * @return array
	 */
	public function loadConfig($file) {
		$varsBefore = array_keys(get_defined_vars());
		require $file;
		$varsAfter  = array_keys(get_defined_vars());
		return compact(array_diff($varsAfter, $varsBefore));
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
		if (isset($this->variables[$name])) return TRUE;

		return FALSE;
	}

	/**
	 * Set the value of a variable
	 * @param string $name  The variable to set
	 * @param string $value The value being set
	 * @param bool   $null  Whether to set a null value (default: false)
	 * @return bool
	 */
	public function set($name,$value,$null=FALSE) {
		if (is_array($name) === TRUE && count($name) > 1) {
			$arrayLen = count($name);
			$count    = 0;

			foreach ($name as $V) {
				$count++;
				if ($count == 1) {
					$this->variables[$V] = array();
					$prevTemp = &$this->variables[$V];
				}
				else if ($count == $arrayLen) {
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

			return TRUE;
		}

		if (isnull($value) && $null === TRUE) {
			$this->variables[$name] = self::NULL_VALUE;
			return TRUE;
		}

		if (isset($value)) {
			$this->variables[$name] = $value;
			return TRUE;
		}

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

		if (is_array($name)) {
			$arrayLen = count($name);
			$count    = 0;

			foreach ($name as $V) {
				$count++;
				if ($count == 1) {
					if (isset($this->variables[$V])) {
						$prevTemp = &$this->variables[$V];
					}
					else {
						return NULL;
					}
				}
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

			// $name not found, return default value
			return $default;
		}

		if (array_key_exists($name, $this->variables)) {
			if ($this->variables[$name] == self::NULL_VALUE) {
				return NULL;
			}
			return $this->variables[$name];
		}

		// $name not found, return default value
		return $default;
	}

	/**
	 * Remove a variable
	 * @param string The name of the variable to remove
	 * @return bool
	 */
	public function remove($name) {
		if (array_key_exists($name, $this->variables)) {
			unset($this->variables[$name]);
			return TRUE;
		}

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
		if (isnull($value) && $null === FALSE) {
			return $this->get($name);
		}

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
