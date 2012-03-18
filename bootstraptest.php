<?php
	/**
	 * PHP version 5.4
	 *
	 * @category  PHP
	 * @package   ADVAccounts
	 * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
	 * @copyright 2010 - 2012
	 * @link      http://www.advancedgroup.com.au
	 *
	 **/
	include_once('PHPUnit/Autoload.php');
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
	define("AJAX_REFERRER", (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
	define('IS_JSON_REQUEST', (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false));
	define('CRLF', chr(13) . chr(10));
	define('PATH_TO_ROOT', substr(str_repeat('..' . DS, substr_count(BASE_URL . DS, DS)), 0, -1) ? : '.');
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
		if (!class_exists('Event', false)) {
			include(COREPATH . 'event.php');
		}
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
	Config::i();
	include(DOCROOT . 'config' . DS . 'defines.php');
	include(DOCROOT . 'config' . DS . 'types.php');
	include(DOCROOT . 'config' . DS . 'access_levels.php');
