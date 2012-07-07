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
  class SessionException extends \Exception
  {
  }

  /**
   * @property \ADVAccounting App
   * @method  getGlobal($var, $default = null)
   * @method setGlobal($var, $value = null)
   * @method kill()
   * @method get()
   * @method set()
   * @method regenerate()
   * @method checkUserAgent()
   * @method setUserAgent()
   *@method Session i()
   */
  class Session implements \ArrayAccess
  {
    use Traits\StaticAccess;

    /***
     * @var \gettextNativeSupport|\gettext_php_support
     */
    public $get_text;
    /**
     * @throws \ADV\Core\SessionException
     */
    final protected function __construct()
    {
      /** @noinspection PhpUndefinedConstantInspection */
      /** @noinspection PhpUndefinedFunctionInspection */
      if (session_status() === PHP_SESSION_DISABLED) {
        throw new SessionException('Sessions are disasbled!');
      }
      ini_set('session.gc_maxlifetime', 3200); // 10hrs
      session_name('ADV' . md5($_SERVER['SERVER_NAME']));
      /** @noinspection PhpUndefinedFunctionInspection */
      if (session_status() === PHP_SESSION_NONE && extension_loaded('Memcached')) {
        ini_set('session.save_handler', 'Memcached');
        ini_set('session.save_path', '127.0.0.1:11211');
        (Memcached::HAVE_IGBINARY)  and  ini_set('session.serialize_handler', 'igbinary');
        session_start();
      }
      /** @noinspection PhpUndefinedFunctionInspection */
      if (session_status() === PHP_SESSION_NONE) {
        ini_restore('session.save_handler');
        ini_restore('session.save_path');
        ini_restore('session.serialize_handler');
        session_start();
      }
      /** @noinspection PhpUndefinedFunctionInspection */
      if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new SessionException('Could not start a Session!');
      }
      header("Cache-control: private");
      $this->setTextSupport();
      $this['language'] = new Language();
      if (!isset($_SESSION['globals'])) {
        $this['globals'] = [];
      }
      // Ajax communication object
      (!class_exists('Ajax'))  or Ajax::i();
    }
    /**
     * @static
     * @return bool
     */
    public function _checkUserAgent()
    {
      if ($this['HTTP_USER_AGENT'] != sha1(Arr::get($_SERVER, 'HTTP_USER_AGENT', $_SERVER['REMOTE_ADDR']))) {
        $this->setUserAgent();

        return false;
      }

      return true;
    }
    /**
     * @static
     * @return bool
     */
    protected function setUserAgent()
    {
      return ($this['HTTP_USER_AGENT'] = sha1(Arr::get($_SERVER, 'HTTP_USER_AGENT', $_SERVER['REMOTE_ADDR'])));
    }
    /**
     * @return mixed
     */
    protected function setTextSupport()
    {
      if (isset($this['get_text'])) {
        $this->get_text = $this['get_text'];
      } else {
        $this->get_text = $this['get_text'] = \gettextNativeSupport::i();
      }
    }
    /**
     * @param string $var
     *
     * @return mixed|null
     */
    public function __get($var)
    {
      return isset($this[$var]) ? $this[$var] : null;
    }
    /**
     * @param $var
     * @param $value
     *
     * @return void
     */
    public function __set($var, $value)
    {
      $this[$var] = $value;
    }
    /**
     * @param $var
     * @param $value
     *
     * @internal param $valie
     * @return float|string
     */
    public function _setGlobal($var, $value = null)
    {
      if ($value === null) {
        if (isset($_SESSION['globals'][$var])) {
          unset($_SESSION['globals'][$var]);
        }

        return null;
      }
      $_SESSION['globals'][$var] = $value;
      $this[$var]                = $value;

      return $value;
    }
    /**
     * @param $var
     * @param $default
     *
     * @return mixed
     */
    public function _getGlobal($var, $default = null)
    {
      return isset($this['globals'][$var]) ? $this['globals'][$var] : $default;
    }
    /**
     * @internal param $globals
     */
    public function _removeGlobal()
    {
      $globals = func_get_args();
      foreach ($globals as $var) {
        if (is_string($var) || is_int($var)) {
          unset ($_SESSION['globals'][$var]);
        }
      }
    }
    /**
     * @static
     * @return void
     */
    public function _kill()
    {
      Config::removeAll();
      session_start();
      $this->_regenerate();
      session_destroy();
    }
    /**
     * @static
     * @return void
     */
    public function _regenerate()
    {
      session_regenerate_id();
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     * </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     *       The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
      return array_key_exists($offset, $_SESSION);
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     * </p>
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
      if (!$this->offsetExists($offset)) {
        return null;
      }

      return $_SESSION[$offset];
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     * </p>
     * @param mixed $value  <p>
     *                      The value to set.
     * </p>
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
      $_SESSION[$offset] = $value;
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     * </p>
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
      unset($_SESSION[$offset]);
    }
    /**
     * @param      $var
     * @param null $default
     *
     * @return null
     */
    public function _get($var, $default = null)
    {
      $value = $default;
      if (!isset($_SESSION[$var])) {
        $value = $_SESSION[$var];
      }

      return $value;
    }
    /**
     * @param $var
     * @param $value
     *
     * @return mixed
     */
    public function _set($var, $value)
    {
      $_SESSION[$var] = $value;

      return $value;
    }
  }
