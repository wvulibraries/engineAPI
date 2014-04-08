<?php

/*

router makes the assumption that there is a .htaccess in your application that looks
like this, where 'myapp' is replaced with the document root (URI) of your application:

<IfModule mod_rewrite.c>
RewriteEngine On

## recursively search parent dir
# if index.php is not found then
# forward to the parent directory of current URI
RewriteCond %{DOCUMENT_ROOT}/myapp/$1$2/index.php !-f
RewriteRule ^(.*?)([^/]+)/[^/]+/?$ /myapp/$1$2/ [L]

# if current index.php is found in parent dir then load it
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{DOCUMENT_ROOT}/myapp/$1index.php -f
RewriteRule ^(.*?)[^/]+/?$ /myapp/$1index.php [L]

</IfModule>

It recursively looks backwards for the closest index.php file and puts all the 
'fake' URI information there. 

 */

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
	 * Returns validate instance
	 * @return validate
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
	 * @return boolean TRUE
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
	 * @param  string $callback a string to a valid function that is executed when 
	 * @return bool           true on success, false otherwise
	 */
	public function defineRoute($uri,$callback=NULL) {
		
		if (!isnull($callback) && is_function($callback) === FALSE) {
			return FALSE;
		}

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
			$this->serverURI = $this->parseURI($_SERVER['REDIRECT_SCRIPT_URL']);
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
	 * @return boolean TRUE on match, FALSE otherwise
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
	 * @return [type] [description]
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

			// @TODO check validations
			// if it doesnt validate, set an error message, a debug, and return false

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

		return $route['callback']($this->serverURI,$variables);

	}

	/**
	 * determine if a string represents a variable
	 * @param  string  $item string to be tested
	 * @return boolean       TRUE if the string could be a variable, false otherwise
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

}
