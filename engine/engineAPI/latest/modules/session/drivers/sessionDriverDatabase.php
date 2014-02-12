<?php
/**
 * EngineAPI session manager
 * @author David Gersting
 * @package EngineAPI\modules\session
 */

// Make sure we have the interface loaded
require_once __DIR__.DIRECTORY_SEPARATOR."sessionDriverInterface.php";

/**
 * Database driver for session manager
 * @package EngineAPI\modules\session\drivers
 * @author David Gersting
 */
class sessionDriverDatabase implements sessionDriverInterface{
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
	 * @var string
	 */
	private $sessionName;
	/**
	 * @var dbDriver
	 */
	private $db;

	/**
	 * Class constructor
	 *
	 * @param session $session The session instance
	 * @param array   $options Array of options
	 *
	 * ###Available Options:
	 * - tableName:    The database table that stores the sessions
	 * - idField:      The table field that's used as the primary key which will be the session's id (which is an alpha string)
	 * - dbObject:     An instance of dbDriver to be used (This takes precedence)
	 * - dbConnection: A named database connection to use
	 *
	 * ####Note:
	 * If no dbObject or dbConnection are given, will use engineAPI's EngineDB via EngineAPI->getEngineDB()
	 * For setup SQL see: sessionDriverDatabase.sql
	 */
	public function __construct($session,$options=array()){
		$this->session = $session;
		$this->options = array_merge(array(
			'tableName' => 'sessions',
			'idField'   => 'ID',
			'dbDriver'  => 'mysql',
		), $options);

		// We need to figure out what database object we're working with
		if(isset($this->options['dbObject']) and $this->options['dbObject'] instanceof dbDriver){
			// We got an engineDB object in the config
			$this->db = $this->options['dbObject'];
		}elseif(isset($this->options['dbConnection'])){
			// We got an engineDB object in the config
			$this->db = db::get($this->options['dbConnection']);
		}else{
			// We weren't given anything so use EngineAPI's engineDB object
			$this->db = db::get(EngineAPI::DB_CONNECTION);
		}

		// Make sure the table actually exists
		$res = $this->db->query('SELECT 1 FROM `'.$this->db->escape($this->options['tableName']).'` LIMIT 1');
		if ($res->errorCode()) {
			// Error! (The table must not exist)
			$this->isReady = FALSE;
		} else {
			// Ok, the table exists
			$this->isReady = session_set_save_handler(
				array($this, 'open'),
				array($this, 'close'),
				array($this, 'read'),
				array($this, 'write'),
				array($this, 'destroy'),
				array($this, 'gc'));
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

	public function open($savePath, $sessionName){
		$this->sessionName = $sessionName;
	}
	public function close(){}

	/**
	 * Reads session data to the database
	 * @param $sessionId
	 * @return string
	 */
	public function read($sessionId){
		$tableName = $this->db->escape($this->options['tableName']);
		$idField   = $this->db->escape($this->options['idField']);
		$dbResult  = $this->db->query("SELECT `data` FROM `$tableName` WHERE `$idField`='?' LIMIT 1", array($sessionId));
		return $dbResult->rowCount()
			? base64_decode($dbResult->fetchField(0))
			: '';
	}

	/**
	 * Write session data to database table
	 *
	 * @param $sessionId
	 * @param $data
	 */
	public function write($sessionId, $data){
//		session::sync();
		$data      = base64_encode(session_encode());
		$tableName = $this->db->escape($this->options['tableName']);
		$idField   = $this->db->escape($this->options['idField']);

		$existingSession = $this->db->query("SELECT `$idField` FROM `$tableName` WHERE `$idField`='?'", array($sessionId));
		if ($existingSession->rowCount()) {
			// Update existing session
			$sql = "UPDATE `$tableName` SET `updated`=NOW(),`data`='?' WHERE `$idField`='?' LIMIT 1";
			$this->db->query($sql, array($data, $sessionId));
		} else {
			// Save new session
			$sql = "INSERT INTO `$tableName` ($idField,updated,fingerprint,name,data) VALUES ('?',NOW(),'?','?','?')";
			$this->db->query($sql, array($sessionId, $_SESSION['fingerprint'], $this->sessionName, $data));
		}
	}

	/**
	 * Destroy (delete) a session from the database table
	 *
	 * @param $sessionId
	 * @return bool
	 */
	public function destroy($sessionId){
		$tableName = $this->db->escape($this->options['tableName']);
		$idField   = $this->db->escape($this->options['idField']);
		$dbResult  = $this->db->query("DELETE * FROM `$tableName` WHERE `$idField`='?' LIMIT 1", array($sessionId));
		return ($dbResult->affectedRows() > 0);
	}

	/**
	 * Perform garbage collection on database table
	 *
	 * @param $lifetime
	 * @return bool
	 */
	public function gc($lifetime){
		$tableName = $this->db->escape($this->options['tableName']);
		$lifetime  = $this->db->escape($lifetime);
		$dbResult  = $this->db->query("DELETE * FROM `$tableName` WHERE (UNIX_TIMESTAMP(`updated`)+$lifetime) <= UNIX_TIMESTAMP()");
		return ($dbResult->affectedRows() > 0);
	}
}
