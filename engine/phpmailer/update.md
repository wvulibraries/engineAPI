When updating PHP mailer, the following line needs to be added as the first line of the
protected function smtpSend($header, $body)

require_once $this->PluginDir . 'class.smtp.php';
