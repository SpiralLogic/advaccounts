<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   *
   **/
  class Input {
    /**
     *
     */
    const NUMERIC = 'number';
    /**
     *
     */
    const OBJECT = 'object';
    /**
     *
     */
    const STRING = 'string';
    /**
     *
     */
    const BOOL = 'boolean';
    /**
     * @var int
     */
    static protected $default_number = 0;
    /**
     * @var string
     */
    static protected $default_string = '';
    /**
     * @var bool
     */
    static protected $default_bool = FALSE;
    /***
     * @static
     *
     * @param mixed $var     $_POST variable to return
     * @param Input $type    Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
     * @param null  $default Default value if there is no current variable
     *
     * @return bool|int|string|object
     */
    static public function post($var, $type = NULL, $default = NULL) {
      return static::_isset($_POST, $var, $type, $default);
    }
    /***
     * @static
     *
     * @param              $var
     * @param \Input|null  $type    Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING,
     *                              Input::BOOL
     * @param mixed        $default Default value if there is no current variable
     *
     * @internal param mixed $public $_GET variable to return
     * @return bool|int|string|object
     */
    static public function get($var, $type = NULL, $default = NULL) {
      return static::_isset($_GET, $var, $type, $default);
    }
    /***
     * @static
     *
     * @param mixed $var     $_REQUEST variable to return
     * @param Input $type    Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
     * @param null  $default Default value if there is no current variable
     *
     * @return bool|int|string|object
     */
    static public function request($var, $type = NULL, $default = NULL) {
      return static::_isset($_REQUEST, $var, $type, $default);
    }
    /***
     * @static
     *
     * @param mixed $var     $_GET variable to return if it doesn't exist $_POST will be tried
     * @param Input $type    Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
     * @param null  $default Default value if there is no current variable
     *
     * @return bool|int|string|object
     */
    static public function get_post($var, $type = NULL, $default = NULL) {
      return static::get_post($_GET, $_POST, $var, $type, $default);
    }
    /***
     * @static
     *
     * @param mixed $var     $_POST  variable to return if it doesn't exist $_GET will be returned
     * @param Input $type    Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
     * @param null  $default Default value if there is no current variable
     *
     * @return bool|int|string|object
     */
    static public function post_get($var, $type = NULL, $default = NULL) {
      return static::get_post($_POST, $_GET, $var, $type, $default);
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
    static public function session($var = array(), $type = NULL, $default = NULL) {
      return (!isset($_SESSION)) ? FALSE : static::_isset($_SESSION, $var, $type, $default);
    }
    /***
     * @static
     *
     * @param mixed $vars Test for existance of $_POST variable
     *
     * @return bool
     */
    static public function has_post($vars) {
      if (is_null($vars)) {
        return TRUE;
      }
      return (static::_has($_POST, func_get_args()));
    }
    /***
     * @static
     *
     * @param mixed $vars Test for existance of $_GET variable
     *
     * @return bool
     */
    static public function has_get($vars) {
      if (is_null($vars)) {
        return TRUE;
      }
      return (static::_has($_GET, func_get_args()));
    }
    /***
     * @static
     *
     * @param mixed $vars Test for existance of either $_POST or $_GET variable
     *
     * @return bool
     */
    static public function has($vars) {
      if (is_null($vars)) {
        return TRUE;
      }
      return (static::_has($_REQUEST, func_get_args()));
    }
    /***
     * @static
     *
     * @param mixed $vars Test for existence of either $_POST or $_GET variable
     *
     * @return bool
     */
    static public function has_session($vars) {
      if (is_null($vars)) {
        return TRUE;
      }
      return (static::_has($_SESSION, func_get_args()));
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
    static protected function _get_post($first, $second, $var, $type = NULL, $default = NULL) {
      $array = (static::_has($first, $var)) ? $first : $second;
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
    static protected function _has(array $array, $vars) {
      if (is_null($vars)) {
        return TRUE;
      }
      $vars = func_get_args();
      array_shift($vars);
      foreach ($vars as $var) {
        if (static::_isset($array, $var) === FALSE) {
          return FALSE;
        }
      }
      return TRUE;
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
    static protected function _isset(array $array, $var, $type = NULL, $default = NULL) {
      $value = (is_string($var) && isset($array[$var])) ? $array[$var] : $default; //chnage back to null if fuckoutz happen
      switch ($type) {
        case self::NUMERIC:
          if (!$value || !is_numeric($value)) {
            return self::$default_number;
          }
          return ($value === self::$default_number) ? TRUE : $value + 0;
        case self::STRING:
          if (!$value || !is_string($value)) {
            return self::$default_string;
          }
      }
      return $value;
    }
  }
