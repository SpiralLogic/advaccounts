<?php
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
	if (!function_exists('adv_shutdown_function_handler')) {
		function adv_shutdown_function_handler() {
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
		}
	}
	if (!function_exists('adv_error_handler')) {
		function adv_error_handler() {
			$error = func_get_args();
			Errors::handler($error[0], $error[1], $error[2], $error[3]);
		}

	}
	if (!function_exists('adv_exception_handler')) {
		function adv_exception_handler(Exception $e) {

			Errors::exception_handler($e);
		}
	}

	if (!function_exists('adv_autoload_handler')) {
		function adv_autoload_handler($className) {

				spl_autoload($className);

		}
	}	if (!function_exists('end_page')) {

	function end_page($no_menu = false, $is_index = false, $hide_back_link = false) {
		if (Input::request('frame' || Input::get('popup'))) {
			$is_index = $hide_back_link = true;
		}
		if (!$is_index && !$hide_back_link && function_exists('link_back')) {
			Display::link_back(true, $no_menu);
		}
		Display::div_end(); // end of _page_body section
		Page::footer($no_menu, $is_index, $hide_back_link);
	}}
