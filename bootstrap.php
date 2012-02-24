<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRAN2Y; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	/**

	 */
	if (extension_loaded('xhprof')) {
		$XHPROF_ROOT = realpath(dirname(__FILE__) . '/xhprof');
		include_once $XHPROF_ROOT . "/xhprof_lib/config.php";
		include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
		include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";
		xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
	}
	error_reporting(-1);
	ini_set('display_errors', 1);
	ini_set("ignore_repeated_errors", "On");
	ini_set("log_errors", "On");
	define('E_SUCCESS', E_ALL << 1);
	define('DS', DIRECTORY_SEPARATOR);
	define('DOCROOT', __DIR__ . DS);
	define('APPPATH', DOCROOT . 'includes' . DS . 'app' . DS);
	define('COREPATH', DOCROOT . 'includes' . DS . 'core' . DS);
	define('VENDORPATH', DOCROOT . 'includes' . DS . 'vendor' . DS);
	defined('ADV_START_TIME') or define('ADV_START_TIME', microtime(true));
	define("AJAX_REFERRER", (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
	define('IS_JSON_REQUEST', (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false));
	define('BASE_URL', str_ireplace(realpath(__DIR__), '', DOCROOT));
	define('CRLF', chr(13) . chr(10));
	define('PATH_TO_ROOT', substr(str_repeat('..' . DS, substr_count(str_replace(DOCROOT, '', realpath('.') . DS), DS)), 0, -1) ? :  '.');
	define('MBSTRING', function_exists('mb_get_info'));
	set_error_handler(function ($severity, $message, $filepath, $line) {
		(!class_exists('Errors', false)) and include(COREPATH . 'errors.php');
		return \Errors::handler($severity, $message, $filepath, $line);
	});
	set_exception_handler(function (\Exception $e) {
		(!class_exists('Errors', false)) and  include(COREPATH . 'errors.php');
		\Errors::exception_handler($e);
	});
	if (!function_exists('e')) {
		function e($string) { return Security::htmlentities($string); }
	}
	require COREPATH . 'autoloader.php';
	register_shutdown_function(function () {
		if (!class_exists('Event', false)) include(COREPATH . 'event.php');
		\Event::shutdown();
	});
	if (!function_exists('adv_ob_flush_handler')) {
		/**
		 * @param $text
		 *
		 * @return string
		 */
		function adv_ob_flush_handler($text) {
			return (Ajax::i()->in_ajax()) ? Errors::format() : Errors::$before_box . Errors::format() . $text;
		}
	}
	Session::i();
	Config::i();
	Ajax::i();
	ob_start('adv_ob_flush_handler', 0);
	include(DOCROOT . 'config' . DS . 'defines.php');
	include(DOCROOT . 'config' . DS . 'types.php');
	include(DOCROOT . 'config' . DS . 'access_levels.php');
	ADVAccounting::i();
