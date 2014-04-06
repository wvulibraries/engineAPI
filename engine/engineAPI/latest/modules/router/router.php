<?php

class router {

	private $definedRoutes = array();
	private $serverURI     = NULL;


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
	 * @return array See parseURI for the return
	 */
	private function getServerURI() {
		return $this->parseURI($_SERVER['REDIRECT_SCRIPT_URL']);
	}

	/**
	 * Determine which defined route we are using based on the server URI
	 * @return [type] [description]
	 */
	private function matchRoute() {

		if (($serverURI = $this->getServerURI()) === FALSE) return FALSE;

		foreach ($this->definedRoutes as $definedRoute) {

			if ($definedRoute['count'] != $serverURI['count']) continue;

			foreach ($definedRoute['items'] as $I=>$V) {

				// we are skipping variables here, if we don't have a match, 
				// we shouldn't be populating variables
				if ($definedRoute['variable'] === FALSE) continue;

				// If the path's do not match, we skip to the next defined route
				if ($definedRoute['items'][$I]['path'] != $serverURI['items'][$I]['path']) {
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

			$variables[$item['variable']['name']] = $serverURI['items'][$I]['URI'];
		}

		return $variables;

	}

	public function route() {

		$route = $this->matchRoute();


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
		else if (isset($item[1]) && !is_empty($item[1]) {
			$variable['validation'] = "regexp";
			$variable['regex']      = $item[1];
		}

		return $variable;

	}


	private function parseURI($uri) {

		// Splits on forward slashes, '/', unless they are in a {} block
		// does not support nested {}
		$items = preg_split("/\/(?![^{]*})/",$URI);

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
