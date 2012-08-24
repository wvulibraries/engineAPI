<?php

class state {
	
	private $name = NULL; // name of the "thing" calling state
	private $id   = NULL; // ID under name in the session
	
	private $states = NULL; // states and the functions that they should run
	private $stateVarName = "stateModule_state";
	
	private $vars   = array(); // all of the variables and metadata that we will be storing in the session 
	
	function __construct($name,$id=NULL) {

		$this->name = $name;
		$this->id   = $this->genID($id);

	}
	
	// if data is null, reserves the spot in the session. 
	public function setVariable($var,$data = NULL) {
	
		$varArray = $this->genVarArray($var);
		return(sessionSet($varArray,$data));
		
	}
	
	// returns the data stored in $var
	public function getVariable($var) {

		$varArray = $this->genVarArray($var);
		return(sessionGet($varArray));
		
	}
	
	/*
	$states is an array of 
	("statename" => reference_to_function)
	
	should be in the order of execution
	
	*/
	public function setStates($states) {
		if ($this->states = $states) {
			return(TRUE);
		}
		return(FALSE);
	}
	
	// Set the current state of the application to a specific state. Useful for flow control
	// that is out of the scope of simple linear operations
	public function setCurrentState($state) {
		$this->setVariable($this->stateVarName,$state);
	}
	
	public function resetCurrentState() {
		$varArray = $this->genVarArray($this->stateVarName);
		return(sessionDelete($varArray));
	}
	
	public function getCurrentState() {
		return($this->getVariable($this->stateVarName));
	}
	
	// returns whatever the callback function returns, false is that function does not exist
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
	
	private function genID($id) {
		$id = (is_null($id))?time():$id;
		return($id);
	}
	
	public function getID() {
		return($this->id);
	}
	
	// generates the array that gets passes to sessionSet or sessionGet 
	// for setting/retrieving variables
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