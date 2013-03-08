<?php
/**
 * EngineAPI session manager
 * @author David Gersting
 * @version 1.0
 * @package EngineAPI\modules\session
 */

// Make sure we have the interface loaded
require_once __DIR__.DIRECTORY_SEPARATOR."sessionDriverInterface.php";

/**
 * Native PHP driver for session manager
 * @package EngineAPI\modules\session\drivers
 */
class sessionDriverNative implements sessionDriverInterface{
	/**
	 * @var bool
	 */
	private $isReady=FALSE;

	/**
	 * Class constructor
	 *
	 * ###Available Options:
	 * - [No options available, it's the native PHP session handler]
	 *
	 * Because we are using the native PHP session implementation, there's actually nothing we need to implement here.
	 *
	 * @param session $session The session instance
	 * @param array $options   Array of options
	 */
	public function __construct($session,$options=array()){
		$this->isReady = TRUE;
	}

	/**
	 * Returns TRUE when the driver is ready
	 * @return bool
	 */
	public function isReady(){
		return $this->isReady;
	}

	/**
	 * [Not used directly - Only here to comply with interface]
	 * @param string $savePath
	 * @param string $sessionName
	 * @return bool
	 */
	public function open($savePath, $sessionName){}

	/**
	 * [Not used directly - Only here to comply with interface]
	 * @return bool
	 */
	public function close(){}

	/**
	 * [Not used directly - Only here to comply with interface]
	 * @param string $sessionId
	 * @return string
	 */
	public function read($sessionId){}

	/**
	 * [Not used directly - Only here to comply with interface]
	 * @param string $sessionId
	 * @param string $data
	 */
	public function write($sessionId, $data){}

	/**
	 * [Not used directly - Only here to comply with interface]
	 * @param string $sessionId
	 * @return bool
	 */
	public function destroy($sessionId){}

	/**
	 * [Not used directly - Only here to comply with interface]
	 * @param int $lifetime
	 * @return bool
	 */
	public function gc($lifetime){}
}


?>