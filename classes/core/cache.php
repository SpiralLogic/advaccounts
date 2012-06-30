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
    /**
     * @abstract
     *
     * @param $key
     *
     * @return mixed
     */
    function delete($key);
    /**
     * @abstract
     *
     * @param $key
     * @param $default
     *
     * @return mixed
     */
    function get($key, $default);
  }
  /**
   * @method get($key, $default = false)
   * @method set($key, $value, $expires = 86400)
   * @method define_constants($name, $constants)
   * @method delete($key)
   * @method Cache i()
   */
  class Cache
  {
    use Traits\StaticAccess;

    /**
     * @var bool
     */
    protected $connected = false;
    protected $connection = false;
    /**
     * @static
     * @return \ADV\Core\Cache
     */
    public function __construct()
    {
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
        $this->connected = ($i->getVersion() !== false);
        if ($this->connected && isset($_GET['reload_cache'])) {
          $i->flush(0);
          if (function_exists('apc_clear_cache')) {
            apc_clear_cache('user');
          }
          \Display::meta_forward('/');
        }
        $this->connection = $i;
      }
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
    public function _set($key, $value, $expires = 86400)
    {
      if ($this->connection !== false) {
        $this->connection->set($key, $value, time() + $expires);
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
    public function _delete($key)
    {
      if ($this->connection !== false) {
        $this->connection->delete($key);
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
    public function _get($key, $default = false)
    {
      if ($this->connection !== false) {
        $result = $this->connection->get($key);
        $result = ($this->connection->getResultCode() === Memcached::RES_NOTFOUND) ? $default : $result;
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
    public function _getStats()
    {
      return ($this->connected) ? $this->connection->getStats() : false;
    }
    /**
     * @static
     * @return mixed
     */
    public function _getVersion()
    {
      return ($this->connected) ? $this->connection->getVersion() : false;
    }
    /**
     * @static
     * @return mixed
     */
    public function _getServerList()
    {
      return ($this->connected) ? $this->connection->getServerList() : false;
    }
    /**
     * @static
     *
     * @param int $time
     */
    public function _flush($time = 0)
    {
      if ($this->connection) {
        $this->connection->flush($time);
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
    public function _define_constants($name, $constants)
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

