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
	 *
	 */
	error_reporting(-1);
	ini_set('display_errors', 1);
	define('DS', DIRECTORY_SEPARATOR);
	define('DOCROOT', realpath(__DIR__) . DS);
	define('APPPATH', DOCROOT . 'includes' . DS . 'app' . DS);
	define('COREPATH', DOCROOT . 'includes' . DS . 'core' . DS);
	define('VENDORPATH', DOCROOT . 'includes' . DS . 'vendor' . DS);
	defined('ADV_START_TIME') or define('ADV_START_TIME', microtime(true));
	define("AJAX_REFERRER", (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
	define('BASE_URL', str_ireplace(realpath(__DIR__), '', DOCROOT));
	define('CRLF', chr(13) . chr(10));
	$path = substr(str_repeat('../', substr_count(str_replace(DOCROOT, '', realpath('.') . DS), DS)), 0, -1);
	define('PATH_TO_ROOT', (!$path) ? '.' : $path);
	/**
	 * Do we have access to mbstring?
	 * We need this in order to work with UTF-8 strings
	 */
	define('MBSTRING', function_exists('mb_get_info'));
	/**
	 * Register all the error/shutdown handlers
	 */

	set_exception_handler(function (\Exception $e) {
		return \Errors::exception_handler($e);
	});
	set_error_handler(function ($severity, $message, $filepath, $line) {
		return \Errors::handler($severity, $message, $filepath, $line);
	});
	require COREPATH . 'autoloader.php';
	register_shutdown_function(function () {
			$Ajax = Ajax::i();
			if (isset($Ajax)) {
				$Ajax->run();
			}
			// flush all output buffers (works also with exit inside any div levels)
			while (ob_get_level()) {
				ob_end_flush();
			}
			Config::store();
			Cache::set('autoloads', Autoloader::getLoaded());
		});
	if (!function_exists('adv_ob_flush_handler')) {
		function adv_ob_flush_handler($text) {
			$Ajax = Ajax::i();
			if ($text && preg_match('/\bFatal error(<.*?>)?:(.*)/i', $text)) {
				$Ajax->aCommands = array();
				Errors::$fatal = true;
				$text = '';
				Errors::$messages[] = error_get_last();
			}
			$Ajax->run();
				return ($Ajax->in_ajax()) ? Errors::format() : Errors::$before_box . Errors::format() . $text;
		}
	}
	Session::init();
	Config::init();
	$_GLOBALS['Ajax']=Ajax::i();
Errors::init();
	/***
	 *
	 */
	ob_start('adv_ob_flush_handler', 0);
	// intercept all output to destroy it in case of ajax call
	// POST vars cleanup needed for direct reuse.
	// We quote all values later with DB::escape() before db update.

	array_walk($_POST, function(&$v) {

		$v = is_string($v) ? trim($v) : $v;
	});
	advaccounting::init();