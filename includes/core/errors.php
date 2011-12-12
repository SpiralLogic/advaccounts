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

	class Errors {
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

		/**
		 * @static
		 *
		 */
		static function init() {

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
		static function exception_handler(\Exception $e) {

			static::$fatal = (bool)(!in_array($e->getCode(), static::$continue_on));

			static::prepare_exception($e, static::$fatal);
		}

		/**
		 * @static
		 * @return string
		 */
		static function format() {

			$msg_class = array(
				E_USER_ERROR => array('ERROR', 'err_msg'), E_USER_WARNING => array('WARNING', 'warn_msg'), E_USER_NOTICE => array('USER', 'note_msg'));

			$content = '';

			while ($msg = static::$messages) {
				$type = $msg['type'];
				$str = $msg['message'];

				if ($type < E_USER_ERROR && $type != null) {
					$str .= ' ' . _('in file') . ': ' . $msg['file'] . ' ' . _('at line ') . $msg['line'];
					$str .= (!isset($msg['backtrace'])) ? : var_export($msg['backtrace']);
					$type = E_USER_ERROR;
				}
				elseif ($type > E_USER_ERROR && $type < E_USER_NOTICE) {
					$type = E_USER_WARNING;
				}
				$class = $msg_class[$type] ? : $msg_class[E_USER_NOTICE];
				if (class_exists('FB', false)) {
					FB::log($msg, $class[0]);
				}
				$content .= "<div test='$class[1]'>$str</div>\n\n";
			}
			return $content;
		}

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
		static function show_db_error($msg, $sql_statement = null) {
			$db_error = DB::error_no();
			if ($db_error == static::DB_DUPLICATE_ERROR_CODE) {
				Errors::error(_("The entered information is a duplicate. Please go back and enter different values."));
				return true;
			}

			if ($db_error == 0) return;
			$str = "<span class='bold'>" . _("DATABASE ERROR $db_error:") . "</span> $msg<br>";

			if ($sql_statement && Config::get('debug')) {
				$str .= "sql that failed was : " . $sql_statement . "<br>with error<br>" . DB::error_msg();
			}
			$str .= "<br>";
			Errors::error($str);
		}


		/**
		 * @static
		 *
		 * @param Exception $e
		 */
		protected static function prepare_exception(\Exception $e) {

			$data = array(
				'type' => $e->getCode(), 'message' => get_class($e) . ' ' . $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'backtrace' => $e->getTrace());

			foreach ($data['backtrace'] as $key => $trace) {
				if (!isset($trace['file'])) {
					unset($data['backtrace'][$key]);
				} elseif ($trace['file'] == COREPATH . 'errors.php') {
					unset($data['backtrace'][$key]);
				}
			}
			static::$messages[] = $data;
		}
	}
