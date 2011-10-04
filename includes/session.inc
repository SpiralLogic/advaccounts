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

	function formatBytes($bytes, $precision = 2) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		// Uncomment one of the following alternatives
		$bytes /= pow(1024, $pow);
		// $bytes /= (1 << (10 * $pow));

		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	function output_html($text) {
		global $before_box, $Ajax, $messages;
		// Fatal errors are not send to error_handler,
		// so we must check the output
		if ($text && preg_match('/\bFatal error(<.*?>)?:(.*)/i', $text, $m)) {
			$Ajax->aCommands = array(); // Don't update page via ajax on errors
			$text = preg_replace('/\bFatal error(<.*?>)?:(.*)/i', '', $text);
			$messages[] = array(E_ERROR, $m[2], null, null);
		}
		$Ajax->run();
		return Ajax::in_ajax() ? fmt_errors() : ($before_box . fmt_errors() . $text);
	}

	function fb_errors() {
		$error = func_get_args();
		FB::info($error);
		FB::info(debug_backtrace());
		error_handler($error[0], $error[1], $error[2], $error[3]);
		if (!(error_reporting() & $error[0])) {
			// This error code is not included in error_reporting
			return;
		}
		return true;
	}

	function kill_login() {
		session_unset();
		session_destroy();
	}

	function login_fail() {

		header("HTTP/1.1 401 Authorization Required");
		echo "<center><br><br><font size='5' color='red'><b>" . _("Incorrect Password") . "<b></font><br><br>";
		echo "<b>" . _("The user and password combination is not valid for the system.") . "<b><br><br>";
		echo _("If you are not an authorized user, please contact your system administrator to obtain an account to enable you to use the system.");
		echo "<br><a href='/index.php'>" . _("Try again") . "</a>";
		echo "</center>";
		kill_login();
		die();
	}

	function check_page_security($page_security) {
		if (!$_SESSION["wa_current_user"]->check_user_access()) {
			$msg = $_SESSION["wa_current_user"]->old_db ? _("Security settings have not been defined for your user account.") . "<br>" . _("Please contact your system administrator.")
			 : _("Please remove \$security_groups and \$security_headings arrays from config.php file!");
			ui_msgs::display_error($msg);
			end_page();
			kill_login();
			exit;
		}
		if (!$_SESSION["wa_current_user"]->can_access_page($page_security)) {
			echo "<center><br><br><br><b>";
			echo _("The security settings on your account do not permit you to access this function");
			echo "</b>";
			echo "<br><br><br><br></center>";
			end_page();
			exit;
		}
	}

	/*
		 Helper function for setting page security level depeding on
		 GET start variable and/or some value stored in session variable.
		 Before the call $page_security should be set to default page_security value.
	 */
	function set_page_security($value = null, $trans = array(), $gtrans = array()) {
		global $page_security;
		// first check is this is not start page call
		foreach ($gtrans as $key => $area) {
			if (isset($_GET[$key])) {
				$page_security = $area;
				return;
			}
		}
		// then check session value
		if (isset($trans[$value])) {
			$page_security = $trans[$value];
			return;
		}
	}

	//	Removing magic quotes from nested arrays/variables
	function strip_quotes($data) {
		if (get_magic_quotes_gpc()) {
			if (is_array($data)) {
				foreach ($data as $k => $v) {
					$data[$k] = strip_quotes($data[$k]);
				}
			} else {
				return stripslashes($data);
			}
		}
		return $data;
	}

	//============================================================================

	function login_timeout() {
		// skip timeout on logout page
		if ($_SESSION["wa_current_user"]->logged) {
			$tout = $_SESSION["wa_current_user"]->timeout;
			if ($tout && (time() > $_SESSION["wa_current_user"]->last_act + $tout)) {
				$_SESSION["wa_current_user"]->logged = false;
			}

			$_SESSION["wa_current_user"]->last_act = time();
		}
	}

	function add_include_path($path = array()) {
		$path = (array)$path;
		$path[] .= get_include_path();
		set_include_path(implode(PATH_SEPARATOR, $path));
	}

	//============================================================================

	function class_autoloader($className) {
		spl_autoload(strtolower($className));
	}

	define('APP_PATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);
	$path = substr(str_repeat('../', substr_count(str_replace(APP_PATH, '', realpath('.') . DIRECTORY_SEPARATOR), '/')), 0, -1);
	$path_to_root = (!$path) ? '.' : $path;
	define('PATH_TO_ROOT', (!$path) ? '.' : $path);
	ini_set('unserialize_callback_func', 'class_autoloader'); // set your callback_function
	spl_autoload_extensions('.php,.inc');
	spl_autoload_register('class_autoloader');
	add_include_path(
		array(
			realpath('.') . '/includes/classes',
			APP_PATH . 'includes/ui2',
			APP_PATH . 'includes/ui',
			APP_PATH . 'includes',
			APP_PATH . 'includes/classes',
			APP_PATH . 'includes/classes/db',
			APP_PATH . 'contacts/includes/classes',
			APP_PATH . 'items/includes/classes',
			APP_PATH . 'sales/includes',
			APP_PATH . 'purchasing/includes',
			APP_PATH . 'reporting/includes'
		));
	include(APP_PATH . "config.php");
	if (Config::get('logs.error.file') != '') {
		ini_set("error_log", Config::get('logs.error.file'));
		ini_set("ignore_repeated_errors", "On");
		ini_set("log_errors", "On");
	}

	include(APP_PATH . "includes/main.inc");

	ini_set('session.gc_maxlifetime', 36000); // 10hrs
	session_name('FA' . md5(dirname(__FILE__)));
	session_start();
	header("Cache-control: private");
	gettext_native_support::get_text_init();
	if (Config::get('debug') && isset($_SESSION["wa_current_user"]) && $_SESSION["wa_current_user"]->user == 1) {

		if (preg_match('/Chrome/i', $_SERVER['HTTP_USER_AGENT'])) {
			include(APP_PATH . 'includes/fb.php');
			FB::useFile(APP_PATH . 'tmp/chromelogs', '/tmp/chromelogs');
		} else {
			include(APP_PATH . 'includes/FirePHP/FirePHP.class.php');
			include(APP_PATH . 'includes/FirePHP/fb.php');
		}
	}
	else {
		Config::set('debug', false);
		error_reporting(E_USER_WARNING | E_USER_ERROR | E_USER_NOTICE);
	}
	// Page Initialisation

	if (!isset($_SESSION['language']) || !method_exists($_SESSION['language'], 'set_language')) {
		$l = Arr::search_value($dflt_lang, $installed_languages, 'code');

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
	register_shutdown_function('end_flush');
	ob_start('output_html', 0);
	// colect all error msgs
	set_error_handler('fb_errors');

	if (!isset($_SESSION["wa_current_user"])) {
		$_SESSION["wa_current_user"] = new current_user();
	}

	// logout.php is the only page we should have always
	// accessable regardless of access level and current login status.
	if (strstr($_SERVER['PHP_SELF'], 'logout.php') == false) {
		login_timeout();
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
				$succeed = isset($db_connections[$_POST["company_login_name"]]) && $_SESSION["wa_current_user"]->login($_POST["company_login_name"], $_POST["user_name_entry_field"], $_POST["password"]);
				// select full vs fallback ui mode on login

				$_SESSION["wa_current_user"]->ui_mode = $_POST['ui_mode'];
				if (!$succeed) {
					// Incorrect password
					login_fail();
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
	$_POST = strip_quotes($_POST);

