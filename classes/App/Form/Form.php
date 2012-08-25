<?php
  namespace ADV\App\Form;
  use \ADV\Core\Ajax;
  use Forms;
  use \ADV\Core\JS;
  use \ADV\Core\SelectBox;
  use User;
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
    /**
     * @param ADV\Core\Input\Input $input
     * @param ADV\Core\Ajax        $ajax
     */
    public function __construct(\ADV\Core\Input\Input $input = null, \ADV\Core\Ajax $ajax = null)
    {
      $this->Ajax  = $ajax ? : Ajax::i();
      $this->Input = $input ? : Input::i();
    }
    /**
     * @static
     *
     * @param bool   $multi
     * @param string $action
     * @param string $name
     */
    public function start($name = '', $action = '', $multi = null, $input_attr = [])
    {
      $attr['enctype'] = $multi ? 'multipart/form-data' : null;
      $attr['name']    = $name;
      $attr['method']  = 'post';
      $attr['action']  = $action;
      array_merge($attr, $input_attr);
      $this->start = HTML::setReturn(true)->form($name, $attr)->setReturn(false);

      return $this->start;
    }
    /**
     * @param int $breaks
     */
    public function end()
    {
      $this->end = HTML::setReturn(true)->input('_focus', ['name'=> '_focus', 'type'=> 'hidden', 'value'=> e($this->Input->post('_focus'))])->form->setReturn(false);

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
    public function findPostPrefix($prefix, $numeric = true)
    {
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
    public function isListUpdated($name)
    {
      return isset($_POST['_' . $name . '_update']) || isset($_POST['_' . $name . '_button']);
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
     * @param      $label
     * @param      $name
     * @param null $control
     */
    public function label($label, $name, $control = null)
    {
      if ($label === null) {
        return;
      }
      $id = $this->nameToId($name);
      if (!$control && isset($this->fields[$id])) {
        $control = $this->fields[$id];
      }
      $content           = "<label for='$name'><span>$label</span>$control</label>";
      $this->fields[$id] = $content;
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
      $attr['value'] = e($value ? : $this->Input->post($name));
      $attr['id']    = $this->nameToId($name);
      $attr['type']  = 'hidden';
      $attr['name']  = $name;
      $this->Ajax->addUpdate($name, $name, $value);
      $this->fields[$attr['id']] = HTML::setReturn(true)->input($attr['id'], $attr, false)->setReturn(false);
    }
    /**
     * @param        $label
     * @param        $name
     * @param        $value
     * @param        $cols
     * @param        $rows
     * @param null   $title
     * @param string $params
     */
    public function  textarea($name, $value = null, $input_attr = [])
    {
      $feild = $this->addFeild('textarea', $name, $value);
      $feild->setContent($value);

      return $feild->mergeAttr($input_attr);
    }
    /**
     * @param            $label
     * @param            $name
     * @param null       $value
     * @param int        $max
     * @param int|string $size
     * @param string     $title
     * @param array      $input_attr
     */
    public function text($name, $value = null, $input_attr = [])
    {
      $feild         = $this->addFeild('input', $name, $value);
      $feild['type'] = 'text';

      return $feild->mergeAttr($input_attr);
    }
    protected function addFeild($tag, $name, $value)
    {
      $feild = new Feild($tag, $name);
      if ($value === null && $this->Input->hasPost($name)) {
        $value = $this->Input->post($name);
      }
      $feild['value']           = $value;
      $this->fields[$feild->id] = $feild;
      $this->Ajax->addUpdate($name, $name, $value);

      return $feild;
    }
    /**
     * @param              $label
     * @param              $name
     * @param null         $value
     * @param array|string $inputparams
     *
     * @return void
     * @internal param null $init
     */
    public function  percent($label, $name, $value = null, $inputparams = [])
    {
      $attr['class'] = 'percent';
      array_merge($attr, $inputparams);
      $this->number($label, $name, $value, User::percent_dec(), '%', $inputparams);
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $value
     * @param null   $dec
     * @param null   $max
     * @param        $size
     * @param null   $post_label
     * @param array  $input_attr
     *
     * @return void
     * @internal param null $init
     * @internal param null $params
     * @internal param null $id
     * @internal param string $inputparams
     * @internal param bool $negatives
     */
    public function number($label, $name, $value = null, $dec = null, $post_label = null, $input_attr = [])
    {
      $attr['placeholder'] = rtrim($label, ':');
      $attr['data-dec']    = $dec ? : User::price_dec();
      if (!$this->Input->post($name)) {
        $value        = $value ? : 0;
        $_POST[$name] = number_format($value, $dec);
      }
      if ($value === null) {
        $attr['value'] = $this->Input->post($name);
      }
      $size = &$input_attr['size'];
      if ($size && is_numeric($size)) {
        $attr['size'] = $size;
      } elseif (is_string($size)) {
        $attr['class'] .= ($name == 'freight') ? ' freight ' : ' amount ';
      }
      $attr['maxlength'] = $input_attr['max'];
      $attr['name']      = $name;
      $attr['id']        = $this->nameToId($name);
      $attr['type']      = 'text';
      array_merge($attr, $input_attr);
      $content = HTML::setReturn(true)->input($name, $attr)->setReturn(false);
      $this->Ajax->addUpdate($name, $name, $value);
      $pre_label = '';
      if (is_array($post_label)) {
        $pre_label  = $post_label[0];
        $post_label = null;
      }
      if ($post_label) {
        $content = "<div class='input-append'>$content<span class='add-on' id='_{$name}_label'>$post_label</div>";
        $this->Ajax->addUpdate($name, '_' . $name . '_label', $post_label);
      } elseif ($pre_label) {
        $content = "<div class='input-prepend'><span class='add-on' >$pre_label</span>$content</div>";
      }
      $this->fields[$attr['id']] = $content;
      $this->label($label, $name);
      $this->Ajax->addUpdate($name, $name, $value);
      $this->Ajax->addAssign($name, $name, 'data-dec', $dec);
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
    public function selectBox($name, $selected_id = null, $sql, $valfield, $namefield, $options = null)
    {
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
    public function arraySelect($name, $selected_id, $items, $options = [])
    {
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
     * @param      $name
     * @param      $value
     * @param bool $echo
     * @param bool $title
     * @param bool $atype
     * @param bool $icon
     *
     * @return string
     */
    public static function submit($name, $value, $echo = true, $title = false, $atype = false, $icon = false)
    {
      $aspect = '';
      if ($atype === null) {
        $aspect = User::fallback() ? " data-aspect='fallback'" : " style='display:none;'";
      } elseif (!is_bool($atype)) { // necessary: switch uses '=='
        $aspect = " data-aspect='$atype' ";
        $types  = explode(' ', $atype);
        foreach ($types as $type) {
          switch ($type) {
            case 'selector':
              $aspect = " data-aspect='selector' rel='$value'";
              $value  = _("Select");
              if ($icon === false) {
                $icon = "<i class='greenfg icon-ok'> </i>";
              }
              break;
            case 'default':
              $atype = true;
              if ($icon === false) {
                $icon = "<i class='greenfg icon-ok'> </i>";
              }
              break;
            case 'cancel':
              if ($icon === false) {
                $icon = "<i class=' icon-danger'> </i>";
              }
              break;
          }
        }
      }
      $caption    = ($name == '_action') ? $title : $value;
      $id         = ($name == '_action') ? '' : "id=\"$name\"";
      $submit_str = "<button class=\"" . (($atype === true || $atype === false) ? (($atype) ? 'ajaxsubmit' : 'inputsubmit') : $atype) . "\" type=\"submit\" " . $aspect . " name=\"$name\"  value=\"$value\"" . ($title ? " title='$title'" : '') . ">" . $icon . "<span>$caption</span>" . "</button>\n";
      if ($echo) {
        echo $submit_str;
      } else {
        return $submit_str;
      }
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
    public function submitAddUpdate($add = true, $title = false, $async = false, $clone = false)
    {
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
    public function submitConfirm($name, $action, $msg = null)
    {
      if ($msg) {
        $name = $action;
      } else {
        $msg = $action;
      }
      JS::_beforeload("_validate.$name=function(){ return confirm('" . strtr($msg, array("\n" => '\\n')) . "');};");
    }
    /**
     * @param             $icon
     * @param bool|string $title
     *
     * @return string
     */
    public function setIcon($icon, $title = '')
    {
      $path  = THEME_PATH . User::theme();
      $title = $title ? " title='$title'" : '';

      return "<img src='$path/images/$icon' style='width:12px; height=12px' $title />";
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
    public function button($name, $value, $title = false, $icon = false, $aspect = '')
    {
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

        return "<button type='submit' class='editbutton' id='" . $name . "' name='" . $name . "' value='1'" . ($title ? " title='$title'" : " title='$value'") . ($aspect ? " data-aspect='$aspect'" : '') . $rel . " />" . Forms::setIcon($icon) . "</button>\n";
      } else {
        return "<button type='submit' class='editbutton' id='" . $name . "' name='" . $name . "' value='$value'" . ($title ? " title='$title'" : '') . ($aspect ? " data-aspect='$aspect'" : '') . $rel . " >$caption</button>\n";
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
    public function offsetExists($offset)
    {
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
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
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
    public function getFields()
    {
      return $this->fields;
    }
  }
