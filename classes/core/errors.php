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
  class Errors
  {
    const DB_DUPLICATE_ERROR_CODE = 1062;
    /** @var array Container for the system messages */
    public static $messages = array();
    /** @var array Container for the system errors */
    public static $errors = array();
    /** @var array */
    protected static $debugLog = array();
    /** @var array Container for DB errors */
    public static $dberrors = array();
    /*** @var bool  Wether the json error status has been sent */
    protected static $jsonerrorsent = false;
    /*** @var int */
    protected static $current_severity = E_ALL;
    /** @var array Error constants to text */
    protected static $session = false;
    /** @var array */
    protected static $levels
      = array(
        -1                => 'Fatal!',
        0                 => 'Error',
        E_ERROR           => 'Error',
        E_WARNING         => 'Warning',
        E_PARSE           => 'Parsing Error',
        E_NOTICE          => 'Notice',
        E_CORE_ERROR      => 'Core Error',
        E_CORE_WARNING    => 'Core Warning',
        E_COMPILE_ERROR   => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR      => 'User Error',
        E_USER_WARNING    => 'User Warning',
        E_USER_NOTICE     => 'User Notice',
        E_STRICT          => 'Runtime Notice',
        E_ALL             => 'No Error',
        E_SUCCESS         => 'Success!'
      );
    /** @var string  temporary container for output html data before error box */
    public static $before_box = '';
    /** @var array Errors which terminate execution */
    protected static $fatal_levels = array(E_PARSE, E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR);
    /** @var array Errors which are user errors */
    protected static $user_errors = array(E_SUCCESS, E_USER_ERROR, E_USER_NOTICE, E_USER_WARNING);
    /** @var array Errors where execution can continue */
    protected static $continue_on = array(E_SUCCESS, E_NOTICE, E_WARNING, E_DEPRECATED, E_STRICT);
    /** @var array Errors to ignore comeletely */
    protected static $ignore = array(E_USER_DEPRECATED, E_DEPRECATED, E_STRICT);
    /** @var */
    protected static $useConfigClass;
    protected static $admin = false;
    /** @static Initialiser */
    public static function init() {
      static::$useConfigClass = class_exists('Config', false);
      //   error_reporting(E_USER_WARNING | E_USER_ERROR | E_USER_NOTICE);
      if (class_exists('\ADV\Core\Event')) {
        Event::registerShutdown(__CLASS__, 'sendDebugEmail');
      }
      static::$admin = strpos($_SERVER['HTTP_HOST'], 'dev') === 0 || (isset($_SESSION['User']) && $_SESSION['User']->username == 'admin');
    }
    /**
     * @static
     *
     * @param      $type
     * @param      $message
     * @param      $file
     * @param      $line
     * @param bool $log
     *
     * @return bool
     */
    public static function handler($type, $message, $file = null, $line = null, $log = true) {
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
        list($message, $file, $line, $log) = explode('||', $message) + [1 => 'No File Given', 2 => 'No Line Given', 3=> true];
      }
      $error = array(
        'type' => $type, 'message' => $message, 'file' => $file, 'line' => $line
      );
      if (in_array($type, static::$user_errors) || in_array($type, static::$fatal_levels)) {
        static::$messages[] = $error;
      }
      if (is_writable(DOCROOT . '../error_log')) {
        error_log(date(DATE_RFC822) . ' ' . $error['type'] . ": " . $error['message'] . " in file: " . $error['file'] . " on line:" . $error['line'] . "\n\n", 3, DOCROOT . '../error_log');
      }
      if (!in_array($type, static::$user_errors) || ($type == E_USER_ERROR && $log)) {
        $error['backtrace'] = static::prepareBacktrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        static::$errors[]   = $error;
      }

      return true;
    }
    /**
     * @static
     *
     * @param \Exception $e
     */
    public static function exceptionHandler(\Exception $e) {
      $error                    = array(
        'type'    => -1,
        'code'    => $e->getCode(),
        'message' => end(explode('\\', get_class($e))) . ' ' . $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine()
      );
      static::$current_severity = -1;
      static::$messages[]       = $error;
      if (is_writable(DOCROOT . '../error_log')) {
        error_log($error['code'] . ": " . $error['message'] . " in file: " . $error['file'] . " on line:" . $error['line'] . "\n", 3, DOCROOT . '../error_log');
      }
      $error['backtrace'] = static::prepareBacktrace($e->getTrace());
      static::$errors[]   = $error;
    }
    /** @static */
    public static function errorBox() {
      printf("<div %s='msgbox'>", AJAX_REFERRER ? 'class' : 'id');
      static::$before_box = ob_get_clean(); // save html content before error box
      ob_start('adv_ob_flush_handler');
      echo "</div>";
    }
    /**
     * @static
     * @return string
     */
    public static function format() {
      $msg_class = array(
        E_USER_ERROR        => array('ERROR', 'err_msg'),
        E_RECOVERABLE_ERROR => array('ERROR', 'err_msg'),
        E_USER_WARNING      => array('WARNING', 'warn_msg'),
        E_USER_NOTICE       => array('USER', 'info_msg'),
        E_SUCCESS           => array('SUCCESS', 'success_msg')
      );
      $content   = '';
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
    public static function sendDebugEmail() {
      if (static::$current_severity == -1 || static::$errors || static::$dberrors || static::$debugLog) {
        $text            = '';
        $with_back_trace = array();
        $text .= count(static::$debugLog) ? "<div><pre><h3>Debug Values: </h3>" . var_export(static::$debugLog, true) . "\n\n" :
          '';
        if (static::$errors) {
          foreach (static::$errors as $id => $error) {
            $with_back_trace[] = $error;
            unset(static::$errors[$id]['backtrace']);
          }
          $text .= "<div><pre><h3>Errors: </h3>" . var_export(static::$errors, true) . "\n\n";
        }
        $text .= static::$dberrors ? "<h3>DB Errors: </h3>" . var_export(static::$dberrors, true) . "\n\n" : '';
        $text .= static::$messages ? "<h3>Messages: </h3>" . var_export(static::$messages, true) . "\n\n" : '';
        $id = md5($text);
        $text .= "<h3>SERVER: </h3>" . var_export($_SERVER, true) . "\n\n";
        $text .= (isset($_POST) && count($_POST)) ? "<h3>POST: </h3>" . var_export($_POST, true) . "\n\n" : '';
        $text .= (isset($_GET) && count($_GET)) ? "<h3>GET: </h3>" . var_export($_GET, true) . "\n\n" : '';
        $text .= (isset($_REQUEST) && count($_REQUEST)) ? "<h3>REQUEST: </h3>" . var_export($_REQUEST, true) . "\n\n" : '';
        $text .= ($with_back_trace) ? "<div><pre><h3>Errors with backtrace: </h3>" . var_export($with_back_trace, true) . "\n\n" :
          '';
        $subject = 'Error log: ';
        $subject .= (isset(static::$session['User'])) ? static::$session['User']->username . ', ' : '';
        if (static::$session) {
          //    unset(static::$session['current_user'], static::$session['config'], static::$session['App']);
          if (isset(static::$session['orders_tbl'])) {
            static::$session['orders_tbl'] = count(static::$session['orders_tbl']);
          }
          if (isset(static::$session['pager'])) {
            static::$session['pager'] = count(static::$session['pager']);
          }
          $text .= "<h3>Session: </h3>" . var_export(static::$session, true) . "\n\n</pre></div>";
        }
        $subject .= (isset(static::$levels[static::$current_severity])) ?
          'Severity: ' . static::$levels[static::$current_severity] : '';
        $subject .= static::$dberrors ? ', DB Error' : '';
        $subject .= ' ' . $id;
        $to      = 'errors@advancedgroup.com.au';
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
    protected static function prepareBacktrace($backtrace) {
      foreach ($backtrace as $key => $trace) {
        if (!isset($trace['file']) || $trace['file'] == __FILE__ || (isset($trace['class']) && $trace['class'] == __CLASS__) || $trace['function'] == 'trigger_error' || $trace['function'] == 'shutdown_handler'
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
      /** @noinspection PhpUndefinedFunctionInspection */
      static::$session = (session_status() == PHP_SESSION_ACTIVE) ? $_SESSION : array();
      // Only show valid fatal errors
      if ($last_error && in_array($last_error['type'], static::$fatal_levels)) {
        if (class_exists('Ajax', false)) {
          Ajax::flush();
        }
        static::$current_severity = -1;
        $error                    = new \ErrorException($last_error['message'], $last_error['type'], 0, $last_error['file'], $last_error['line']);
        static::exceptionHandler($error);
      }
      if (class_exists('Ajax', false) && Ajax::inAjax()) {
        Ajax::run();
      } elseif (AJAX_REFERRER && IS_JSON_REQUEST && !static::$jsonerrorsent) {
        ob_end_clean();
        echo static::getJSONError();
      } elseif (static::$current_severity == -1) {
        static::fatal();
      }
    }
    /**
     * @static
     * @return int
     */
    public static function dbErrorCount() {
      return count(static::$dberrors);
    }
    /**
     * @static
     * @return int
     */
    public static function messageCount() {
      return count(static::$messages);
    }
    /**
     * @static
     * @internal param null $e
     */
    protected static function fatal() {
      ob_end_clean();
      $content = strip_tags(static::format());
      if (!$content) {
        $content = 'A fatal error has occured!';
      }
      $view            = new View('fatal_error');
      $view['message'] = $content;
      $view->set('debug', (static::$admin) ? var_export(Errors::$errors, true) : '');
      $view->render();
      session_write_close();
      if (function_exists('fastcgi_finish_request')) {
        /** @noinspection PhpUndefinedFunctionInspection */
        fastcgi_finish_request();
      }
      static::sendDebugEmail();
      exit();
    }
    /***
     * @static
     * @return int
     */
    public static function getSeverity() {
      return static::$current_severity;
    }
    /**
     * @static
     * @internal param bool $json
     * @return array|bool|string
     */
    public static function JSONError() {
      $status = false;
      if (count(static::$dberrors) > 0) {
        $dberror           = end(static::$dberrors);
        $status['status']  = E_ERROR;
        $status['message'] = $dberror['message'];
      } elseif (count(static::$messages) > 0) {
        $message           = end(static::$messages);
        $status['status']  = $message['type'];
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
    protected static function getJSONError() {
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
    public static function databaseError($error, $sql = null, $data = array()) {
      $errorCode        = DB\DB::errorNo();
      $error['message'] = _("DATABASE ERROR $errorCode:") . $error['message'];
      if ($errorCode == static::DB_DUPLICATE_ERROR_CODE) {
        $error['message'] .= _("The entered information is a duplicate. Please go back and enter different values.");
      }
      $error['debug']     = '<br>SQL that failed was: "' . $sql . '" with data: ' . serialize($data) . '<br>with error: ' . $error['debug'];
      $backtrace          = debug_backtrace();
      $source             = array_shift($backtrace);
      $error['backtrace'] = static::prepareBacktrace($backtrace);
      static::$dberrors[] = $error;
      $db_class_file      = $source['file'];
      while ($source['file'] == $db_class_file) {
        $source = array_shift($backtrace);
      }
      if (is_writable(DOCROOT . '../error_log')) {
        error_log(date(DATE_RFC822) . ": " . var_export($error['debug'], true) . "\n\n\n", 3, DOCROOT . '../error_log');
      }
      trigger_error($error['message'] . '||' . $source['file'] . '||' . $source['line'], E_USER_ERROR);
    }
    /**
     * @static

     */
    public static function log() {
      $source  = reset(debug_backtrace());
      $args    = func_get_args();
      $content = array();
      foreach ($args as $arg) {
        $content[] = var_export($arg, true);
      }
      $error              = array('line' => $source['line'], 'file' => $source['file'], 'content' => $content);
      static::$debugLog[] = $error;
      error_log(date(DATE_RFC822) . ' ' . 'LOG' . ": " . print_r($content, true) . " in file: " . $error['file'] . " on line:" . $error['line'] . "\n\n", 3, DOCROOT . '../error_log');
    }
  }

  Errors::init();
