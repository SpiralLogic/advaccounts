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

	function adv_ob_flush_handler($text) {
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

	define('APP_PATH', realpath(__DIR__) . DIRECTORY_SEPARATOR);
	$path = substr(str_repeat('../', substr_count(str_replace(APP_PATH, '', realpath('.') . DIRECTORY_SEPARATOR), '/')), 0, -1);
	$path_to_root = (!$path) ? '.' : $path;
	define('PATH_TO_ROOT', (!$path) ? '.' : $path);
	define("AJAX_REFERRER", (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
	define('BASE_URL', str_ireplace(realpath(__DIR__), '', APP_PATH));
	require_once(APP_PATH . 'includes/autoloader.php');

	Autoloader::init();

	!class_exists('Config', false) and include(APP_PATH . 'includes/classes/config.inc');
	Config::init();

	require_once(APP_PATH . "includes/main.inc");

	// intercept all output to destroy it in case of ajax call
	register_shutdown_function('adv_shutdown_function_handler');
	ob_start('adv_ob_flush_handler', 0);
	// POST vars cleanup needed for direct reuse.
	// We quote all values later with db_escape() before db update.
	array_walk($_POST, function(&$v) {
			$v = is_string($v) ? trim($v) : $v;
		});
	$_POST = Security::strip_quotes($_POST);

