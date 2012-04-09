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
  use \Memcached;

  /**

   */
  class SessionException extends \Exception {

  }

  ;
  /**

   */
  class Session extends \Input {

    /**
     * @static
     * @return Session|mixed
     */
    static public function i() {
      (static::$i === NULL) and static::$i = new static;
      return static::$i;
    }
    /**
     * @static
     * @return void
     */
    static public function kill() {
      session_unset();
      session_destroy();
    }
    /**
     * @static
     * @return void
     */
    static public function regenerate() {
      session_regenerate_id();
    }
    /**
     * @var Session
     */
    static private $i = NULL;
    /***
     * @var \gettextNativeSupport|\gettext_php_support
     */
    static public $get_text;
    /**
     * @var array
     */
    protected $_session = array();
    /**
     * @throws \ADV\Core\SessionException
     */
    final protected function __construct() {
      /** @noinspection PhpUndefinedConstantInspection */
      /** @noinspection PhpUndefinedFunctionInspection */
      if (session_status() === PHP_SESSION_DISABLED) {
        throw new SessionException('Sessions are disasbled!');
      }
      ini_set('session.gc_maxlifetime', 3200); // 10hrs
      session_name('ADV' . md5($_SERVER['SERVER_NAME']));
      $old_serializer = $old_handler = $old_path = NULL;
      if (session_status() === PHP_SESSION_NONE && extension_loaded('Memcached')) {
        $old_handler = ini_set('session.save_handler', 'Memcached');
        $old_path = ini_set('session.save_path', '127.0.0.1:11211');

        (Memcached::HAVE_IGBINARY)  and  $old_serializer = ini_set('session.serialize_handler', 'igbinary');
        session_start();
      }
      if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.save_handler', $old_handler);
        ini_set('session.save_path', $old_path);
        $old_serializer and  ini_set('session.serialize_handler', $old_serializer);
        session_start();
      }
      if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new SessionException('Could not start a Session!');
      }
      header("Cache-control: private");
      $this->setTextSupport();
      Language::set();
      $this->_session = &$_SESSION;
      // Ajax communication object
      (!class_exists('Ajax'))  or Ajax::i();
    }
    /**
     * @static
     * @return bool
     */
    public static function checkUserAgent() {
      if (Arr::get($_SESSION, 'HTTP_USER_AGENT') != sha1(Arr::get($_SERVER, 'HTTP_USER_AGENT', $_SERVER['REMOTE_ADDR']))) {
        static::setUserAgent();
        return FALSE;
      }
      return TRUE;
    }
    /**
     * @static
     * @return bool
     */
    protected static function setUserAgent() {
      return ($_SESSION['HTTP_USER_AGENT'] = sha1(Arr::get($_SERVER, 'HTTP_USER_AGENT', $_SERVER['REMOTE_ADDR'])));
    }
    /**
     * @return mixed
     */
    protected function setTextSupport() {
      if (isset($_SESSION['get_text'])) {
        static::$get_text = $_SESSION['get_text'];
      }
      else {
        static::$get_text = $_SESSION['get_text'] = \gettextNativeSupport::i();
      }
    }
    /**
     * @param string $var
     *
     * @return mixed|null
     */
    public function __get($var) {
      return isset($this->_session[$var]) ? $this->_session[$var] : NULL;
    }
    /**
     * @param $var
     * @param $value
     *
     * @return void
     */
    public function __set($var, $value) {
      $this->_session[$var] = $value;
    }
  }
