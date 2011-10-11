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
	$messages = array(); // container for system messages
	$before_box = ''; // temporary container for output html data before error box

	//-----------------------------------------------------------------------------
	//    Error handler - collects all php/user messages for
	//    display in message box.

	function error_handler($errno, $errstr, $file, $line) {
		global $messages;

		// skip well known warnings we don't care about.
		// Please use restrainedly to not risk loss of important messages
		$excluded_warnings = array('html_entity_decode', 'htmlspecialchars');
		foreach ($excluded_warnings as $ref) {
			if (strpos($errstr, $ref) !== false) {
				return true;
			}
		}

		// error_reporting==0 when messages are set off with @
		if ($errno & error_reporting())
			$messages[] = array($errno, $errstr, $file, $line);
		else if ($errno & ~E_NOTICE) // log all not displayed messages
			error_log($_SESSION["wa_current_user"]->loginname . ':'
				 . basename($file) . ":$line: $errstr");

		return true;
	}

	//------------------------------------------------------------------------------
	//	Formats system messages before insert them into message <div>
	// FIX center is unused now
	function fmt_errors($center = false) {
		global $messages;

		$msg_class = array(
			E_USER_ERROR => 'err_msg',
			E_USER_WARNING => 'warn_msg',
			E_USER_NOTICE => 'note_msg'
		);

		$type = E_USER_NOTICE;
		$content = '';
		//  $class = 'no_msg';
		if (count($messages)) {
			foreach ($messages as $cnt => $msg) {
				if ($msg[0] > $type) continue;

				if ($msg[0] < $type) {
					if ($msg[0] == E_USER_WARNING) {
						$type = E_USER_WARNING; // user warnings
						$content = ''; // clean notices when we have errors
					} else {
						$type = E_USER_ERROR; // php or user errors
						if ($type == E_USER_WARNING)
							$content = ''; // clean other messages
					}
				}
				$str = $msg[1];
				if ($msg[0] < E_USER_ERROR && $msg[2] != null)
					$str .= ' ' . _('in file') . ': ' . $msg[2] . ' ' . _('at line ') . $msg[3];
				$content .= ($cnt ? '<hr>' : '') . $str;
			}
			$class = $msg_class[$type];
			$content = "<div class='$class'>$content</div>";
		} else
			if (PATH_TO_ROOT == '.')
				return '';
		return $content;
	}

	//-----------------------------------------------------------------------------
	// Error box <div> element.
	//
	function error_box() {
		global $before_box;

		echo "<div id='msgbox'>";

		// Necessary restart instead of get_contents/clean calls due to a bug in php 4.3.2
		$before_box = ob_get_clean(); // save html content before error box
		ob_start('output_html');
		echo "</div>";
	}

	/*
	 Helper to avoid sparse log notices.
 */
	function end_flush() {
		global $Ajax;

		if (isset($Ajax))
			$Ajax->run();
		// flush all output buffers (works also with exit inside any div levels)
		while (ob_get_level()) ob_end_flush();
	}

	function display_db_error($msg, $sql_statement = null, $exit = true) {
		global $db;

		$warning = $msg == null;
		$db_error = db_error_no();

		//	$str = "<span class='errortext'><b>" . _("DATABASE ERROR :") . "</b> $msg</span><br>";
		if ($warning)
			$str = "<b>" . _("Debug mode database warning:") . "</b><br>";
		else
			$str = "<b>" . _("DATABASE ERROR :") . "</b> $msg<br>";

		if ($db_error != 0) {
			$str .= "error code : " . $db_error . "<br>";
			$str .= "error message : " . db_error_msg($db) . "<br>";
		}

		if (Config::get('debug')) {
			$str .= "sql that failed was : " . $sql_statement . "<br>";
		}

		$str .= "<br><br>";
		if ($msg)
			trigger_error($str, E_USER_ERROR);
		else // $msg can be null here only in debug mode, otherwise the error is ignored
			trigger_error($str, E_USER_WARNING);
		if ($exit)
			exit;
	}

	function frindly_db_error($db_error) {
		global $db_duplicate_error_code;

		if ($db_error == $db_duplicate_error_code) {
			ui_msgs::display_error(_("The entered information is a duplicate. Please go back and enter different values."));
			return true;
		}

		return false;
	}

	function check_db_error($msg, $sql_statement, $exit_if_error = true, $rollback_if_error = true) {
		$db_error = db_error_no();

		if ($db_error != 0) {

			if ((isset($_SESSION["wa_current_user"]) && $_SESSION["wa_current_user"]->user == 1) && (Config::get('debug') || !frindly_db_error($db_error))) {
				display_db_error($msg, $sql_statement, false);
			}

			if ($rollback_if_error) {
				$rollback_result = db_query("rollback", "could not rollback");
			}

			if ($exit_if_error) {
				end_page();
				exit;
			}
		}
		return $db_error;
	}

?>