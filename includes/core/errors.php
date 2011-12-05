<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
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
		/**
		 *
		 */
		const DB_DUPLICATE_ERROR_CODE = 1062;
		/**
		 * @var array
		 */
		public static $messages = array(); // container for system messages
		/**
		 * @var bool
		 */
		public static $fatal = false; // container for system messages
		/**
		 * @var string
		 */
		public static $before_box = ''; // temporary container for output html data before error box
		/**
		 * @var array
		 */
		public static $fatal_levels = array(E_PARSE, E_ERROR, E_USER_ERROR, E_COMPILE_ERROR);
		/**
		 * @var array
		 */
		public static $continue_on = array(E_NOTICE, E_WARNING, E_DEPRECATED, E_STRICT);

		// Error handler - collects all php/user messages for
		// display in message box.
		/**
		 * @static
		 *
		 */
		static function init() {
			set_error_handler('adv_error_handler');
			set_exception_handler('adv_exception_handler');
			if (class_exists('Config') && class_exists('User') && Config::get('debug') && User::get()->user == 1) {
				if (preg_match('/Chrome/i', $_SERVER['HTTP_USER_AGENT'])) {
					/** @noinspection PhpIncludeInspection */
					include(realpath(VENDORPATH . 'fb.php'));
					FB::useFile(DOCROOT . 'tmp/chromelogs', DS . 'tmp' . DS . 'chromelogs');
				} else {
					/** @noinspection PhpIncludeInspection */
					include(realpath(VENDORPATH . 'FirePHP/FirePHP.class.php'));
					/** @noinspection PhpIncludeInspection */
					include(realpath(VENDORPATH . 'FirePHP/fb.php'));
				}
			} else {
				error_reporting(E_USER_WARNING | E_USER_ERROR | E_USER_NOTICE);
			}
		}

		/**
		 * @static
		 *
		 * @param $msg
		 */
		static function error($msg) {
			trigger_error($msg, E_USER_ERROR);
		}

		/**
		 * @static
		 *
		 * @param $msg
		 */
		static function notice($msg) {
			trigger_error($msg, E_USER_NOTICE);
		}

		/**
		 * @static
		 *
		 * @param $msg
		 */
		static function warning($msg) {
			trigger_error($msg, E_USER_WARNING);
		}

		/**
		 * @static
		 *
		 * @param $type
		 * @param $message
		 * @param $file
		 * @param $line
		 *
		 * @return bool
		 */
		static function handler($type, $message, $file, $line) {
			// skip well known warnings we don't care about.
			// Please use restrainedly to not risk loss of important messages
			$excluded_warnings = array('html_entity_decode', 'htmlspecialchars');
			foreach ($excluded_warnings as $ref) {
				if (strpos($message, $ref) !== false) {
					return true;
				}
			}
			// error_reporting==0 when messages are set off with @
			$last_error = error_get_last();
			if (!empty($last_error)) {
				extract($last_error);
			}
			if ($type & error_reporting()) {
				static::$messages[] = array(
					'type' => $type, 'message' => $message, 'file' => $file, 'line' => $line);
			} else if ($type & ~E_NOTICE) { // log all not displayed messages
				error_log(User::get()->loginname . ':' . basename($file) . ":$line: $message");
			}
			return true;
		}

		/**
		 * @static
		 *
		 * @param Exception $e
		 */
		static function exception_handler(Exception $e) {
				static::$fatal = (bool)(!in_array($e->getCode(), static::$continue_on));
			static::prepare_exception($e, static::$fatal);
			if (static::$fatal) {
				exit(static::format());
			}
		}

		//	Formats system messages before insert them into message <div>
		// FIX center is unused now
		/**
		 * @static
		 * @return string
		 */
		static function format() {
			$msg_class = array(
				E_USER_ERROR => array('ERROR', 'err_msg'),
				E_USER_WARNING => array('WARNING', 'warn_msg'),
				E_USER_NOTICE => array('USER', 'note_msg'));
			$content = '';
			if (count(static::$messages) == 0) {
				return '';
			}
			foreach (static::$messages as $msg) {
				$type = E_USER_NOTICE;
				if ($msg['type'] > E_USER_NOTICE) {
					continue;
				}
				$str = $msg['message'];
				$str .= ' ' . _('in file') . ': ' . $msg['file'] . ' ' . _('at line ') . $msg['line'];
				if ($msg['type'] <= E_USER_ERROR && $msg['type'] != null) {

					$type = E_USER_ERROR;
				} elseif ($msg['type']>E_USER_ERROR && $msg['type']<E_USER_NOTICE) {
					$type = E_USER_WARNING;
				}
				$class = $msg_class[$type] ? : $msg_class[E_USER_NOTICE];
				if (class_exists('FB', false)) {
					FB::log($msg, $class[0]);
				}
				$content .= "<div class='$class[1]'>$str</div>";
			}
			return $content;
		}

		// Error box <div> element.
		//
		/**
		 * @static
		 *
		 */
		static function error_box() {
			echo "<div id='msgbox'>";
			static::$before_box = ob_get_clean(); // save html content before error box
			ob_start('adv_ob_flush_handler');
			echo "</div>";
		}

		/**
		 * @static
		 *
		 * @param			$msg
		 * @param null $sql_statement
		 * @param bool $exit
		 *
		 * @throws DB_Exception
		 */
		static function show_db_error($msg, $sql_statement = null, $exit = true) {
			$warning = $msg == null;
			$db_error = DB::error_no();
			if ($warning) {
				$str = "<b>" . _("Debug mode database warning:") . "</b><br>";
			} else {
				$str = "<b>" . _("DATABASE ERROR :") . "</b> $msg<br>";
			}
			if ($db_error != 0) {
				$str .= "error code : " . $db_error . "<br>";
				$str .= "error message : " . DB::error_msg() . "<br>";
			}
			if ($sql_statement && Config::get('debug')) {
				$str .= "sql that failed was : " . $sql_statement . "<br>";
			}
			$str .= "<br><br>";
		}

		/**
		 * @static
		 *
		 * @param $db_error
		 *
		 * @return bool
		 */
		static function nice_db_error($db_error) {
			if ($db_error == static::DB_DUPLICATE_ERROR_CODE) {
				Errors::error(_("The entered information is a duplicate. Please go back and enter different values."));
				return true;
			}
			return false;
		}

		/**
		 * @static
		 *
		 * @param			$msg
		 * @param			$sql_statement
		 * @param bool $exit_if_error
		 * @param bool $rollback_if_error
		 *
		 * @return mixed
		 * @throws DB_Exception
		 */
		static function check_db_error($msg, $sql_statement, $exit_if_error = true, $rollback_if_error = true) {
			$db_error = DB::error_no();
			if ($db_error != 0) {
				if (User::get()->user == 1 && (Config::get('debug') || !Errors::nice_db_error($db_error))) {
					Errors::show_db_error($msg, $sql_statement, false);
				}
				if ($rollback_if_error) {
					DB::query("rollback", "could not rollback");
				}
				if ($exit_if_error) {
					throw new DB_Exception($db_error);
				}
			}
			return $db_error;
		}

		/**
		 * @static
		 *
		 * @param Exception $e
		 */
		protected static function prepare_exception(\Exception $e) {
			$data = array(
				'type' => $e->getCode(),
				'message' => get_class($e) . ' ' . $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'backtrace' => $e->getTrace());
			foreach ($data['backtrace'] as $key => $trace)
			{
				if (!isset($trace['file'])) {
					unset($data['backtrace'][$key]);
				}
				elseif ($trace['file'] == COREPATH . 'errors.php')
				{
					unset($data['backtrace'][$key]);
				}
			}
			static::$messages[] = $data;
		}
	}
