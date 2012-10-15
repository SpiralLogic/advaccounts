<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      6/10/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Form;

  /**

   */
  class Custom extends Field {
    protected $control;
    /**
     * @param $control
     *
     * @internal param $name
     */
    public function __construct($control) {
      if (preg_match('/name=([\'"]?)(.+?)\1/', $control, $matches)) {
        $this->name($matches[2]);
        $control = preg_replace('/id=([\'"]?)' . preg_quote($this->name) . '\1/', "id='" . $this->id . "'", $control, 1);
      }
      $this->control = $control;
    }
    /**
     * @return string
     */
    public function __toString() {
      $value            = (isset($this->value)) ? $this->value : $this->default;
      $this->attr['id'] = $this->id;
      $values           = (array) $value;
      $control          = $this->control;
      foreach ($values as $v) {
        $control = preg_replace('/value=([\'"]?)' . preg_quote($v) . '\1/', 'selected \0', $control);
      }
      $control = $this->formatAddOns($control);
      if ($this->label) {
        $control = "<label for='" . $this->id . "'><span>" . $this->label . "</span>$control</label>";
      }

      return $control;
    }
  }
