<?php
/**
 * EngineAPI database manager
 * @package EngineAPI\modules\db
 */

/**
 * EngineAPI database driver abstract class
 *
 * @package EngineAPI\modules\db
 */
abstract class dbDriver{
	/**
	 * Prepare the given SQL for execution
	 *
	 * @param string $sql
	 *        The SQL to prepare
	 * @param array $params
	 *        If given, will be use to 'auto-execute' the prepared SQL
	 * @return dbStatement
	 */
	public abstract function query($sql, array $params);

	/**
	 * Execute a raw SQL statement against the database
	 *
	 * @param $sql
	 * @return dbStatement
	 */
	public abstract function exec($sql);

	/**
	 * Escape the given var to use used safely in SQL for this driver
	 *
	 * @param $var
	 * @return string
	 */
	public abstract function escape($var);

	/**
	 * Start a transaction
	 *
	 * @return bool
	 */
	public abstract function beginTransaction();

	/**
	 * Commit (cancel) the current transaction
	 *
	 * @return bool
	 */
	public abstract function commit();

	/**
	 * Rollback (cancel) the current transaction
	 *
	 * @return bool
	 */
	public abstract function rollback();

	/**
	 * Return TRUE if currently in a transaction
	 *
	 * @return bool
	 */
	public abstract function inTransaction();

	/**
	 * Place this connection into 'Read Only' mode.
	 * The driver will do it's best ability to prevent database writes in this mode.
	 *
	 * @return bool
	 */
	public abstract function readOnly();

	/**
	 * Disconnect the underlying driver and self-destruct
	 *
	 * @return bool
	 */
	public abstract function destroy();
}