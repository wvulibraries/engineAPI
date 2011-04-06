<?php

function validateEmailAddr($email,$internal=FALSE) {

	if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
		if($internal) {
			if (internalEmail($email)) {
				return(TRUE);
			}
			return(FALSE);
		}
	    return(TRUE);
	}

	return(FALSE);
}

function internalEmail($email) {
	global $engineVars;

	foreach ($engineVars['internalEmails'] as $key => $regex) {
		if(preg_match($regex,$email)) {
			return(TRUE);
		}	
	}

	return(FALSE);
}

function obfuscateEmail($attPairs) {

	$email = $attPairs['email'];

	$output = "";
	for ($i=0; $i<strlen($email); $i++){
		$output .= "&#" . ord($email[$i]) . ";";
	}

	return($output);
}

class mailSender {

	private $from       = NULL;
	private $to         = array();
	private $cc         = array();
	private $bcc        = array();
	private $replyTo    = array();
	private $subject    = NULL;
	private $body       = NULL;
	private $altbody    = NULL;
	private $attachment = array();
	private $engine     = NULL;

	public $ignoredEmail = 'dev@null.com';

	public function __construct() {

		$this->engine = EngineAPI::singleton();

	}

	function validateEmail($emailAddy,$internal=FALSE) {

		if(validateEmailAddr($emailAddy,$internal)) {
			return(TRUE);
		}

		return(FALSE);

	}

	public function addSender($address, $name = '') {

		if ($address == $this->ignoredEmail) {
			return TRUE;
		}

		if (!$this->validateEmail($address) === FALSE) {
			$this->from	= array("address" => $address, "name" => $name);
			return TRUE;
		}

		return FALSE;

	}

	public function addRecipient($address, $name = '') {

		if ($address == $this->ignoredEmail) {
			return TRUE;
		}

		if (!$this->validateEmail($address) === FALSE) {
			$this->to[]	= array("address" => $address, "name" => $name);
			return TRUE;
		}

		return FALSE;

	}

	public function addCC($address, $name = '') {

		if ($address == $this->ignoredEmail) {
			return TRUE;
		}

		if (!$this->validateEmail($address) === FALSE) {
			$this->cc[]	= array("address" => $address, "name" => $name);
			return TRUE;
		}

		return FALSE;

	}

	public function addBCC($address, $name = '') {

		if ($address == $this->ignoredEmail) {
			return TRUE;
		}

		if (!$this->validateEmail($address) === FALSE) {
			$this->bcc[]	= array("address" => $address, "name" => $name);
			return TRUE;
		}

		return FALSE;

	}

	public function addReplyTo($address, $name = '') {

		if ($address == $this->ignoredEmail) {
			return TRUE;
		}

		if (!$this->validateEmail($address) === FALSE) {
			$this->replyTo[]	= array("address" => $address, "name" => $name);
			return TRUE;
		}

		return FALSE;

	}

	public function addSubject($subject) {
		$this->subject	= $subject;
		return TRUE;
	}

	public function addBody($body,$altbody=NULL) {
		$this->body    = $body;
		$this->altbody = $altbody;
		return TRUE;
	}

	public function addAttachment($location, $name, $encoding, $type) {
		$this->attachment[]	= array("location" => $location, "name" => $name, "encoding" => $encoding, "type" => $type);
		return TRUE;
	}

	public function sendEmail() {

		global $engineVars;

		$mailClassLocation = $engineVars['rootPHPDir'] ."/phpmailer/class.phpmailer.php";

		if (!file_exists($mailClassLocation)) {
			return("Mailer Error: Failed to open class.phpmailer.php");
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
			return("Mailer Error: " . $PHPMailer->ErrorInfo);
		}

		return TRUE;

	}

	public function sendEmailBulk($sendID,$emails,$dbInfo) {

		global $engineVars;

		// Populate table with emails
		//
		// A table MUST be defined in teh database with the following fields:
		// <Add field definitions here>
		foreach ($emails as $email) {
			$sql = sprintf("INSERT INTO %s (sendID,sender,recipient,subject,body) VALUES ('%s','%s','%s','%s','%s')",
				$this->engine->openDB->escape($this->engine->dbTables($dbInfo['table'])),
				$this->engine->openDB->escape($sendID),
				$this->engine->openDB->escape($email['sender']),
				$this->engine->openDB->escape($email['recipient']),
				$this->engine->openDB->escape($email['subject']),
				$this->engine->openDB->escape($email['body'])
				);
			$this->engine->openDB->sanitize = FALSE;
			$sqlResult                      = $this->engine->openDB->query($sql);

			if (!$sqlResult['result']) {
				return webHelper_errorMsg("Failed to populate bulk email table.");
			}

		}

		$dbInfo['database'] = isset($dbInfo['database'])?$dbInfo['database']:NULL;
		$dbInfo['username'] = isset($dbInfo['username'])?$dbInfo['username']:$engineVars['mysql']['username'];
		$dbInfo['password'] = isset($dbInfo['password'])?$dbInfo['password']:$engineVars['mysql']['password'];
		$dbInfo['server']   = isset($dbInfo['server'])?$dbInfo['server']:$engineVars['mysql']['server'];
		$dbInfo['port']     = isset($dbInfo['port'])?$dbInfo['port']:$engineVars['mysql']['port'];

		// Send emails that have given sendID
		$result = $this->performBulkSend($sendID,$dbInfo);

		return $result;

	}

	private function performBulkSend($sendID,$dbInfo) {

		$sendID = escapeshellarg($sendID);
		foreach ($dbInfo as $key => $value) {
			$dbInfo[$key] = escapeshellarg($value);
		}

		return exec("php ".$engineVars['documentRoot']."/engineIncludes/emailSendBulk.php -id=$sendID -d=".$dbInfo['database']." -t=".$dbInfo['table']." -u=".$dbInfo['username']." -p=".$dbInfo['password']." -s=".$dbInfo['server']." -P=".$dbInfo['port']." -f=".$dbInfo['fileTable']." >/dev/null &");

	}

}
?>
