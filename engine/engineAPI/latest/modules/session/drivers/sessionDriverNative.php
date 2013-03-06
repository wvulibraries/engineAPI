<?php
/**
 * Native PHP driver for session manager
 * @package EngineAPI\modules\session\drivers
 */

// Make sure we have the interface loaded
require_once __DIR__.DIRECTORY_SEPARATOR."sessionDriverInterface.php";

/**
 * Native PHP driver for session manager
 * @package EngineAPI\modules\session\drivers
 */
class sessionDriverNative implements sessionDriverInterface{
	private $isReady=FALSE;

	/**
	 * Class constructor
	 *
	 * @param session $session
	 * @param array $options
	 *        [No options available]
	 */
	public function __construct($session,$options=array()){
		// This driver is a little odd.
		// Because it's using the native PHP session implementation, there's actually nothing we need to do here
		$this->isReady = TRUE;
	}

	/**
	 * Returns TRUE when the driver is ready
	 *
	 * @return bool
	 */
	public function isReady(){
		return $this->isReady;
	}
	public function open($savePath, $sessionName){}
	public function close(){}
	public function read($sessionId){}
	public function write($sessionId, $data){}
	public function destroy($sessionId){}
	public function gc($lifetime){}
}


?>