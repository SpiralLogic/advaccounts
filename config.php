<?php

	/** ********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 * ********************************************************************* */
	//--------------------------------------------------
	// User configurable variables
	//---------------------------------------------------
	/* Show debug messages returned from an error on the page.
				 Debugging info level also determined by settings in PHP.ini
				 if $debug=1 show debugging info, dont show if $debug=0 */
	if (!class_exists('Config', false)) include(APP_PATH . 'includes/classes/config.inc');
	if (!isset($path_to_root) || isset($_GET['path_to_root']) || isset($_POST['path_to_root'])) {
		//die("Restricted Access");
	}

	Config::load();

	define("AJAX_REFERRER", (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
	define('BASE_URL', str_ireplace(realpath(__DIR__ ), '', APP_PATH));

	if (!defined('ICON_EDIT')) {
		define("ICON_EDIT", "edit.gif");
		define("ICON_DELETE", "delete.gif");
		define("ICON_ADD", "ok.gif");
		define("ICON_UPDATE", "ok.gif");
		define("ICON_OK", "ok.gif");
		define("ICON_CANCEL", "cancel.png");
		define("ICON_GL", "gl.png");
		define("ICON_PRINT", "print.png");
		define("ICON_PDF", "pdf.gif");
		define("ICON_DOC", "invoice.gif");
		define("ICON_CREDIT", "credit.gif");
		define("ICON_RECEIVE", "receive.gif");
		define("ICON_DOWN", "download.gif");
		define("ICON_MONEY", "money.png");
		define("ICON_REMOVE", "remove.png");
		define("ICON_REPORT", "report.png");
		define("ICON_VIEW", "view.gif");
		define("ICON_SUBMIT", "ok.gif");
		define("ICON_ESCAPE", "escape.png");
	}

	define('DEFAULT_LOCATION', 'MEL');
	define('DEFAULT_TAX_GROUP', 1);
	define('DEFAULT_AREA', 1);
	define('DEFAULT_SHIP_VIA', 1);
	define('APP_TITLE', "Advanced Accounting");
	define('VERSION', "2.2.12");
	define('BUILD_VERSION', date("d.m.Y", filemtime(__FILE__)));
	define('POWERED_BY', "Advanced Accounting");
	define('POWERED_URL', "http://www.advancedgroup.com.au");
	define('COMPANY_PATH', APP_PATH . '/company');
	define("BACKUP_PATH", COMPANY_PATH . "/backup/");
	JS::headerFile(array('/js/JsHttpRequest.js', '/js/behaviour.js', '/js/utils.js', '/js/inserts.js', '/js/js2/jquery-1.6.4.min.js', '/js/js2/jquery-ui-1.8.14.min.js'));
	JS::footerFile(array('/js/js2/advanced.js', '/js/js2/shortmenu.js', '/js/js2/jquery.calculator.pack.js', '/js/js2/calc.js'));

	$db_connections = array(
		0 => array('name' => 'Advanced Group PTY LTD',
							 'host' => 'localhost',
							 'dbuser' => 'fa',
							 'dbpassword' => '1willenberg',
							 'dbname' => 'fa',
							 'type' => 'pdo',
							 'connection' => array(
								 'dsn' => 'mysql:host=localhost;dbname=fa',
								 'username' => 'fa',
								 'password' => '1willenberg',
								 'persistent' => false,
							 ),
							 'table_prefix' => '',
							 'charset' => 'utf8',
							 'caching' => false,
							 'profiling' => false,)
	);

