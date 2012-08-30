<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      30/08/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Form;

  use ADV\Core\JS;
  use ADV\Core\HTML;

  /**

   */
  class Button implements \ArrayAccess
  {
    protected $attr = [];
    public $id;
    protected $name;
    protected $caption = '';
    public $validator;
    protected $preicon;
    protected $posticon;
    /**
     * @param $name
     * @param $value
     * @param $caption

     */
    public function __construct($name, $value, $caption) {
      $this->name = $this->attr['name'] = $name;
      $this->id                         = $this->nameToId();
      $this->attr['type']               = 'submit';
      $this->attr['value']              = e($value);
      $this->attr['title']              = e($caption);
      $this->attr['class']              = 'btn';
      $this->caption                    = $caption;
    }
    /**
     * @return mixed
     */
    protected function nameToId() {
      return str_replace(['[', ']'], ['-', ''], $this->name);
    }
    /**
     * @param $warning
     *
     * @return Button
     */
    public function setWarning($warning) {
      JS::_beforeload("_validate." . $this->name . "=function(){ return confirm('" . strtr($warning, array("\n" => '\\n')) . "');};");

      return $this;
    }
    /**
     * @param $attr
     *
     * @return Button
     */
    public function mergeAttr($attr) {
      $this->attr = array_merge($this->attr, (array) $attr);

      return $this;
    }
    /**
     * @param $icon
     *
     * @internal param $text
     * @return \ADV\App\Form\Button
     */
    public function preIcon($icon) {
      $this->preicon = $icon;

      return $this;
    }
    /**
     * @param $icon
     *
     * @return \ADV\App\Form\Button
     */
    public function postIcon($icon) {
      $this->posticon = $icon;

      return $this;
    }
    /**

     */
    protected function formatIcons() {
      if ($this->preicon) {
        $this->caption = "<i class='" . $this->preicon . "' > </i> " . $this->caption;
      }
      if ($this->posticon) {
        $this->caption .= " <i class='" . $this->posticon . "' > </i>";
      }
    }
    /**
     * @param $validator
     *
     * @return \ADV\App\Form\Button
     */
    public function setValidator($validator) {
      $this->validator = $validator;

      return $this;
    }
    /**
     * @return string
     */
    public function __toString() {
      $this->formatIcons();

      return HTML::setReturn(true)->button($this->id, $this->caption, $this->attr, false)->setReturn(false);
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
