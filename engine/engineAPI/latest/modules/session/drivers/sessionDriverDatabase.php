<?php
/**
 * Database driver for session manager
 * @package EngineAPI\modules\session\drivers
 */

// Make sure we have the interface loaded
require_once __DIR__.DIRECTORY_SEPARATOR."sessionDriverInterface.php";

/**
 * Database driver for session manager
 * @package EngineAPI\modules\session\drivers
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
	 * @var engineDB
	 */
	private $db;

	/**
	 * Class constructor
	 *
	 * Note: If no dbObject object nor db credentials (dbUser,dbPass,dbHost,dbPort,dbName) given, will use engineAPI's EngineDB via EngineAPI->getEngineDB()
	 * For setup SQL see: sessionDriverDatabase.sql
	 *
	 * @see sessionDriverDatabase.sql
	 * @param session $session
	 * @param array $options
	 *        Array of options for Filesystem driver
	 *          - tableName: The database table that stores the sessions
	 *          - idField:   The table field that's used as the primary key which will be the session's id (which is an alpha string)
	 *          - dbObject:  An instance of engineDB to be used (This takes precedence)
	 *          - dbUser:    The username to use (will create a local instance of engineDB)
	 *          - dbPass:    The password to use
	 *          - dbHost:    The host to connect to
	 *          - dbPort:    The port to connect to
	 *          - dbName:    The database name where the session table is
	 */
	public function __construct($session,$options=array()){
		$this->session = $session;
		$this->options = array_merge(array(
			'tableName' => 'sessions',
			'idField'   => 'ID',

		), $options);

		// We need to figure out what database object we're working with
		if(isset($this->options['dbObject']) and $this->options['dbObject'] instanceof engineDB){
			// We got an engineDB object in the config
			$this->db = $this->options['dbObject'];
		}elseif(isset($this->options['dbUser']) and isset($this->options['dbPass']) and isset($this->options['dbHost']) and isset($this->options['dbPort']) and isset($this->options['dbName'])){
			// We have database login info in the config (create our own engineDB object)
			$this->db = new engineDB($this->options['dbUser'],$this->options['dbPass'],$this->options['dbHost'],$this->options['dbPort'],$this->options['dbName']);
		}else{
			// We weren't given anything so use EngineAPI's engineDB object
			$this->db = $this->session->getEngine()->getEngineDB();
		}

		$this->isReady = session_set_save_handler(
			array($this, 'open'),
			array($this, 'close'),
			array($this, 'read'),
			array($this, 'write'),
			array($this, 'destroy'),
			array($this, 'gc'));
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
		$sql = sprintf("SELECT `data` FROM `%s` WHERE `%s`='%s' LIMIT 1",
			$this->db->escape($this->options['tableName']),
			$this->db->escape($this->options['idField']),
			$this->db->escape($sessionId));
		$dbResult = $this->db->query($sql);
		return $dbResult['numRows']
			? base64_decode(mysql_result($dbResult['result'], 0, 'data'))
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
		$data = base64_encode(session_encode());
		$sql  = sprintf("INSERT INTO `%s` (ID,updated,fingerprint,name,data) VALUES ('%s',NOW(),'%s','%s','%s') ON DUPLICATE KEY UPDATE updated=NOW(),data='%s'",
			$this->db->escape($this->options['tableName']),
			$this->db->escape($sessionId),
			$this->db->escape($_SESSION['fingerprint']),
			$this->db->escape($this->sessionName),
			$this->db->escape($data),
			$this->db->escape($data));
		$this->db->query($sql);
	}

	/**
	 * Destroy (delete) a session from the database table
	 *
	 * @param $sessionId
	 * @return bool
	 */
	public function destroy($sessionId){
		$sql = sprintf("DELETE FROM `%s` WHERE `%s`='%s'",
			$this->db->escape($this->options['tableName']),
			$this->db->escape($this->options['idField']),
			$this->db->escape($sessionId));
		$dbResult = $this->db->query($sql);
		return (0 == $dbResult['errorNumber']);
	}

	/**
	 * Perform garbage collection on database table
	 *
	 * @param $lifetime
	 * @return bool
	 */
	public function gc($lifetime){
		$sql = sprintf("DELETE FROM `%s` WHERE (UNIX_TIMESTAMP(`updated`)+%s) <= UNIX_TIMESTAMP()",
			$this->db->escape($this->options['tableName']),
			$this->db->escape($lifetime));
		$dbResult = $this->db->query($sql);
		return (0 == $dbResult['errorNumber']);
	}
}
