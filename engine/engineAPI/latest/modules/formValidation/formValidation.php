<?php
/**
 * EngineAPI formValidation Module
 * @package EngineAPI\modules\formValidation
 */
class formValidation {
	/**
	 * @var array
	 */
	private $fields = array();

	/**
	 * @var string
	 */
	private $type;

	/**
	 * The "type" in engine's cleanGet and cleanPort. RAW, HTML, MYSQL
	 * @var string
	 */
	public $sanType = "RAW"; // the "type" in engine's cleanGet and cleanPort. RAW, HTML, MYSQL

	/**
	 * when TRUE, validate will look at each POST and GET variable. If they are not defined it will return FALSE
	 * When true, even submit buttons and the like need to be defined. But, makes sure that nothing that isn't supposed to be passed in is.
	 * @var bool
	 */
	public $strict = TRUE;


	/**
	 * Class constructor
	 * @param string $type post,get
	 */
	function __construct($type=NULL) {

		$this->fields['get']  = array();
		$this->fields['post'] = array();
		$this->fields['all']  = array();

		if (lc($type) == "post") {
			$this->type = lc($type);
		}
		else if (lc($type) == "get") {
			$this->type = lc($type);
		}

	}

	function __destruct() {
	}

	/**
	 * Adds a field to the validation
	 *
	 * @param array $field
	 *   - type:     post or get
	 *   - var:      variable name
	 *   - validate: type of validation to perform. If set to NULL, does not perform validation
	 * @return bool
	 */
	public function addField($field) {

		if (!isset($field['type']) || (lc($field['type']) != "post" && lc($field['type']) != "get")) {
			errorHandle::newError(__METHOD__."() - type not set or invalid", errorHandle::DEBUG);
			return(FALSE);
		}

		if (!isset($field['var'])) {
			errorHandle::newError(__METHOD__."() - variable name not set", errorHandle::DEBUG);
			return(FALSE);
		}

		if (!array_key_exists('validate',$field)) {
			errorHandle::newError(__METHOD__."() - validate not set", errorHandle::DEBUG);
			return(FALSE);
		}

		if (lc($field['type']) == "get") {
			$field['type'] = "get";
			$this->fields['get'][$field['var']] = $field;
		}
		else if (lc($field['type']) == "post") {
			$field['type'] = "post";
			$this->fields['post'][$field['var']] = $field;
		}

		$this->fields["all"][$field['var']] = $field;


	}

	/**
	 * Do validation
	 *
	 * @param bool $bool
	 *        If TRUE, return is boolean for 'is valid'
	 *        else return is an array with specifics
	 * @return array|bool
	 */
	public function validate($bool=TRUE) {

		$engine = EngineAPI::Singleton();

		if (!$this->validateSanType()) {
			errorHandle::newError(__METHOD__."() - Invalid sanType", errorHandle::DEBUG);
			return(FALSE);
		}

		$inputs = array();
		if (isset($engine->cleanPost[$this->sanType])) {
			$inputs['post'] = $engine->cleanPost[$this->sanType];
		}
		if (isset($engine->cleanGet[$this->sanType])) {
			$inputs['get']  = $engine->cleanGet[$this->sanType];
		}

		if (!isnull($this->type) && !isset($inputs[$this->type])) {
			errorHandle::newError(__METHOD__."() - invalid type definition", errorHandle::DEBUG);
			return(FALSE);
		}

		$types = array();
		switch ($this->type) {
			case "post":
				if (isset($inputs['post'])) {
					$types[] = "post";
				}
				break;
			case "get";
				if (isset($inputs['get'])) {
					$types[] = "get";
				}
				break;
			default:
				if (isset($inputs['post'])) {
					$types[] = "post";
				}
				if (isset($inputs['get'])) {
					$types[] = "get";
				}
				break;
		}

		if (count($types) == 0) {
			return(FALSE);
		}

		$valid = array();
		foreach ($types as $I=>$type) {
			foreach ($inputs[$type] as $var=>$value) {

				if ($var == "engineCSRFCheck") {
					continue;
				}

				if (!isset($this->fields[$type][$var]) && $this->strict === TRUE) {
					if ($bool === TRUE) {
						return(FALSE);
					}

					$temp          = array();
					$temp["var"]   = $var;
					$temp["value"] = $value;
					$temp["valid"] = FALSE;
					$valid[]       = $temp;

					continue;
				}
				else if (!isset($this->fields[$type][$var]) && $this->strict === FALSE) {
					continue;
				}

				if (isnull($this->fields[$type][$var]['validate'])) {
					continue;
				}

				$return = FALSE;
				if (preg_match('/^\/(.+?)\/$/',$this->fields[$type][$var]['validate'])) {
					$return = call_user_func(array('validate','regexp'),$this->fields[$type][$var]['validate'],$value);
				}
				else {
					$return = call_user_func(array('validate', $this->fields[$type][$var]['validate']),$value);
				}

				if ($return === FALSE) {
					if ($bool === TRUE) {
						return(FALSE);
					}

					$temp          = array();
					$temp["var"]   = $var;
					$temp["value"] = $value;
					$temp["valid"] = FALSE;
					$valid[]       = $temp;

				}
			}
		}

		if (count($valid) == 0 && $bool === TRUE) {
			return(TRUE);
		}

		return($valid);

	}

	/**
	 * Unknown
	 *
	 * @return bool
	 */
	private function validateSanType() {
		$engine = EngineAPI::Singleton();

		if (isset($engine->cleanGet[$this->sanType]) || isset($engine->cleanPost[$this->sanType])) {
			return(TRUE);
		}

		return(FALSE);
	}

}

?>