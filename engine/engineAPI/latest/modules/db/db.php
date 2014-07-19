<?php
/**
 * EngineAPI database manager
 * @package EngineAPI\modules\db
 */

// Make sure the abstract dbDriver and dbStatement classes are loaded!
require_once __DIR__.DIRECTORY_SEPARATOR.'dbDriver.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'dbStatement.php';

/**
 * EngineAPI database manager
 *
 * @package EngineAPI\modules\db
 */
class db implements Countable {
    const STORED_OBJECT_MARKER = '%eapi%encodedObject%b223b3d857af21850d1ae44c50ef774760b6c20a%';

    /**
     * @var self
     */
    private static $classInstance;
    /**
     *
     * @var string
     */
    public static $driverDir;
    /**
     * @var string[]
     */
    private static $drivers = array();
    /**
     * @var dbDriver[]
     */
    private static $connections = array();

    public static function getInstance() {
        return new self;
    }

    /**
     * [Countable] Returns the number of object currently registered
     *
     * @author David Gersting
     * @return int
     */
    public function count() {
        return sizeof(self::$connections);
    }

    /**
     * Reset the db class back to it's vanilla state
     *
     * Warning: This will destroy() all registered objects unless FALSE is passed in
     *
     * @author David Gersting
     * @param bool $destroyObjects
     */
    public static function reset($destroyObjects = TRUE) {
        foreach (self::$connections as $object) {
            if ($destroyObjects) $object->destroy();
            self::unregisterObject($object);
        }
        self::$drivers = array();
        self::$connections = array();
    }

    /**
     * Create a new database driver
     *
     * @author David Gersting
     * @param string       $driver
     *        The driver type to use (@see self::listDrivers())
     * @param array|string $options
     *        An optional array of params to pass to the driver *driver-specific*
     *        OR a fully qualified DSN (@see http://us2.php.net/manual/en/pdo.construct.php)
     *        OR an instantiated PDO object
     * @param string       $alias
     *        If provided, register this driver under the given alias.
     *        If name collision, return FALSE
     * @return dbDriver|bool
     */
    public static function create($driver, $options = array(), $alias = NULL) {
        $alias  = trim(strtolower($alias));
        $driver = trim(strtolower($driver));

        try {
            // Make sure alias isn't already taken
            if (isset($alias) and !empty($alias) and isset(self::$connections[$alias])) throw new Exception('Alias already registered!');

            // Make sure requested driver is a valid one
            if (!self::loadDriver($driver)) throw new Exception('Failed to load driver!');

            // Create the new driver
            $dbDriverClass = "dbDriver_$driver";
            $dbDriverObj   = new $dbDriverClass($options);

            // Save the driver for later if it's been given an alias
            if (!empty($alias)) self::$connections[$alias] = $dbDriverObj;

            // Return the new driver object
            return $dbDriverObj;
        }catch(Exception $e) {
            trigger_error(__METHOD__."() {$e->getMessage()} thrown from line {$e->getLine()}", E_USER_ERROR);
            return FALSE;
        }
    }

    /**
     * Returns TRUE if the given named connection has been defined
     *
     * @author David Gersting
     * @param $name
     * @return bool
     */
    public static function exists($name){
        $name = trim(strtolower($name));
        return isset(self::$connections[$name]);
    }

    /**
     * Returns the given named connection or NULL if it hasn't been defined yet
     *
     * @author David Gersting
     * @param string $name
     * @return dbDriver
     */
    public static function get($name){
		if($name instanceof dbDriver) return $name;
        $name = trim(strtolower($name));

        return isset(self::$connections[$name])
            ? self::$connections[$name]
            : NULL;
    }

    /**
     * [PHP Magic Method] Allow drivers to be called via virtual static methods (eg: $db->system->...)
     *
     * @see self::get()
     */
    public function __get($name) {
        return self::get($name);
    }

    /**
     * Register a given dbDriver under the given alias
     *
     * @author David Gersting
     * @param dbDriver $driver
     * @param string   $alias
     * @return bool
     */
    public static function registerAs(dbDriver $driver, $alias) {
        $alias = trim(strtolower($alias));
        if (isset(self::$connections[$alias])) {
            errorHandle::newError(__METHOD__."() - Alias already exists!", errorHandle::DEBUG);

            return FALSE;
        }
        else {
            self::$connections[$alias] = $driver;

            return TRUE;
        }
    }

