<?php
  namespace ADV\App\Form;

  use \ADV\Core\Ajax;
  use ADV\App\Forms;
  use ADV\App\User;
  use ADV\Core\Session;
  use ADV\Core\Arr;
  use ADV\Core\Num;

  use \ADV\Core\JS;
  use \ADV\Core\SelectBox;
  use \ADV\Core\HTML;
  use \ADV\Core\Input\Input;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /**
   * @param bool   $multi
   * @param string $action
   * @param string $name
   */
  class Form implements \ArrayAccess
  {
    protected $fields = [];
    protected $start;
    protected $end;
    /** @var Ajax */
    protected $Ajax;
    /** @var Input */
    protected $Input;
    protected $uniqueid;
    protected $validators = [];
    /**
     * @param ADV\Core\Input\Input $input
     * @param ADV\Core\Ajax        $ajax
     * @param \ADV\Core\Session    $session
     */
    public function __construct(\ADV\Core\Input\Input $input = null, \ADV\Core\Ajax $ajax = null, \ADV\Core\Session $session = null) {
      $this->Ajax  = $ajax ? : Ajax::i();
      $this->Input = $input ? : Input::i();
    }
    /**
     * @static
     *
     * @param string $name
     * @param string $action
     * @param bool   $multi
     * @param array  $input_attr
     *
     * @return
     */
    public function start($name = '', $action = '', $multi = null, $input_attr = []) {
      $attr['enctype'] = $multi ? 'multipart/form-data' : null;
      $attr['name']    = $name;
      $attr['method']  = 'post';
      $attr['action']  = $action;
      array_merge($attr, $input_attr);
      $this->start = HTML::setReturn(true)->form($name, $attr)->input(null, ['type'=> 'hidden', 'value'=> $this->uniqueid, 'name'=> '_form_id'])->setReturn(false);

      return $this->start;
    }
    /**
     * @return
     * @internal param int $breaks
     */
    public function end() {
      $this->end = HTML::setReturn(true)->form->setReturn(false);

      return $this->end;
    }
    /**
     * Seek for _POST variable with $prefix.
     * If var is found returns variable name with prefix stripped,
     * and null or -1 otherwise.
     *
     * @param      $prefix
     * @param bool $numeric
     *
     * @return int|null|string
     */
    public function findPostPrefix($prefix, $numeric = true) {
      foreach ($_POST as $postkey => $postval) {
        if (strpos($postkey, $prefix) === 0) {
          $id = substr($postkey, strlen($prefix));

          return $numeric ? (int) $id : $id;
        }
      }

      return $numeric ? -1 : null;
    }
    /**
     *  Helper function.
     *  Returns true if selector $name is subject to update.
     *
     * @param $name
     *
     * @return bool
     */
    public function isListUpdated($name) {
      return isset($_POST['_' . $name . '_update']) || isset($_POST['_' . $name . '_button']);
    }
    /**
     * @param $name
     *
     * @return mixed
     */
    protected function nameToId($name) {
      return str_replace(['[', ']'], ['-', ''], $name);
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $control
     * @param null $control
     */
    public function label($label, $name, $control = null) {
      if ($label === null) {
        return;
      }
      $id = $this->nameToId($name);
      if (!$control && isset($this->fields[$id])) {
        $control = $this->fields[$id];
      }
      $content           = "<label for='$id'><span>$label</span>$control</label>";
      $this->fields[$id] = $content;
    }
    /**
     * @param $control
     *
     * @return \ADV\App\Form\Field
     */
    public function custom($control) {
      preg_match('/name=([\'"]?)(.+?)\1/', $control, $matches);
      $name    = $matches[2];
      $id      = $this->nameToId($name);
      $control = preg_replace('/id=([\'"]?)' . preg_quote($name) . '\1/', "id='$id'", $control, 1);

      $validator = null;
      $field     = new Field('custom', $name);
      $field->customControl($control);
      $this->fields[$id]     = $field;
      $this->validators[$id] =& $field->validator;

      return $field;
    }
    /**
     * @param      $name
     * @param null $value
     *
     * @internal param bool $echo
     * @return string
     */
    public function hidden($name, $value = null) {
      $field         = $this->addField('input', $name, $value);
      $field['type'] = 'hidden';
      $this->Ajax->addUpdate($name, $name, $value);
    }
    /**
     * @param        $name
     * @param        $value
     * @param array  $input_attr
     *
     * @return \ADV\App\Form\Field
     */
    public function  textarea($name, $value = null, $input_attr = []) {
      $field = $this->addField('textarea', $name, $value);
      $field->setContent($value);

      return $field->mergeAttr($input_attr);
    }
    /**
     * @param            $name
     * @param null       $value
     * @param array      $input_attr
     *
     * @return \ADV\App\Form\Field
     */
    public function text($name, $value = null, $input_attr = []) {
      $field         = $this->addField('input', $name, $value);
      $field['type'] = 'text';

      return $field->mergeAttr($input_attr);
    }
    /**
     * @param $tag
     * @param $name
     * @param $value
     *
     * @return Field
     */
    protected function addField($tag, $name, $value) {
      $field = new Field($tag, $name);
      if ($value === null && $this->Input->hasPost($name)) {
        $value = $this->Input->post($name);
      }
      if ($tag !== 'textarea') {
        $field['value'] = e($value);
      }
      $this->fields[$field->id]     = $field;
      $this->validators[$field->id] =& $field->validator;
      $this->Ajax->addUpdate($name, $name, $value);

      return $field;
    }
    /**
     * @param       $name
     * @param null  $value
     * @param array $inputparams
     *
     * @return Field

     */
    public function  percent($name, $value = null, $inputparams = []) {
      return $this->number($name, $value, User::percent_dec(), $inputparams)->append('%');
    }
    /**
     * @param       $name
     * @param null  $value
     * @param array $inputparams
     *
     * @return Field

     */
    public function  amount($name, $value = null, $inputparams = []) {
      return $this->number($name, $value, User::price_dec(), $inputparams)->prepend('$');
    }
    /**
     * @param        $name
     * @param null   $value
     * @param null   $dec
     * @param array  $input_attr
     *
     * @return \ADV\App\Form\Field
     */
    public function number($name, $value = null, $dec = null, $input_attr = []) {
      $field             = $this->addField('input', $name, $value);
      $field['data-dec'] = $dec ? : User::price_dec();
      $_POST[$name]      = $field['value'] = Num::_format($field['value'] ? : 0, $field['data-dec']);
      $size              = Arr::get($input_attr, 'size');
      if ($size && is_numeric($size)) {
        $field['size'] = $size;
      } elseif (is_string($size)) {
        $field['class'] .= ($name == 'freight') ? ' freight ' : ' amount ';
      }
      $field['maxlength'] = $input_attr['max'];
      $field['type']      = 'text';
      $this->Ajax->addAssign($name, $name, 'data-dec', $dec);

      return $field->mergeAttr($input_attr);
    }
    /**
     * Universal sql combo generator
     * $sql must return selector values and selector texts in columns 0 & 1
     * Options are merged with default.
     *
     * @param          $name
     * @param          $selected_id
     * @param          $sql
     * @param          $valfield
     * @param          $namefield
     * @param null     $options
     *
     * @return string
     */
    public function selectBox($name, $selected_id = null, $sql, $valfield, $namefield, $options = null) {
      $box = new SelectBox($name, $selected_id, $sql, $valfield, $namefield, $options);

      return $box->create();
    }
    /**
     *  Universal array combo generator
     *  $items is array of options 'value' => 'description'
     *  Options is reduced set of combo_selector options and is merged with defaults.
     *
     * @param            $name
     * @param            $selected_id
     * @param            $items
     * @param array|null $options
     *
     * @return string
     */
    public function arraySelect($name, $selected_id, $items, $options = []) {
      $spec_option   = false; // option text or false
      $spec_id       = 0; // option id
      $select_submit = false; //submit on select: true/false
      $async         = true; // select update via ajax (true) vs _page_body reload
      $default       = null; // default value when $_POST is not set
      $multi         = false; // multiple select
      // search box parameters
      $height   = false; // number of lines in select box
      $sel_hint = null; //
      $disabled = null;
      // ------ merge options with defaults ----------
      extract($options, EXTR_IF_EXISTS);
      if ($selected_id == null) {
        $selected_id = $this->Input->post($name, null, $default);
      }
      $selected_id = (array) $selected_id;
      // code is generalized for multiple selection support
      if ($this->Input->post("_{$name}_update")) {
        $async ? $this->Ajax->activate($name) : $this->Ajax->activate('_page_body');
      }
      // ------ make selector ----------
      $selector = $first_opt = '';
      $found    = $first_id = false;
      foreach ($items as $value => $descr) {
        $sel   = in_array((string) $value, $selected_id);
        $found = ($sel) ? $value : false;
        if ($first_id === false) {
          $first_id = $value;
        }
        $selector .= HTML::setReturn(true)->option(null, $descr, ['selected'=> $sel, 'value'=> $value], false)->setReturn(false);
      }
      // Prepend special option.
      if ($spec_option !== false) { // if special option used - add it
        $first_id = $spec_id;
        $sel      = $found === false;
        $selector .= HTML::setReturn(true)->option(null, $spec_option, ['selected'=> $sel, 'value'=> $spec_id], false)->setReturn(false) . $selector;
      }
      if ($found === false) {
        $selected_id = [$first_id];
      }
      $_POST[$name] = $multi ? $selected_id : $selected_id[0];
      $input_attr   = [
        'multiple'=> $multi, //
        'disabled'=> $disabled, //
        'id'      => $this->nameToId($name), //
        'name'    => $name . ($multi ? '[]' : ''), //
        'class'   => 'combo', //
        'title'   => $sel_hint
      ];
      $selector     = HTML::setReturn(true)->div("_{$name}_sel", ['class'=> 'combodiv'])->select($input_attr['id'], $selector, $input_attr, false)->_div()->setReturn(false);
      $this->Ajax->addUpdate($name, "_{$name}_sel", $selector);
      if ($select_submit != false) { // if submit on change is used - add select button
        $input_attr = [
          'disabled'=> $disabled,
          'type'    => 'submit',
          'class'   => 'combo_select',
          'stle'    => 'border:0;background:url(/themes/' . User::theme() . '/images/button_ok.png) no-repeat' . (User::fallback() ? '' : 'display:none;'),
          'name'    => '_' . $name . '_update',
          'title'   => _("Select"),
          'value'   => ' '
        ];
        $selector .= HTML::setReturn(true)->input(null, $input_attr, false)->setReturn(false);
      }
      JS::_defaultFocus($name);

      return $selector;
    }
    // SUBMITS //
    /**
     * Universal submit form button.
     * $atype - type of submit:
     *  Normal submit:
     *   false - normal button; optional icon
     *   null  - button visible only in fallback mode; optional icon
     *  Ajax submit:
     *   true    - standard button; optional icon
     *   'default' - default form submit on Ctrl-Enter press; dflt ICON_OK icon
     *   'selector' - ditto with closing current popup editor window
     *   'cancel'  - cancel form entry on Escape press; dflt ICON_CANCEL
     *   'process' - displays progress bar during call; optional icon
     * $atype can contain also multiply type selectors separated by space,
     * however make sense only combination of 'process' and one of defualt/selector/cancel
     *
     * @param       $name
     * @param       $value
     * @param bool  $caption
     * @param bool  $icon
     * @param array $input_attr
     */
    public function submit($name, $value, $caption = false, $icon = false, $input_attr = []) {
      $field     = $field = new Field('button', $name);
      $field->id = $value;
      $field['class'] .= 'ajaxsubmit';
      $field['type']  = 'submit';
      $field['value'] = $value;
      $field['title'] = $caption;
      $icon           = ($icon) ? "<i class='" . $icon . "'> </i> " : '';
      $field->setContent($icon . $caption);
      $this->fields[$field->id] = $field;

      return $field->mergeAttr($input_attr);
    }
    /**
     * For following controls:
     * 'both' - use both Ctrl-Enter and Escape hotkeys
     * 'cancel' - apply to MODE_RESET button
     *
     * @param bool $add
     * @param bool $title
     * @param bool $async
     * @param bool $clone
     */
    public function submitAddUpdate($add = true, $title = false, $async = false, $clone = false) {
      $cancel = $async;
      if ($async === 'both') {
        $async  = 'default';
        $cancel = 'cancel';
      } elseif ($async === 'default') {
        $cancel = true;
      } elseif ($async === 'cancel') {
        $async = true;
      }
      if ($add) {
        Forms::submit(ADD_ITEM, _("Add new"), true, $title, $async);
      } else {
        Forms::submit(UPDATE_ITEM, _("Update"), true, _('Submit Changes'), $async);
        if ($clone) {
          Forms::submit(MODE_CLONE, _("Clone"), true, _('Edit new record with current data'), $async);
        }
        Forms::submit(MODE_RESET, _("Cancel"), true, _('Cancel Changes'), $cancel);
      }
    }
    /**
     * @param $name
     * @param $action
     * @param $msg
     */
    public function submitConfirm($name, $action, $msg = null) {
      if ($msg) {
        $name = $action;
      } else {
        $msg = $action;
      }
      JS::_beforeload("_validate.$name=function(){ return confirm('" . strtr($msg, array("\n" => '\\n')) . "');};");
    }
    /**
     * @param        $name
     * @param        $value
     * @param bool   $title
     * @param bool   $icon
     * @param string $aspect
     *
     * @return string
     */
    public function button($name, $value, $title = false, $icon = false, $aspect = '') {
      // php silently changes dots,spaces,'[' and characters 128-159
      // to underscore in POST names, to maintain compatibility with register_globals
      $rel = '';
      if ($aspect == 'selector') {
        $rel   = " rel='$value'";
        $value = _("Select");
      }
      $caption = ($name == '_action') ? $title : $value;
      $name    = htmlentities(strtr($name, array('.' => '=2E', ' ' => '=20', '=' => '=3D', '[' => '=5B')));
      if (User::graphic_links() && $icon) {
        if ($value == _("Delete")) // Helper during implementation
        {
          $icon = ICON_DELETE;
        }

        return "<button type='submit' class='editbutton' id='" . $name . "' name='" . $name . "' value='1'" . ($title ? " title='$title'" : " title='$value'") . ($aspect ?
          " data-aspect='$aspect'" : '') . $rel . " />" . Forms::setIcon($icon) . "</button>\n";
      } else {
        return "<button type='submit' class='editbutton' id='" . $name . "' name='" . $name . "' value='$value'" . ($title ? " title='$title'" : '') . ($aspect ?
          " data-aspect='$aspect'" : '') . $rel . " >$caption</button>\n";
      }
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
      return array_key_exists($offset, $this->fields);
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
    public function offsetGet($offset) {
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
    public function offsetSet($offset, $value) {
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
    public function offsetUnset($offset) {
      unset($this->fields[$offset]);
    }
    /**
     * @return array
     */
    public function getFields() {
      return $this->fields;
    }
    /**
     * @param $valids
     */
    public function runValidators($valids) {
      foreach ($_SESSION['forms'][$this->uniqueid]->validators as $function) {
        $valids->$function();
      }
    }
    /**
     * @return array
     */
    public function __sleep() {
      return ['validators'];
    }
  }
