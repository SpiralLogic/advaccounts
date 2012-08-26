<?php

  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 25/08/12
   * Time: 5:04 PM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\App\Form;

  use \ADV\Core\HTML;
  use ADV\Core\Event;
  use ADV\Core\JS;

  /**

   */
  class Feild implements \ArrayAccess
  {
    protected $attr = [];
    public $id;
    protected $name;
    protected $content = '';
    protected $label;
    protected $tag;
    protected $validator;
    protected $append;
    protected $prepend;
    protected $control;
    /**
     * @param $tag
     * @param $name
     */
    public function __construct($tag, $name) {
      $this->tag  = $tag;
      $this->name = $this['name'] = $name;
      $this->id   = $this->nameToId();
    }
    /**
     * @param $label
     *
     * @return \ADV\App\Form\Feild
     */
    public function label($label) {
      if ($label === null) {
        return $this;
      }
      $this->label = $label;
      if (!isset($this->attr['placeholder'])) {
        $this['placeholder'] = rtrim($label, ':');
      }

      return $this;
    }
    /**
     * @return mixed
     */
    protected function nameToId() {
      return str_replace(['[', ']'], ['-', ''], $this->name);
    }
    /**
     * @param $content
     *
     * @return Feild
     */
    public function setContent($content) {
      $this->content = $content;

      return $this;
    }
    /**
     * @param $control
     */
    public function customControl($control) {
      $this->control = $control;
    }
    /**
     * @param $attr
     *
     * @return Feild
     */
    public function mergeAttr($attr) {
      $this->attr = array_merge($this->attr, (array) $attr);

      return $this;
    }
    /**
     * @param $text
     *
     * @return \ADV\App\Form\Feild
     */
    public function append($text) {
      $this->append = $text;

      return $this;
    }
    /**
     * @param $text
     *
     * @return \ADV\App\Form\Feild
     */
    public function prepend($text) {
      $this->prepend = $text;

      return $this;
    }
    /**
     * @param $content
     *
     * @return string
     */
    protected function formatAddOns($content) {
      if ($this->append && $this->prepend) {
        $return = "<div class='input-append input-prepend'><span class='add-on'>" . $this->prepend . "</span>";
      } elseif ($this->append) {
        $return = "<div class='input-append'>";
      } elseif ($this->prepend) {
        $return = "<div class='input-prepend'><span class='add-on'>" . $this->prepend . "</span>";
      } else {
        return $content;
      }
      $return .= $content;
      if ($this->append) {
        $return .= "<span class='add-on' id='_" . $this->name . "_label'>" . $this->append . "</span>";
      }

      return $return . "</div>";
    }
    /**
     * @param $function
     */
    public function setValidation(Callable $function) {
      $this->validator = $function;
    }
    /**
     * @param array $args
     *
     * @return bool
     */
    public function isValid(array $args) {
      $result = call_user_func_array($this->validator, $args);
      if ($result === true) {
        return true;
      }
      if (is_string($result)) {
        Event::error($result);
      }
      JS::setFocus($this->id);

      return false;
    }
    /**
     * @return string
     */
    public function __toString() {
      if (!isset($this->control)) {
        $tag     = $this->tag;
        $control = HTML::setReturn(true)->$tag($this->id, $this->content, $this->attr, false)->setReturn(false);
      } else {
        $control = $this->control;
      }
      $control = $this->formatAddOns($control);
      if ($this->label) {
        $control = "<label for='" . $this->name . "'><span>" . $this->label . "</span>$control</label>";
      }

      return $control;
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
      return array_key_exists($offset, $this->attr);
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
      return $this->attr[$offset];
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
      $this->attr[$offset] = $value;
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
      unset($this->attr[$offset]);
    }
  }
