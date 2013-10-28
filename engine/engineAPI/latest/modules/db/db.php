<?php
/**
 * EngineAPI database manager
 * @package EngineAPI\modules\db
 */

/**
 * EngineAPI database manager
 *
 * @package EngineAPI\modules\db
 */
class db{

	/**
	 * Create a new database driver
	 *
	 * @author David Gersting
	 * @param string $driver
	 *        The driver type to use (@see self::listDrivers())
	 * @param array $options
	 *        An optional array of params to pass to the driver *driver-specific*
	 * @param string $alias
	 *        If provided, register this driver under the given alias.
	 *        If name collision, return FALSE
	 * @return dbDriver|bool
	 */
	public static function create($driver, $options=array(), $alias=''){

    }

	/**
	 * [PHP Magic Method] Allow drivers to be called via virtual static methods (eg: db::system->...)
	 *
	 * @author David Gersting
	 * @param $name
	 * @param $arguments
	 * @return dbDriver
	 */
	public static function __callStatic($name, $arguments){

    }

	/**
	 * Register a given dbDriver under the given alias
	 *
	 * @author David Gersting
	 * @param dbDriver $driver
	 * @param string $alias
	 * @return bool
	 */
	public static function registerAs(dbDriver $driver, $alias){

    }

	/**
	 * Un-register the given alias (remove it from the internal index)
	 *
	 * @author David Gersting
	 * @param string $alias
	 * @return bool
	 */
	public static function unregisterAlias($alias){

    }

	/**
	 * Un-register the given driver from the internal index (remove all alias(es) associated with it)
	 *
	 * @author David Gersting
	 * @param dbDriver $driver
	 * @return bool
	 */
	public static function unregisterObject(dbDriver $driver){

    }

	/**
	 * [Helper Method] Returns an array of all valid driver types for use in self::create()
	 *
	 * @author David Gersting
	 * @return array
	 */
	public static function listDrivers(){

    }
} 