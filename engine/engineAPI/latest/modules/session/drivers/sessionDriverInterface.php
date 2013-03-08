<?php
/**
 * EngineAPI session manager
 * @author David Gersting
 * @version 1.0
 * @package EngineAPI\modules\session
 */

/**
 * Backend session driver interface - This ensures all drivers are compatible and will operate as a session driver.
 *
 * With PHP 5.4 this interface will be replaced with PHP's native SessionHandlerInterface
 *
 * @author David Gersting
 * @version 1.0
 * @package EngineAPI\modules\session\drivers
 */
interface sessionDriverInterface{
	/**
	 * Driver constructor
	 * @param session $session
	 * @param array $options
	 */
	public function __construct($session,$options=array());

	/**
	 * Returns TRUE if/when the driver is ready
	 * @return bool
	 */
	public function isReady();

	/**
	 * Open the session
	 *
	 * The open callback works like a constructor in classes and is executed when the session is being opened.<br>
	 * It is the first callback function executed when the session is started automatically or manually with session_start().<br>
	 * Return value is TRUE for success, FALSE for failure.
	 *
	 * @param string $savePath
	 * @param string $sessionName
	 * @return bool
	 */
	public function open($savePath, $sessionName);

	/**
	 * Close the session
	 *
	 * The close callback works like a destructor in classes and is executed after the session write callback has been called.<br>
	 * It is also invoked when session_write_close() is called. Return value should be TRUE for success, FALSE for failure.
	 *
	 * @return bool
	 */
	public function close();

	/**
	 * Read session data from the data store
	 *
	 * The read callback must always return a session encoded (serialized) string, or an empty string if there is no data to read.<br>
	 * This callback is called internally by PHP when the session starts or when session_start() is called. Before this callback is invoked PHP will invoke the open callback.
	 *
	 * @param string $sessionId
	 * @return string
	 */
	public function read($sessionId);

	/**
	 * Write session data to the data store
	 *
	 * The write callback is called when the session needs to be saved and closed.
	 * This callback is invoked when PHP shuts down or explicitly when session_write_close() is called. Note that after executing this function PHP will internally execute the close callback.
	 *
	 * This callback receives the current session ID a serialized version the $_SESSION superglobal.
	 * The serialized session data passed to this callback should be stored against the passed session ID. When retrieving this data, the read callback must return the exact value that was originally passed to the write callback.
	 *
	 * @param string $sessionId
	 * @param string $data
	 */
	public function write($sessionId, $data);

	/**
	 * This callback is executed when a session is destroyed with session_destroy() or with session_regenerate_id() with the destroy parameter set to TRUE.
	 * Return value should be TRUE for success, FALSE for failure.
	 * @param string $sessionId
	 * @return bool
	 */
	public function destroy($sessionId);

	/**
	 * The garbage collector callback is invoked internally by PHP periodically in order to purge old session data.
	 * The frequency is controlled by session.gc_probability and session.gc_divisor. The value of lifetime which is passed to this callback can be set in session.gc_maxlifetime.
	 * Return value should be TRUE for success, FALSE for failure.
	 * @param int $lifetime
	 * @return bool
	 */
	public function gc($lifetime);
}
