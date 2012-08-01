<?php
  namespace ADV\Core\Input;
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 21/07/12
   * Time: 4:57 PM
   * To change this template use File | Settings | File Templates.
   */
  class Base implements \ArrayAccess {
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
    protected $container = null;
    /**
     * @param $container
     */
    public function __construct(&$container) {
      $this->container = &$container;
    }
    /***
     * @param mixed     $var     $_POST variable to return
     * @param Input|int $type    Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
     * @param null      $default Default value if there is no current variable
     *
     * @return bool|int|string|object
     */
    public function get($var, $type = null, $default = null) {
      return $this->hasSet($var, $type, $default);
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
      foreach ($vars as $var) {

        if (!array_key_exists($var, $this->container) || is_null($this->container[$var])) {
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
    protected function hasSet($var, $type = null, $default = null) {
      //     if ($type!==null&&$default===null) $default=$type;
      $array = $this->container;
      if (is_array($var)) {
        $keys = $var;
        $var  = array_pop($keys);
        foreach ($keys as $key) {
          if (!array_key_exists($key, $array) || !is_array($array)) {
            $array = [];
            break;
          }
          $array = $array[$key];
        }
      }
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
    public function offsetExists($offset) {
      return array_key_exists($offset, $this->container);
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
    public function offsetGet($offset) {
      return $this->hasSet($offset);
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
    public function offsetSet($offset, $value) {
      $this->container[$offset] = $value;
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
    public function offsetUnset($offset) {
      unset($this->container[$offset]);
    }
  }