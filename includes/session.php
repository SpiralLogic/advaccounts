<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
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
		private static $_instance = null;
		public static $lang;
		public static $get_text;
		protected $installed_languages;
		protected $_session = array();

		public static function init()
		{
			if (static::$_instance === null) {
				static::$_instance = new static;
			}
			return static::$_instance;
		}

		public static function get()
		{
			return static::init();
		}

		public static function kill()
		{
			session_unset();
			session_destroy();
		}

		public static function hasLogin()
		{

			static::init()->checkLogin();

		}

		final protected function __construct()
		{
			ini_set('session.gc_maxlifetime', 36000); // 10hrs
			session_name('FA' . md5($_SERVER['SERVER_NAME']));
			session_start();
			header("Cache-control: private");
			$this->setText();
			$this->setLanguage();
			$this->_session = &$_SESSION;
			// Ajax communication object
			$GLOBALS['Ajax'] = Ajax::instance();
		}

		protected function setLanguage()
		{
			if (!isset($_SESSION['language']) || !method_exists($_SESSION['language'], 'set_language')) {
				$l = Arr::search_value(Config::get('default_lang'), Config::get('languages.installed'), 'code');
				static::$lang = new language($l['name'], $l['code'], $l['encoding'], isset($l['rtl']) ? 'rtl' : 'ltr');
				static::$lang->set_language(static::$lang->code);
				if (file_exists(APP_PATH . "lang/" . static::$lang->code . "/locale.php")) {
					include(APP_PATH . "lang/" . static::$lang->code . "/locale.php");
				}
				$_SESSION['language'] = static::$lang;
			} else {
				static::$lang = $_SESSION['language'];
			}
		}

		protected function setText()
		{
			if (isset($_SESSION['get_text'])) {
				static::$get_text = $_SESSION['get_text'];
				return;
			}
			static::$get_text = $_SESSION['get_text'] = gettextNativeSupport::init();
		}

		protected function checkLogin()
		{
			// logout.php is the only page we should have always
			// accessable regardless of access level and current login status.
			$currentUser = User::get();

			if (strstr($_SERVER['PHP_SELF'], 'logout.php') == false) {
				$currentUser->timeout();
				if (!$currentUser->logged_in()) {
					$this->showLogin();
				}

					$succeed = (Config::get('db.' . $_POST["company_login_name"])) && $currentUser->login($_POST["company_login_name"], $_POST["user_name_entry_field"], $_POST["password"]);

				// select full vs fallback ui mode on login
				$currentUser->ui_mode = $_POST['ui_mode'];
				if (!$succeed) {
					// Incorrect password
					$this->loginFail();
				}
				static::$lang->set_language($_SESSION['language']->code);
			} else {
				if (Input::session('change_password') && strstr($_SERVER['PHP_SELF'], 'change_current_user_password.php') == false) {
					meta_forward('/admin/change_current_user_password.php', 'selected_id=' . $currentUser->username);
				}

			}
		}

		protected function showLogin()
		{
			if (!Input::post("user_name_entry_field")) {
				// strip ajax marker from uri, to force synchronous page reload
				$_SESSION['timeout'] = array('uri' => preg_replace('/JsHttpRequest=(?:(\d+)-)?([^&]+)/s', '', @$_SERVER['REQUEST_URI']),
					'post' => $_POST);
				require(APP_PATH . "access/login.php");
				if (Ajax::in_ajax() || AJAX_REFERRER) {
					$Ajax->activate('_page_body');
				}
				exit();
			}
		}

		protected function loginFail()
		{
			header("HTTP/1.1 401 Authorization Required");
			echo "<center><br><br><font size='5' color='red'><b>" . _("Incorrect Password") . "<b></font><br><br>";
			echo "<b>" . _("The user and password combination is not valid for the system.") . "<b><br><br>";
			echo _("If you are not an authorized user, please contact your system administrator to obtain an account to enable you to use the system.");
			echo "<br><a href='/index.php'>" . _("Try again") . "</a>";
			echo "</center>";
			static::kill();
			die();
		}

		public function __get($var)
		{
			return static::_isset($this->_session, $var) ? : null;
		}

		public function __set($var, $value)
		{
			$this->_session[$var] = $value;
		}
	}