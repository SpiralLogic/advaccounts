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
	class Errors
	{
		const DB_DUPLICATE_ERROR_CODE = 1062;
		public static $messages = array(); // container for system messages
		public static $before_box = ''; // temporary container for output html data before error box
		//-----------------------------------------------------------------------------
		//    Error handler - collects all php/user messages for
		//    display in message box.
		static function init()
		{
			set_error_handler('adv_error_handler');
			set_exception_handler('adv_exception_handler');
			if (Config::get('debug') && CurrentUser::instance()->user == 1) {
				if (preg_match('/Chrome/i', $_SERVER['HTTP_USER_AGENT'])) {
					include(dirname('.') . DS . 'fb.php');
					FB::useFile(APP_PATH . 'tmp' . DS . 'chromelogs', DS . 'tmp' . DS . 'chromelogs');
				} else {
					include(dirname('.') . DS . 'FirePHP/FirePHP.class.php');
					include(dirname('.') . DS . 'FirePHP/fb.php');
				}
				return;
			}
			// colect all error msgs
			error_reporting(E_USER_WARNING | E_USER_ERROR | E_USER_NOTICE);
		}

		static function handler($errno, $errstr, $file, $line)
		{
			// skip well known warnings we don't care about.
			// Please use restrainedly to not risk loss of important messages
			$excluded_warnings = array('html_entity_decode', 'htmlspecialchars');
			foreach ($excluded_warnings as $ref)
			{
				if (strpos($errstr, $ref) !== false) {
					return true;
				}
			}
			// error_reporting==0 when messages are set off with @
			if ($errno & error_reporting()) {
				static::$messages[] = array('error_no'   => $errno,
																		'error_str'  => $errstr,
																		'error_file' => $file,
																		'error_line' => $line);
			} else if ($errno & ~E_NOTICE) { // log all not displayed messages
				error_log(CurrentUser::instance()->loginname . ':' . basename($file) . ":$line: $errstr");
			}
			return true;
		}

		//------------------------------------------------------------------------------
		//	Formats system messages before insert them into message <div>
		// FIX center is unused now
		static function format()
		{
			$msg_class = array(
				E_USER_ERROR   => 'err_msg',
				E_USER_WARNING => 'warn_msg',
				E_USER_NOTICE  => 'note_msg'
			);
			$type    = E_USER_NOTICE;
			$content = '';
			if (PATH_TO_ROOT == '.' && count(static::$messages)) {
				return '';
			}
			foreach (static::$messages as $cnt => $msg) {
				if ($msg['error_no'] > $type) {
					continue;
				}
				if ($msg['error_no'] < $type) {
					$type = ($msg['error_no'] == E_USER_WARNING) ? E_USER_WARNING : E_USER_ERROR; // php or user errors
				}
				$str = $msg['error_string'];
				if ($msg['error_no'] < E_USER_ERROR && $msg['error_no'] != null) {
					$str .= ' ' . _('in file') . ': ' . $msg['error_file'] . ' ' . _('at line ') . $msg['error_line'];
				}
				$content .= ($cnt ? '<hr>' : '') . $str;
			}
			$class   = $msg_class[$type];
			$content = "<div class='$class'>$content</div>";
			return $content;
		}

		//-----------------------------------------------------------------------------
		// Error box <div> element.
		//
		static function error_box()
		{
			echo "<div id='msgbox'>";
			static::$before_box = ob_get_clean(); // save html content before error box
			ob_start('adv_ob_flush_handler');
			echo "</div>";
		}

		/*
						 Helper to avoid sparse log notices.
					 */
		static function show_db_error($msg, $sql_statement = null, $exit = true)
		{
			$db       = DBOld::getInstance();
			$warning  = $msg == null;
			$db_error = DBOld::error_no();
			//	$str = "<span class='errortext'><b>" . _("DATABASE ERROR :") . "</b> $msg</span><br>";
			if ($warning) {
				$str = "<b>" . _("Debug mode database warning:") . "</b><br>";
			}
			else
			{
				$str = "<b>" . _("DATABASE ERROR :") . "</b> $msg<br>";
			}
			if ($db_error != 0) {
				$str .= "error code : " . $db_error . "<br>";
				$str .= "error message : " . DBOld::error_msg($db) . "<br>";
			}
			if (Config::get('debug')) {
				$str .= "sql that failed was : " . $sql_statement . "<br>";
			}
			$str .= "<br><br>";
			if ($msg) {
				trigger_error($str, E_USER_ERROR);
			}
			else // $msg can be null here only in debug mode, otherwise the error is ignored
			{
				trigger_error($str, E_USER_WARNING);
			}
			if ($exit) {
				throw new DB_Exception($str);
			}
		}

		static function nice_db_error($db_error)
		{
			if ($db_error == static::DB_DUPLICATE_ERROR_CODE) {
				ui_msgs::display_error(_("The entered information is a duplicate. Please go back and enter different values."));
				return true;
			}
			return false;
		}

		static function check_db_error($msg, $sql_statement, $exit_if_error = true, $rollback_if_error = true)
		{
			$db_error = DBOld::error_no();
			if ($db_error != 0) {
				if (CurrentUser::instance()->user == 1 && (Config::get('debug') || !Errors::nice_db_error($db_error))) {
					Errors::show_db_error($msg, $sql_statement, false);
				}
				if ($rollback_if_error) {
					DBOld::query("rollback", "could not rollback");
				}
				if ($exit_if_error) {
					throw new DB_Exception($db_error);
					end_page();
				}
			}
			return $db_error;
		}
	}