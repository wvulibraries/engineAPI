<?php

class mailSender {

	private $from        = NULL;
	private $to          = array();
	private $cc          = array();
	private $bcc         = array();
	private $replyTo     = array();
	private $subject     = NULL;
	private $body        = NULL;
	private $altbody     = NULL;
	private $attachment  = array();
	private $engine      = NULL;
	private $database    = NULL;

	public $ignoredEmail = array("dev@null.com");
	public $debug        = FALSE; // Must be FALSE in Production

	function __construct($database=NULL) {

		$this->engine   = EngineAPI::singleton();
		$this->database = ($database instanceof engineDB) ? $database : $this->engine->openDB;

	}

	/**
	 * Add the sender to the email
	 *
	 * @param string $address   The email address to add, as a string
	 * @param string $name      The human friendly name, as a string
	 * @param bool   $internal  Whether or not to enforce an internal email address, as a boolean
	 * @return bool
	 **/
	public function addSender($address,$name='',$internal=FALSE) {

		if (in_array($address,$this->ignoredEmail)) {
			return TRUE;
		}

		if (email::validate($address,$internal) === TRUE) {
			$this->from = array("address" => $address, "name" => $name);
			return TRUE;
		}

		if ($this->debug === TRUE) {
			errorHandle::newError("Failed to add Sender: <".$address.">.",errorHandle::DEBUG);
		}

		return FALSE;

	}

	/**
	 * Add a recipient to the email
	 *
	 * @param string $address   The email address to add, as a string
	 * @param string $name      The human friendly name, as a string
	 * @param bool   $internal  Whether or not to enforce an internal email address, as a boolean
	 * @return bool
	 **/
	public function addRecipient($address,$name='',$internal=FALSE) {

		if (in_array($address,$this->ignoredEmail)) {
			return TRUE;
		}

		if (email::validate($address,$internal) === TRUE) {
			$this->to[] = array("address" => $address, "name" => $name);
			return TRUE;
		}

		if ($this->debug === TRUE) {
			errorHandle::newError("Failed to add Recipient: <".$address.">.",errorHandle::DEBUG);
		}

		return FALSE;

	}

	/**
	 * Add a CC recipient to the email
	 *
	 * @param string $address   The email address to add, as a string
	 * @param string $name      The human friendly name, as a string
	 * @param bool   $internal  Whether or not to enforce an internal email address, as a boolean
	 * @return bool
	 **/
	public function addCC($address,$name='',$internal=FALSE) {

		if (in_array($address,$this->ignoredEmail)) {
			return TRUE;
		}

		if (email::validate($address,$internal) === TRUE) {
			$this->cc[] = array("address" => $address, "name" => $name);
			return TRUE;
		}

		if ($this->debug === TRUE) {
			errorHandle::newError("Failed to add CC: <".$address.">.",errorHandle::DEBUG);
		}

		return FALSE;

	}

	/**
	 * Add a BCC (hidden) recipient to the email
	 *
	 * @param string $address   The email address to add, as a string
	 * @param string $name      The human friendly name, as a string
	 * @param bool   $internal  Whether or not to enforce an internal email address, as a boolean
	 * @return bool
	 **/
	public function addBCC($address,$name='',$internal=FALSE) {

		if (in_array($address,$this->ignoredEmail)) {
			return TRUE;
		}

		if (email::validate($address,$internal) === TRUE) {
			$this->bcc[] = array("address" => $address, "name" => $name);
			return TRUE;
		}

		if ($this->debug === TRUE) {
			errorHandle::newError("Failed to add BCC: <".$address.">.",errorHandle::DEBUG);
		}

		return FALSE;

	}

	/**
	 * Add a reply to address to the email
	 *
	 * @param string $address   The email address to add, as a string
	 * @param string $name      The human friendly name, as a string
	 * @param bool   $internal  Whether or not to enforce an internal email address, as a boolean
	 * @return bool
	 **/
	public function addReplyTo($address,$name='',$internal=FALSE) {

		if (in_array($address,$this->ignoredEmail)) {
			return TRUE;
		}

		if (email::validate($address,$internal) === TRUE) {
			$this->replyTo[] = array("address" => $address, "name" => $name);
			return TRUE;
		}

		if ($this->debug === TRUE) {
			errorHandle::newError("Failed to add Reply To: <".$address.">.",errorHandle::DEBUG);
		}

		return FALSE;

	}

	/**
	 * Set the email subject
	 *
	 * @param string $subject  The email subject to add, as a string
	 * @return bool
	 **/
	public function addSubject($subject) {
		$this->subject = $subject;
		return TRUE;
	}

	/**
	 * Set the email body
	 *
	 * @param string $body     The email body to add, as an HTML string
	 * @param string $altbody  The email body to add, as a plain text string
	 * @return bool
	 **/
	public function addBody($body,$altbody=NULL) {
		$this->body    = $body;
		$this->altbody = $altbody;
		return TRUE;
	}

	
	/**
	 * Add an attachment to the email
	 *
	 * @param string $location  The location of the file to add, as a string
	 * @param string $name      The name of the file to add, as a string
	 * @param string $encoding  The file encoding of the file to add, as a string
	 * @param string $type      The type of the fileto add, as a string
	 * @return bool
	 **/
	public function addAttachment($location,$name,$encoding,$type) {
		$this->attachment[] = array("location" => $location,
									"name"     => $name,
									"encoding" => $encoding,
									"type"     => $type);
		return TRUE;
	}

