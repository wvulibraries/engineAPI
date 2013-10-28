<?php
/**
 * EngineAPI database manager
 * @package EngineAPI\modules\db
 */

/**
 * EngineAPI database statement abstract class
 *
 * @package EngineAPI\modules\db
 */
abstract class dbStatement{
	const FETCH_ASSOC = 1;
	const FETCH_NUM = 2;
	const FETCH_OBJ = 3;

	/**
	 * Execute the prepared SQL
	 *
	 * This method MUST accept an unknown number of params (via PHP's func_get_args)
	 * @example execute(1, "abc", (float)3.25)
	 *
	 * @return bool
	 */
	public abstract function execute();

	/**
	 * Return the number of fields in the prepared SQL/result
	 * (depending on the state of the statement)
	 *
	 * @return int
	 */
	public abstract function fieldCount();

	/**
	 * Return an array of field names in the result
	 * (will be a blank array until the statement is executed)
	 *
	 * @return array
	 */
	public abstract function fieldNames();

	/**
	 * Return the number of rows in the result
	 *
	 * @return int
	 */
	public abstract function rowCount();

	/**
	 * Return the number of rows which were affected by the executed SQL
	 *
	 * @return int
	 */
	public abstract function affectedRows();

	/**
	 * Return the last insertID from the executed SQL
	 *
	 * @return int
	 */
	public abstract function insertId();

	/**
	 * Fetch one row at a time from the result setup according to the current fetch mode.
	 *
	 * @param $fetchMode
	 *        The 'Fetch mode' to use. See FETCH_* constants on dbStatement
	 * @return mixed
	 */
	public abstract function fetch($fetchMode=self::FETCH_ASSOC);

	/**
	 * Return an array of all rows from the result set.
	 *
	 * @param $fetchMode
	 *        The 'Fetch mode' to use. See FETCH_* constants on dbStatement
	 * @return array
	 */
	public abstract function fetchAll($fetchMode=self::FETCH_ASSOC);

	/**
	 * Return only the given field from one row at a time. (Defaults to the 1st field)
	 *
	 * @param $fetchMode
	 *        The 'Fetch mode' to use. See FETCH_* constants on dbStatement
	 * @param int|string $field
	 *        Either the index (base 0) or the name of the field to fetch
	 * @return mixed
	 */
	public abstract function fetchField($fetchMode=self::FETCH_ASSOC, $field=0);

	/**
	 * Return an array of all rows of only the given field. (Defaults to the 1st field)
	 *
	 * @param $fetchMode
	 *        The 'Fetch mode' to use. See FETCH_* constants on dbStatement
	 * @param int|string $field
	 *        Either the index (base 0) or the name of the field to fetch
	 * @return mixed
	 */
	public abstract function fetchFieldAll($fetchMode=self::FETCH_ASSOC, $field=0);

	/**
	 * The error code/number of the last error *driver specific*
	 *
	 * @return int
	 */
	public abstract function errorCode();

	/**
	 * The error message of the last error *driver specific*
	 *
	 * @return string
	 */
	public abstract function errorMsg();
}