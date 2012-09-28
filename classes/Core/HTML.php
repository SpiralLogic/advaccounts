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
   * @method HTML button()
   * @method HTML _button()
   * @method HTML table()
   * @method static HTML tr()
   * @method HTML td()
   * @method HTML div()
   * @method HTML textarea()
   * @method HTML optgroup()
   * @method HTML form()
   * @method HTML _form()
   * @method HTML label()
   * @method HTML input()
   * @method static HTML _td()
   * @method HTML _div()
   * @method HTML script()
   * @method static HTML span()
   * @method HTML _span()
   * @method HTML option()
   * @method HTML select()
   * @method HTML _select()
   * @property HTML tr
   * @property HTML td
   * @property HTML script
   * @property HTML table
   * @property HTML div
   * @property HTML form
   * @property HTML option
   * @property HTML select
   */
  class HTML {
    use Traits\HTML;

    /**
     * @var HTML
     */
    protected static $_instance = null;
    /**
     * @var bool
     */
    public $content;
    /**
     * @param $func
     * @param $args
     *
     * @return null
     */
    public function __call($func, $args) {
      if (count($args) == 0) {
        $this->content .= '</' . ltrim($func, '_') . '>';
      } else {
        $this->_Builder($func, $args);
      }

      return $this;
    }
    /**
     * @param $func
     *
     * @return null
     */
    public function __get($func) {
      $this->__call($func, []);

      return $this;
    }
    /**
     * @static
     *
     * @param       $func
     * @param array $args
     *
     * @return null
     */
    public static function __callStatic($func, $args = []) {
      if (static::$_instance === null) {
        static::$_instance = new static;
      }
      static::$_instance->__call($func, $args);

      return static::$_instance;
    }
    /**
     * @param $attr
     *
     * @return string
     */
    public static function attr($attr) {
      return static::expandAttributes($attr);
    }
    /**
     * @param        $func
     * @param        $args
     *
     * @internal param array $attr
     * @internal param string $content
     */
    protected function _Builder($func, $args) {
      $attr = [];
      $open = (is_bool(end($args))) ? array_pop($args) : true;
      foreach ($args as $key => $val) {
        if ($key == 0 && is_string($val)) {
          $attr['id'] = $val;
        } elseif (!isset($attr['content']) && is_string($val)) {
          $content = $val;
        } elseif (is_array($val)) {
          $attr = array_merge($attr, $val);
        }
      }
      if (!isset($content) && isset($attr['content'])) {
        $content = $attr['content'];
        unset($attr['content']);
      }
      $this->content .= $this->makeElement($func, $attr, $content, !$open);
    }
    /**
     * @return HTML|string
     */
    public function __tostring() {
      $content       = $this->content;
      $this->content = '';

      return $content;
    }
  }