	/**
	 * Send the email to all recipients at once
	 *
	 * @return bool
	 **/
	public function sendEmail() {

		global $engineVars;

		$mailClassLocation = $engineVars['rootPHPDir'] ."/phpmailer/class.phpmailer.php";

		if (!file_exists($mailClassLocation)) {
			if ($this->debug === TRUE) {
				errorHandle::newError("File does not exist: ".$mailClassLocation,errorHandle::DEBUG);
			}
			return FALSE;
		}

		include_once($mailClassLocation);

		$PHPMailer = new PHPMailer();
		$PHPMailer->IsSMTP();

		$PHPMailer->From     = $this->from['address'];
		$PHPMailer->FromName = $this->from['name'];
		$PHPMailer->Subject  = $this->subject;
		$PHPMailer->AltBody  = $this->altbody;
		$PHPMailer->MsgHTML(nl2br($this->body));

		foreach ($this->to as $to) {
			$PHPMailer->AddAddress($to['address'], $to['name']);
		}

		foreach ($this->cc as $cc) {
			$PHPMailer->AddCc($cc['address'], $cc['name']);
		}

		foreach ($this->bcc as $bcc) {
			$PHPMailer->AddBcc($bcc['address'], $bcc['name']);
		}

		foreach ($this->replyTo as $replyTo) {
			$PHPMailer->AddReplyTo($replyTo['address'], $replyTo['name']);
		}

		foreach ($this->attachment as $file) {
			$PHPMailer->AddAttachment($file['location'], $file['name'], $file['encoding'], $file['type']);
		}


		if(!$PHPMailer->Send()) {
			if ($this->debug === TRUE) {
				errorHandle::newError("Mailer Error: ".$PHPMailer->ErrorInfo,errorHandle::DEBUG);
			}
			return FALSE;
		}

		return TRUE;

	}

	/**
	 * Prepare to send the email to each recipient individually, at a timed interval
	 *
	 * @return mixed
	 **/
	public function sendEmailBulk($sendID,$emails,$dbInfo) {

		global $engineVars;

		// Populate table with emails
		//
		// A table MUST be defined in the database with the following fields:
		//  Field name   Type           Allow nulls?  Default
		//   sendID       varchar(15)    Yes           NULL
		//   sender       varchar(50)    Yes           NULL
		//   recipient    varchar(50)    Yes           NULL
		//   subject      varchar(255)   Yes           NULL
		//   body         blob           Yes           NULL
		foreach ($emails as $email) {
			
			$sql = sprintf("INSERT INTO %s (sendID,sender,recipient,subject,body) VALUES ('%s','%s','%s','%s','%s')",
				$this->database->escape($this->engine->dbTables($dbInfo['table'])),
				$this->database->escape($sendID),
				$this->database->escape($email['sender']),
				$this->database->escape($email['recipient']),
				$this->database->escape($email['subject']),
				$this->database->escape($email['body'])
				);
			$sqlResult = $this->database->query($sql);

			if ($this->debug === TRUE) {
				if (!$sqlResult['result']) {
					errorHandle::errorMsg($sqlResult['error']."<br />");
					errorHandle::errorMsg($sqlResult['query']."<br />");
				}
			}

		}

		$dbInfo['database'] = isset($dbInfo['database']) ? $dbInfo['database'] : NULL;
		$dbInfo['username'] = isset($dbInfo['username']) ? $dbInfo['username'] : $engineVars['mysql']['username'];
		$dbInfo['password'] = isset($dbInfo['password']) ? $dbInfo['password'] : $engineVars['mysql']['password'];
		$dbInfo['server']   = isset($dbInfo['server'])   ? $dbInfo['server']   : $engineVars['mysql']['server'];
		$dbInfo['port']     = isset($dbInfo['port'])     ? $dbInfo['port']     : $engineVars['mysql']['port'];

		// Send emails that have the given sendID
		$result = $this->performBulkSend($sendID,$dbInfo);

		return $result;

	}

	/**
	 * Send the email to each recipient individually, at a timed interval
	 *
	 * @return mixed
	 **/
	private function performBulkSend($sendID,$dbInfo) {

		$sendID = escapeshellarg($sendID);
		foreach ($dbInfo as $key => $value) {
			$dbInfo[$key] = escapeshellarg($value);
		}

		return exec("php ".$engineVars['documentRoot']."/engineIncludes/emailSendBulk.php -id=$sendID -d=".$dbInfo['database']." -t=".$dbInfo['table']." -u=".$dbInfo['username']." -p=".$dbInfo['password']." -s=".$dbInfo['server']." -P=".$dbInfo['port']." -f=".$dbInfo['fileTable']." >/dev/null &");

	}

}

?>