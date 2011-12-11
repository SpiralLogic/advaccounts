<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	class Session extends Input
	{
		/**
		 * @static
		 * @return Session
		 */
		public static function init() {
			return static::i();
		}

		/**
		 * @static
		 * @return Session|mixed
		 */
		public static function i() {
			if (static::$_i === null) {
				static::$_i = new static;
			}
			return static::$_i;
		}

		/**
		 * @static
		 *
		 */
		public static function kill() {
			session_unset();
			session_destroy();
		}

		public static function regenerate() {
			session_regenerate_id();
		}

		/**
		 * @static
		 *
		 */
		public static function hasLogin() {
			static::i()->checkLogin();
		}

		/**
		 * @var Session
		 */
		private static $_i = null;
		/**
		 * @var Language
		 */
		public static $lang;
		/***
		 * @var gettextNativeSupport|gettext_php_support
		 */
		public static $get_text;
		/**
		 * @var array
		 */
		protected $installed_languages;
		/**
		 * @var array
		 */
		protected $_session = array();

		/**
		 *
		 */
		final protected function __construct() {
			ini_set('session.gc_maxlifetime', 36000); // 10hrs
			session_name('ADV' . md5($_SERVER['SERVER_NAME']));
			if (!class_exists('Memcached', false)) {
				ini_set('session.save_handler', 'files');
			}
			if (!session_start()) {
				ini_set('session.save_handler', 'files');
				session_start();
			}
			if (!session_start()) {
				die();
			}
			if (isset($_SESSION['HTTP_USER_AGENT'])) {
				if ($_SESSION['HTTP_USER_AGENT'] != sha1($_SERVER['HTTP_USER_AGENT'])) {
				}
			} else {
				$_SESSION['HTTP_USER_AGENT'] = sha1($_SERVER['HTTP_USER_AGENT']);
			}
			header("Cache-control: private");
			$this->setText();
			$this->setLanguage();
			$this->_session = &$_SESSION;
			// Ajax communication object
			if (class_exists('Ajax', false)) {
				$GLOBALS['Ajax'] = Ajax::i();
			}
		}

		/**
		 *
		 */
		protected function setLanguage() {
			if (!isset($_SESSION['Language']) || !method_exists($_SESSION['Language'], 'set_language')) {
				$l = Arr::search_value(Config::get('default_lang'), Config::get('languages.installed'), 'code');
				static::$lang = new Language($l['name'], $l['code'], $l['encoding'], isset($l['rtl']) ? 'rtl' : 'ltr');
				static::$lang->set_language(static::$lang->code);
				if (file_exists(DOCROOT . "lang/" . static::$lang->code . "/locale.php")) {
					/** @noinspection PhpIncludeInspection */
					include(DOCROOT . "lang/" . static::$lang->code . "/locale.php");
				}
				$_SESSION['Language'] = static::$lang;
			} else {
				static::$lang = $_SESSION['Language'];
			}
		}

		/**
		 * @return mixed
		 */
		protected function setText() {
			if (isset($_SESSION['get_text'])) {
				static::$get_text = $_SESSION['get_text'];
				return;
			}
			static::$get_text = $_SESSION['get_text'] = gettextNativeSupport::init();
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