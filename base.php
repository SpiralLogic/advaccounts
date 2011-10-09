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
			while (ob_get_level()) ob_end_flush();
		}
	}
	if (!function_exists('adv_error_handler')) {
		function adv_error_handler() {
			static $firsterror = 0;
			$error = func_get_args();

			if ($firsterror < 2) {
				FB::log(array('Line' => $error[3], 'Message' => $error[1], 'File' => $error[2]), 'ERROR');
				//FB::info(debug_backtrace());
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
	if (!function_exists('adv_autoload_handler')) {
		function adv_autoload_handler($className) {
			spl_autoload(strtolower($className));
		}
	}
	function page($title, $no_menu = false, $is_index = false, $onload = "", $js = "", $script_only = false) {

		global $page_security;

		$hide_menu = $no_menu;

		include(APP_PATH . "includes/page/header.inc");

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
		include(APP_PATH . "includes/page/footer.inc");
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

	function cache_js_file($fpath, $text) {

		if (!Config::get('debug')) {
			$text = js_compress($text);
		}

		$file = fopen($fpath, 'w');
		if (!$file) {
			return false;
		}
		if (!fwrite($file, $text)) {
			return false;
		}
		return fclose($file);
	}

	/**
	 * Compresses the Javascript code for more efficient delivery.
	 * copyright (c) 2005 by Jared White & J. Max Wilson
	 * http://www.xajaxproject.org
	 * Added removing comments from output.
	 * Warning: Fails on RegExp with quotes - use new RegExp() in this case.
	 */
	function js_compress($sJS) {
		//remove windows cariage returns
		$sJS = str_replace("\r", "", $sJS);

		//array to store replaced literal strings
		$literal_strings = array();

		//explode the string into lines
		$lines = explode("\n", $sJS);
		//loop through all the lines, building a new string at the same time as removing literal strings
		$clean = "";
		$inComment = false;
		$literal = "";
		$inQuote = false;
		$escaped = false;
		$quoteChar = "";

		for ($i = 0; $i < count($lines); $i++) {
			$line = $lines[$i];
			$inNormalComment = false;

			//loop through line's characters and take out any literal strings, replace them with ___i___ where i is the index of this string
			$len = strlen($line);
			for ($j = 0; $j < $len; $j++) {
				$c = $line[$j]; // this is _really_ faster than subst
				$d = $c . $line[$j + 1];

				//look for start of quote
				if (!$inQuote && !$inComment) {
					//is this character a quote or a comment
					if (($c == "\"" || $c == "'") && !$inComment && !$inNormalComment) {
						$inQuote = true;
						$inComment = false;
						$escaped = false;
						$quoteChar = $c;
						$literal = $c;
					} else if ($d == "/*" && !$inNormalComment) {
						$inQuote = false;
						$inComment = true;
						$escaped = false;
						$quoteChar = $d;
						$literal = $d;
						$j++;
					} else if ($d == "//") //ignore string markers that are found inside comments
					{
						$inNormalComment = true;
						$clean .= $c;
					} else {
						$clean .= $c;
					}
				} else //allready in a string so find end quote
				{
					if ($c == $quoteChar && !$escaped && !$inComment) {
						$inQuote = false;
						$literal .= $c;

						//subsitute in a marker for the string
						$clean .= "___" . count($literal_strings) . "___";

						//push the string onto our array
						array_push($literal_strings, $literal);
					} else if ($inComment && $d == "*/") {
						$inComment = false;
						$literal .= $d;

						//subsitute in a marker for the string
						$clean .= "___" . count($literal_strings) . "___";

						//push the string onto our array
						array_push($literal_strings, $literal);

						$j++;
					} else if ($c == "\\" && !$escaped)
						$escaped = true; else
						$escaped = false;

					$literal .= $c;
				}
			}
			if ($inComment) $literal .= "\n";
			$clean .= "\n";
		}
		//explode the clean string into lines again
		$lines = explode("\n", $clean);

		//now process each line at a time
		for ($i = 0; $i < count($lines); $i++) {
			$line = $lines[$i];

			//remove comments
			$line = preg_replace("/\/\/(.*)/", "", $line);

			//strip leading and trailing whitespace
			$line = trim($line);

			//remove all whitespace with a single space
			$line = preg_replace("/\s+/", " ", $line);

			//remove any whitespace that occurs after/before an operator
			$line = preg_replace("/\s*([!\}\{;,&=\|\-\+\*\/\)\(:])\s*/", "\\1", $line);

			$lines[$i] = $line;
		}

		//implode the lines
		$sJS = implode("\n", $lines);

		//make sure there is a max of 1 \n after each line
		$sJS = preg_replace("/[\n]+/", "\n", $sJS);

		//strip out line breaks that immediately follow a semi-colon
		$sJS = preg_replace("/;\n/", ";", $sJS);

		//curly brackets aren't on their own
		$sJS = preg_replace("/[\n]*\{[\n]*/", "{", $sJS);

		//finally loop through and replace all the literal strings:
		for ($i = 0; $i < count($literal_strings); $i++) {
			if (strpos($literal_strings[$i], "/*") !== false)
				$literal_strings[$i] = '';
			$sJS = str_replace("___" . $i . "___", $literal_strings[$i], $sJS);
		}
		return $sJS;
	}