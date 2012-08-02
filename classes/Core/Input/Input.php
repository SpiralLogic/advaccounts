<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\Input;
  /**
   * @method post($var, $type = null, $default = null)
   * @method get($var, $type = null, $default = null)
   * @method session($var, $type = null, $default = null)
   * @method getPost($var, $type = null, $default = null)
   * @method Input i()
   * @method postGet($var, $type = null, $default = null)
   * @method postGetGlobal($var, $type = null, $default = null)
   * @method getPostGlobal($var, $type = null, $default = null)
   * @method request($var, $type = null, $default = null)
   * @method hasPost($vars)
   */
  class Input
  {
    use \ADV\Core\Traits\StaticAccess;

    const NUMERIC = 1;
    const OBJECT  = 2;
    const STRING  = 3;
    const BOOL    = 4;
    /**
     * @var int
     */
    protected $default_number = 0;
    /**
     * @var string
     */
    protected $default_string = '';
    /**
     * @var bool
     */
    protected $default_bool = false;
    /** @var Base */
    public static $post;
    /** @var Base */
    public static $get;
    /** @var Base */
    public static $session;
    /** @var Base */
    public static $request;
    public function __construct() {
      static::$post    = new Base($_POST);
      static::$get     = new Base($_GET);
      static::$session = new Base($_SESSION);
      static::$request = new Base($_REQUEST);
    }
    /***
     * @param mixed     $var     $_POST variable to return
     * @param Input|int $type    Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
     * @param null      $default Default value if there is no current variable
     *
     * @return bool|int|string|object
     */
    public function _post($var, $type = null, $default = null) {
      return static::$post->get($var, $type, $default);
    }
    /***
     * @method
     * @param           $var
     * @param Input|int $type       Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING,
     *                              Input::BOOL
     * @param mixed     $default    Default value if there is no current variable
     *
     * @internal param mixed $public $_GET variable to return
     * @return bool|int|string|object
     */
    public function _get($var, $type = null, $default = null) {
      return static::$get->get($var, $type, $default);
    }
    /***
     * @static
     *
     * @param mixed     $var     $_REQUEST variable to return
     * @param Input|int $type    Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
     * @param null      $default Default value if there is no current variable
     *
     * @return bool|int|string|object
     */
    public function _request($var, $type = null, $default = null) {
      return static::$request->get($var, $type, $default);
    }
    /***
     * @static
     *
     * @param mixed     $var     $_GET variable to return if it doesn't exist $_POST will be tried
     * @param Input|int $type    Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
     * @param null      $default Default value if there is no current variable
     *
     * @return bool|int|string|object
     */
    public function _getPost($var, $type = null, $default = null) {
      return $this->firstThenSecond(static::$get, static::$post, $var, $type, $default);
    }
    /**
     * @static
     *
     * @param      $var
     * @param      $type
     * @param null $default
     *
     * @return bool|int|null|string
     */
    public function _getPostGlobal($var, $type = null, $default = null) {
      $result = $this->firstThenSecond(static::$get, static::$post, $var, $type, false);
      if ($result === false) {
        $result = $this->_global($var, $type, $default);
      }
      return $result;
    }
    /**
     * @static
     *
     * @param      $var
     * @param      $type
     * @param null $default
     *
     * @return bool|int|null|string
     */
    public function _postGetGlobal($var, $type = null, $default = null) {
      $result = $this->firstThenSecond(static::$post, static::$get, $var, $type, false);
      if ($result === false) {
        $result = $this->_global($var, $type, $default);
      }
      return $result;
    }
    /**
     * @static
     *
     * @param      $var
     * @param      $type
     * @param null $default
     *
     * @return bool|int|null|string
     */
    public function _postGlobal($var, $type = null, $default = null) {
      $result = $this->_post($var, $type, false);
      if ($result === false) {
        return $this->_global($var, $type, $default);
      }
      return $result;
    }
    /***
     * @static
     *
     * @param mixed     $var     $_POST  variable to return if it doesn't exist $_GET will be returned
     * @param int|Input $type    Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
     * @param null      $default Default value if there is no current variable
     *
     * @return bool|int|string|object
     */
    public function _postGet($var, $type = null, $default = null) {
      return $this->firstThenSecond(static::$post, static::$get, $var, $type, $default);
    }
    /***
     * @static
     *
     * @param mixed $var     $_SESSION variable to return
     * @param Input $type    Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
     * @param null  $default Default value if there is no current variable
     *
     * @return bool|int|string|object
     */
    public function _session($var = [], $type = null, $default = null) {
      return (session_status() === PHP_SESSION_NONE) ? false : static::$session->get($var, $type, $default);
    }
    /***
     * @static
     *
     * @param mixed $vars Test for existance of $_POST variable
     *
     * @return bool
     */
    public function _hasPost($vars) {
      if (is_null($vars)) {
        return true;
      } elseif (!is_array($vars)) {
        $vars = func_get_args();
      }
      return static::$post->has($vars);
    }
    /***
     * @static
     *
     * @param mixed $vars Test for existance of $_GET variable
     *
     * @return bool
     */
    public function _hasGet($vars) {
      if (is_null($vars)) {
        return true;
      } elseif (!is_array($vars)) {
        $vars = func_get_args();
      }
      return static::$get->has($vars);
    }
    /***
     * @static
     *
     * @param mixed $vars Test for existance of either $_POST or $_GET variable
     *
     * @return bool
     */
    public function _has($vars) {
      if (is_null($vars)) {
        return true;
      } elseif (!is_array($vars)) {
        $vars = func_get_args();
      }
      return static::$request->has($vars);
    }
    /***
     * @static
     *
     * @param mixed $vars Test for existence of either $_POST or $_GET variable
     *
     * @return bool
     */
    public function _hasSession($vars) {
      if (is_null($vars)) {
        return true;
      } elseif (!is_array($vars)) {
        $vars = func_get_args();
      }
      return static::$session->has($vars);
    }
    /**
     * @static
     *
     * @param $var
     * @param $type
     * @param $default
     *
     * @return bool|int|null|string
     */
    protected function _global($var, $type, $default) {
      return static::$session->get(['globals', $var], $type, $default);
    }
    /**
     * @static
     *
     * @param      $first
     * @param      $second
     * @param      $var
     * @param null $type
     * @param null $default
     *
     * @return bool|int|null|string
     */
    protected function firstThenSecond(Base $first, Base $second, $var, $type = null, $default = null) {
      $container = ($first->has($var)) ? $first : $second;
      return $container->get($var, $type, $default);
    }
    /**
     * @static
     *
     * @param array $array
     * @param       $vars
     *
     * @return bool
     */
    protected function doesHave(Base $container, $vars) {
      return $container->has($vars);
    }
    /**
     * @static
     *
     * @param array $array
     * @param       $var
     * @param null  $type
     * @param null  $default
     *
     * @return bool|int|null|string
     */
    protected function _isset(Base $container, $var, $type = null, $default = null) {
      return $container->get($var, $type, $default);
    }
  }
