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
	class Session {
		public static function init() {
			ini_set('session.gc_maxlifetime', 36000); // 10hrs
			session_name('FA' . md5(dirname(__FILE__)));
			session_start();
			header("Cache-control: private");
			Config::init();
			Errors::init();
		}

		public static function start() {
			gettextNativeSupport::get_text_init();
			if (!isset($_SESSION['language']) || !method_exists($_SESSION['language'], 'set_language')) {
				$l = Arr::search_value(Config::get('default_lang'), Config::get(null, null, 'installed_languages'), 'code');

				$_SESSION['language'] = new language($l['name'], $l['code'], $l['encoding'], isset($l['rtl']) ? 'rtl' : 'ltr');
			}
			$_SESSION['language']->set_language($_SESSION['language']->code);

			// include $Hooks object if locale file exists
			if (file_exists(APP_PATH . "lang/" . $_SESSION['language']->code . "/locale.php")) {
				include(APP_PATH . "lang/" . $_SESSION['language']->code . "/locale.php");
			}

			if (!isset($_SESSION["wa_current_user"])) {
				$_SESSION["wa_current_user"] = new CurrentUser();
			}

			// logout.php is the only page we should have always
			// accessable regardless of access level and current login status.
			// Ajax communication object

			$_GLOBAL['Ajax'] = Ajax::instance();
			// js/php validation rules container

			// bindings for editors

			// page help. Currently help for function keys.

			if (strstr($_SERVER['PHP_SELF'], 'logout.php') == false) {
				Session::timeout();
				if (!$_SESSION["wa_current_user"]->logged_in()) {
					// Show login screen
					if (!isset($_POST["user_name_entry_field"]) or $_POST["user_name_entry_field"] == "") {
						// strip ajax marker from uri, to force synchronous page reload
						$_SESSION['timeout'] = array('uri' => preg_replace('/JsHttpRequest=(?:(\d+)-)?([^&]+)/s', '', @$_SERVER['REQUEST_URI']), 'post' => $_POST);
						include(APP_PATH . "access/login.php");
						if (Ajax::in_ajax() || AJAX_REFERRER) {
							$Ajax->activate('_page_body');
						}
						exit();
					} else {
						$succeed = (Config::get($_POST["company_login_name"], null, 'db')) && $_SESSION["wa_current_user"]->login($_POST["company_login_name"], $_POST["user_name_entry_field"], $_POST["password"]);
						// select full vs fallback ui mode on login

						$_SESSION["wa_current_user"]->ui_mode = $_POST['ui_mode'];
						if (!$succeed) {
							// Incorrect password
							Session::fail();
						}
						$_SESSION['language']->set_language($_SESSION['language']->code);
					}
				}
				else
				{
					if (Input::session('change_password') && strstr($_SERVER['PHP_SELF'], 'change_current_user_password.php') == false) {
						meta_forward('/admin/change_current_user_password.php', 'selected_id=' . $_SESSION["wa_current_user"]->username);
					}
					DBOld::getInstance();
				}

				if (!isset($_SESSION["App"])) {
					$_SESSION["App"] = new frontaccounting();
					$_SESSION["App"]->init();
				}
			}
		}

		static function kill() {
			session_unset();
			session_destroy();
		}

		static function fail() {
			header("HTTP/1.1 401 Authorization Required");
			echo "<center><br><br><font size='5' color='red'><b>" . _("Incorrect Password") . "<b></font><br><br>";
			echo "<b>" . _("The user and password combination is not valid for the system.") . "<b><br><br>";
			echo _("If you are not an authorized user, please contact your system administrator to obtain an account to enable you to use the system.");
			echo "<br><a href='/index.php'>" . _("Try again") . "</a>";
			echo "</center>";
			Session::kill();
			die();
		}

		static function timeout() {
			// skip timeout on logout page
			if ($_SESSION["wa_current_user"]->logged) {
				$tout = $_SESSION["wa_current_user"]->timeout;
				if ($tout && (time() > $_SESSION["wa_current_user"]->last_act + $tout)) {
					$_SESSION["wa_current_user"]->logged = false;
				}

				$_SESSION["wa_current_user"]->last_act = time();
			}
		}
	}


	//--------------------------------------------------------------------------
	function session_timeout() {
		$tout = @DB_Company::get_pref('login_tout'); // mask warning for db ver. 2.2
		return $tout ? $tout : ini_get('session.gc_maxlifetime');
	}