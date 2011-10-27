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

			if (isset($Ajax))
				$Ajax->run();
			// flush all output buffers (works also with exit inside any div levels)
			Config::store();
			while (ob_get_level()) ob_end_flush();
		}
	}
	if (!function_exists('adv_error_handler')) {
		function adv_error_handler() {
			static $firsterror = 0;
			$error = func_get_args();

			if ($firsterror < 2 && !(E_USER_ERROR || E_USER_NOTICE || E_USER_WARNING)) {
				FB::log(array('Line' => $error[3],
					'Message' => $error[1],
					'File' => $error[2], $error
				), 'ERROR');
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
			var_dump(func_get_args());
		}
	}
	if (!function_exists('adv_autoload_handler')) {
		function adv_autoload_handler($className) {
			spl_autoload(strtolower($className));
		}
	}
	function page($title, $no_menu = false, $is_index = false, $onload = "", $js = "", $script_only = false) {

		global $page_security;
		if (empty($page_security)) $page_security = 'SA_OPEN';
		$hide_menu = $no_menu;

		include(APP_PATH . "includes/page/header.php");

		page_header($title, $no_menu, $is_index, $onload, $js);
		Security::check_page($page_security);
		//	Errors::error_box();
		if ($script_only) {
			echo '<noscript>';
			echo ui_msgs::display_heading(_('This page is usable only with javascript enabled browsers.'));
			echo '</noscript>';
			div_start('_page_body', null, true);
		} else {
			div_start('_page_body'); // whole page content for ajax reloading
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
		include(APP_PATH . "includes/page/footer.php");
		page_footer($no_menu, $is_index, $hide_back_link);
	}

	function flush_dir($path, $wipe = false) {
		$dir = opendir($path);
		while (false !== ($fname = readdir($dir))) {
			if ($fname == '.' || $fname == '..' || $fname == 'CVS' || (!$wipe && $fname == 'index.php')) continue;
			if (is_dir($path . '/' . $fname)) {
				flush_dir($path . '/' . $fname, $wipe);
				if ($wipe) @rmdir($path . '/' . $fname);
			} else
				@unlink($path . '/' . $fname);
		}
	}

