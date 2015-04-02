<?php

class router {

	private static $instance = NULL;
	private $definedRoutes   = array();	
	private $serverURI       = NULL;

	private function __construct($uri=NULL) {
		$this->setServerURI();

		if (!isnull($uri)) {
			$this->defineRoute($uri);
		}
	}

	/**
	 * Returns router instance
	 * @param string $uri
	 * @return self
	 */
	public static function getInstance($uri=NULL) {
		if (!isset(self::$instance)) {
			$c = __CLASS__;
            self::$instance = new $c($uri);
        }

        return self::$instance;
	}

	/**
	 * reset the router instance.
	 * @return bool TRUE
	 */
	public static function resetInstance() {
		if (isset(self::$instance)) {
			self::$instance = NULL; 
		}

		return TRUE;
	}

	/**
	 * Define a new route that can be matched on
	 * @param  string $uri      complete url, from document root. Can contain variables, and validation requirements. 
	 *                          example:
	 *                          /users/edit/{ID=integer}
	 * @param  callable|string $callback A callable to bind to this uri
	 * @return bool           true on success, false otherwise
	 */
	public function defineRoute($uri,$callback=NULL) {

		// If $callback is not null, then it must be callable or point to a file
		if(!isnull($callback)){
			if(!is_callable($callback)){
				if(!is_string($callback) || !is_file($callback)) return FALSE;
			}
		}
		if (!isnull($callback) && !is_callable($callback) && !is_file($callback)) return FALSE;

		$route             = array();
		$route['rule']     = $this->parseURI($uri);
		$route['callback'] = $callback;

		$this->definedRoutes[] = $route;

		return TRUE;

	}

	/**
	 * returns a parsed uri from the redirect
	 * @param string $uri URI to parse and set as serverURI, optional, if null system uses $_SERVER value
	 * @return array|bool See parseURI for the return array. If non-strong passed in for $URI returns FALSE
	 */
	public function setServerURI($uri=NULL) {

		if (isnull($uri)) {
			$this->serverURI = $this->parseURI($this->getURI());
		}
		else {

			if (!is_string($uri)) return FALSE;

			$this->serverURI = $uri;
		}

		return $this->serverURI;
	}

	/**
	 * Returns the parsed serverURI
	 * @return array See parseURI for the return array
	 */
	public function getServerURI() {
		return $this->serverURI;
	}

	/**
	 * does the server uri match one of the defined routes?
	 * @return bool TRUE on match, FALSE otherwise
	 */
	public function match() {

		if ($this->matchRoute() !== FALSE) {
			return TRUE;
		}
		else {
			return FALSE;
		}

	}

	/**
	 * Determine which defined route we are using based on the server URI
	 * @return array [type] [description]
	 */
	private function matchRoute() {

		if (is_empty($this->definedRoutes)) return FALSE;

		foreach ($this->definedRoutes as $definedRoute) {

			if ($definedRoute['rule']['count'] != $this->serverURI['count']) continue;

			foreach ($definedRoute['rule']['items'] as $I=>$V) {

				// we are skipping variables here, if we don't have a match, 
				// we shouldn't be populating variables
				if ($V['variable'] !== FALSE) continue;

				// If the path's do not match, we skip to the next defined route
				if ($V['path'] != $this->serverURI['items'][$I]['path']) {
					continue 2;
				}

			}

			return $definedRoute;

		}

		return FALSE;

	}

	private function validateVariable($item,$variable) {

		if (isset($variable['validation'])) {

			$validate = new validate;

			if (!$validate->isValidMethod($variable['validation'])) {
				errorHandle::newError(__METHOD__."() - Invalid validation method defined.", errorHandle::DEBUG);
				return FALSE;
			}

			if ($variable['validation'] != "regexp" && $validate->$variable['validation']($item) === FALSE) {
				return FALSE;
			}
			else if (isset($variable['regex']) && $validate->$variable['validation']($variable['regex'],$item) === FALSE) {
				return FALSE;
			}
		}

		return TRUE;

	}

