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
   * @method _post($var, $type = null, $default = null)
   * @method _get($var, $type = null, $default = null)
   * @method _session($var, $type = null, $default = null)
   * @method _getPost($var, $type = null, $default = null)
   * @method Input i()
   * @method _postGet($var, $type = null, $default = null)
   * @method _postGetGlobal($var, $type = null, $default = null)
   * @method _getPostGlobal($var, $type = null, $default = null)
   * @method _request($var, $type = null, $default = null)
   * @method _hasPost($vars)
   */
  class Input
  {

    use \ADV\Core\Traits\StaticAccess2;

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
    /**

     */
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
     * @return bool|int|string
     */
    public function post($var, $type = null, $default = null) {
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
     * @return bool|int|string
     */
    public function get($var, $type = null, $default = null) {
      return static::$get->get($var, $type, $default);
    }
    /***
     * @static
     *
     * @param mixed     $var     $_REQUEST variable to return
     * @param Input|int $type    Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
     * @param null      $default Default value if there is no current variable
     *
     * @return bool|int|string
     */
    public function request($var, $type = null, $default = null) {
      return static::$request->get($var, $type, $default);
    }
    /***
     * @static
     *
     * @param mixed     $var     $_GET variable to return if it doesn't exist $_POST will be tried
     * @param Input|int $type    Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
     * @param null      $default Default value if there is no current variable
     *
     * @return bool|int|string
     */
    public function getPost($var, $type = null, $default = null) {
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
    public function getPostGlobal($var, $type = null, $default = null) {
      $result = $this->firstThenSecond(static::$get, static::$post, $var, $type, false);
      if ($result === false) {
        $result = $this->getGlobal($var, $type, $default);
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
    public function postGetGlobal($var, $type = null, $default = null) {
      $result = $this->firstThenSecond(static::$post, static::$get, $var, $type, false);
      if ($result === false) {
        $result = $this->getGlobal($var, $type, $default);
      }
      return $result;
    }
    /**
     * @param      $var
     * @param      $type
     * @param null $default
     *
     * @return bool|int|null|string
     */
    public function postGlobal($var, $type = null, $default = null) {
      $result = $this->post($var, $type, false);
      if ($result === false) {
        return $this->getGlobal($var, $type, $default);
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
     * @return bool|int|string
     */
    public function postGet($var, $type = null, $default = null) {
      return $this->firstThenSecond(static::$post, static::$get, $var, $type, $default);
    }
    /***
     * @static
     *
     * @param mixed $var     $_SESSION variable to return
     * @param Input $type    Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
     * @param null  $default Default value if there is no current variable
     *
     * @return bool|int|string
     */
    public function session($var = [], $type = null, $default = null) {
      return (session_status() === PHP_SESSION_NONE) ? false : static::$session->get($var, $type, $default);
    }
    /***
     * @static
     *
     * @param mixed $vars Test for existance of $_POST variable
     *
     * @return bool
     */
    public function hasPost($vars) {
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
    public function hasGet($vars) {
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
    public function has($vars) {
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
    public function hasSession($vars) {
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
    protected function getGlobal($var, $type, $default) {
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
     * @param Base  $container
     * @param       $vars
     *
     * @internal param array $array
     * @return bool
     */
    protected function doesHave(Base $container, $vars) {
      return $container->has($vars);
    }
    /**
     * @static
     *
     * @param Base  $container
     * @param       $var
     * @param null  $type
     * @param null  $default
     *
     * @internal param array $array
     * @return bool|int|null|string
     */
    protected function getVar(Base $container, $var, $type = null, $default = null) {
      return $container->get($var, $type, $default);
    }
  }
