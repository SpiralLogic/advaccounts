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
	error_reporting(E_ALL);
	ini_set("display_errors", "On");
	ini_set("ignore_repeated_errors", "On");
	ini_set("log_errors", "On");

	function start_handler($text) {
		global $Ajax;
		// Fatal errors are not send to Errors::handler,
		// so we must check the output
		if ($text && preg_match('/\bFatal error(<.*?>)?:(.*)/i', $text, $m)) {
			$Ajax->aCommands = array(); // Don't update page via ajax on errors
			$text = preg_replace('/\bFatal error(<.*?>)?:(.*)/i', '', $text);
			Errors::$messages[] = array(E_ERROR, $m[2], null, null);
		}
		$Ajax->run();

		return Ajax::in_ajax() ? Errors::format() : Errors::$before_box . Errors::format() . $text;
	}

	function adv_shutdown_function_handler() {
		global $Ajax;

		if (isset($Ajax))
			$Ajax->run();
		// flush all output buffers (works also with exit inside any div levels)
		while (ob_get_level()) ob_end_flush();
	}

	function adv_error_handler() {
		static $firsterror = 0;
		$error = func_get_args();

		if ($firsterror < 2) {
			FB::log(array('Line' => $error[3], 'Message' => $error[1], 'File' => $error[2]), 'ERROR');
			//FB::info(debug_backtrace());
			$firsterror++;
		}
		Errors::handler($error[0], $error[1], $error[2], $error[3]);

		if (!(error_reporting() & $error[0])) {
			// This error code is not included in error_reporting
			return;
		}
		return true;
	}

	function adv_autoload_handler($className) {
		spl_autoload(strtolower($className));
	}

	define('APP_PATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);
	$path = substr(str_repeat('../', substr_count(str_replace(APP_PATH, '', realpath('.') . DIRECTORY_SEPARATOR), '/')), 0, -1);
	$path_to_root = (!$path) ? '.' : $path;
	define('PATH_TO_ROOT', (!$path) ? '.' : $path);
	define("AJAX_REFERRER", (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
	define('BASE_URL', str_ireplace(realpath(__DIR__), '', APP_PATH));
	require_once(APP_PATH . 'includes/autoloader.php');

	Autoloader::init();

	!class_exists('Config', false) and include(APP_PATH . 'includes/classes/config.inc');
	include(APP_PATH . "config.php");
	Config::init();

	include(APP_PATH . "includes/main.inc");

	ini_set('session.gc_maxlifetime', 36000); // 10hrs
	session_name('FA' . md5(dirname(__FILE__)));
	session_start();
	header("Cache-control: private");
	gettext_native_support::get_text_init();

	Errors::init();
	// Page Initialisation

	if (!isset($_SESSION['language']) || !method_exists($_SESSION['language'], 'set_language')) {
		$l = Arr::search_value(Config::get('default_lang'), Config::get(null, null, 'installed_languages'), 'code');

		$_SESSION['language'] = new language($l['name'], $l['code'], $l['encoding'], isset($l['rtl']) ? 'rtl' : 'ltr');
	}
	$_SESSION['language']->set_language($_SESSION['language']->code);

	// include $Hooks object if locale file exists
	if (file_exists(APP_PATH . "lang/" . $_SESSION['language']->code . "/locale.inc")) {
		include(APP_PATH . "lang/" . $_SESSION['language']->code . "/locale.inc");
		if (class_exists('Hooks')) $Hooks = new Hooks();
	}

	// Ajax communication object
	$Ajax = new Ajax();
	// js/php validation rules container
	$Validate = array();
	// bindings for editors
	$Editors = array();
	// page help. Currently help for function keys.
	$Pagehelp = array();

	$SysPrefs = new sys_prefs();
	$Refs = new references();
	// intercept all output to destroy it in case of ajax call
	register_shutdown_function('adv_shutdown_function_handler');
	ob_start('start_handler', 0);

	if (!isset($_SESSION["wa_current_user"])) {
		$_SESSION["wa_current_user"] = new current_user();
	}

	// logout.php is the only page we should have always
	// accessable regardless of access level and current login status.
	if (strstr($_SERVER['PHP_SELF'], 'logout.php') == false) {
		Login::timeout();
		if (!$_SESSION["wa_current_user"]->logged_in()) {
			// Show login screen
			if (!isset($_POST["user_name_entry_field"]) or $_POST["user_name_entry_field"] == "") {
				// strip ajax marker from uri, to force synchronous page reload
				$_SESSION['timeout'] = array('uri' => preg_replace('/JsHttpRequest=(?:(\d+)-)?([^&]+)/s', '', @$_SERVER['REQUEST_URI']), 'post' => $_POST);
				include(APP_PATH . "access/login.php");
				if (Ajax::in_ajax() || AJAX_REFERRER) {
					$Ajax->activate('_page_body');
				}

				exit;
			} else {
				$succeed = (Config::get($_POST["company_login_name"], null, 'db')) && $_SESSION["wa_current_user"]->login($_POST["company_login_name"], $_POST["user_name_entry_field"], $_POST["password"]);
				// select full vs fallback ui mode on login

				$_SESSION["wa_current_user"]->ui_mode = $_POST['ui_mode'];
				if (!$succeed) {
					// Incorrect password
					Login::fail();
				}
				$lang = &$_SESSION['language'];
				$lang->set_language($_SESSION['language']->code);
			}
		}
		else
		{
			if (Input::session('change_password') && strstr($_SERVER['PHP_SELF'], 'change_current_user_password.php') == false) {
				meta_forward('/admin/change_current_user_password.php', 'selected_id=' . $_SESSION["wa_current_user"]->username);
			}
			set_global_connection();
		}
		if (!$_SESSION["wa_current_user"]->old_db) {
			include(APP_PATH . 'company/installed_extensions.php');
		}
		if (!isset($_SESSION["App"])) {
			$_SESSION["App"] = new frontaccounting();
			$_SESSION["App"]->init();
		}
	}

	// POST vars cleanup needed for direct reuse.
	// We quote all values later with db_escape() before db update.
	array_walk($_POST, function(&$v) {
			$v = is_string($v) ? trim($v) : $v;
		});
	$_POST = Security::strip_quotes($_POST);

