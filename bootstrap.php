<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
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
	defined('ADV_START_TIME') or define('ADV_START_TIME', microtime(true));
	define('DS', DIRECTORY_SEPARATOR);
	/** @define "" "VALUE" */
	define('APP_PATH', realpath(__DIR__) . DS);
	require APP_PATH . 'base.php';
	define("AJAX_REFERRER", (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
	define('BASE_URL', str_ireplace(realpath(__DIR__), '', APP_PATH));
	define('CRLF', chr(13) . chr(10));
	$path = substr(str_repeat('../', substr_count(str_replace(APP_PATH, '', realpath('.') . DS), DS)), 0, -1);
	define('PATH_TO_ROOT', (!$path) ? '.' : $path);
	require APP_PATH . 'includes/autoloader.php';
	Session::init();
	!class_exists('Config', false) and require(APP_PATH . 'includes/config.php');
	Config::init();
	require APP_PATH . "includes/main.php";
	register_shutdown_function('adv_shutdown_function_handler');
	ob_start('adv_ob_flush_handler', 0);
	Errors::init();
	// intercept all output to destroy it in case of ajax call
	// POST vars cleanup needed for direct reuse.
	// We quote all values later with DB::escape() before db update.
	array_walk($_POST, function(&$v)
		{
			$v = is_string($v) ? trim($v) : $v;
		});
	//$_POST = Security::strip_quotes($_POST);
	frontaccounting::init();

