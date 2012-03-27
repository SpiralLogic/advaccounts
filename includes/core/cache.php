<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Cache {
    /**
     * @var Memcached
     */
    static protected $i = NULL;
    /**
     * @var bool
     */
    static protected $connected = FALSE;
    /**
     * @static
     * @return Memcached
     */
    static protected function i() {
      if (static::$i === NULL) {
        if (class_exists('Memcached', FALSE)) {
          $i = new Memcached($_SERVER["SERVER_NAME"] . '.');
          if (!count($i->getServerList())) {
            $i->setOption(Memcached::OPT_RECV_TIMEOUT, 1000);
            $i->setOption(Memcached::OPT_SEND_TIMEOUT, 3000);
            $i->setOption(Memcached::OPT_TCP_NODELAY, TRUE);
            $i->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, TRUE);
            $i->setOption(Memcached::OPT_PREFIX_KEY, $_SERVER["SERVER_NAME"] . '.');
            (Memcached::HAVE_IGBINARY) and $i->setOption(Memcached::SERIALIZER_IGBINARY, TRUE);
            $i->addServer('127.0.0.1', 11211);
          }
          static::$connected = ($i->getVersion() !== FALSE);
          if (static::$connected && isset($_GET['reload_cache'])) {
            $i->flush(0);
            if (function_exists('apc_clear_cache')) {
              apc_clear_cache('user');
            }
          }
          static::$i = $i;
        }
      }
      return (static::$connected) ? static::$i : FALSE;
    }
    /**
     * @static
     *
     * @param     $key
     * @param     $value
     * @param int $expires
     *
     * @return mixed
     */
    static public function set($key, $value, $expires = 86400) {
      if (static::i() !== FALSE) {
        static::i()->set($key, $value, time() + $expires);
      }
      elseif (class_exists('Session', FALSE)) {
        $_SESSION['cache'][$key] = $value;
      }
      return $value;
    }
    /**
     * @static
     *
     * @param $key
     */
    static public function delete($key) {
      if (static::i() !== FALSE) {
        static::i()->delete($key);
      }
      elseif (class_exists('Session', FALSE)) {
        unset($_SESSION['cache'][$key]);
      }
    }
    /**
     * @static
     *
     * @param $key
     *
     * @return mixed
     */
    static public function get($key, $default = FALSE) {
      if (static::i() !== FALSE) {
        $result = static::i()->get($key);
        $result = (static::$i->getResultCode() === Memcached::RES_NOTFOUND) ? $default : $result;
      }
      elseif (class_exists('Session', FALSE)) {
        if (!isset($_SESSION['cache'])) {
          $_SESSION['cache'] = array();
        }
        $result = (!isset($_SESSION['cache'][$key])) ? $default : $_SESSION['cache'][$key];
      }
      else {
        $result = $default;
      }
      return $result;
    }
    /**
     * @static
     * @return mixed
     */
    static public function getStats() {
      return (static::$connected) ? static::i()->getStats() : FALSE;
    }
    /**
     * @static
     * @return mixed
     */
    static public function getVersion() {
      return (static::$connected) ? static::i()->getVersion() : FALSE;
    }
    /**
     * @static
     * @return mixed
     */
    static public function getServerList() {
      return (static::$connected) ? static::i()->getServerList() : FALSE;
    }
    /**
     * @static
     *
     * @param int $time
     */
    static public function flush($time = 0) {
      if (static::i()) {
        static::i()->flush($time);
      }
      else {
        $_SESSION['cache'] = array();
      }
    }
    /**
     * @static
     *
     * @param array|callable $constants
     * @param null           $name
     */
    static public function define_constants($name, $constants) {
      if (function_exists('apc_load_constants')) {

        if (!apc_load_constants($name)) {
          if (is_callable($constants)) {

            $constants = (array) call_user_func($constants);
          }
          apc_define_constants($name, $constants);
        }
      }
      else {
        if (is_callable($constants)) {
          $constants = (array) call_user_func($constants);
        }
        foreach ($constants as $constant => $value) {
          define($constant, $value);
        }
      }
    }
  }

