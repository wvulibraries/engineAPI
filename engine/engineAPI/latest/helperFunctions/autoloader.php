<?php

class autoloader {

	private static $classInstance;
	private static $modulesDirectory;
	private static $availableModules = array();

	public function __construct() {

	}

	public static function getInstance($modulesDirectory = NULL) {

		if (!isset(self::$classInstance)) { 

			if (isnull($modulesDirectory)) return FALSE;

			self::$classInstance = new self();

			// @TODO this needs updated with new database info
			self::$classInstance->set_modulesDirectory($modulesDirectory);;
		}

		return self::$classInstance;

	}

	public function set_modulesDirectory($directory) {
		self::$modulesDirectory = $directory;
		return TRUE;
	}

	public function exists($className) {

		if (array_key_exists($className, self::$availableModules)) return TRUE;
		
		return FALSE;

	}

	function loadModules() {

		$modules_dirHandle = @opendir(self::$modulesDirectory) or die("Unable to open (Modules) ".self::$modulesDirectory);

		while (false !== ($dir = readdir($modules_dirHandle))) {
		// Check to make sure that it isn't a hidden file and that the file is a directory
			if ($dir != "." && $dir != ".." && is_dir(self::$modulesDirectory."/".$dir) === TRUE) {
				if ($dir == "templates") continue;
				$singleMod_dirHandle = @opendir(self::$modulesDirectory."/".$dir) or die("Unable to open (Single Module) ".self::$modulesDirectory);
				while (false !== ($file = readdir($singleMod_dirHandle))) {
					if ($file != "." && $file != ".." && $file) {

						if ($file == "onLoad.php") {
							include_once(self::$modulesDirectory."/".$dir."/".$file);
						}
						else {
							$fileChunks = array_reverse(explode(".", $file));
							$ext= $fileChunks[0];
							if ($ext == "php") {
								self::$availableModules[$fileChunks[1]] = self::$modulesDirectory."/".$dir."/".$file;
							}
						}

					}
				}
			}
		}
	}

	/**
	 * Base EngineAPI autoloader
	 * @param $className
	 * @return bool
	 */
	function autoloader($className) {

		if (!class_exists($className, FALSE)) {
			if (isset(self::$availableModules[$className]) && file_exists(self::$availableModules[$className])) {
				require_once self::$availableModules[$className];
				return TRUE;
			}
		}

	}

	/**
	 * Adds the given autoload function to the autoload stack
	 * @internal
	 * @param mixed $autoload
	 * @return bool
	 */
	function addAutoloader($autoload) {

		if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
			$return = spl_autoload_register($autoload,TRUE,TRUE);
			return $return;
		}

		$functions = spl_autoload_functions();

		if ($functions === FALSE) {
			$return = spl_autoload_register($autoload);
			return $return;
		}

		foreach ($functions as $I=>$V) {
			$return = spl_autoload_unregister($V);
			if ($return === FALSE) {
				return FALSE;
			}
		}

		$return = spl_autoload_register($autoload);
		if ($return === FALSE) {
			return FALSE;
		}
		foreach ($functions as $I=>$V) {
			$return = spl_autoload_register($V);
			if ($return === FALSE) {
				return FALSE;
			}
		}

		return $return;

	}

	/**
	 * Adds a library
	 *
	 * @param string $libraryDir
	 * @return bool
	 */
	public function addLibrary($libraryDir) {

		// Make sure that it is a directory
		if (is_dir($libraryDir) === FALSE) {
			return FALSE;
		}

		// Make sure that we can read it
		if (is_readable($libraryDir) === FALSE) {
			return FALSE;
		}

		$dirHandle = @opendir($libraryDir);

		if ($dirHandle === FALSE) {
			return FALSE;
		}

		while (false !== ($file = readdir($dirHandle))) {

			$fileChunks = array_reverse(explode(".", $file));
			$ext        = $fileChunks[0];
			if ($ext == "php") {
				self::$availableModules[$fileChunks[1]] = $libraryDir."/".$file;
			}
		}
	}

	public function export() {

		print "<pre>";
		var_dump(self::$availableModules);
		print "</pre>";

	}

}

?>