<?php

class config {

	const NULL_VALUE = '%eapi%1ee6ba19c95e25f677e7963c6ce293b4%api%';

	protected $variables = array();

	public function loadConfig($file) {
		// Store variables before including $file
		$varsBefore = array_keys(get_defined_vars());

		// Bring in the file
		require $file;

		// Store variables after including $file
		$varsAfter  = array_keys(get_defined_vars());

		// Return the differences
		return compact(array_diff($varsAfter, $varsBefore));
	}

	public static function getInstance() {
		return new self;
	}

	public function is_set($name) {
		// Check if variable exists
		if (isset($this->variables[$name])) {
			// If set, return true
			return TRUE;
		}

		// Not set, return false
		return FALSE;
	}

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

	public function variable($name, $value=NULL, $null=FALSE) {
		// Get variable if only $name is passed
		if (isnull($value) && $null === FALSE) {
			return $this->get($name);
		}

		// Set variable
		return $this->set($name, $value, $null);
	}

	public function export() {
		return $this->variables;
	}

}
?>
