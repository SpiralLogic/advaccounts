<?php
	if (!function_exists('adv_ob_flush_handler')) {
		function adv_ob_flush_handler($text) {
			$Ajax = Ajax::instance();
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
	}
	if (!function_exists('adv_shutdown_function_handler')) {
		function adv_shutdown_function_handler() {
			$Ajax = Ajax::instance();
			if (isset($Ajax)) {
				$Ajax->run();
			}
			// flush all output buffers (works also with exit inside any div levels)
			Config::store();
			while (ob_get_level()) {
				ob_end_flush();
			}
		}
	}
	if (!function_exists('adv_error_handler')) {
		function adv_error_handler() {
			static $firsterror = 0;
			$error = func_get_args();
			if ($firsterror < 2 && !(E_USER_ERROR || E_USER_NOTICE || E_USER_WARNING)) {
				FB::log(
					array(
						'Line' => $error[3],
						'Message' => $error[1],
						'File' => $error[2], $error
					), 'ERROR'
				);
				//var_dump(debug_backtrace());
				$firsterror++;
			}
			Errors::handler($error[0], $error[1], $error[2], $error[3]);
			if (!(error_reporting() & $error[0])) {
				// This error code is not included in error_reporting
				return;
			}
			return true;
		}
	}
	if (!function_exists('adv_exception_handler')) {
		function adv_exception_handler() {
			echo '<pre>';
			var_dump(func_get_args());
		}
	}
	if (!function_exists('adv_autoload_handler')) {
		function adv_autoload_handler($className) {
			try {
				spl_autoload(strtolower($className));
			}
			catch (LogicException $e) {
				echo('<pre>');
		if (Config::get('debug'))		debug_print_backtrace();
				session_unset();
				session_destroy();
				// strip ajax marker from uri, to force synchronous page reload
							$_SESSION['timeout'] = array(
								'uri' => preg_replace('/JsHttpRequest=(?:(\d+)-)?([^&]+)/s', '', @$_SERVER['REQUEST_URI']),
								'post' => $_POST
							);
							include(APP_PATH . "access/login.php");
							if (Ajax::in_ajax() || AJAX_REFERRER) {
								$Ajax->activate('_page_body');
							}
							exit();
			}
		}
	}
	function end_page($no_menu = false, $is_index = false, $hide_back_link = false) {
		if (isset($_REQUEST['frame']) && $_REQUEST['frame']) {
			$nomenu = $is_index = $hide_back_link = true;
		}
		if (!$is_index && !$hide_back_link && function_exists('hyperlink_back')) {
			hyperlink_back(true, $no_menu);
		}
		div_end(); // end of _page_body section
		//if (!$_REQUEST['frame'])
		Page::footer($no_menu, $is_index, $hide_back_link);
	}