    /**
     * Un-register the given alias (remove it from the internal index)
     *
     * @author David Gersting
     * @param string $alias
     * @return bool
     */
    public static function unregisterAlias($alias) {
        $alias = trim(strtolower($alias));
        if (isset(self::$connections[$alias])) {
            unset(self::$connections[$alias]);

            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    /**
     * Un-register the given driver from the internal index (remove all alias(es) associated with it)
     *
     * @author David Gersting
     * @param dbDriver $driver
     * @return bool
     */
    public static function unregisterObject(dbDriver $driver) {
        $removedCounter = 0;
        foreach (self::$connections as $alias => $object) {
            if ($driver === $object) {
                $removedCounter++;
                unset(self::$connections[$alias]);
            }
        }

        return ($removedCounter > 0);
    }

    /**
     * [Helper Method] Returns an array of all valid driver types for use in self::create()
     *
     * @author David Gersting
     * @return array
     */
    public static function listDrivers() {
        if (!sizeof(self::$drivers)) {
            self::$drivers = array();
            foreach (glob(__DIR__.DIRECTORY_SEPARATOR.'drivers'.DIRECTORY_SEPARATOR.'*') as $dir) {
                self::loadDriver(basename($dir));
            }
        }

        return array_keys(array_filter(self::$drivers));
    }


    private static function loadDriver($driver) {
        if (!isset(self::$driverDir)) self::$driverDir = __DIR__.DIRECTORY_SEPARATOR.'drivers'.DIRECTORY_SEPARATOR;
        $driver = trim(strtolower($driver));

        // If we already know the answer, just return the known answer
        if (in_array($driver, self::$drivers)) return (bool)self::$drivers[$driver];

        // Try and figure out the answer
        try {
            // Make sure the driver directory exists
            if (!is_dir(self::$driverDir.$driver)) throw new Exception("No driver directory for given driverType $driver: '".self::$driverDir.".$driver'");

            // Make sure the driver's dbDriver file exists
            $dbDriverFilename = self::$driverDir.$driver.DIRECTORY_SEPARATOR."dbDriver_$driver.php";
            if (!is_readable($dbDriverFilename)) throw new Exception("No dbDriver file found for $driver: '$dbDriverFilename'");
            require_once $dbDriverFilename;

            // Make sure we have a dbDriver and dbStatement class for the driver
            if (!class_exists("dbDriver_$driver", FALSE)) throw new Exception("Failed to load dbDriver class for 'dbDriver_$driver'");
            if (!class_exists("dbStatement_$driver", FALSE)) throw new Exception("Failed to load dbDriver class for 'dbStatement_$driver'");

            // Make sure the driver's dbDriver class extends the right parent classes and is instantiable
            $dbDriver = new ReflectionClass("dbDriver_$driver");
            if (!$dbDriver->isSubclassOf('dbDriver')) throw new Exception("dbDriver_$driver doesn't extend dbDriver!");
            if (!$dbDriver->isInstantiable()) throw new Exception("dbDriver_$driver isn't instantiable!");

            // Make sure the driver's dbStatement class extends the right parent classes and is instantiable
            $dbStatement = new ReflectionClass("dbStatement_$driver");
            if (!$dbStatement->isSubclassOf('dbStatement')) throw new Exception("dbStatement_$driver doesn't extend dbStatement!");
            if (!$dbStatement->isInstantiable()) throw new Exception("dbDriver_$driver isn't instantiable!");

            // If we're here, then this driver is good to go
            self::$drivers[$driver] = TRUE;
        }catch(Exception $e) {
            // If we're here, then this driver is NOT good to go
            errorHandle::newError(__METHOD__."() {$e->getMessage()} thrown from line {$e->getLine()}", errorHandle::DEBUG);
            self::$drivers[$driver] = FALSE;
        }

        // Return the now known answer
        return (bool)self::$drivers[$driver];
    }
}
