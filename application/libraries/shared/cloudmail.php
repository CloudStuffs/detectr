<?php

/**
 * Handles sending of mails
 *
 * @author Hemant Mann
 */
namespace Shared;
use Mail\SMTPClient as MailClient;

class CloudMail extends Framework\Base {
	/**
	 * Stores the sender email-id
	 * @readwrite
	 */
	protected $_from = "user-email";

	/**
	 * Stores the SMTP server address
	 * @readwrite
	 */
	protected $_server = "server-addr";

	/**
	 * Stores the port through which mail is to be sent
	 * @readwrite
	 */
	protected $_port = "25";

	/**
	 * Stores the security information
	 * @readwrite
	 */
	protected $_security = false;

	/**
	 * Stores the password of the mail account
	 */
	protected $_password = "your-password";

	public function __construct($options = array()) {
		parent::__construct($options);

		$smtpClient = new MailClient();
		$smtpClient->setServer($this->server, $this->port, $this->security);
		$smtpClient->setSender($this->from, $this->from, $this->_password);
		$smtpClient->setMail($options["to"], $options["subject"], $options["body"], $contentType = "text/html");

		$smtpClient->sendMail();
	}

}
