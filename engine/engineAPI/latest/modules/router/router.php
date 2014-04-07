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
	 * Determine which defined route we are using based on the server URI
	 * @return [type] [description]
	 */
	private function matchRoute() {

		foreach ($this->definedRoutes as $definedRoute) {

			if ($definedRoute['count'] != $this->serverURI['count']) continue;

			foreach ($definedRoute['items'] as $I=>$V) {

				// we are skipping variables here, if we don't have a match, 
				// we shouldn't be populating variables
				if ($definedRoute['variable'] === FALSE) continue;

				// If the path's do not match, we skip to the next defined route
				if ($definedRoute['items'][$I]['path'] != $this->serverURI['items'][$I]['path']) {
					continue 2;
				}

			}

			return $definedRoute;

		}

		return FALSE;

	}

	/**
	 * Return the variables array
	 * @return array [description]
	 */
	public function getVariables() {

		$route = $this->matchRoute();

		$variables = array();
		foreach ($route['items'] as $I=>$item) {
			if ($item['variable'] === FALSE) continue;

			// @TODO check validations
			// if it doesnt validate, set an error message, a debug, and return false

			$variables[$item['variable']['name']] = $this->serverURI['items'][$I]['URI'];
		}

		return $variables;

	}

	/**
	 * For routing with callbacks. The parsed ServerURI and variables are passed to the callback function
	 * 
	 * @return mixed from callback function
	 */
	public function route() {

		$route     = $this->matchRoute();
		$variables = $this->getVariables();

		return $this->definedRoutes[$route]['callback']($this->serverURI,$variables);

	}

	/**
	 * Parse the provided variable string, {varName[=validation]}
	 * @param  string $item variable string
	 * @return array  $variable['name']
	 *                $variable['validation']
	 *                $variable['regex']
	 */
	private function parseVariable($item) {
		
		$item = str_replace("{", "", $item);
		$item = str_replace("}", "", $item);
		$item = explode("=",$item);

		$variable['name'] = $item[0];

		if (isset($item[1]) && !is_empty($item[1]) && validate::isValidMethod($item[1])) {
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
		$parsedURI['URI']   = $URI;
		$parsedURI['count'] = count($items);

		foreach ($items as $item) {

			if (is_variable($item)) {
				$variable = $this->parseVariable($item);
			}
			else {
				$variable = FALSE;
			}

			$parsedURI['items'][] = array('path' => $item, 'variable' => $variable);
		}

		return $parsedURI;

	}

}
