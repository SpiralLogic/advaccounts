<?php
	/**
	 * PHP version 5.4
	 *
	 * @category  PHP
	 * @package   ADVAccounts
	 * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
	 * @copyright 2010 - 2012
	 * @link      http://www.advancedgroup.com.au
	 *
	 **/
	class SessionException extends Exception
	{
	}

	;
	/**
	 *
	 */
	class Session extends Input
	{
		/**
		 * @static
		 * @return Session|mixed
		 */
		static public function i() {
			(static::$i === null) and static::$i = new static;
			return static::$i;
		}
		/**
		 * @static

		 */
		static public function kill() {
			session_unset();
			session_destroy();
		}
		/**
		 * @static
		 */
		static public function regenerate() {
			session_regenerate_id();
		}
		/**
		 * @var Session
		 */
		static private $i = null;
		/***
		 * @var gettextNativeSupport|gettext_php_support
		 */
		static public $get_text;
		/**
		 * @var array
		 */
		protected $_session = array();
		/**

		 */
		final protected function __construct() {
			ini_set('session.gc_maxlifetime', 3200); // 10hrs
			session_name('ADV' . md5($_SERVER['SERVER_NAME']));
			if (class_exists('Memcached', false)) {
				ini_set('session.save_handler', 'Memcached');
				ini_set('session.save_path', '127.0.0.1:11211');
				(Memcached::HAVE_IGBINARY)  and ini_set('session.serialize_handler', 'igbinary');
			}
			if (!session_start()) {
				ini_set('session.save_handler', 'files');
				if (!session_start()) {
					throw new SessionException('Could not start a Session Handler');
				}
			}
			if (isset($_SESSION['HTTP_USER_AGENT'])) {
				if ($_SESSION['HTTP_USER_AGENT'] != sha1($_SERVER['HTTP_USER_AGENT'])) {
				}
			}
			else {
				$_SESSION['HTTP_USER_AGENT'] = sha1($_SERVER['HTTP_USER_AGENT']);
			}
			header("Cache-control: private");
			$this->setTextSupport();
			Language::set();
			$this->_session = &$_SESSION;
			// Ajax communication object
			(!class_exists('Ajax'))  or Ajax::i();
		}
		/**
		 * @return mixed
		 */
		protected function setTextSupport() {
			if (isset($_SESSION['get_text'])) {
				static::$get_text = $_SESSION['get_text'];
				return;
			}
			static::$get_text = $_SESSION['get_text'] = gettextNativeSupport::i();
		}
		/**
		 * @param $var
		 *
		 * @return null
		 */
		public function __get($var) {
			return isset($this->_session[$var]) ? $this->_session[$var] : null;
		}
		/**
		 * @param $var
		 * @param $value
		 */
		public function __set($var, $value) {
			$this->_session[$var] = $value;
		}
	}
