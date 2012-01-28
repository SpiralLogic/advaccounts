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
		 * @var array Container for the system messages
		 */
		static public $messages = array();
		/**
		 * @var array Container for the system errors
		 */
		static public $errors = array();
		/**
		 * @var array Container for DB errors
		 */
		static public $dberrors = array();
		/**
		 * @var bool Whether a fatal error has occuered
		 */
		static public $fatal = false;
		/**
		 * @var int How many messages have currently been recorded
		 */
		static public $count = 0;
		/***
		 * @var bool	Wether the json error status has been sent
		 */
		static protected $jsonerrorsent = false;
		/**
		 * @var array Error constants to text
		 */
		static public $levels
		 = array(
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
			 E_STRICT => 'Runtime Notice'
		 );
		/**
		 * @var string	temporary container for output html data before error box
		 */
		static public $before_box = '';
		/**
		 * @var array Errors which terminate execution
		 */
		static public $fatal_levels = array(E_PARSE, E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR);
		/**
		 * @var array Errors where execution can continue
		 */
		static public $continue_on = array(E_NOTICE, E_WARNING, E_DEPRECATED, E_STRICT);
		/**
		 * @var array Errors to ignore comeletely
		 */
		static public $ignore = array(E_USER_DEPRECATED, E_DEPRECATED, E_STRICT);

		/**
		 * @static Initialiser
		 *
		 */
		static function init() {
			if (class_exists('Config') && class_exists('User') && Config::get('debug') && User::get()->user == 1) {
				if (preg_match('/Chrome/i', $_SERVER['HTTP_USER_AGENT'])) {
					/** @noinspection PhpIncludeInspection */
					include(realpath(VENDORPATH . 'FirePHP/fb.chrome.php'));
					FB::useFile(DOCROOT . 'tmp/chromelogs', DS . 'tmp' . DS . 'chromelogs');
				}
				else {
					/** @noinspection PhpIncludeInspection */
					include(realpath(VENDORPATH . 'FirePHP/FirePHP.class.php'));
					/** @noinspection PhpIncludeInspection */
					include(realpath(VENDORPATH . 'FirePHP/fb.php'));
				}
			}
			else {
				error_reporting(E_USER_WARNING | E_USER_ERROR | E_USER_NOTICE);
			}
			Event::register_shutdown(__CLASS__);
		}

		/**
		 * @static
		 *
		 * @param bool $json
		 *
		 * @return array|bool|string
		 */
		static public function JSONError() {
			$status = false;
			if (count(Errors::$dberrors) > 0) {
				$dberror = end(Errors::$dberrors);
				$status['status'] = false;
				$status['message'] = $dberror['message'];
			}
			elseif (count(Errors::$messages) > 0) {
				$message = end(Errors::$messages);
				$status['status'] = ($message['type'] == E_USER_NOTICE);
				$status['message'] = $message['message'];
				if (Config::get('debug')) {
					$status['var'] = 'file: ' . basename($message['file']) . ' line: ' . $message['line'];
				}
				$status['process'] = '';
			}
			static::$jsonerrorsent = true;
			return $status;
		}

		static public function getJSONError() {
			return json_encode(array('status' => static::JSONError()));
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
		static function handler($type, $message, $file = null, $line = null) {
			if ($type == E_USER_ERROR || $type == E_USER_NOTICE || $type == E_USER_WARNING) {
				list($message, $file, $line) = explode('||', $message);
			}
			if (in_array($type, static::$ignore)) {
				return true;
			}
			if (static::$count > 5) {
				Page::footer_exit();
			}
			static::$count++;
			$error = array(
				'type' => $type, 'message' => $message, 'file' => $file, 'line' => $line
			);
			static::$messages[] = $error;
			if (in_array($type, static::$fatal_levels) || $type == E_USER_ERROR) {
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
			if (static::$count > 5) {
				Page::footer_exit();
			}
			static::$count++;
			static::$fatal = (bool)(!in_array($e->getCode(), static::$continue_on));
			static::prepare_exception($e);
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
				E_USER_NOTICE => array('USER', 'note_msg')
			);
			$content = '';

			foreach (static::$messages as $msg) {
				$type = $msg['type'];
				$str = $msg['message'];
				if ($type < E_USER_ERROR && $type != null) {
					if ($msg['file']) {
						$str .= ' ' . _('in file') . ': ' . $msg['file'] . ' ' . _('at line ') . $msg['line'];
					}
					$str .= (!isset($msg['backtrace'])) ? '' : "\n" . var_export($msg['backtrace'], true);
					$type = E_USER_ERROR;
				}
				$class = $msg_class[$type] ? : $msg_class[E_USER_NOTICE];
				$content .= "<div class='$class[1]'>$str</div>\n\n";
			}
			return $content;
		}

		static public function _shutdown() {
return 			static::send_debug_email();
		}

		static function send_debug_email() {
			if ((Errors::$fatal || count(static::$errors) > 0 || count(static::$dberrors) > 0) && Config::get('debug_email')) {
				$text = "<div><pre><h3>Errors: </h3>" . var_export(static::$errors, true) . "\n\n";
				$text .= "<h3>DB Errors: </h3>" . var_export(static::$dberrors, true) . "\n\n";
				$text .= "<h3>Messages: </h3>" . var_export(static::$messages, true) . "\n\n";
				$text .= "<h3>SERVER: </h3>" . var_export($_SERVER, true) . "\n\n";
				$text .= "<h3>POST: </h3>" . var_export($_POST, true) . "\n\n";
				$text .= "<h3>GET: </h3>" . var_export($_GET, true) . "\n\n";
				$text .= "<h3>Session: </h3>" . var_export($_SESSION, true) . "\n\n</pre></div>";
				$subject = 'Error log: ';
				if (isset($_SESSION['current_user'])) {
					$subject .= $_SESSION['current_user']->username;
				}
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

		/**
		 * @static
		 *
		 */
		static function error_box() {
			printf("<div %s='msgbox'>", AJAX_REFERRER ? 'class' : 'id');
			static::$before_box = ob_get_clean(); // save html content before error box
			ob_start('adv_ob_flush_handler');
			echo "</div>";
		}

		/**
		 * @static
		 *
		 * @param						$msg
		 * @param null			 $sql_statement
		 *
		 * @internal param bool $exit
		 * @throws DBException
		 */
		static function show_db_error($error, $sql = null, $data = array()) {
			$errorCode = DB::error_no();
			$error['message'] = _("DATABASE ERROR $errorCode:") . $error['message'];
			if ($errorCode == static::DB_DUPLICATE_ERROR_CODE) {
				$error['message'] .= _("The entered information is a duplicate. Please go back and enter different values.");
			}
			$error['debug'] = '<br>SQL that failed was: "' . $sql . '" with data: ' . serialize($data) . '<br>with error: ' . $error['debug'];
			$backtrace = debug_backtrace();
			$error['source'] = array_shift($backtrace);
			$error['backtrace'] = var_export($backtrace, true);
			static::$dberrors[] = $error;
			$db_source_file = $error['source']['file'];
			while ($error['source']['file'] == $db_source_file) {
				$error['source'] = array_shift($backtrace);
			}
			trigger_error($error['message'] . '||' . $error['source']['file'] . '||' . $error['source']['line'] . '||', E_USER_ERROR);
		}

		/**
		 * @static
		 *
		 * @param Exception $e
		 */
		static protected function prepare_exception(\Exception $e) {
			$data = array(
				'type' => ($e->getCode()) ? $e->getCode() : E_USER_ERROR,
				'message' => get_class($e) . ' ' . $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'backtrace' => $e->getTrace()
			);
			foreach ($data['backtrace'] as $key => $trace) {
				if (!isset($trace['file'])) {
					unset($data['backtrace'][$key]);
				}
				elseif ($trace['file'] == __FILE__ || $trace['function'] == 'shutdown_handler') {
					unset($data['backtrace'][$key]);
				}
			}
			static::$messages[] = $data;
			static::$errors[] = $data;
		}

		public static function process() {
			$last_error = error_get_last();
			// Only show valid fatal errors
			if ($last_error){// && in_array($last_error['type'], static::$fatal_levels)) {
				Ajax::i()->aCommands = array();
				static::$fatal = true;
				$error = new \ErrorException($last_error['message'], $last_error['type'], 0, $last_error['file'], $last_error['line']);
				static::exception_handler($error);
			}
			if (Ajax::in_ajax()) {
				Ajax::i()->run();
			}
			elseif (AJAX_REFERRER && IS_JSON_REQUEST && !static::$jsonerrorsent) {
				ob_end_clean();
				echo static::getJSONError();
			}
			elseif (static::$fatal) {
				static::fatal();
			}
		}

		static public function fatal() {

			$content = static::format();
			static::send_debug_email();
			Page::error_exit($content);

		}
	}

	Errors::init();

