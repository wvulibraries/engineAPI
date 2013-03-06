<?php
/**
 * EngineAPI state module
 * @package EngineAPI\modules\state
 */
class state {
	/**
	 * Name of the "thing" calling state
	 * @var string
	 */
	private $name = NULL;
	/**
	 * ID under name in the session
	 * @var int
	 */
	private $id = NULL;
	/**
	 * States and the functions that they should run
	 * @var array
	 */
	private $states = NULL;
	private $stateVarName = "stateModule_state";

	/**
	 * All of the variables and metadata that we will be storing in the session
	 * @todo this doesn't appear to be used
	 * @var array
	 */
	private $vars = array();

	/**
	 * @param string $name
	 * @param int $id
	 */
	function __construct($name,$id=NULL) {

		$this->name = $name;
		$this->id   = $this->genID($id);

	}
	
	/**
	 * Set a variable in the session
	 *
	 * @todo Why is this public?
	 * @param string $var
	 * @param mixed $data
	 *        If data is null, reserves the spot in the session.
	 * @return bool
	 */
	public function setVariable($var,$data = NULL) {
	
		$varArray = $this->genVarArray($var);
		return(sessionSet($varArray,$data));
		
	}
	
	/**
	 * Returns the data stored in $var from the session
	 *
	 * @todo Why is this public?
	 * @param $var
	 * @return mixed|null
	 */
	public function getVariable($var) {

		$varArray = $this->genVarArray($var);
		return(sessionGet($varArray));
		
	}
	
	/**
	 * Sets the internal states array to the passes array
	 *
	 * @param array $states
	 *        An array of states like
	 *        "statename" => reference_to_function
	 * @return bool
	 */
	public function setStates($states) {
		if ($this->states = $states) {
			return(TRUE);
		}
		return(FALSE);
	}
	
	/**
	 * Set the current state of the application
	 * Useful for flow control that is out of the scope of simple linear operations
	 *
	 * @param string $state
	 */
	public function setCurrentState($state) {
		$this->setVariable($this->stateVarName,$state);
	}

	/**
	 * Clears the current state
	 *
	 * @return bool
	 */
	public function resetCurrentState() {
		$varArray = $this->genVarArray($this->stateVarName);
		return(sessionDelete($varArray));
	}

	/**
	 * Gets the current state
	 *
	 * @return mixed|null
	 */
	public function getCurrentState() {
		return($this->getVariable($this->stateVarName));
	}
	
	/**
	 * Executes the function assigned to the current state
	 * Returns whatever the callback function returns, false if that function does not exist
	 *
	 * @return bool|mixed
	 */
	public function execute() {
		
		$prevState = $this->getCurrentState();
		if (is_null($prevState)) {
			$currentState = array_getFirstIndex($this->states);
		}
		else {
			$currentState = array_nextIndex($this->states,$prevState);
		}
		
		$this->setCurrentState($currentState);

		if (!isset($this->states[$currentState]) || !functionExists($this->states[$currentState])) {
			return(FALSE);
		}

		$output = call_user_func($this->states[$currentState]);

		return($output);
		
	}

	/**
	 * Generate a unique id
	 *
	 * @param int $id
	 * @return int
	 */
	private function genID($id) {
		$id = (is_null($id))?time():$id;
		return($id);
	}

	/**
	 * Gets the current ID
	 *
	 * @return int
	 */
	public function getID() {
		return($this->id);
	}
	
	/**
	 * Generates the array that gets passes to sessionSet or sessionGet for setting/retrieving variables
	 *
	 * @todo Doesn't actually generate an array???
	 * @param $var
	 * @return string
	 */
	private function genVarArray($var) {
		
		// sending back a string, the array was getting replaced incorrectly
		
		// $varArray = array();
		// 	
		// $varArray[] = $this->name;
		// $varArray[] = $this->id;
		// $varArray[] = $var;
		
		$sessionVar = $this->name."_".$this->id."_".$var;
		
		return($sessionVar);
	}
	
}

?>