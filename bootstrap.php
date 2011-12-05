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
	defined('ADV_START_TIME') or define('ADV_START_TIME', microtime(true));
	define('DS', DIRECTORY_SEPARATOR);
	define('DOCROOT', realpath(__DIR__) . DS);
	define('APPPATH', DOCROOT . 'includes' . DS . 'app' . DS);
	define('COREPATH', DOCROOT . 'includes' . DS . 'core' . DS);
	define('VENDORPATH', DOCROOT . 'includes' . DS . 'vendor' . DS);
	/***
	 *
	 */
	require DOCROOT . 'base.php';

	define("AJAX_REFERRER", (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
	define('BASE_URL', str_ireplace(realpath(__DIR__), '', DOCROOT));
	define('CRLF', chr(13) . chr(10));
	/***
	 *
	 */
	$path = substr(str_repeat('../', substr_count(str_replace(DOCROOT, '', realpath('.') . DS), DS)), 0, -1);
	define('PATH_TO_ROOT', (!$path) ? '.' : $path);
	require COREPATH . DS . 'autoloader.php';
	Autoloader::add_core_classes(
		array(
			'Adv_Exception', 'Ajax', 'Arr', 'Auth', 'Autoloader', 'Cache', 'Config', 'DatePicker', 'Dates', 'DB',
			'DB_Connection', 'DB_Exception', 'DB_Query', 'DB_Query_Delete', 'DB_Query_Insert', 'DB_Query_Result', 'DB_Query_Select',
			'DB_Query_Update', 'DB_Query_Where', 'Dialog', 'Errors', 'Files', 'gettextNativeSupport', 'HTML', 'Input', 'JS',
			'Language', 'Menu', 'MenuUi', 'Num', 'Session', 'Status', 'UploadHandler'
		)
	);
	Autoloader::add_vendor_classes(
		array(
			'Crypt_AES', 'Crypt_DES',	'Crypt_Hash', 'Crypt_Random', 'Crypt_RC4', 'Crypt_Rijndael', 'Crypt_RSA', 'Crypt_TripleDES',
			'FB','PHPQuickProfiler','Console', 'PHPMailer', 'SMTP', 'OLEwriter','JsHttpRequest', 'TCPDF', 'Cpdf'
		)
	);
	require APPPATH . "main.php";
	Session::init();
	Config::init();
	/***
	 *
	 */	ob_start('adv_ob_flush_handler', 0);

	register_shutdown_function('adv_shutdown_function_handler');
	Errors::init();
	// intercept all output to destroy it in case of ajax call
	// POST vars cleanup needed for direct reuse.
	// We quote all values later with DB::escape() before db update.

	advaccounting::init();