<?php
	/*
			 Name: eMail
			 Description: Simple sending eMail in text and HTML with CC, BCC and attachment
			 Version: 1.0
			 last modified: 2004-05-14

			 Autor: Daniel K�fer
			 Homepage: http://www.danielkaefer.de

			 Leave this header in this file!
	 */
	class Reports_Email
	{
		public $to = array();
		public $cc = array();
		public $bcc = array();
		public $attachment = array();
		public $boundary = "";
		public $header = "";
		public $subject = "";
		public $body = "";
		public $mail;
		public $toerror = "No vaild email address";

		public function __construct($name, $mail) {
			$this->mail = new PHPMailer();
			$this->mail->IsSMTP(); // telling the class to use SMTP
			$this->mail->Host = Config::get('email.server'); // SMTP server
			$this->mail->SMTPAuth = true;
			$this->mail->WordWrap = 50;
			$this->mail->Username = Config::get('email.username');
			$this->mail->Password = Config::get('email.password');
			$this->mail->From = Config::get('email.from_email');
			$this->mail->FromName = Config::get('email.from_name');
			$bcc = Config::get('email.bcc');
			if ($bcc) {
				$this->mail->AddBCC($bcc);
			}
		}

		private function _checkEmail($email) {
			if (preg_match('/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/', $email)) {
				$this->toerror = false;
			}
		}

		public function to($mail) {
			$this->_checkEmail($mail);
			$this->mail->AddAddress($mail);
		}

		public function from($mail) {
			$this->_checkEmail($mail);
			$this->mail->From = $mail;
		}

		public function cc($mail) {
			$this->_checkEmail($mail);
			$this->mail->AddCC($mail);
		}

		public function bcc($mail) {
			$this->_checkEmail($mail);
			$this->mail->AddBCC($mail);
		}

		public function attachment($file) {
			$this->mail->AddAttachment($file);
		}

		public function subject($subject) {
			$this->mail->Subject = $subject;
		}

		public function text($text) {
			//$this->mail->ContentType = "Content-Type: text/plain; charset=ISO-8859-1\n";
			//$this->mail->Encoding = "8bit";
			$this->mail->Body = $text . "\n";
		}

		public function html($html) {
			//$this->mail->ContentType = "text/html; charset=ISO-8859-1";
			//$this->mail->Encoding = "quoted-printable";
			$this->mail->IsHTML(true);
			$this->mail->AltBody = $html . "\n";
			$this->mail->Body = "<html><body>\n" . $html . "\n</body></html>\n";
		}

		public function mime_type($filename) {
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

		public function send() {
			if ($this->toerror) {
				return false;
			}
			$ret = $this->mail->Send();
			return $ret;
		}
	}