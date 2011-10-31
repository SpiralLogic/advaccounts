<?php
	/*
			 Name:           eMail
			 Description:    Simple sending eMail in text and HTML with CC, BCC and attachment
			 Version:        1.0
			 last modified:  2004-05-14

			 Autor:          Daniel K�fer
			 Homepage:       http://www.danielkaefer.de

			 Leave this header in this file!
	 */
	include(dirname(__FILE__).'/email/phpmailer.php');
	class Reports_Email
	{
		var $to = array();
		var $cc = array();
		var $bcc = array();
		var $attachment = array();
		var $boundary = "";
		var $header = "";
		var $subject = "";
		var $body = "";
		var $mail;
		var $toerror = "No vaild email address";

		function __construct($name, $mail)
		{
			$this->mail = new PHPMailer();
			$this->mail->IsSMTP(); // telling the class to use SMTP
			$this->mail->Host = "mx2.sorijen.net.au"; // SMTP server
			$this->mail->SMTPAuth = true;
			$this->mail->WordWrap = 50;
			$this->mail->Username = 'sales@advancedroadsigns.com.au';
			$this->mail->Password = '1w1llenberg';
			$this->mail->From     = "sales@advancedroadsigns.com.au";
			$this->mail->FromName = 'Advanced Group Accounts';
			$this->mail->AddBCC("sales@advancedroadsigns.com.au");
		}

		private function _checkEmail($email)
		{
			if (preg_match('/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/', $email)) {
				$this->toerror = false;
			}
		}

		function to($mail)
		{
			$this->_checkEmail($mail);
			$this->mail->AddAddress($mail);
		}

		function cc($mail)
		{
			$this->_checkEmail($mail);
			$this->mail->AddCC($mail);
		}

		function bcc($mail)
		{
			$this->_checkEmail($mail);
			$this->mail->AddBCC($mail);
		}

		function attachment($file)
		{
			$this->mail->AddAttachment($file);
		}

		function subject($subject)
		{
			$this->mail->Subject = $subject;
		}

		function text($text)
		{
			//$this->mail->ContentType = "Content-Type: text/plain; charset=ISO-8859-1\n";
			//$this->mail->Encoding = "8bit";
			$this->mail->Body = $text . "\n";
		}

		function html($html)
		{
			//$this->mail->ContentType = "text/html; charset=ISO-8859-1";
			//$this->mail->Encoding = "quoted-printable";
			$this->mail->IsHTML(true);
			$this->mail->AltBody = $html . "\n";
			$this->mail->Body    = "<html><body>\n" . $html . "\n</body></html>\n";
		}

		function mime_type($filename)
		{
			$file = basename($filename, '.zip');
			if ($filename == $file . '.zip') {
				return 'application/x-zip-compressed';
			}
			$file = basename($filename, '.pdf');
			if ($filename == $file . '.pdf') {
				return 'application/pdf';
			}
			$file = basename($filename, '.csv');
			if ($filename == $file . '.csv') {
				return 'application/vnd.ms-excel';
			}
			$file = basename($filename, '.tar');
			if ($filename == $file . '.tar') {
				return 'application/x-tar';
			}
			$file = basename($filename, '.tar.gz');
			if ($filename == $file . '.tar.gz') {
				return 'application/x-tar-gz';
			}
			$file = basename($filename, '.tgz');
			if ($filename == $file . '.tgz') {
				return 'application/x-tar-gz';
			}
			$file = basename($filename, '.gz');
			if ($filename == $file . '.gz') {
				return 'application/x-gzip';
			}
			return 'application/unknown';
		}

		function send()
		{
			if ($this->toerror) {
				return false;
			}
			$ret = $this->mail->Send();
			return $ret;
		}
	}

?>