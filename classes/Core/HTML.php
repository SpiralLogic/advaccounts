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
   * @method HTML table()
   * @method HTML tr()
   * @method HTML td()
   * @method HTML div()
   * @method HTML textarea()
   * @method HTML label()
   * @method HTML input()
   * @method HTML _td()
   * @method HTML _div()
   * @method HTML script()
   * @property HTML tr
   * @property HTML td
   * @property HTML script
   * @property HTML table
   * @property HTML div
   */
  class HTML
  {

    /**
     * @var HTML
     */
    protected static $_instance = null;
    /**
     * @var bool
     */
    protected static $_return = false;
    /**
     * @param $func
     * @param $args
     *
     * @return null
     */
    public function __call($func, $args)
    {
      return static::__callStatic($func, $args);
    }
    /**
     * @param $func
     *
     * @return null
     */
    public function __get($func)
    {
      static::__callStatic($func);

      return static::$_instance;
    }
    /**
     * @static
     *
     * @param null $state
     *
     * @return HTML|string
     */
    public static function setReturn($state = null)
    {
      if (static::$_instance === null) {
        static::$_instance = new static;
      }
      static::$_return = ($state === null) ? !(static::$_return) : $state;
      if (!static::$_return) {
        return ob_get_clean();
      } else {
        ob_start();
      }

      return static::$_instance;
    }
    /**
     * @static
     *
     * @param       $func
     * @param array $args
     *
     * @return null
     */
    public static function __callStatic($func, $args = [])
    {
      if (static::$_instance === null) {
        static::$_instance = new static;
      }
      (count($args) == 0) ? static::$_instance->_closeTag(($func[0] == '_') ? substr($func, 1) : $func) : static::$_instance->_Builder($func, $args);

      return static::$_instance;
    }
    /**
     * @param        $type
     * @param array  $attr
     * @param string $content
     */
    protected function _openTag($type, $attr = [], $content = '')
    {
      $attrs = '';
      foreach ($attr as $key => $value) {
        if (is_bool($value)) {
          $attrs .= ' ' . $key;
          continue;
        }
        if (is_null($value)) {
          continue;
        }
        if ($key == 'input') {
          $value = \Forms::prep_value($value);
        }
        $attrs .= ((empty($value)&&$key!=='value') || $key == 'content') ? '' : ' ' . $key . '="' . $value . '"';
      }
      echo  '<' . $type . ' ' . $attrs . '>' . (isset($attr['content']) ? $attr['content'] : $content);
    }
    /**
     * @param $type
     */
    protected function _closeTag($type)
    {
      echo '</' . $type . '>';
    }
    /**
     * @param        $func
     * @param        $args
     * @param array  $attr
     * @param string $content
     */
    protected function _Builder($func, $args, $attr = [], $content = '')
    {
      $open = (is_bool(end($args))) ? array_pop($args) : true;
      foreach ($args as $key => $val) {
        if ($key == 0 && is_string($val)) {
          $attr['id'] = $val;
        } elseif (!isset($attr['content']) && is_string($val)) {
          $content = $attr['content'] = $val;
        } elseif (is_array($val)) {
          $attr = array_merge($attr, $val);
        }
      }
      if (!$open) {
        if ($open === false) {
          $this->_openTag($func, $attr, $content);
        }
        $this->_closeTag($func);
      } else {
        $this->_openTag($func, $attr);
      }
    }
  }
