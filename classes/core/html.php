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

   */
  class HTML {

    /**
     * @var HTML
     */
    static protected $_instance = NULL;
    /**
     * @var bool
     */
    static protected $_return = FALSE;
    /**
     * @param $func
     * @param $args
     *
     * @return null
     */
    function __call($func, $args) {
      return static::__callStatic($func, $args);
    }
    /**
     * @param $func
     *
     * @return null
     */
    function __get($func) {
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
    static function setReturn($state = NULL) {
      if (static::$_instance === NULL) {
        static::$_instance = new static;
      }
      static::$_return = ($state === NULL) ? !(static::$_return) : $state;
      if (!static::$_return) {
        return ob_get_clean();
      }
      else {
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
    static function __callStatic($func, $args = array()) {
      if (static::$_instance === NULL) {
        static::$_instance = new static;
      }
      (count($args) == 0) ? static::$_instance->_closeTag(($func[0] == '_') ? substr($func, 1) : $func)
        : static::$_instance->_Builder($func, $args);
      return static::$_instance;
    }
    /**
     * @param        $type
     * @param array  $attr
     * @param string $content
     */
    protected function _openTag($type, $attr = array(), $content = '') {
      $attrs = '';
      foreach (
        $attr as $key => $value
      ) {
        if (is_bool($value)) {
          $attrs .= ' ' . $key;
          continue;
        }
        if ($key == 'input') {
          $value = \Form::prep_value($value);
        }
        $attrs .= (empty($value) || $key == 'content') ? '' : ' ' . $key . '="' . $value . '"';
      }
      echo  '<' . $type . ' ' . $attrs . '>' . (isset($attr['content']) ? $attr['content'] : $content);
    }
    /**
     * @param $type
     */
    protected function _closeTag($type) {
      echo '</' . $type . '>';
    }
    /**
     * @param        $func
     * @param        $args
     * @param array  $attr
     * @param string $content
     */
    protected function _Builder($func, $args, $attr = array(), $content = '') {
      $open = (is_bool(end($args))) ? array_pop($args) : TRUE;
      foreach (
        $args as $key => $val
      ) {
        if ($key == 0 && is_string($val)) {
          $attr['id'] = $val;
        }
        elseif (!isset($attr['content']) && is_string($val)) {
          $content = $attr['content'] = $val;
        }
        elseif (is_array($val)) {
          $attr = array_merge($attr, $val);
        }
      }
      if (!$open) {
        if ($open === FALSE) {
          $this->_openTag($func, $attr, $content);
        }
        $this->_closeTag($func);
      }
      else {
        $this->_openTag($func, $attr);
      }
    }
  }
