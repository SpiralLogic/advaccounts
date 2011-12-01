<?php
	if (!function_exists('adv_ob_flush_handler')) {
		function adv_ob_flush_handler($text)
		{
			$Ajax = Ajax::i();
			if ($text && preg_match('/\bFatal error(<.*?>)?:(.*)/i', $text)) {
				$Ajax->aCommands    = array();
				Errors::$fatal      = true;
				$text               = '';
				Errors::$messages[] = error_get_last();
			}
			$Ajax->run();
			return Ajax::in_ajax() ? Errors::format() : Errors::$before_box . Errors::format() . $text;
		}
	}
	if (!function_exists('adv_shutdown_function_handler')) {
		function adv_shutdown_function_handler()
		{
			$Ajax = Ajax::i();
			if (isset($Ajax)) {
				$Ajax->run();
			}
			// flush all output buffers (works also with exit inside any div levels)
			while (ob_get_level()) {
				ob_end_flush();
			}
			Config::store();
			Cache::set('autoloads',Autoloader::getLoaded());
		}
	}
	if (!function_exists('adv_error_handler')) {
		function adv_error_handler()
		{
			static $firsterror = 0;
			$error = func_get_args();
			if ($firsterror < 2) {
				$firsterror++;
			}
			Errors::handler($error[0], $error[1], $error[2], $error[3]);
		}
	}
	if (!function_exists('adv_exception_handler')) {
		function adv_exception_handler(Exception $e)
		{
			Errors::handler($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		}
	}
	if (!function_exists('adv_autoload_handler')) {
		function adv_autoload_handler($className)
		{
			try {
				spl_autoload($className);
			} catch (LogicException $e) {
				if (Config::get('debug')) {
					echo('<pre>');
					debug_print_backtrace();
				}
				session_unset();
				session_destroy();
				// strip ajax marker from uri, to force synchronous page reload
				$Ajax                = Ajax::i();
				$_SESSION['timeout'] = array(
					'uri'  => preg_replace('/JsHttpRequest=(?:(\d+)-)?([^&]+)/s', '', @$_SERVER['REQUEST_URI']),
					'post' => $_POST
				);
				require(DOCROOT . "access/login.php");
				if (Ajax::in_ajax() || AJAX_REFERRER) {
					$Ajax->activate('_page_body');
				}
				exit();
			}
		}
	}
	function end_page($no_menu = false, $is_index = false, $hide_back_link = false)
	{

		if (Input::request('frame' || Input::get('popup'))) {
			$is_index = $hide_back_link = true;
		}
		if (!$is_index && !$hide_back_link && function_exists('hyperlink_back')) {
			hyperlink_back(true, $no_menu);
		}
		div_end(); // end of _page_body section
		Page::footer($no_menu, $is_index, $hide_back_link);
	}
