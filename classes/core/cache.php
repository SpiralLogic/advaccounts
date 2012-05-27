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

  interface Cachable
  {
    /**
     * @abstract
     *
     * @param $key
     * @param $value
     * @param $expires
     *
     * @return mixed
     */
    function set($key, $value, $expires);
    function delete($key);
    function get($key, $default);
  }
  /**

   */
  class Cache
  {
    /**
     * @var Memcached
     */
    protected static $i = null;
    /**
     * @var bool
     */
    protected static $connected = false;
    /**
     * @static
     * @return Memcached
     */
    protected static function i()
    {
      if (static::$i === null) {
        if (class_exists('\\Memcached', false)) {
          $i = new Memcached($_SERVER["SERVER_NAME"] . '.');
          if (!count($i->getServerList())) {
            $i->setOption(Memcached::OPT_RECV_TIMEOUT, 1000);
            $i->setOption(Memcached::OPT_SEND_TIMEOUT, 3000);
            $i->setOption(Memcached::OPT_TCP_NODELAY, true);
            $i->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
            $i->setOption(Memcached::OPT_PREFIX_KEY, $_SERVER["SERVER_NAME"] . '.');
            (Memcached::HAVE_IGBINARY) and $i->setOption(Memcached::SERIALIZER_IGBINARY, true);
            $i->addServer('127.0.0.1', 11211);
          }
          static::$connected = ($i->getVersion() !== false);
          if (static::$connected && isset($_GET['reload_cache'])) {
            $i->flush(0);
            if (function_exists('apc_clear_cache')) {
              apc_clear_cache('user');
            }
            \Display::meta_forward('/index.php');
          }
          static::$i = $i;
        }
      }
      return (static::$connected) ? static::$i : false;
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
    public static function set($key, $value, $expires = 86400)
    {
      if (static::i() !== false) {
        static::i()->set($key, $value, time() + $expires);
      } elseif (class_exists('Session', false)) {
        $_SESSION['cache'][$key] = $value;
      }
      return $value;
    }
    /**
     * @static
     *
     * @param $key
     */
    public static function delete($key)
    {
      if (static::i() !== false) {
        static::i()->delete($key);
      } elseif (class_exists('Session', false)) {
        unset($_SESSION['cache'][$key]);
      }
    }
    /**
     * @static
     *
     * @param      $key
     * @param bool $default
     *
     * @return mixed
     */
    public static function get($key, $default = false)
    {
      if (static::i() !== false) {
        $result = static::i()->get($key);
        $result = (static::$i->getResultCode() === Memcached::RES_NOTFOUND) ? $default : $result;
      } elseif (class_exists('Session', false)) {
        if (!isset($_SESSION['cache'])) {
          $_SESSION['cache'] = array();
        }
        $result = (!isset($_SESSION['cache'][$key])) ? $default : $_SESSION['cache'][$key];
      } else {
        $result = $default;
      }
      return $result;
    }
    /**
     * @static
     * @return mixed
     */
    public static function getStats()
    {
      return (static::$connected) ? static::i()->getStats() : false;
    }
    /**
     * @static
     * @return mixed
     */
    public static function getVersion()
    {
      return (static::$connected) ? static::i()->getVersion() : false;
    }
    /**
     * @static
     * @return mixed
     */
    public static function getServerList()
    {
      return (static::$connected) ? static::i()->getServerList() : false;
    }
    /**
     * @static
     *
     * @param int $time
     */
    public static function flush($time = 0)
    {
      if (static::i()) {
        static::i()->flush($time);
      } else {
        $_SESSION['cache'] = array();
      }
    }
    /**
     * @static
     *
     * @param array|closure $constants
     * @param null          $name
     */
    public static function define_constants($name, $constants)
    {
      if (function_exists('apc_load_constants')) {
        if (!apc_load_constants($name)) {
          if (is_callable($constants)) {
            $constants = (array) call_user_func($constants);
          }
          apc_define_constants($name, $constants);
        }
      } else {
        if (is_callable($constants)) {
          $constants = (array) call_user_func($constants);
        }
        foreach ($constants as $constant => $value) {
          define($constant, $value);
        }
      }
    }
  }