	/**
	 * Return the variables array
	 * @return array [description]
	 */
	public function getVariables() {

		if (($route = $this->matchRoute()) === FALSE) return FALSE;

		$variables = array();
		foreach ($route['rule']['items'] as $I=>$item) {
			if ($item['variable'] === FALSE) continue;

			if ($this->validateVariable($this->serverURI['items'][$I]['path'],$item['variable']) === FALSE) {
				errorHandle::newError(__METHOD__."() - Validation Error.", errorHandle::DEBUG);
				return FALSE;
			}

			$variables[$item['variable']['name']] = $this->serverURI['items'][$I]['path'];
		}

		return $variables;

	}

	/**
	 * For routing with callbacks. The parsed ServerURI and variables are passed to the callback function
	 * 
	 * @return mixed from callback function
	 */
	public function route() {

		if (($route = $this->matchRoute()) === FALSE) return FALSE;

		$variables = $this->getVariables();

		if(is_callable($route['callback'])){
			return call_user_func($route['callback'], $this->serverURI,$variables);
		}else{
			echo file_get_contents($route['callback']);
			return TRUE;
		}
	}

	/**
	 * determine if a string represents a variable
	 * @param  string  $item string to be tested
	 * @return bool       TRUE if the string could be a variable, false otherwise
	 */
	private function is_variable($item) {

		if (preg_match('/^{(.+?)}$/',$item)) {
			return TRUE;
		}

		return FALSE;

	}

	/**
	 * Parse the provided variable string, {varName[=validation]}
	 * @param  string $item variable string
	 * @return array  $variable['name']
	 *                $variable['validation']
	 *                $variable['regex']
	 */
	private function parseVariable($item) {
		
		if (!$this->is_variable($item)) return FALSE;

		$item = str_replace("{", "", $item);
		$item = str_replace("}", "", $item);
		$item = explode("=",$item);

		$variable['name'] = $item[0];

		$validate = validate::getInstance();

		if (isset($item[1]) && !is_empty($item[1]) && $validate->isValidMethod($item[1])) {
			$variable['validation'] = $item[1];
		}
		else if (isset($item[1]) && !is_empty($item[1])) {
			$variable['validation'] = "regexp";
			$variable['regex']      = $item[1];
		}

		return $variable;

	}


	/**
	 * Splits the given URI by "/" accounting for "/" inside variable {} blocks
	 * Parses variables into name and value according to parseVariable() method.
	 * 
	 * @param  string $uri URI to parse
	 * @return array      $parsedArray['URI']    = original URI
	 *                    $parsedArracy['count'] = how many 'pieces' to the path in the URL
	 *                    $parsedArray['items']  = array('path' => the uri element, 'variable' = parseVariable return) 
	 */
	private function parseURI($uri) {

		// Splits on forward slashes, '/', unless they are in a {} block
		// does not support nested {}
		$items = preg_split("/\/(?![^{]*})/",$uri);

		if (is_empty($items[0])) array_shift($items);

		// If the last item is a GET string, blank it
		$lastItem = $items[ sizeof($items)-1 ];
		if (!is_empty($lastItem) && $lastItem[0] == '?'){
			$items[ sizeof($items)-1 ] = '';
		}

		if (is_empty($items)) return FALSE;

		$parsedURI          = array();
		$parsedURI['URI']   = $uri;
		$parsedURI['count'] = count($items);

		foreach ($items as $item) {

			$variable = $this->parseVariable($item);

			$parsedURI['items'][] = array('path' => $item, 'variable' => $variable);
		}

		return $parsedURI;

	}

	private function getURI() {
		return (isset($_SERVER['REDIRECT_SCRIPT_URL']))?$_SERVER['REDIRECT_SCRIPT_URL']:$_SERVER['REQUEST_URI'];	
	}

}
