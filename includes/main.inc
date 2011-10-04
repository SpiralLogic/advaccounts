<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	include_once(APP_PATH . "admin/db/security_db.inc");

	include_once(APP_PATH . "gl/includes/gl_db.inc");
	//	include_once(APP_PATH . "includes/banking.inc");
	include_once(APP_PATH . "includes/ui/ui_lists.inc");
	include_once(APP_PATH . "includes/ui/ui_controls.inc");
	include_once(APP_PATH . "includes/ui/ui_input.inc");
	//	include_once(APP_PATH . "includes/ui/ui_msgs.inc");
	//	include_once(APP_PATH . "includes/ui/ui_globals.inc");
	include_once(APP_PATH . "includes/db/inventory_db.inc");

	include_once(APP_PATH . "sales/includes/sales_db.inc");
	include_once(APP_PATH . "purchasing/includes/purchasing_db.inc");
	include_once(APP_PATH . "inventory/includes/inventory_db.inc");
	include_once(APP_PATH . "manufacturing/includes/manufacturing_db.inc");

	include_once(APP_PATH . "admin/db/voiding_db.inc");
	//	include_once(APP_PATH . "includes/ui/ui_view.inc");
	include_once(APP_PATH . "includes/data_checks.inc");
	//include_once(APP_PATH . "includes/dates.inc");

	include_once(APP_PATH . "includes/db/connect_db.inc");

	include_once(APP_PATH . "includes/errors.inc");
	include_once(APP_PATH . "includes/systypes.inc");
	//include_once(APP_PATH . "includes/references.inc");
	include_once(APP_PATH . "includes/db/comments_db.inc");
	include_once(APP_PATH . "includes/db/sql_functions.inc");
	include_once(APP_PATH . "includes/db/audit_trail_db.inc");
	include_once(APP_PATH . "admin/db/users_db.inc");

	function page($title, $no_menu = false, $is_index = false, $onload = "", $js = "", $script_only = false) {

		global $page_security;

		$hide_menu = $no_menu;

		include(APP_PATH . "includes/page/header.inc");

		page_header($title, $no_menu, $is_index, $onload, $js);
		check_page_security($page_security);
		//	error_box();
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
