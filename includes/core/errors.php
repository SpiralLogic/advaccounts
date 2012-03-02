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

		 */
		const DB_DUPLICATE_ERROR_CODE = 1062;
		/** @var array Container for the system messages */
		static public $messages = array();
		/** @var array Container for the system errors */
		static protected $errors = array();
		/** @var array */
		static protected $debugLog = array();
		/** @var array Container for DB errors */
		static public $dberrors = array();
		/*** @var bool  Wether the json error status has been sent */
		static protected $jsonerrorsent = false;
		/*** @var int */
		static protected $current_severity = E_ALL;
		/** @var array Error constants to text */
		static protected $session = false;
		/** @var array */
		static protected $levels
		 = array(
			 -1 => 'Fatal!',
			 0 => 'Error',
			 E_ERROR => 'Error',
			 E_WARNING => 'Warning',
			 E_PARSE => 'Parsing Error',
			 E_NOTICE => 'Notice',
			 E_CORE_ERROR => 'Core Error',
			 E_CORE_WARNING => 'Core Warning',
			 E_COMPILE_ERROR => 'Compile Error',
			 E_COMPILE_WARNING => 'Compile Warning',
			 E_USER_ERROR => 'User Error',
			 E_USER_WARNING => 'User Warning',
			 E_USER_NOTICE => 'User Notice',
			 E_STRICT => 'Runtime Notice',
			 E_ALL => 'No Error',
			 E_SUCCESS => 'Success!'
		 );
		/** @var string  temporary container for output html data before error box */
		static public $before_box = '';
		/** @var array Errors which terminate execution */
		static protected $fatal_levels = array(E_PARSE, E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR);
		/** @var array Errors which are user errors */
		static protected $user_errors = array(E_SUCCESS, E_USER_ERROR, E_USER_NOTICE, E_USER_WARNING);
		/** @var array Errors where execution can continue */
		static protected $continue_on = array(E_SUCCESS, E_NOTICE, E_WARNING, E_DEPRECATED, E_STRICT);
		/** @var array Errors to ignore comeletely */
		static protected $ignore = array(E_USER_DEPRECATED, E_DEPRECATED, E_STRICT);
		static protected $useConfigClass;
		/** @static Initialiser */
		static function init() {
			static::$useConfigClass = class_exists('Config');
			error_reporting(E_USER_WARNING | E_USER_ERROR | E_USER_NOTICE);
			if (class_exists('Event', false)) {
				Event::register_shutdown(__CLASS__);
			}
		}
		/**
		 * @static
		 *
		 * @param $type
		 * @param $message
		 * @param $file
		 * @param $line
		 */
		static function handler($type, $message, $file = null, $line = null) {
			if (in_array($type, static::$ignore)) {
				return true;
			}
			if (count(static::$errors) > 10 || (static::$useConfigClass && count(static::$errors) > Config::get('debug.throttling'))) {
				static::fatal();
			}
			if (static::$current_severity > $type) {
				static::$current_severity = $type;
			}
			if (in_array($type, static::$user_errors)) {
				list($message, $file, $line) = explode('||', $message);
			}
			$error = array(
				'type' => $type, 'message' => $message, 'file' => $file, 'line' => $line
			);
			if (in_array($type, static::$user_errors) || in_array($type, static::$fatal_levels)) {
				static::$messages[] = $error;
			}
			if (!in_array($type, static::$user_errors) || $type == E_USER_ERROR) {
				$error['backtrace'] = static::prepare_backtrace(debug_backtrace());
				static::$errors[] = $error;
			}
			return true;
		}
		/**
		 * @static
		 *
		 * @param Exception $e
		 */
		static function exception_handler(\Exception $e) {
			$error = array(
				'type' => -1,
				'code' => $e->getCode(),
				'message' => get_class($e) . ' ' . $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine()
			);
			static::$current_severity = -1;
			static::$messages[] = $error;
			$error['backtrace'] = static::prepare_backtrace($e->getTrace());
			static::$errors[] = $error;
		}
		/** @static */
		static function error_box() {
			printf("<div %s='msgbox'>", AJAX_REFERRER ? 'class' : 'id');
			static::$before_box = ob_get_clean(); // save html content before error box
			ob_start('adv_ob_flush_handler');
			echo "</div>";
		}
		/**
		 * @static
		 * @return string
		 */
		static function format() {
			$msg_class = array(
				E_USER_ERROR => array('ERROR', 'err_msg'),
				E_RECOVERABLE_ERROR => array('ERROR', 'err_msg'),
				E_USER_WARNING => array('WARNING', 'warn_msg'),
				E_USER_NOTICE => array('USER', 'info_msg'),
				E_SUCCESS => array('SUCCESS', 'success_msg')
			);
			$content = '';
			foreach (static::$messages as $msg) {
				if (!isset($msg['type']) || $msg['type'] < E_USER_ERROR) {
					$msg['type'] = E_USER_ERROR;
				}
				$class = $msg_class[$msg['type']] ? : $msg_class[E_USER_NOTICE];
				$content .= "<div class='$class[1]'>" . $msg['message'] . "</div>\n";
			}
			if (static::$current_severity > -1) {
				if (class_exists('JS', false)) {
					JS::beforeload("Adv.showStatus();");
				}
			}
			return $content;
		}
		/**
		 * @static

		 */
		static public function _shutdown() {
			static::send_debug_email();
		}
		/**
		 * @static

		 */
		static protected function send_debug_email() {
			if (!class_exists('Reports_Email')) {
				return;
			}
			if ((static::$current_severity == -1 || count(static::$errors) || count(static::$dberrors) || count(static::$debugLog))
			 && static::$useConfigClass && Config::get('debug.email')) {
				$withbacktrace = $text = '';
				if (count(static::$debugLog)) {
					$text .= "<div><pre><h3>Debug Values: </h3>" . var_export(static::$debugLog, true) . "\n\n";
				}
				if (count(static::$errors)) {
					$withbacktrace = array();
					foreach (static::$errors as $id => $error) {
						$withbacktrace[] = $error;
						unset(static::$errors[$id]['backtrace']);
					}
					$text .= "<div><pre><h3>Errors: </h3>" . var_export(static::$errors, true) . "\n\n";
				}
				if (count(static::$dberrors)) {
					$text .= "<h3>DB Errors: </h3>" . var_export(static::$dberrors, true) . "\n\n";
				}
				if (count(static::$messages)) {
					$text .= "<h3>Messages: </h3>" . var_export(static::$messages, true) . "\n\n";
				}
				$id = md5($text);
				$text .= "<h3>SERVER: </h3>" . var_export($_SERVER, true) . "\n\n";
				if (isset($_POST) && count($_POST)) {
					$text .= "<h3>POST: </h3>" . var_export($_POST, true) . "\n\n";
				}
				if (isset($_GET) && count($_GET)) {
					$text .= "<h3>GET: </h3>" . var_export($_GET, true) . "\n\n";
				}
				if (isset($_REQUEST) && count($_REQUEST)) {
					$text .= "<h3>REQUEST: </h3>" . var_export($_REQUEST, true) . "\n\n";
				}
				if ($withbacktrace) {
					$text .= "<div><pre><h3>Errors with backtrace: </h3>" . var_export($withbacktrace, true) . "\n\n";
				}
				$subject = 'Error log: ';
				if (isset(static::$session['current_user'])) {
					$subject .= static::$session['current_user']->username;
				}
				if (count(static::$session)) {
					unset(static::$session['current_user'], static::$session['config'], static::$session['App'], static::$session['orders_tbl']);
					$text .= "<h3>Session: </h3>" . var_export(static::$session, true) . "\n\n</pre></div>";
				}
				if (isset(static::$levels[static::$current_severity])) {
					$subject .= ', Severity: ' . static::$levels[static::$current_severity];
				}
				if (count(static::$dberrors)) {
					$subject .= ', DB Error';
				}
				$subject .= ' ' . $id;
				$mail = new Reports_Email(false);
				$mail->to('errors@advancedgroup.com.au');
				$mail->mail->FromName = "Accounts Errors";
				$mail->subject($subject);
				$mail->html($text);
				$success = $mail->send();
				if (!$success) {
					static::handler(E_ERROR, $success, __FILE__, __LINE__);
				}
			}
		}
		/***
		 * @static
		 *
		 * @param $backtrace
		 *
		 * @return mixed
		 */
		static protected function prepare_backtrace($backtrace) {
			foreach ($backtrace as $key => $trace) {
				if (!isset($trace['file']) || $trace['file'] == __FILE__ || (isset($trace['class']) && $trace['class'] == __CLASS__)
				 || $trace['function'] == 'trigger_error' || $trace['function'] == 'shutdown_handler'
				) {
					unset($backtrace[$key]);
				}
			}
			return $backtrace;
		}
		/**
		 * @static

		 */
		public static function process() {
			$last_error = error_get_last();
			static::$session = $_SESSION;
			// Only show valid fatal errors
			if ($last_error && in_array($last_error['type'], static::$fatal_levels)) {
				if (class_exists('Ajax', false)) {
					Ajax::i()->aCommands = array();
				}
				static::$current_severity = -1;
				$error = new \ErrorException($last_error['message'], $last_error['type'], 0, $last_error['file'],
					$last_error['line']);
				static::exception_handler($error);
			}
			if (class_exists('Ajax', false) && Ajax::in_ajax()) {
				Ajax::i()->run();
			}
			elseif (AJAX_REFERRER && IS_JSON_REQUEST && !static::$jsonerrorsent) {
				ob_end_clean();
				echo static::getJSONError();
			}
			elseif (static::$current_severity == -1) {
				static::fatal();
			}
		}
		/**
		 * @static
		 * @return int
		 */
		static public function dbErrorCount() {
			return count(static::$dberrors);
		}
		/**
		 * @static
		 * @return int
		 */
		static public function messageCount() {
			return count(static::$messages);
		}
		/**
		 * @static
		 *
		 * @param null $e
		 */
		static protected function fatal() {
			ob_end_clean();
			$content = static::format();
			if (!$content) {
				$content = '<div class="err_msg">A fatal error has occured!</div>';
			}
			if (class_exists('Page')) {
				Page::error_exit($content, false);
			}
			session_write_close();
			if (function_exists('fastcgi_finish_request')) {
				fastcgi_finish_request();
			}
			static::send_debug_email();
			exit();
		}
		/***
		 * @static
		 * @return int
		 */
		static public function getSeverity() { return static::$current_severity; }
		/**
		 * @static
		 *
		 * @param bool $json
		 *
		 * @return array|bool|string
		 */
		static public function JSONError() {
			$status = false;
			if (count(static::$dberrors) > 0) {
				$dberror = end(static::$dberrors);
				$status['status'] = E_ERROR;
				$status['message'] = $dberror['message'];
			}
			elseif (count(static::$messages) > 0) {
				$message = end(static::$messages);
				$status['status'] = $message['type'];
				$status['message'] = $message['message'];
				if (static::$useConfigClass && Config::get('debug.enabled')) {
					$status['var'] = 'file: ' . basename($message['file']) . ' line: ' . $message['line'];
				}
				$status['process'] = '';
			}
			static::$jsonerrorsent = true;
			return $status;
		}
		/**
		 * @static
		 * @return string
		 */
		static protected function getJSONError() {
			return json_encode(array('status' => static::JSONError()));
		}
		/**
		 * @static
		 *
		 * @param            $msg
		 * @param null       $sql_statement
		 *
		 * @internal param bool $exit
		 * @throws DBException
		 */
		static public function db_error($error, $sql = null, $data = array()) {
			$errorCode = DB::error_no();
			$error['message'] = _("DATABASE ERROR $errorCode:") . $error['message'];
			if ($errorCode == static::DB_DUPLICATE_ERROR_CODE) {
				$error['message'] .= _("The entered information is a duplicate. Please go back and enter different values.");
			}
			$error['debug'] = '<br>SQL that failed was: "' . $sql . '" with data: ' . serialize($data) . '<br>with error: ' . $error['debug'];
			$backtrace = debug_backtrace();
			$source = array_shift($backtrace);
			$error['backtrace'] = static::prepare_backtrace($backtrace);
			static::$dberrors[] = $error;
			$db_class_file = $source['file'];
			while ($source['file'] == $db_class_file) {
				$source = array_shift($backtrace);
			}
			trigger_error($error['message'] . '||' . $source['file'] . '||' . $source['line'], E_USER_ERROR);
		}
		/**
		 * @static

		 */
		static public function log() {
			$source = reset(debug_backtrace());
			$args = func_get_args();
			$content = array();
			foreach ($args as $arg) {
				$content[] = var_export($arg, true);
			}
			static::$debugLog[] = array('line' => $source['line'], 'file' => $source['file'], 'content' => $content);
		}
	}

	Errors::init();
