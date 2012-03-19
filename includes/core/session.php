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
			/** @noinspection PhpUndefinedConstantInspection */
			/** @noinspection PhpUndefinedFunctionInspection */
			if	(session_status()===PHP_SESSION_DISABLED)  throw new SessionException('Sessions are disasbled!');

			ini_set('session.gc_maxlifetime', 3200); // 10hrs
			session_name('ADV' . md5($_SERVER['SERVER_NAME']));
			$old_serializer=$old_handler=$old_path=null;
				if (session_status()===PHP_SESSION_NONE && extension_loaded('Memcached')) {
					$old_handler=ini_set('session.save_handler', 'Memcached');
					$old_path= ini_set('session.save_path', '127.0.0.1:11211');
						(Memcached::HAVE_IGBINARY)  and  $old_serializer=ini_set('session.serialize_handler', 'igbinary');
					session_start();
				}
			if (session_status()===PHP_SESSION_NONE) {
				$old_handler and ini_set('session.save_handler', $old_handler);
				$old_path and ini_set('session.save_path', $old_path);
				$old_serializer and	ini_set('session.serialize_handler', $old_serializer);
			}
			/** @noinspection PhpUndefinedConstantInspection */
			/** @noinspection PhpUndefinedFunctionInspection */
		if	(session_status()!==PHP_SESSION_ACTIVE)  throw new SessionException('Could not start a Session!');


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
