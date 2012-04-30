<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;
    /**

     */
  /**

   */
  class Errors {

    /**

     */
    const DB_DUPLICATE_ERROR_CODE = 1062;
    /** @var array Container for the system messages */
    static public $messages = array();
    /** @var array Container for the system errors */
    static $errors = array();
    /** @var array */
    static protected $debugLog = array();
    /** @var array Container for DB errors */
    static public $dberrors = array();
    /*** @var bool  Wether the json error status has been sent */
    static protected $jsonerrorsent = FALSE;
    /*** @var int */
    static protected $current_severity = E_ALL;
    /** @var array Error constants to text */
    static protected $session = FALSE;
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
    /**
     * @var
     */
    static protected $useConfigClass;
    /** @static Initialiser */
    static function init() {
      static::$useConfigClass = class_exists('Config', FALSE);
      error_reporting(E_USER_WARNING | E_USER_ERROR | E_USER_NOTICE);
      if (class_exists('\ADV\Core\Event')) {
        Event::register_shutdown(__CLASS__, 'send_debug_email');
      }
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
    static function handler($type, $message, $file = NULL, $line = NULL) {
      if (in_array($type, static::$ignore)) {
        return TRUE;
      }
      if (count(static::$errors) > 10 || (static::$useConfigClass && count(static::$errors) > Config::get('debug.throttling'))) {
        static::fatal();
      }
      if (static::$current_severity > $type) {
        static::$current_severity = $type;
      }
      if (in_array($type, static::$user_errors)) {
        list($message, $file, $line) = explode('||', $message) + [1 => 'No File Given', 2 => 'No Line Given'];
      }
      $error = array(
        'type' => $type, 'message' => $message, 'file' => $file, 'line' => $line
      );
      if (in_array($type, static::$user_errors) || in_array($type, static::$fatal_levels)) {
        static::$messages[] = $error;
      }
      if (is_writable(DOCROOT . '../error_log')) {
        error_log($error['type'] . ": " . $error['message'] . " in file: " . $error['file'] . " on line:" . $error['line'] . "\n", 3, DOCROOT . '../error_log');
      }
      if (!in_array($type, static::$user_errors) || $type == E_USER_ERROR) {
        $error['backtrace'] = static::prepare_backtrace(debug_backtrace());
        static::$errors[] = $error;
      }
      return TRUE;
    }
    /**
     * @static
     *
     * @param \Exception $e
     */
    static function exception_handler(\Exception $e) {
      $error = array(
        'type' => -1,
        'code' => $e->getCode(),
        'message' => end(explode('\\', get_class($e))) . ' ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
      );
      static::$current_severity = -1;
      static::$messages[] = $error;
      if (is_writable(DOCROOT . '../error_log')) {
        error_log($error['code'] . ": " . $error['message'] . " in file: " . $error['file'] . " on line:" . $error['line'] . "\n", 3, DOCROOT . '../error_log');
      }
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
        if (class_exists('JS', FALSE)) {
          JS::beforeload("Adv.showStatus();");
        }
      }
      return $content;
    }
    /**
     * @static

     */
    static public function send_debug_email() {
      if ((static::$current_severity == -1 || count(static::$errors) || count(static::$dberrors) || count(static::$debugLog))) {
        $text = '';
        $with_back_trace = array();
        if (count(static::$debugLog)) {
          $text .= "<div><pre><h3>Debug Values: </h3>" . var_export(static::$debugLog, TRUE) . "\n\n";
        }

        if (count(static::$errors)) {
          foreach (static::$errors as $id => $error) {
            $with_back_trace[] = $error;
            unset(static::$errors[$id]['backtrace']);
          }
          $text .= "<div><pre><h3>Errors: </h3>" . var_export(static::$errors, TRUE) . "\n\n";
        }
        if (count(static::$dberrors)) {
          $text .= "<h3>DB Errors: </h3>" . var_export(static::$dberrors, TRUE) . "\n\n";
        }
        if (count(static::$messages)) {
          $text .= "<h3>Messages: </h3>" . var_export(static::$messages, TRUE) . "\n\n";
        }

        $id = md5($text);
        $text .= "<h3>SERVER: </h3>" . var_export($_SERVER, TRUE) . "\n\n";
        if (isset($_POST) && count($_POST)) {
          $text .= "<h3>POST: </h3>" . var_export($_POST, TRUE) . "\n\n";
        }
        if (isset($_GET) && count($_GET)) {
          $text .= "<h3>GET: </h3>" . var_export($_GET, TRUE) . "\n\n";
        }
        if (isset($_REQUEST) && count($_REQUEST)) {
          $text .= "<h3>REQUEST: </h3>" . var_export($_REQUEST, TRUE) . "\n\n";
        }
        if ($with_back_trace) {

          $text .= "<div><pre><h3>Errors with backtrace: </h3>" . var_export($with_back_trace, TRUE) . "\n\n";
        }
        $subject = 'Error log: ';
        if (isset(static::$session['current_user'])) {
          $subject .= static::$session['current_user']->username;
        }
        if (count(static::$session)) {
          unset(static::$session['current_user'], static::$session['config'], static::$session['App']);
          if (isset(static::$session['orders_tbl'])) {
            static::$session['orders_tbl'] = count(static::$session['orders_tbl']);
          }
          if (isset(static::$session['pager'])) {
            static::$session['pager'] = count(static::$session['pager']);
          }
          $text .= "<h3>Session: </h3>" . var_export(static::$session, TRUE) . "\n\n</pre></div>";
        }
        if (isset(static::$levels[static::$current_severity])) {
          $subject .= ', Severity: ' . static::$levels[static::$current_severity];
        }
        if (count(static::$dberrors)) {
          $subject .= ', DB Error';
        }
        $subject .= ' ' . $id;
        $to = 'errors@advancedgroup.com.au';
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= "From: Accounts Errors <errors@advancedgroup.com.au>\r\n";
        $headers .= "Reply-To: errors@advancedgroup.com.au\r\n";
        $headers .= "X-Mailer: " . BUILD_VERSION . "\r\n";
        $success = mail($to, $subject, $text, $headers);
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
       static::$session = (session_status()==PHP_SESSION_ACTIVE)?$_SESSION:array();
      // Only show valid fatal errors
      if ($last_error && in_array($last_error['type'], static::$fatal_levels)) {
        if (class_exists('Ajax', FALSE)) {
          Ajax::i()->aCommands = array();
        }
        static::$current_severity = -1;
        $error = new \ErrorException($last_error['message'], $last_error['type'], 0, $last_error['file'],
          $last_error['line']);
        static::exception_handler($error);
      }
      if (class_exists('Ajax', FALSE) && Ajax::in_ajax()) {
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
     * @internal param null $e
     */
    static protected function fatal() {
      ob_end_clean();
      $content = static::format();
      if (!$content) {
        $content = '<div class="err_msg">A fatal error has occured!</div>';
      }
      if ($_SESSION['current_user']->username == 'admin') {
        $content .= '<pre class="left">' . var_export(Errors::$errors, TRUE) . '</pre>';
      }
      if (class_exists('Page')) {
        \Page::error_exit($content, FALSE);
      }
      session_write_close();
      if (function_exists('fastcgi_finish_request')) {
        /** @noinspection PhpUndefinedFunctionInspection */
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
     * @internal param bool $json
     * @return array|bool|string
     */
    static public function JSONError() {
      $status = FALSE;
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
      static::$jsonerrorsent = TRUE;
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
     * @param       $error
     * @param null  $sql
     * @param array $data
     *
     * @internal param $msg
     * @internal param null $sql_statement
     * @internal param bool $exit
     */
    static public function db_error($error, $sql = NULL, $data = array()) {
      $errorCode = DB\DB::error_no();
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
        $content[] = var_export($arg, TRUE);
      }
      static::$debugLog[] = array('line' => $source['line'], 'file' => $source['file'], 'content' => $content);
    }
  }

  Errors::init();