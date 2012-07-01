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
   * @method post($var, $type = null, $default = null)
   * @method get($var, $type = null, $default = null)

   */
  class Input
  {
    use Traits\StaticAccess;

    /**

     */
    const NUMERIC = 1;
    /**

     */
    const OBJECT = 2;
    /**

     */
    const STRING = 3;
    /**

     */
    const BOOL = 4;
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
    /***
     * @param mixed     $var     $_POST variable to return
     * @param Input|int $type    Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
     * @param null      $default Default value if there is no current variable
     *
     * @return bool|int|string|object
     */
    public function _post($var, $type = null, $default = null)
    {
      return $this->_isset($_POST, $var, $type, $default);
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
    public function _get($var, $type = null, $default = null)
    {
      return $this->_isset($_GET, $var, $type, $default);
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
    public function _request($var, $type = null, $default = null)
    {
      return $this->_isset($_REQUEST, $var, $type, $default);
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
    public function _getPost($var, $type = null, $default = null)
    {
      return $this->getThenPost($_GET, $_POST, $var, $type, $default);
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
    public function _getPostGlobal($var, $type, $default = null)
    {
      $result = $this->getThenPost($_GET, $_POST, $var, $type, false);
      if ($result === false) {
        $result = $this->_getGlobal($var, $type, $default);
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
    public function _postGlobal($var, $type, $default = null)
    {
      $result = $this->_isset($_POST, $var, $type, false);
      if ($result === false) {
        $result = $this->_getGlobal($var, $type, $default);
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
    public function _postGet($var, $type = null, $default = null)
    {
      return $this->getThenPost($_POST, $_GET, $var, $type, $default);
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
    public function _session($var = array(), $type = null, $default = null)
    {
      return (!isset($_SESSION)) ? false : $this->_isset($_SESSION, $var, $type, $default);
    }
    /***
     * @static
     *
     * @param mixed $vars Test for existance of $_POST variable
     *
     * @return bool
     */
    public function _hasPost($vars)
    {
      if (is_null($vars)) {
        return true;
      }
      return ($this->doesHave($_POST, func_get_args()));
    }
    /***
     * @static
     *
     * @param mixed $vars Test for existance of $_GET variable
     *
     * @return bool
     */
    public function _hasGet($vars)
    {
      if (is_null($vars)) {
        return true;
      }
      return ($this->doesHave($_GET, func_get_args()));
    }
    /***
     * @static
     *
     * @param mixed $vars Test for existance of either $_POST or $_GET variable
     *
     * @return bool
     */
    public function _has($vars)
    {
      if (is_null($vars)) {
        return true;
      }
      return ($this->doesHave($_REQUEST, func_get_args()));
    }
    /***
     * @static
     *
     * @param mixed $vars Test for existence of either $_POST or $_GET variable
     *
     * @return bool
     */
    public function hasSession($vars)
    {
      if (is_null($vars)) {
        return true;
      }
      return ($this->doesHave($_SESSION, func_get_args()));
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
    protected function _getGlobal($var, $type, $default)
    {
      if (!isset($_SESSION['globals'])) {
        $_SESSION['globals'] = array();
        return null;
      }
      return $this->_isset($_SESSION['globals'], $var, $type, $default);
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
    protected function getThenPost($first, $second, $var, $type = null, $default = null)
    {
      $array = ($this->doesHave($first, $var)) ? $first : $second;
      return static::_isset($array, $var, $type, $default);
    }
    /**
     * @static
     *
     * @param array $array
     * @param       $vars
     *
     * @return bool
     */
    public function doesHave(array $array, $vars)
    {
      if (is_null($vars)) {
        return true;
      } elseif (!is_array($vars)) {
        $vars = func_get_args();
        array_shift($vars);
      }
      foreach ($vars as $var) {
        if ($this->_isset($array, $var) === null) {
          return false;
        }
      }
      return true;
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
    protected function _isset(array $array, $var, $type = null, $default = null)
    {
      //     if ($type!==null&&$default===null) $default=$type;
      $value = (is_string($var) && isset($array[$var])) ? $array[$var] : $default; //chnage back to null if fuckoutz happen
      switch ($type) {
        case self::NUMERIC:
          if ($value === null || !is_numeric($value)) {
            return ($default === null) ? $this->default_number : $default;
          }
          return ($value === $this->default_number) ? true : $value + 0;
        case self::STRING:
          if ($value === null || !is_string($value)) {
            return ($default === null) ? $this->default_string : $default;
          }
      }
      return $value;
    }
  }
