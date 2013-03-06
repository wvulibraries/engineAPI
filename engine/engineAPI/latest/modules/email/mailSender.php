<?php
/**
 * mailSender Library
 * @package EngineAPI\modules\email
 */
class mailSender {
	/**
	 * Hold the sender info
	 * @see self::addSender()
	 * @var null
	 */
	private $from        = NULL;
	/**
	 * Array of recipients
	 * @see self::addRecipient()
	 * @var array
	 */
	private $to          = array();
	/**
	 * Array of CC'd recipients
	 * @see self::addCC()
	 * @var array
	 */
	private $cc          = array();
	/**
	 * Array of BCC's recipients
	 * @see self::addBCC()
	 * @var array
	 */
	private $bcc         = array();
	/**
	 * Array of replyTo addresses
	 * @see self::addReplyTo()
	 * @var array
	 */
	private $replyTo     = array();
	/**
	 * The email's subject line
	 * @see self::addSubject()
	 * @var string
	 */
	private $subject     = NULL;
	/**
	 * The email's main body
	 * @see self::addBody()
	 * @var string
	 */
	private $body        = NULL;
	/**
	 * The email's alternate body
	 * @see self::addBody()
	 * @var string
	 */
	private $altbody     = NULL;
	/**
	 * Boolean flag telling if body is HTML
	 * @see self::isHTML()
	 * @var bool
	 */
	private $htmlMessage = FALSE;
	/**
	 * Array of email attachments
	 * @see self::addAttachment()
	 * @var array
	 */
	private $attachment  = array();
	/**
	 * The instance of EngineAPI
	 * @var EngineAPI
	 */
	private $engine      = NULL;
	/**
	 * The instance of engineDB
	 * @var engineDB
	 */
	private $database    = NULL;
	/**
	 * An array of email addresses to ignore
	 * @var array
	 */
	public $ignoredEmail = array("dev@null.com");
	/**
	 * Boolean debug flag
	 * @var bool
	 */
	public $debug        = FALSE; // Must be FALSE in Production

	/**
	 * Class constructor
	 * @param engineDB $database
	 */
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
	 * Set the body of the email to plain text or html
	 *
	 * @param bool $html  TRUE to set message to HTML, FALSE to set message to plain text, default is TRUE
	 * @return void
	 **/
	public function isHTML($html=TRUE) {
		$this->htmlMessage = $html;
	}

	/**
	 * Send the email to all recipients at once
	 *
	 * @return bool
	 **/
	public function sendEmail() {

		$mailClassLocation = engineAPI::$engineVars['rootPHPDir'] ."/phpmailer/class.phpmailer.php";

		if (!file_exists($mailClassLocation)) {
			if ($this->debug === TRUE) {
				errorHandle::newError("File does not exist: ".$mailClassLocation,errorHandle::DEBUG);
			}
			return FALSE;
		}

		include_once($mailClassLocation);

		$PHPMailer = new PHPMailer($this->debug);

		// Set PHPMailer to use smtp and sets settings
		$PHPMailer->IsSMTP();
		$PHPMailer->Hostname  = "smtp.wvu.edu";
		$PHPMailer->Host      = "smtp.wvu.edu";
		$PHPMailer->Port      = 25;
		// $PHPMailer->SMTPDebug = $this->debug;

		$PHPMailer->Subject = $this->subject;
		
		$PHPMailer->IsHTML($this->htmlMessage);
		if ($this->htmlMessage === TRUE) {
			$PHPMailer->AltBody = $this->altbody;
			$PHPMailer->MsgHTML($this->body);
		}
		else {
			$PHPMailer->Body = $this->body;
		}

		$PHPMailer->SetFrom($this->from['address'], $this->from['name']);

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
	 * @param $sendID
	 * @param $emails
	 * @param $dbInfo
	 * @return mixed|void
	 */
	public function sendEmailBulk($sendID,$emails,$dbInfo) {

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
				$this->database->escape($dbInfo['table']),
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

		$dbInfo['database'] = isset($dbInfo['database']) ? $dbInfo['database']  : NULL;
		$dbInfo['table']    = isset($dbInfo['table'])    ? $dbInfo['table']     : NULL;
		$dbInfo['username'] = isset($dbInfo['username']) ? $dbInfo['username']  : NULL;
		$dbInfo['password'] = isset($dbInfo['password']) ? $dbInfo['password']  : NULL;
		$dbInfo['server']   = isset($dbInfo['server'])   ? $dbInfo['server']    : NULL;
		$dbInfo['port']     = isset($dbInfo['port'])     ? $dbInfo['port']      : NULL;

		if (isnull($dbInfo['database'])) {
			return errorHandle::newError("Database must be specified.",errorHandle::HIGH);
		}
		if (isnull($dbInfo['table'])) {
			return errorHandle::newError("Table must be specified.",errorHandle::HIGH);
		}

		// Send emails that have the given sendID
		$result = $this->performBulkSend($sendID,$dbInfo);

		return $result;

	}

	/**
	 * Send the email to each recipient individually, at a timed interval
	 *
	 * @param $sendID
	 * @param $dbInfo
	 * @return string
	 */
	private function performBulkSend($sendID,$dbInfo) {

		$sendID = escapeshellarg($sendID);
		foreach ($dbInfo as $key => $value) {
			$dbInfo[$key] = escapeshellarg($value);
		}

		$exec  = "php ".engineAPI::$engineVars['documentRoot']."/engineIncludes/emailSendBulk.php";
		$exec .= " -id=$sendID";
		$exec .= " -d=".$dbInfo['database'];
		$exec .= " -t=".$dbInfo['table'];
		$exec .= ($dbInfo['username'] == "''") ? "" : " -u=".$dbInfo['username'];
		$exec .= ($dbInfo['password'] == "''") ? "" : " -p=".$dbInfo['password'];
		$exec .= ($dbInfo['server'] == "''")   ? "" : " -s=".$dbInfo['server'];
		$exec .= ($dbInfo['port'] == "''")     ? "" : " -P=".$dbInfo['port'];
		$exec .= " -S=10";
		$exec .= " >/dev/null &";

		return exec($exec);

	}

	/**
	 * Clear recipients (TO) list
	 * @return bool Always true
	 */
	public function clearRecipients() {
		$this->to = array();
		return TRUE;
	}

	/**
	 * Clear recipients (CC) list
	 * @return bool Always true
	 */
	public function clearCCs() {
		$this->cc = array();
		return TRUE;
	}

	/**
	 * Clear recipients (BCC) list
	 * @return bool Always true
	 */
	public function clearBCCs() {
		$this->bcc = array();
		return TRUE;
	}

	/**
	 * Clear ReplyTo list
	 * @return bool Always true
	 */
	public function clearReplyTos() {
		$this->replyTo = array();
		return TRUE;
	}

	/**
	 * Clear all recipients
	 * Equivalent to calling each of these
	 *   - $this->clearRecipients();
	 *   - $this->clearCCs();
	 *   - $this->clearBCCs();
	 *   - $this->clearReplyTos();
	 * @return bool Always true
	 */
	public function clearAllAddresses() {
		$this->clearRecipients();
		$this->clearCCs();
		$this->clearBCCs();
		$this->clearReplyTos();
		return TRUE;
	}

	/**
	 * Clear attachments list
	 * @return bool Always true
	 */
	public function clearAttachments() {
		$this->attachment = array();
		return TRUE;
	}
}

?>