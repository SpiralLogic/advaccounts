<?php
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
  class Form
  {
    /**
     * @static
     *
     * @param bool   $multi
     * @param string $action
     * @param string $name
     */
    public static function start($multi = false, $action = "", $name = "")
    {
      if ($name != "") {
        $name = "name='$name' id='$name'";
      }
      if ($action == "") {
        $action = $_SERVER['DOCUMENT_URI'];
      }
      if ($multi) {
        echo "<form enctype='multipart/form-data' method='post' action='$action' $name>\n";
      } else {
        echo "<form method='post' action='$action' $name>\n";
      }
    }
    /**
     * @param int $breaks
     */
    public static function end($breaks = 0)
    {
      if ($breaks) {
        Display::br($breaks);
      }
      echo "<input type=\"hidden\" name=\"_focus\" value=\"" . Form::getPost('_focus') . "\">\n";
      echo "</form>\n";
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
    public static function findPostPrefix($prefix, $numeric = true)
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
     * @param        $name
     * @param string $dflt
     *
     * @return string|int
     */
    public static function getPost($name, $dflt = '')
    {
      return ((!isset($_POST[$name]) || $_POST[$name] === '') ? $dflt : $_POST[$name]);
    }
    /**
     *  Helper function.
     *  Returns true if selector $name is subject to update.
     *
     * @param $name
     *
     * @return bool
     */
    public static function isListUpdated($name)
    {
      return isset($_POST['_' . $name . '_update']) || isset($_POST['_' . $name . '_button']);
    }
    /**
     * @param      $name
     * @param null $value
     * @param bool $echo
     *
     * @return string
     */
    public static function hidden($name, $value = null, $echo = true)
    {
      if ($value === null) {
        $value = Form::getPost($name);
      }
      $ret = "<input type=\"hidden\" id=\"$name\" name=\"$name\" value=\"$value\">";
      Ajax::i()->addUpdate($name, $name, $value);
      if ($echo) {
        echo $ret . "\n";
      } else {
        return $ret;
      }
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
    public static function selectBox($name, $selected_id = null, $sql, $valfield, $namefield, $options = null)
    {
      $box = new SelectBox($name, $selected_id, $sql, $valfield, $namefield, $options);
      return $box->create();
    }
    /**
     *  Universal array combo generator
     *  $items is array of options 'value' => 'description'
     *  Options is reduced set of combo_selector options and is merged with defaults.
     *
     * @param      $name
     * @param      $selected_id
     * @param      $items
     * @param null $options
     *
     * @return string
     */
    public static function arraySelect($name, $selected_id, $items, $options = null)
    {
      $opts = array( // default options
        'spec_option'   => false, // option text or false
        'spec_id'       => 0, // option id
        'select_submit' => false, //submit on select: true/false
        'async'         => true, // select update via ajax (true) vs _page_body reload
        'default'       => '', // default value when $_POST is not set
        'multi'         => false, // multiple select
        // search box parameters
        'height'        => false, // number of lines in select box
        'sel_hint'      => null, 'disabled' => false
      );
      // ------ merge options with defaults ----------
      if ($options != null) {
        $opts = array_merge($opts, $options);
      }
      $select_submit = $opts['select_submit'];
      $spec_id       = $opts['spec_id'];
      $spec_option   = $opts['spec_option'];
      $disabled      = $opts['disabled'] ? "disabled" : '';
      $multi         = $opts['multi'];
      if ($selected_id == null) {
        $selected_id = Form::getPost($name, $opts['default']);
      }
      if (!is_array($selected_id)) {
        $selected_id = array($selected_id);
      } // code is generalized for multiple selection support
      if (isset($_POST['_' . $name . '_update'])) {
        if (!$opts['async']) {
          Ajax::i()->activate('_page_body');
        } else {
          Ajax::i()->activate($name);
        }
      }
      // ------ make selector ----------
      $selector = $first_opt = '';
      $first_id = false;
      $found    = false;
      //if($name=='SelectStockFromList') Event::error($sql);
      foreach ($items as $value => $descr) {
        $sel = '';
        if (in_array((string) $value, $selected_id)) {
          $sel   = 'selected';
          $found = $value;
        }
        if ($first_id === false) {
          $first_id  = $value;
          $first_opt = $descr;
        }
        $selector .= "<option $sel value='$value'>$descr</option>\n";
      }
      // Prepend special option.
      if ($spec_option !== false) { // if special option used - add it
        $first_id  = $spec_id;
        $first_opt = $spec_option;
        $sel       = $found === false ? 'selected' : '';
        $selector  = "<option $sel value='$spec_id'>$spec_option</option>\n" . $selector;
      }
      if ($found === false) {
        $selected_id = array($first_id);
      }
      $_POST[$name] = $multi ? $selected_id : $selected_id[0];
      $selector     = "<select " . ($multi ? "multiple" : '') . ($opts['height'] !== false ? ' size="' . $opts['height'] . '"' :
        '') . "$disabled id='$name' name='$name" . ($multi ? '[]' :
        '') . "' class='combo' title='" . $opts['sel_hint'] . "'>" . $selector . "</select>\n";
      Ajax::i()->addUpdate($name, "_{$name}_sel", $selector);
      $selector = "<span id='_{$name}_sel'>" . $selector . "</span>\n";
      if ($select_submit != false) { // if submit on change is used - add select button
        $_select_button
          = "<input %s type='submit' class='combo_select' style='border:0;background:url
            (/themes/%s/images/button_ok.png) no-repeat;%s' data-aspect='fallback' name='%s' value=' ' title='" . _("Select") . "'> ";
        $selector .= sprintf($_select_button, $disabled, User::theme(), (User::fallback() ? '' :
          'display:none;'), '_' . $name . '_update') . "\n";
      }
      JS::default_focus($name);
      return $selector;
    }
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
                $icon = ICON_SUBMIT;
              }
              break;
            case 'default':
              if ($icon === false) {
                $icon = ICON_SUBMIT;
              }
              break;
            case 'cancel':
              if ($icon === false) {
                $icon = ICON_ESCAPE;
              }
              break;
          }
        }
      }
      $caption    = ($name == '_action') ? $title : $value;
      $id         = ($name == '_action') ? '' : "id=\"$name\"";
      $submit_str = "<button class=\"" . (($atype === true || $atype === false) ? (($atype) ? 'ajaxsubmit' : 'inputsubmit') :
        $atype) . "\" type=\"submit\" " . $aspect . " name=\"$name\"  value=\"$value\"" . ($title ? " title='$title'" :
        '') . ">" . ($icon ? "<img alt='$value' src='/themes/" . User::theme() . "/images/$icon' height='12'>" :
        '') . "<span>$caption</span>" . "</button>\n";
      if ($echo) {
        echo $submit_str;
      } else {
        return $submit_str;
      }
    }
    /**
     * @param      $name
     * @param      $value
     * @param bool $echo
     * @param bool $title
     * @param bool $async
     * @param bool $icon
     */
    public static function submitCenter($name, $value, $echo = true, $title = false, $async = false, $icon = false)
    {
      if ($echo) {
        echo "<div class='center'>";
      }
      Form::submit($name, $value, $echo, $title, $async, $icon);
      if ($echo) {
        echo "</div>";
      }
    }
    /**
     * @param      $name
     * @param      $value
     * @param bool $title
     * @param bool $async
     * @param bool $icon
     */
    public static function submitCenterBegin($name, $value, $title = false, $async = false, $icon = false)
    {
      echo "<div class='center'>";
      Form::submit($name, $value, true, $title, $async, $icon);
      echo "&nbsp;";
    }
    /**
     * @param      $name
     * @param      $value
     * @param bool $title
     * @param bool $async
     * @param bool $icon
     */
    public static function submitCenterInsert($name, $value, $title = false, $async = false, $icon = false)
    {
      Form::submit($name, $value, true, $title, $async, $icon);
      echo "&nbsp;";
    }
    /**
     * @param      $name
     * @param      $value
     * @param bool $title
     * @param bool $async
     * @param bool $icon
     */
    public static function submitCenterEnd($name, $value, $title = false, $async = false, $icon = false)
    {
      echo "&nbsp;";
      Form::submit($name, $value, true, $title, $async, $icon);
      echo "</div>";
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
    public static function submitAddUpdate($add = true, $title = false, $async = false, $clone = false)
    {
      $cancel = $async;
      if ($async === 'both') {
        $async  = 'default';
        $cancel = 'cancel';
      } else {
        if ($async === 'default') {
          $cancel = true;
        } else {
          if ($async === 'cancel') {
            $async = true;
          }
        }
      }
      if ($add) {
        Form::submit(ADD_ITEM, _("Add new"), true, $title, $async);
      } else {
        Form::submit(UPDATE_ITEM, _("Update"), true, _('Submit changes'), $async);
        if ($clone) {
          Form::submit(MODE_CLONE, _("Clone"), true, _('Edit new record with current data'), $async);
        }
        Form::submit(MODE_RESET, _("Cancel"), true, _('Cancel edition'), $cancel);
      }
    }
    /**
     * @param bool $add
     * @param bool $title
     * @param bool $async
     * @param bool $clone
     */
    public static function submitAddUpdateCenter($add = true, $title = false, $async = false, $clone = false)
    {
      echo "<div class='center'>";
      Form::submitAddUpdate($add, $title, $async, $clone);
      echo "</div>";
    }
    /**
     * @param bool   $add
     * @param bool   $right
     * @param string $extra
     * @param bool   $title
     * @param bool   $async
     * @param bool   $clone
     */
    public static function submitAddUpdateRow($add = true, $right = true, $extra = "", $title = false, $async = false, $clone = false)
    {
      echo "<tr>";
      if ($right) {
        echo "<td>&nbsp;</td>\n";
      }
      echo "<td $extra>";
      Form::submitAddUpdate($add, $title, $async, $clone);
      echo "</td></tr>\n";
    }
    /**
     * @param        $name
     * @param        $value
     * @param string $extra
     * @param bool   $title
     * @param bool   $async
     */
    public static function submitCells($name, $value, $extra = "", $title = false, $async = false)
    {
      echo "<td $extra>";
      Form::submit($name, $value, true, $title, $async);
      echo "</td>\n";
    }
    /**
     * @param        $name
     * @param        $value
     * @param bool   $right
     * @param string $extra
     * @param bool   $title
     * @param bool   $async
     */
    public static function submitRow($name, $value, $right = true, $extra = "", $title = false, $async = false)
    {
      echo "<tr>";
      if ($right) {
        echo "<td>&nbsp;</td>\n";
      }
      Form::submitCells($name, $value, $extra, $title, $async);
      echo "</tr>\n";
    }
    /**
     * @param      $name
     * @param      $value
     * @param bool $title
     */
    public static function submitReturn($name, $value, $title = false)
    {
      if (Input::request('frame')) {
        Form::submit($name, $value, true, $title, 'selector');
      }
    }
    /**
     * @param $name
     * @param $action
     * @param $msg
     */
    public static function submitConfirm($name, $action, $msg = null)
    {
      if (!$msg) {
        $msg = $action;
      } else {
        $name = $action;
      }

      JS::beforeload("_validate.$name=function(){ return confirm('" . strtr($msg, array("\n" => '\\n')) . "');};");
    }
    /**
     * @param      $icon
     * @param bool $title
     *
     * @return string
     */
    public static function setIcon($icon, $title = false)
    {
      return "<img src='/themes/" . User::theme() . "/images/$icon' style='width:12' height='12' " . ($title ? " title='$title'" :
        "") . " />\n";
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
    public static function button($name, $value, $title = false, $icon = false, $aspect = '')
    {
      // php silently changes dots,spaces,'[' and characters 128-159
      // to underscore in POST names, to maintain compatibility with register_globals
      $rel = '';
      if ($aspect == 'selector') {
        $rel   = " rel='$value'";
        $value = _("Select");
      }
      $caption = ($name == '_action') ? $title : $value;

      $name = htmlentities(strtr($name, array('.' => '=2E', ' ' => '=20', '=' => '=3D', '[' => '=5B')));
      if (User::graphic_links() && $icon) {
        if ($value == _("Delete")) // Helper during implementation
        {
          $icon = ICON_DELETE;
        }
        return "<button type='submit' class='editbutton' id='" . $name . "' name='" . $name . "' value='1'" . ($title ?
          " title='$title'" : " title='$value'") . ($aspect ? " data-aspect='$aspect'" :
          '') . $rel . " />" . Form::setIcon($icon) . "</button>\n";
      } else {
        return "<button type='submit' class='editbutton' id='" . $name . "' name='" . $name . "' value='$value'" . ($title ?
          " title='$title'" : '') . ($aspect ? " data-aspect='$aspect'" : '') . $rel . " >$caption</button>\n";
      }
    }
    /**
     * @param        $name
     * @param        $value
     * @param bool   $title
     * @param bool   $icon
     * @param string $aspect
     */
    public static function buttonCell($name, $value, $title = false, $icon = false, $aspect = '')
    {
      echo "<td class='center'>";
      echo Form::button($name, $value, $title, $icon, $aspect);
      echo "</td>";
    }
    /**
     * @param      $name
     * @param      $value
     * @param bool $title
     */
    public static function buttonDeleteCell($line_no, $value, $title = false)
    {
      if (strpos($line_no, 'Delete') === 0) {
        Form::buttonCell($line_no, $value, $title, ICON_DELETE);
      } else {
        Form::buttonCell('_action', Orders::DELETE_LINE . $line_no, $value, ICON_DELETE);
      }
    }
    /**
     * @param      $name
     * @param      $value
     * @param bool $title
     */
    public static function buttonEditCell($line_no, $value, $title = false)
    {
      if (strpos($line_no, 'Edit') === 0) {
        Form::buttonCell($line_no, $value, $title, ICON_EDIT);
      } else {
        Form::buttonCell('_action', Orders::EDIT_LINE . $line_no, $value, ICON_EDIT);
      }
    }
    /**
     * @param      $name
     * @param      $value
     * @param bool $title
     */
    public static function buttonSelectCell($name, $value, $title = false)
    {
      Form::buttonCell($name, $value, $title, ICON_ADD, 'selector');
    }
    /**
     * @param $name
     *
     * @return int
     */
    public static function hasPost($name)
    {
      if (!isset($_POST[$name])) {
        return 0;
      }
      return 1;
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $value
     * @param bool $submit_on_change
     * @param bool $title
     *
     * @return string
     */
    public static function checkbox($label, $name, $value = null, $submit_on_change = false, $title = false)
    {
      $str = '';
      if ($label) {
        $str .= $label . " ";
      }
      if ($submit_on_change !== false) {
        if ($submit_on_change === true) {
          $submit_on_change = "JsHttpRequest.request(\"_{$name}_update\", this.form);";
        }
      }
      if ($value === null) {
        $value = Form::getPost($name, 0);
      }
      $str .= "<input" . ($value == 1 ? ' checked' :
        '') . " type='checkbox' name='$name' id='$name' value='1'" . ($submit_on_change ? " onclick='$submit_on_change'" :
        '') . ($title ? " title='$title'" : '') . " >\n";
      Ajax::i()->addUpdate($name, $name, $value);
      return $str;
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $value
     * @param bool $submit_on_change
     * @param bool $title
     */
    public static function check($label, $name, $value = null, $submit_on_change = false, $title = false)
    {
      echo Form::checkbox($label, $name, $value, $submit_on_change, $title);
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $value
     * @param bool   $submit_on_change
     * @param bool   $title
     * @param string $params
     */
    public static function  checkCells($label, $name, $value = null, $submit_on_change = false, $title = false, $params = '')
    {
      echo "<td $params>";
      if ($label != null) {
        echo "<label for=\"$name\"> $label</label>";
      }
      Form::check(null, $name, $value, $submit_on_change, $title);
      echo "</td>";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $value
     * @param bool $submit_on_change
     * @param bool $title
     */
    public static function  checkRow($label, $name, $value = null, $submit_on_change = false, $title = false)
    {
      echo "<tr><td class='label'>$label</td>";
      Form::checkCells(null, $name, $value, $submit_on_change, $title);
      echo "</tr>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $value
     * @param string $size
     * @param string $max
     * @param bool   $title
     * @param string $labparams
     * @param string $post_label
     * @param string $inparams
     */
    public static function  textCells($label, $name, $value = null, $size = "", $max = "", $title = false, $labparams = "", $post_label = "", $inparams = "")
    {
      if ($label != null) {
        Cell::label($label, $labparams);
      }
      echo "<td>";
      if ($value === null) {
        $value = Form::getPost($name);
      }
      echo "<input $inparams type=\"text\" name=\"$name\" id=\"$name\" size=\"$size\" maxlength=\"$max\" value=\"$value\"" . ($title ?
        " title='$title'" : '') . ">";
      if ($post_label != "") {
        echo " " . $post_label;
      }
      echo "</td>\n";
      Ajax::i()->addUpdate($name, $name, $value);
    }
    /**
     * @param      $label
     * @param      $name
     * @param      $size
     * @param null $max
     * @param null $init
     * @param null $title
     * @param null $labparams
     * @param null $post_label
     * @param bool $submit_on_change
     */
    public static function  textCellsEx($label, $name, $size, $max = null, $init = null, $title = null, $params = null, $post_label = null, $submit_on_change = false)
    {
      JS::default_focus($name);
      if (!isset($_POST[$name]) || $_POST[$name] == "") {
        if ($init !== null) {
          $_POST[$name] = $init;
        } else {
          $_POST[$name] = "";
        }
      }
      if ($label != null) {
        echo "<td class='label' $params> <label for=\"$name\"> $label</label>";
      } else {
        echo "<td >";
      }
      if (!isset($max)) {
        $max = $size;
      }
      $class = $submit_on_change ? 'class="searchbox"' : '';
      echo "<input $class type=\"text\" name=\"$name\" id=\"$name\" size=\"$size\" maxlength=\"$max\" value=\"" . $_POST[$name] . "\"" . ($title ?
        " title='$title'" : '') . " >";
      if ($post_label) {
        echo " " . $post_label;
      }
      echo "</td>\n";
      Ajax::i()->addUpdate($name, $name, $_POST[$name]);
    }
    /**
     * @param        $label
     * @param        $name
     * @param        $value
     * @param bool   $size
     * @param        $max
     * @param null   $title
     * @param string $params
     * @param string $post_label
     */
    public static function  textRow($label, $name, $value, $size = false, $max, $title = null, $params = "", $post_label = "")
    {
      echo "<tr><td class='label'><label for='$name'>$label</label></td>";
      Form::textCells(null, $name, $value, $size, $max, $title, $params, $post_label);
      echo "</tr>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param        $size
     * @param null   $max
     * @param null   $title
     * @param null   $value
     * @param null   $params
     * @param null   $post_label
     * @param string $params2
     * @param bool   $submit_on_change
     */
    public static function  textRowEx($label, $name, $size, $max = null, $title = null, $value = null, $params = null, $post_label = null, $params2 = '', $submit_on_change = false)
    {
      echo "<tr {$params}><td class='label' {$params2}>$label</td>";
      Form::textCellsEx(null, $name, $size, $max, $value, $title, $params, $post_label, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param        $value
     * @param        $size
     * @param        $max
     * @param null   $title
     * @param string $params
     * @param string $post_label
     */
    public static function emailRow($label, $name, $value, $size, $max, $title = null, $params = "", $post_label = "")
    {
      if (Form::getPost($name)) {
        $label = "<a href='Mailto:" . $_POST[$name] . "'>$label</a>";
      }
      Form::textRow($label, $name, $value, $size, $max, $title, $params, $post_label);
    }
    /**
     * @param      $label
     * @param      $name
     * @param      $size
     * @param null $max
     * @param null $title
     * @param null $value
     * @param null $params
     * @param null $post_label
     */
    public static function emailRowEx($label, $name, $size, $max = null, $title = null, $value = null, $params = null, $post_label = null)
    {
      if (Form::getPost($name)) {
        $label = "<a href='Mailto:" . $_POST[$name] . "'>$label</a>";
      }
      Form::textRowEx($label, $name, $size, $max, $title, $value, $params, $post_label);
    }
    /**
     * @param        $label
     * @param        $name
     * @param        $value
     * @param        $size
     * @param        $max
     * @param null   $title
     * @param string $params
     * @param string $post_label
     */
    public static function linkRow($label, $name, $value, $size, $max, $title = null, $params = "", $post_label = "")
    {
      $val = Form::getPost($name);
      if ($val) {
        if (strpos($val, 'http://') === false) {
          $val = 'http://' . $val;
        }
        $label = "<a href='$val' target='_blank'>$label</a>";
      }
      Form::textRow($label, $name, $value, $size, $max, $title, $params, $post_label);
    }
    /**
     * @param      $label
     * @param      $name
     * @param      $size
     * @param null $max
     * @param null $title
     * @param null $value
     * @param null $params
     * @param null $post_label
     */
    public static function linkRowEx($label, $name, $size, $max = null, $title = null, $value = null, $params = null, $post_label = null)
    {
      $val = Form::getPost($name);
      if ($val) {
        if (strpos($val, 'http://') === false) {
          $val = 'http://' . $val;
        }
        $label = "<a href='$val' target='_blank'>$label</a>";
      }
      Form::textRowEx($label, $name, $size, $max, $title, $value, $params, $post_label);
    }
    /**
     *   Since ADV 2.2  $init parameter is superseded by $check.
     *   When $check!=null current date is displayed in red when set to other
     *   than current date.
     *
     * @param       $label
     * @param       $name
     * @param null  $title
     * @param null  $check
     * @param int   $inc_days
     * @param int   $inc_months
     * @param int   $inc_years
     * @param null  $params
     * @param bool  $submit_on_change
     * @param array $options
     */
    public static function dateCells($label, $name, $title = null, $check = null, $inc_days = 0, $inc_months = 0, $inc_years = 0, $params = null, $submit_on_change = false, $options = array())
    {
      if (!isset($_POST[$name]) || $_POST[$name] == "") {
        if ($inc_years == 1001) {
          $_POST[$name] = null;
        } else {
          $dd = Dates::today();
          if ($inc_days != 0) {
            $dd = Dates::add_days($dd, $inc_days);
          }
          if ($inc_months != 0) {
            $dd = Dates::add_months($dd, $inc_months);
          }
          if ($inc_years != 0) {
            $dd = Dates::add_years($dd, $inc_years);
          }
          $_POST[$name] = $dd;
        }
      }
      $post_label = "";
      if ($label != null) {
        echo "<td class='label'><label for=\"$name\"> $label</label>";
      } else {
        echo "<td >";
      }
      $class  = $submit_on_change ? 'searchbox datepicker' : 'datepicker';
      $aspect = $check ? ' data-aspect="cdate"' : '';
      if ($check && (Form::getPost($name) != Dates::today())) {
        $aspect .= ' style="color:#FF0000"';
      }
      echo "<input id='$name' type='text' name='$name' class='$class' $aspect size=\"10\" maxlength='10' value=\"" . $_POST[$name] . "\"" . ($title ?
        " title='$title'" : '') . " > $post_label";
      echo "</td>\n";
      Ajax::i()->addUpdate($name, $name, $_POST[$name]);
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $title
     * @param null $check
     * @param int  $inc_days
     * @param int  $inc_months
     * @param int  $inc_years
     * @param null $params
     * @param bool $submit_on_change
     */
    public static function  dateRow($label, $name, $title = null, $check = null, $inc_days = 0, $inc_months = 0, $inc_years = 0, $params = null, $submit_on_change = false)
    {
      echo "<tr><td class='label'><label for='$name'> $label</label></td>";
      Form::dateCells(null, $name, $title, $check, $inc_days, $inc_months, $inc_years, $params, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @param $label
     * @param $name
     * @param $value
     */
    public static function passwordRow($label, $name, $value)
    {
      echo "<tr><td class='label'><label for='$name'>$label</label></td>";
      Cell::label("<input type='password' name='$name' id='$name' value='$value' />");
      echo "</tr>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param string $id
     */
    public static function fileCells($label, $name, $id = "")
    {
      if ($id != "") {
        $id = "id='$id'";
      }
      Cell::labels($label, "<input type='file' name='$name' $id />");
    }
    /**
     * @param        $label
     * @param        $name
     * @param string $id
     */
    public static function fileRow($label, $name, $id = "")
    {
      echo "<tr><td class='label'>$label</td>";
      Form::fileCells(null, $name, $id);
      echo "</tr>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $title
     * @param null $init
     * @param null $params
     * @param bool $submit_on_change
     */
    public static function  refCells($label, $name, $title = null, $init = null, $params = null, $submit_on_change = false)
    {
      Form::textCellsEx($label, $name, 9, 18, $init, $title, $params, null, $submit_on_change);
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $title
     * @param null $init
     * @param bool $submit_on_change
     */
    public static function  refRow($label, $name, $title = null, $init = null, $submit_on_change = false)
    {
      echo "<tr><td class='label'><label for='$name'> $label</label></td>";
      Form::refCells(null, $name, $title, $init, null, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $init
     * @param string $cellparams
     * @param string $inputparams
     */
    public static function percentRow($label, $name, $init = null, $cellparams = '', $inputparams = '')
    {
      if (!isset($_POST[$name]) || $_POST[$name] == "") {
        $_POST[$name] = ($init === null) ? '' : $init;
      }
      Form::SmallAmountRow($label, $name, $_POST[$name], null, "%", User::percent_dec(), 0, $inputparams);
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $init
     * @param string $inputparams
     */
    public static function  percentCells($label, $name, $init = null, $inputparams = '')
    {
      if (!isset($_POST[$name]) || $_POST[$name] == "") {
        $_POST[$name] = ($init === null) ? '' : $init;
      }
      Form::amountCellsSmall($label, $name, null, null, "%", User::percent_dec(), $inputparams);
    }
    /**
     * @param        $label
     * @param        $name
     * @param        $size
     * @param null   $max
     * @param null   $init
     * @param null   $params
     * @param null   $post_label
     * @param null   $dec
     * @param null   $id
     * @param string $inputparams
     * @param bool   $negatives
     */
    public static function amountCellsEx($label, $name, $size, $max = null, $init = null, $params = null, $post_label = null, $dec = null, $id = null, $inputparams = '', $negatives = false)
    {
      if (is_null($dec)) {
        $dec = User::price_dec();
      }
      if (!isset($_POST[$name]) || $_POST[$name] == "") {
        if ($init !== null) {
          $_POST[$name] = $init;
        } else {
          $_POST[$name] = 0;
        }
      }
      if ($label != null) {
        if ($params == null) {
          $params = " class='label'";
        }
        Cell::label($label, $params);
      }
      if (!isset($max)) {
        $max = $size;
      }
      if ($label != null) {
        echo "<td>";
      } else {
        echo "<td class='right'>";
      }
      echo "<input ";
      if ($id != null) {
        echo "id='$id'";
      }
      if ($name == 'freight') {
        echo "class='freight' ";
      } else {
        echo "class='amount' ";
      }
      if (!Input::post($name)) {
        $_POST[$name] = number_format(0, $dec);
      }
      echo "type='text' name='$name' size='$size' maxlength='$max' data-dec='$dec' value='" . $_POST[$name] . "' $inputparams>";
      if ($post_label) {
        echo "<span id='_{$name}_label'> $post_label</span>";
        Ajax::i()->addUpdate($name, '_' . $name . '_label', $post_label);
      }
      echo "</td>\n";
      Ajax::i()->addUpdate($name, $name, $_POST[$name]);
      Ajax::i()->addAssign($name, $name, 'data-dec', $dec);
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $init
     * @param null   $params
     * @param null   $post_label
     * @param null   $dec
     * @param null   $id
     * @param string $inputparams
     */
    public static function  amountCells($label, $name, $init = null, $params = null, $post_label = null, $dec = null, $id = null, $inputparams = '')
    {
      Form::amountCellsEx($label, $name, 10, 15, $init, $params, $post_label, $dec, $id, $inputparams);
    }
    /**
     *   JAM  Allow entered unit prices to be fractional
     *
     * @param      $label
     * @param      $name
     * @param null $init
     * @param null $params
     * @param null $post_label
     * @param null $dec
     */
    public static function unitAmountCells($label, $name, $init = null, $params = null, $post_label = null, $dec = null)
    {
      if (!isset($dec)) {
        $dec = User::price_dec() + 2;
      }
      Form::amountCellsEx($label, $name, 10, 15, $init, $params, $post_label, $dec + 2);
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $init
     * @param null   $params
     * @param null   $post_label
     * @param null   $dec
     * @param string $inputparams
     */
    public static function  AmountRow($label, $name, $init = null, $params = null, $post_label = null, $dec = null, $inputparams = '')
    {
      echo "<tr>";
      Form::amountCells($label, $name, $init, $params, $post_label, $dec, $inputparams);
      echo "</tr>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $init
     * @param null   $params
     * @param null   $post_label
     * @param null   $dec
     * @param int    $leftfill
     * @param string $inputparams
     */
    public static function  SmallAmountRow($label, $name, $init = null, $params = null, $post_label = null, $dec = null, $leftfill = 0, $inputparams = '')
    {
      echo "<tr>";
      Form::amountCellsSmall($label, $name, $init, $params, $post_label, $dec, $inputparams);
      if ($leftfill != 0) {
        echo "<td colspan=$leftfill></td>";
      }
      echo "</tr>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $init
     * @param null $params
     * @param null $post_label
     * @param null $dec
     */
    public static function  qtyCells($label, $name, $init = null, $params = null, $post_label = null, $dec = null)
    {
      if (!isset($dec)) {
        $dec = User::qty_dec();
      }
      Form::amountCellsEx($label, $name, 6, 15, $init, $params, $post_label, $dec, null, null, true);
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $init
     * @param null $params
     * @param null $post_label
     * @param null $dec
     */
    public static function qtyRow($label, $name, $init = null, $params = null, $post_label = null, $dec = null)
    {
      if (!isset($dec)) {
        $dec = User::qty_dec();
      }
      echo "<tr>";
      Form::amountCells($label, $name, $init, $params, $post_label, $dec);
      echo "</tr>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $init
     * @param null $params
     * @param null $post_label
     * @param null $dec
     */
    public static function qtyRowSmall($label, $name, $init = null, $params = null, $post_label = null, $dec = null)
    {
      if (!isset($dec)) {
        $dec = User::qty_dec();
      }
      echo "<tr>";
      Form::amountCellsSmall($label, $name, $init, $params, $post_label, $dec, null, true);
      echo "</tr>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $init
     * @param null   $params
     * @param null   $post_label
     * @param null   $dec
     * @param string $inputparams
     * @param bool   $negatives
     */
    public static function  amountCellsSmall($label, $name, $init = null, $params = null, $post_label = null, $dec = null, $inputparams = '', $negatives = false)
    {
      Form::amountCellsEx($label, $name, 4, 12, $init, $params, $post_label, $dec, null, $inputparams, $negatives);
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $init
     * @param null $params
     * @param null $post_label
     * @param null $dec
     */
    public static function  qtyCellsSmall($label, $name, $init = null, $params = null, $post_label = null, $dec = null)
    {
      if (!isset($dec)) {
        $dec = User::qty_dec();
      }
      Form::amountCellsEx($label, $name, 7, 12, $init, $params, $post_label, $dec, null, null, true);
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
    public static function  textareaCells($label, $name, $value, $cols, $rows, $title = null, $params = "")
    {
      if ($label != null) {
        echo "<td $params>$label</td>\n";
      }
      if ($value === null) {
        $value = (!isset($_POST[$name]) ? "" : $_POST[$name]);
      }
      echo "<td><textarea id='$name' name='$name' cols='" . ($cols + 2) . "' rows='$rows'" . ($title ? " title='$title'" :
        '') . ">$value</textarea></td>\n";
      Ajax::i()->addUpdate($name, $name, $value);
    }
    /**
     * @param        $label
     * @param        $name
     * @param        $value
     * @param        $cols
     * @param        $rows
     * @param null   $title
     * @param string $params
     * @param string $labelparams
     */
    public static function  textareaRow($label, $name, $value, $cols, $rows, $title = null, $params = "", $labelparams = "")
    {
      echo "<tr><td class='label' $labelparams><label for='$name'>$label</label></td>";
      Form::textareaCells(null, $name, $value, $cols, $rows, $title, $params);
      echo "</tr>\n";
    }
    /**
     *   When show_inactive page option is set
     *   displays value of inactive field as checkbox cell.
     *   Also updates database record after status change.
     *
     * @param $id
     * @param $value
     * @param $table
     * @param $key
     */
    public static function  inactiveControlCell($id, $value, $table, $key)
    {
      $name  = "Inactive" . $id;
      $value = $value ? 1 : 0;
      if (Form::hasPost('show_inactive')) {
        if (isset($_POST['LInact'][$id]) && (Form::getPost('_Inactive' . $id . '_update') || Form::getPost('Update')) && (Form::hasPost('Inactive' . $id) != $value)
        ) {
          DB::update_record_status($id, !$value, $table, $key);
        }
        echo '<td class="center">' . Form::checkbox(null, $name, $value, true, '', "class='center'") . Form::hidden("LInact[$id]", $value, false) . '</td>';
      }
    }
    /**
     *   Displays controls for optional display of inactive records
     *
     * @param $th
     */
    public static function  inactiveControlRow($th)
    {
      echo  "<tr><td colspan=" . (count($th)) . ">" . "<div style='float:left;'>" . Form::checkbox(null, 'show_inactive', null, true) . _("Show also Inactive") . "</div><div style='float:right;'>" . Form::submit('Update', _('Update'), false, '', null) . "</div></td></tr>";
    }
    /**
     *   Inserts additional column header when display of inactive records is on.
     *
     * @param $th
     */
    public static function inactiveControlCol(&$th)
    {
      if (Form::hasPost('show_inactive')) {
        Arr::insert($th, count($th) - 2, _("Inactive"));
      }
      if (Form::getPost('_show_inactive_update')) {
        Ajax::i()->activate('_page_body');
      }
    }
    /**
     * @param        $name
     * @param null   $selected_id
     * @param string $name_yes
     * @param string $name_no
     * @param bool   $submit_on_change
     *
     * @return string
     */
    public static function  yesnoList($name, $selected_id = null, $name_yes = "", $name_no = "", $submit_on_change = false)
    {
      $items      = array();
      $items['0'] = strlen($name_no) ? $name_no : _("No");
      $items['1'] = strlen($name_yes) ? $name_yes : _("Yes");
      return Form::arraySelect($name, $selected_id, $items, array(
                                                                 'select_submit' => $submit_on_change, 'async' => false
                                                            )); // FIX?
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $selected_id
     * @param string $name_yes
     * @param string $name_no
     * @param bool   $submit_on_change
     */
    public static function yesnoListCells($label, $name, $selected_id = null, $name_yes = "", $name_no = "", $submit_on_change = false)
    {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo  Form::yesnoList($name, $selected_id, $name_yes, $name_no, $submit_on_change);
      echo "</td>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $selected_id
     * @param string $name_yes
     * @param string $name_no
     * @param bool   $submit_on_change
     */
    public static function  yesnoListRow($label, $name, $selected_id = null, $name_yes = "", $name_no = "", $submit_on_change = false)
    {
      echo "<tr><td class='label'>$label</td>";
      Form::yesnoListCells(null, $name, $selected_id, $name_yes, $name_no, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @param $label
     * @param $name
     */
    public static function  recordStatusListRow($label, $name)
    {
      Form::yesnoListRow($label, $name, null, _('Inactive'), _('Active'));
    }
    /**
     * @param      $name
     * @param      $selected
     * @param      $from
     * @param      $to
     * @param bool $no_option
     *
     * @return string
     */
    public static function numberList($name, $selected, $from, $to, $no_option = false)
    {
      $items = array();
      for ($i = $from; $i <= $to; $i++) {
        $items[$i] = "$i";
      }
      return Form::arraySelect($name, $selected, $items, array(
                                                              'spec_option' => $no_option, 'spec_id' => ALL_NUMERIC
                                                         ));
    }
    /**
     * @param      $label
     * @param      $name
     * @param      $selected
     * @param      $from
     * @param      $to
     * @param bool $no_option
     */
    public static function numberListCells($label, $name, $selected, $from, $to, $no_option = false)
    {
      if ($label != null) {
        Cell::label($label);
      }
      echo "<td>\n";
      echo  Form::numberList($name, $selected, $from, $to, $no_option);
      echo "</td>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param      $selected
     * @param      $from
     * @param      $to
     * @param bool $no_option
     */
    public static function numberListRow($label, $name, $selected, $from, $to, $no_option = false)
    {
      echo "<tr><td class='label'>$label</td>";
      Form::numberListCells(null, $name, $selected, $from, $to, $no_option);
      echo "</tr>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $value
     */
    public static function dateFormatsListRow($label, $name, $value = null)
    {
      echo "<tr><td class='label'>$label</td>\n<td>";
      echo Form::arraySelect($name, $value, Config::get('date.formats'));
      echo "</td></tr>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $value
     */
    public static function dateSepsListRow($label, $name, $value = null)
    {
      echo "<tr><td class='label'>$label</td>\n<td>";
      echo Form::arraySelect($name, $value, Config::get('date.separators'));
      echo "</td></tr>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $value
     */
    public static function thoSepsListRow($label, $name, $value = null)
    {
      echo "<tr><td class='label'>$label</td>\n<td>";
      echo Form::arraySelect($name, $value, Config::get('separators_thousands'));
      echo "</td></tr>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $value
     */
    public static function decSepsListRow($label, $name, $value = null)
    {
      echo "<tr><td class='label'>$label</td>\n<td>";
      echo Form::arraySelect($name, $value, Config::get('separators_decimal'));
      echo "</td></tr>\n";
    }
    /**
     * @param $row
     *
     * @return string
     */
    public static function dateFormat($row)
    {
      return Dates::sql2date($row['reconciled']);
    }
    /**
     * @param $row
     *
     * @return string
     */
    public static function addCurrFormat($row)
    {
      static $company_currency;
      if ($company_currency == null) {
        $company_currency = Bank_Currency::for_company();
      }
      return $row[1] . ($row[2] == $company_currency ? '' : ("&nbsp;-&nbsp;" . $row[2]));
    }
    /**
     * @param $row
     *
     * @return string
     */
    public static function stockItemsFormat($row)
    {
      return (User::show_codes() ? ($row[0] . "&nbsp;-&nbsp;") : "") . $row[1];
    }
    /**
     * @param $row
     *
     * @return string
     */
    public static function templateItemsFormat($row)
    {
      return ($row[0] . "&nbsp;- &nbsp;" . _("Amount") . "&nbsp;" . $row[1]);
    }
    /**
     * @param $row
     *
     * @return string
     */
    public static function fiscalYearFormat($row)
    {
      return Dates::sql2date($row[1]) . "&nbsp;-&nbsp;" . Dates::sql2date($row[2]) . "&nbsp;&nbsp;" . ($row[3] ? _('Closed') :
        _('Active')) . "</option>\n";
    }
    /**
     * @param $row
     *
     * @return string
     */
    public static function accountFormat($row)
    {
      return $row[0] . "&nbsp;&nbsp;&nbsp;&nbsp;" . $row[1];
    }
    /**
     * Prep Value
     * Prepares the value for display in the form
     *
     * @param   string
     *
     * @return string
     */
    public static function prep_value($value)
    {
      $value = htmlspecialchars($value);
      $value = str_replace(array("'", '"'), array("&#39;", "&quot;"), $value);
      return $value;
    }
  }
