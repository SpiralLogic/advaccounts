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
	class Session extends Input {
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
		 * @static
		 * @return Session
		 */
		public static function init() {
			if (static::$_i === null) {
				static::$_i = new static;
			}
			return static::$_i;
		}

		/**
		 * @static
		 * @return Session|mixed
		 */
		public static function i() {
			return static::init();
		}

		/**
		 * @static
		 *
		 */
		public static function kill() {
			session_unset();
			session_destroy();
		}

		/**
		 * @static
		 *
		 */
		public static function hasLogin() {
			static::i()->checkLogin();
		}

		/**
		 *
		 */
		final protected function __construct() {
			ini_set('session.gc_maxlifetime', 36000); // 10hrs
			session_name('ADV' . md5($_SERVER['SERVER_NAME']));
			session_start();
			if (isset($_SESSION['HTTP_USER_AGENT'])) {
				if ($_SESSION['HTTP_USER_AGENT'] != sha1($_SERVER['HTTP_USER_AGENT'])) {
					$this->showLogin();
				}
			}
			else
			{
				$_SESSION['HTTP_USER_AGENT'] = sha1($_SERVER['HTTP_USER_AGENT']);
			}
			header("Cache-control: private");
			$this->setText();
			$this->setLanguage();
			$this->_session = &$_SESSION;
			// Ajax communication object
			if (class_exists('Ajax')) $GLOBALS['Ajax'] = Ajax::i();
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
		 *
		 */
		protected function checkLogin() {
			// logout.php is the only page we should have always
			// accessable regardless of access level and current login status.
			$currentUser = User::get();
			if (strstr($_SERVER['PHP_SELF'], 'logout.php') == false) {
				$currentUser->timeout();
				if (!$currentUser->logged_in()) {
					$this->showLogin();
				}
				$succeed = (Config::get('db.' . $_POST["company_login_name"])) && $currentUser->login($_POST["company_login_name"],
					$_POST["user_name_entry_field"], $_POST["password"]);
				// select full vs fallback ui mode on login
				$currentUser->ui_mode = $_POST['ui_mode'];
				if (!$succeed) {
					// Incorrect password
					$this->loginFail();
				}
				session_regenerate_id();
				static::$lang->set_language($_SESSION['Language']->code);
			} else {
				if (Input::session('change_password') && strstr($_SERVER['PHP_SELF'], 'change_current_user_password.php') == false) {
					meta_forward('/system/change_current_user_password.php', 'selected_id=' . $currentUser->username);
				}
			}
		}

		/**
		 *
		 */
		protected function showLogin() {
			$Ajax = Ajax::i();
			if (!Input::post("user_name_entry_field")) {
				// strip ajax marker from uri, to force synchronous page reload
				$_SESSION['timeout'] = array(
					'uri' => preg_replace('/JsHttpRequest=(?:(\d+)-)?([^&]+)/s', '', $_SERVER['REQUEST_URI']),
					'post' => $_POST);
				require(DOCROOT . "access/login.php");
				if (Ajax::in_ajax() || AJAX_REFERRER) {
					$Ajax->activate('_page_body');
				}
				exit();
			}
		}

		/**
		 *
		 */
		protected function loginFail() {
			header("HTTP/1.1 401 Authorization Required");
			echo "<div class='center'><br><br><font size='5' color='red'><b>" . _("Incorrect Password") . "<b></font><br><br>";
			echo "<b>" . _("The user and password combination is not valid for the system.") . "<b><br><br>";
			echo _("If you are not an authorized user, please contact your system administrator to obtain an account to enable you to use the system.");
			echo "<br><a href='/index.php'>" . _("Try again") . "</a>";
			echo "</div>";
			static::kill();
			die();
		}

		/**
		 * @param $var
		 *
		 * @return null
		 */
		public function __get($var) {
			return static::_isset($this->_session, $var) ? : null;
		}

		/**
		 * @param $var
		 * @param $value
		 */
		public function __set($var, $value) {
			$this->_session[$var] = $value;
		}
	}