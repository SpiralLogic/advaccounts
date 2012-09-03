<?php

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Form;
  use \ADV\App\Forms;
  use \ADV\App\User;
  use \ADV\Core\Ajax;
  use \ADV\Core\Arr;
  use \ADV\Core\Num;
  use \ADV\Core\JS;
  use \ADV\Core\SelectBox;
  use \ADV\Core\HTML;
  use \ADV\Core\Input\Input;

  /**
   * @param bool   $multi
   * @param string $action
   * @param string $name
   */
  class Form implements \ArrayAccess, \Iterator, \JsonSerializable
  {
    const NO_VALUES = 1;
    /** @var Field[] */
    protected $fields = [];
    protected $groups = [];
    protected $start;
    protected $end;
    /** @var Ajax */
    protected $Ajax;
    /** @var Input */
    protected $Input;
    protected $validators = [];
    protected $uniqueid;
    protected $current;
    protected $options = [
      self::NO_VALUES=> false,
    ];
    protected $currentgroup;
    public $useDefaults = false;
    /**
     * @param ADV\Core\Input\Input $input
     * @param ADV\Core\Ajax        $ajax
     * @param \ADV\Core\Session    $session
     */
    public function __construct(\ADV\Core\Input\Input $input = null, \ADV\Core\Ajax $ajax = null, \ADV\Core\Session $session = null)
    {
      $this->Ajax  = $ajax ? : Ajax::i();
      $this->Input = $input ? : Input::i();
      $this->group();
    }
    /**
     * @param $tag
     * @param $name
     * @param $value
     *
     * @return Field
     */
    protected function addField($tag, $name, $value = null)
    {
      $field          = new Field($tag, $name);
      $field->default = $value;
      if ($value === null && $this->Input->hasPost($name)) {
        $field->value = $this->Input->post($name);
      }
      if ($tag !== 'textarea') {
        $field->value = e($field->value);
      }
      if (is_array($this->currentgroup)) {
        $this->currentgroup[] = $field;
      }
      $this->fields[$field->id]     = $field;
      $this->validators[$field->id] =& $field->validator;
      $this->Ajax->addUpdate($name, $name, $field->value);

      return $field;
    }
    /**
     * @param $name
     *
     * @return \ADV\App\Form\Form
     */
    public function group($name = '_default')
    {
      if (!isset($this->groups[$name])) {
        $this->groups[$name] = [];
      }
      $this->currentgroup = &$this->groups[$name];

      return $this;
    }
    /**
     * @param $option
     * @param $value
     */
    public function option($option, $value)
    {
      if (isset($this->options[$option])) {
        $this->options[$option] = $value;
      }
    }
    /**
     * @static
     *
     * @param string $name
     * @param string $action
     * @param bool   $multi
     * @param array  $input_attr
     *
     * @return \ADV\Core\HTML|string
     */
    public function start($name = '', $action = '', $multi = null, $input_attr = [])
    {
      $attr['enctype'] = $multi ? 'multipart/form-data' : null;
      $attr['name']    = $name;
      $attr['method']  = 'post';
      $attr['action']  = $action;
      $this->uniqueid  = $this->nameToId($name);
      array_merge($attr, $input_attr);
      $this->start = HTML::setReturn(true)->form($name, $attr)->input(
        null,
        [
        'type' => 'hidden',
        'value'=> $this->uniqueid,
        'name' => '_form_id'
        ]
      )->setReturn(false);

      return $this->start;
    }
    /**
     * @return \ADV\Core\HTML|string
    @internal param int $breaks
     */
    public function end()
    {
      $this->end = HTML::setReturn(true)->form->setReturn(false);

      return $this->end;
    }
    /**
     * @param      $name
     * @param null $value
     *
     * @internal param bool $echo
     * @return string
     */
    public function hidden($name, $value = null)
    {
      $field         = $this->addField('input', $name, $value);
      $field['type'] = 'hidden';
      $this->Ajax->addUpdate($name, $name, $value);
    }
    /**
     * @param       $name
     * @param null  $value
     * @param array $input_attr
     *
     * @return \ADV\App\Form\Field
     */
    public function text($name, $value = null, $input_attr = [])
    {
      $field         = $this->addField('input', $name, $value);
      $field['type'] = 'text';

      return $field->mergeAttr($input_attr);
    }
    /**
     * @param       $name
     * @param       $value
     * @param array $input_attr
     *
     * @return \ADV\App\Form\Field
     */
    public function textarea($name, $value = null, $input_attr = [])
    {
      $field = $this->addField('textarea', $name, $value);
      $field->setContent($value);

      return $field->mergeAttr($input_attr);
    }
    /**
     * @param       $name
     * @param       $value
     * @param array $input_attr
     *
     * @return Field
     */
    public function date($name, $value, $input_attr = [])
    {
      $field              = $this->addField('input', $name, $value);
      $field['type']      = 'text';
      $field['maxlength'] = 10;
      $field['class']     = 'datepicker';

      return $field->mergeAttr($input_attr);
    }
    /**
     * @param           $name
     * @param bool      $value
     * @param array     $input_attr
     *
     * @return Field
     */
    public function checkbox($name, $value, $input_attr = [])
    {
      $field            = $this->addField('input', $name, !!$value);
      $field['type']    = 'checkbox';
      $field['checked'] = !!$value;

      return $field->mergeAttr($input_attr);
    }
    /**
     * @param       $name
     * @param null  $value
     * @param array $inputparams
     *
     * @return Field

     */
    public function percent($name, $value = null, $inputparams = [])
    {
      return $this->number($name, $value, User::percent_dec(), $inputparams)->append('%');
    }
    /**
     * @param       $name
     * @param null  $value
     * @param int   $dec
     * @param array $input_attr
     *
     * @return \ADV\App\Form\Field
     */
    public function number($name, $value = null, $dec = null, $input_attr = [])
    {
      $value             = (is_numeric($dec)) ? $value : Num::_round($value, $dec);
      $field             = $this->addField('input', $name, $value);
      $field['data-dec'] = (int) $dec;
      $field['value']    = Num::_format($field['value'] ? : 0, $field['data-dec']);
      $size              = Arr::get($input_attr, 'size');
      if ($size && is_numeric($size)) {
        $field['size'] = $size;
      } elseif (is_string($size)) {
        $field['class'] .= ($name == 'freight') ? ' freight ' : ' amount ';
      }
      $field['type'] = 'text';
      $this->Ajax->addAssign($name, $name, 'data-dec', $dec);

      return $field->mergeAttr($input_attr);
    }
    /**
     * @param       $name
     * @param null  $value
     * @param array $inputparams
     *
     * @return Field

     */
    public function amount($name, $value = null, $inputparams = [])
    {
      return $this->number($name, $value, User::price_dec(), $inputparams)->prepend('$');
    }
    /**
     * @param $control
     *
     * @return \ADV\App\Form\Field
     */
    public function custom($control)
    {
      preg_match('/name=([\'"]?)(.+?)\1/', $control, $matches);
      $name      = $matches[2];
      $field     = $this->addField('custom', $name, null);
      $id        = $field->id;
      $control   = preg_replace('/id=([\'"]?)' . preg_quote($name) . '\1/', "id='$id'", $control, 1);
      $validator = null;
      $field->customControl($control);

      return $field;
    }
    /**
     * Universal sql combo generator
     * $sql must return selector values and selector texts in columns 0 & 1
     * Options are merged with default.
     *
     * @param       $name
     * @param       $selected_id
     * @param       $sql
     * @param       $valfield
     * @param       $namefield
     * @param array $options
     *
     * @return string
     */
    public function selectBox($name, $selected_id = null, $sql, $valfield, $namefield, $options = null)
    {
      $box = new SelectBox($name, $selected_id, $sql, $valfield, $namefield, $options);

      return $box->create();
    }
    /**
     * Universal array combo generator
     * $items is array of options 'value' => 'description'
     * Options is reduced set of combo_selector options and is merged with defaults.
     *
     * @param                 $name
     * @param                 $selected_id
     * @param   array         $items   Associative array [ value=>label ]
     * @param array|null      $options [  spec_option   => false, // option text or false<br>
     *                                 spec_id       => 0, // option id<br>
     *                                 select_submit => false, //submit on select: true/false<br>
     *                                 async         => true, // select update via ajax (true) vs _page_body reload<br>
     *                                 default       => null, // default value when $_POST is not set<br>
     *                                 multi         => false, // multiple select<br>
     *                                 sel_hint => null,<br>
     *                                 disabled => null,<br>
     * ]
     *
     * @return Field
     */
    public function arraySelect($name, $items, $selected_id = null, $options = [])
    {
      $spec_option = false; // option text or false
      $spec_id     = 0; // option id
      $async       = true; // select update via ajax (true) vs _page_body reload
      $multi       = false; // multiple select
      // search box parameters
      //TODO $height = false; // number of lines in select box
      $sel_hint = null; //
      $disabled = null;
      // ------ merge options with defaults ----------
      extract($options, EXTR_IF_EXISTS);
      $selected_id = $multi ? (array) $selected_id : $selected_id;
      $field       = $this->addField('select', $name, $selected_id);
      // code is generalized for multiple selection support
      if ($this->Input->post("_{$name}_update")) {
        $async ? $this->Ajax->activate($name) : $this->Ajax->activate('_page_body');
      }
      // ------ make selector ----------
      $selector = '';
      if ($spec_option !== false) { // if special option used - add it
        array_unshift($items, [$spec_id=> $spec_option]);
      }
      if ($field->default === null) {
        reset($items);
        $field->default = key($items);
      }
      foreach ($items as $value => $label) {
        $selector .= HTML::setReturn(true)->option(null, $label, ['value'=> $value], false)->setReturn(false);
      }
      $input_attr = [
        'multiple'=> $multi, //
        'disabled'=> $disabled, //
        'name'    => $name . ($multi ? '[]' : ''), //
        'class'   => 'combo', //
        'title'   => $sel_hint
      ];
      $selector   = HTML::setReturn(true)->span("_{$name}_sel", ['class'=> 'combodiv'])->select($field->id, $selector, $input_attr, false)->_span()->setReturn(false);
      $this->Ajax->addUpdate($name, "_{$name}_sel", $selector);
      $field->customControl($selector);

      return $field;
    }
    /**
     * @param             $name
     * @param string|null $value
     * @param             $caption
     * @param array       $input_attr Input attributes
     *
     * @return string
     */
    public function button($name, $value, $caption, $input_attr = [])
    {
      $button = new Button($name, $value, $caption);
      if (is_array($this->currentgroup)) {
        $this->currentgroup[] = $button;
      }
      $this->fields[$button->id] = $button;

      return $button->mergeAttr($input_attr);
    }
    /**
     * Universal submit form button.
     * $atype - type of submit:
     * Normal submit:
     * false - normal button; optional icon
     * null - button visible only in fallback mode; optional icon
     * Ajax submit:
     * true - standard button; optional icon
     * 'default' - default form submit on Ctrl-Enter press; dflt ICON_OK icon
     * 'selector' - ditto with closing current popup editor window
     * 'cancel' - cancel form entry on Escape press; dflt ICON_CANCEL
     * 'process' - displays progress bar during call; optional icon
     * $atype can contain also multiply type selectors separated by space,
     * however make sense only combination of 'process' and one of defualt/selector/cancel
     *
     * @param             $action
     * @param bool|string $caption
     * @param array       $input_attr
     *
     * @return \ADV\App\Form\Button
     */
    public function submit($action, $caption = '', $input_attr = [])
    {
      $button     = new Button('_action', $action, $caption);
      $button->id = $this->nameToId($action);
      if (is_array($this->currentgroup)) {
        $this->currentgroup[] = $button;
      }
      $this->fields[$button->id] = $button;

      return $button->mergeAttr($input_attr);
    }
    public function setValues($values)
    {
      $values = (array) $values;
      foreach ($values as $id=> $value) {
        if (array_key_exists($id, $this->fields)) {
          $this->fields[$id]->value = $value;
        }
      }
    }

    /**
     * Helper function.
     * Returns true if selector $name is subject to update.
     *
     * @param $name
     *
     * @return bool
     */
    public function isListUpdated($name)
    {
      return isset($_POST['_' . $name . '_update']);
    }
    /**
     * @param $valids
     */
    public function runValidators($valids)
    {
      foreach ($_SESSION['forms'][$this->uniqueid]->validators as $function) {
        $valids->$function();
      }
    }
    /**
     * @param $name
     *
     * @return mixed
     */
    protected function nameToId($name)
    {
      return str_replace(['[', ']'], ['-', ''], $name);
    }
    /**
     * @return array
     */
    public function jsonSerialize()
    {
      $return = [];
      $use    = ($this->useDefaults) ? 'default' : 'value';
      foreach ($this->fields as $id=> $field) {
        if ($field instanceof Button) {
          continue;
        }
        $return[$id] = ['value'=> $field->$use];
      }

      return $return;
    }
    /**
     * @return array
     */
    public function __sleep()
    {
      return ['validators'];
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
    public function offsetExists($offset)
    {
      return array_key_exists($offset, $this->fields) || array_key_exists($offset, $this->groups);
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
     * @return \ADV\App\Form\Field Can return all value types.
     */
    public function offsetGet($offset)
    {
      if (!isset($this->fields[$offset]) && isset($this->groups[$offset])) {
        return $this->groups[$offset];
      }

      return $this->fields[$offset];
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
    public function offsetSet($offset, $value)
    {
      $this->fields[$offset] = $value;
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
    public function offsetUnset($offset)
    {
      unset($this->fields[$offset]);
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
      return current($this->groups['_default']);
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
      $this->current = next($this->groups['_default']);
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
      return key($this->groups['_default']);
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     *       Returns true on success or false on failure.
     */
    public function valid()
    {
      return $this->current !== false;
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
      reset($this->groups['_default']);
    }
  }