<?php
// Make sure we have the interface loaded
require_once __DIR__.DIRECTORY_SEPARATOR."sessionDriverInterface.php";

// And now for the driver itself
class sessionDriverFilesystem implements sessionDriverInterface{
	/**
	 * @var bool
	 */
	private $isReady=FALSE;
	/**
	 * @var session
	 */
	private $session;
	/**
	 * @var array
	 */
	private $options;
	/**
	 * @var
	 */
	private $sessionName;

	/**
	 * Class constructor
	 *
	 * @throws RuntimeException
	 * @param session $session
	 * @param array $options
	 *        Array of options for Filesystem driver
	 *          - savePath:
	 *            The directory to save the files to
	 *            Will use session_save_path() as default
	 *          - filename:
	 *            The filename template to use (default: 'sess_{SessionID}')
	 *              The following placeholders are available: (these are case sensitive)
	 *                - {SessionID}:   The session's id
	 *                - {SessionName}: The session's name
	 *                - {fingerprint}: The browser fingerprint
	 */
	public function __construct($session,$options=array()){
		$this->session = $session;
		$this->options = array_merge(array(
			'savePath' => session_save_path(),
			'filename' => 'sess_{SessionID}'
		), $options);

		/*
		 * Before we get going, we need to check a few things about the savePath to make sure our session files will be safe there
		 * We will be checking the following things;
		 *   - The savePath is owned by the running user
		 *   - the savePath is both readable and writable by the running user
		 *   - The savePath is NOT world readable or writable
		 */
		try{
			// Check savePath's owner
			/**
			 * @todo Need to find a better solution
			 * See:
			 *   - http://us2.php.net/manual/en/function.fileowner.php
			 *   - http://www.php.net/manual/en/function.getmyuid.php
			 *   - http://us2.php.net/manual/en/function.posix-getuid.php
			 */
			if(trim(strtolower(PHP_OS)) == 'linux'){
				// Linux OS
				preg_match('/uid=(\d+)\((\w+)\)\sgid=(\d+)\((\w+)\)/', `id`, $m);
				$userID    = (int)$m[1];
				$username  = $m[2];
				$groupID   = (int)$m[3];
				$groupName = $m[4];
				$savePathOwnerID = fileowner($this->options['savePath']);
				$savePathGroupID = filegroup($this->options['savePath']);

				if($savePathOwnerID != $userID){
					throw new RuntimeException(sprintf("savePath not owned by running user! (running user: %s(%s) savePath owner: %s)", $userID, $username, $savePathOwnerID));
				}
			}else{
				// Non-Linux OS
				// @todo Find a way to do this
			}

			// Check savePath's readable/writable permissions
			if(!is_readable($this->options['savePath'])){
				throw new RuntimeException(sprintf("savePath not readable by running user! (running user: %s)", `whoami`));
			}
			if(!is_writable($this->options['savePath'])){
				throw new RuntimeException(sprintf("savePath not writable by running user! (running user: %s)", `whoami`));
			}

			// Check savePath's world permission
			$filePermissions = fileperms($this->options['savePath']);
			if($filePermissions & 0x0004 or $filePermissions & 0x0002){
				throw new RuntimeException("Insecure savePath (".$this->options['savePath'].") for session files!");
			}

			// Okay, everything looks safe. Lets do this
			$this->savePath = $this->options['savePath'];
			session_save_path($this->savePath);
			$this->isReady = session_set_save_handler(
				array($this, 'open'),
				array($this, 'close'),
				array($this, 'read'),
				array($this, 'write'),
				array($this, 'destroy'),
				array($this, 'gc'));

		}catch(RuntimeException $e){
			errorHandle::newError(__METHOD__."() - ".$e->getMessage(), errorHandle::HIGH);
		}
	}

	/**
	 * Returns TRUE when the driver is ready
	 *
	 * @return bool
	 */
	public function isReady(){
		return $this->isReady;
	}

	/**
	 * The open callback works like a constructor in classes and is executed when the session is being opened.
	 *
	 * @param $savePath
	 * @param $sessionName
	 */
	public function open($savePath, $sessionName){
		// We just need to save the session name for when we read/write the session data
		$this->sessionName = $sessionName;
	}

	/**
	 * The close callback works like a destructor in classes and is executed after the session write callback has been called.
	 */
	public function close(){}

	/**
	 * The read callback must always return a session encoded (serialized) string, or an empty string if there is no data to read.
	 *
	 * @param $sessionId
	 * @return string
	 */
	public function read($sessionId){
		$filename = $this->buildFilename($sessionId);
		if(file_exists($filename)){
			return file_get_contents($this->buildFilename($sessionId));
		}else{
			return '';
		}
	}

	/**
	 * The write callback is called when the session needs to be saved and closed.
	 *
	 * @param $sessionId
	 * @param $data
	 */
	public function write($sessionId, $data){
		file_put_contents($this->buildFilename($sessionId), session_encode());
	}

	/**
	 * This callback is executed when a session is destroyed with session_destroy() or with session_regenerate_id() with the destroy parameter set to TRUE.
	 * Return value should be TRUE for success, FALSE for failure.
	 *
	 * @param $sessionId
 	 * @return bool
	 */
	public function destroy($sessionId){
		return unlink($this->buildFilename($sessionId));
	}

	/**
	 * The garbage collector callback is invoked internally by PHP periodically in order to purge old session data.
	 * The frequency is controlled by session.gc_probability and session.gc_divisor. The value of lifetime which is passed to this callback can be set in session.gc_maxlifetime.
	 * Return value should be TRUE for success, FALSE for failure.
	 *
	 * @param $lifetime
	 * @return bool
	 */
	public function gc($lifetime){
		$globPattern = $this->savePath.DIRECTORY_SEPARATOR.$this->options['filePrefix'].'*';
		foreach(glob($globPattern) as $file) {
			if(filemtime($file)+$lifetime < time() && file_exists($file)){
				if(!unlink($file)) return FALSE;
			}
		}

		return true;
	}

	/**
	 * Returns the full filename for the session
	 *
	 * @param $sessionId
	 * @return string
	 */
	private function buildFilename($sessionId){
		$filename = $this->options['filename'];
		$filename = str_replace('{SessionID}', $sessionId, $filename);
		$filename = str_replace('{SessionName}', $sessionId, $filename);
		$filename = str_replace('{fingerprint}', session::browserFingerprint(), $filename);
		return $this->savePath.DIRECTORY_SEPARATOR.$filename;
	}
}
