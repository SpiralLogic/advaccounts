<?php

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
	set_exception_handler(function (\Exception $e) {
		Errors::init();
		return \Errors::exception_handler($e);
	});
	set_error_handler(function ($severity, $message, $filepath, $line) {
		Errors::init();
		return \Errors::handler($severity, $message, $filepath, $line);
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
			return Ajax::in_ajax() ? Errors::format() : Errors::$before_box . Errors::format() . $text;
		}
	}
	if (!function_exists('end_page')) {
		function end_page($no_menu = false, $is_index = false, $hide_back_link = false) {
			if (Input::request('frame' || Input::get('popup'))) {
				$is_index = $hide_back_link = true;
			}
			if (!$is_index && !$hide_back_link && function_exists('link_back')) {
				Display::link_back(true, $no_menu);
			}
			Display::div_end(); // end of _page_body section
			Page::footer($no_menu, $is_index, $hide_back_link);
		}
	}
