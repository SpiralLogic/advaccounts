<?php
  /**
   * PHP version 5.4
   *
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
  function start_form($multi = FALSE, $action = "", $name = "") {
    if ($name != "") {
      $name = "name='$name' id='$name'";
    }
    if ($action == "") {
      $action = $_SERVER['DOCUMENT_URI'];
    }
    if ($multi) {
      echo "<form enctype='multipart/form-data' method='post' action='$action' $name>\n";
    }
    else {
      echo "<form method='post' action='$action' $name>\n";
    }
  }

  /**
   * @param int $breaks
   */
  function end_form($breaks = 0) {
    if ($breaks) {
      Display::br($breaks);
    }
    echo "<input type=\"hidden\" name=\"_focus\" value=\"" . get_post('_focus') . "\">\n";
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
  function find_submit($prefix, $numeric = TRUE) {
    foreach ($_POST as $postkey => $postval) {
      if (strpos($postkey, $prefix) === 0) {
        $id = substr($postkey, strlen($prefix));
        return $numeric ? (int) $id : $id;
      }
    }
    return $numeric ? -1 : NULL;
  }

  /**
   * @param        $name
   * @param string $dflt
   *
   * @return string|int
   */
  function get_post($name, $dflt = '') {
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
  function list_updated($name) {
    return isset($_POST['_' . $name . '_update']) || isset($_POST['_' . $name . '_button']);
  }

  /**
   * @param      $name
   * @param null $value
   * @param bool $echo
   *
   * @return string
   */
  function hidden($name, $value = NULL, $echo = TRUE) {
    if ($value === NULL) {
      $value = get_post($name);
    }
    $ret = "<input type=\"hidden\" id=\"$name\" name=\"$name\" value=\"$value\">";
    Ajax::i()->addUpdate($name, $name, $value);
    if ($echo) {
      echo $ret . "\n";
    }
    else {
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
  function select_box($name, $selected_id = NULL, $sql, $valfield, $namefield, $options = NULL) {
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
  function array_selector($name, $selected_id, $items, $options = NULL) {
    $opts = array( // default options
      'spec_option' => FALSE, // option text or false
      'spec_id' => 0, // option id
      'select_submit' => FALSE, //submit on select: true/false
      'async' => TRUE, // select update via ajax (true) vs _page_body reload
      'default' => '', // default value when $_POST is not set
      'multi' => FALSE, // multiple select
      // search box parameters
      'height' => FALSE, // number of lines in select box
      'sel_hint' => NULL, 'disabled' => FALSE
    );
    // ------ merge options with defaults ----------
    if ($options != NULL) {
      $opts = array_merge($opts, $options);
    }
    $select_submit = $opts['select_submit'];
    $spec_id = $opts['spec_id'];
    $spec_option = $opts['spec_option'];
    $disabled = $opts['disabled'] ? "disabled" : '';
    $multi = $opts['multi'];
    if ($selected_id == NULL) {
      $selected_id = get_post($name, $opts['default']);
    }
    if (!is_array($selected_id)) {
      $selected_id = array($selected_id);
    } // code is generalized for multiple selection support
    if (isset($_POST['_' . $name . '_update'])) {
      if (!$opts['async']) {
        Ajax::i()->activate('_page_body');
      }
      else {
        Ajax::i()->activate($name);
      }
    }
    // ------ make selector ----------
    $selector = $first_opt = '';
    $first_id = FALSE;
    $found = FALSE;
    //if($name=='SelectStockFromList') Event::error($sql);
    foreach ($items as $value => $descr) {
      $sel = '';
      if (in_array((string) $value, $selected_id)) {
        $sel = 'selected';
        $found = $value;
      }
      if ($first_id === FALSE) {
        $first_id = $value;
        $first_opt = $descr;
      }
      $selector .= "<option $sel value='$value'>$descr</option>\n";
    }
    // Prepend special option.
    if ($spec_option !== FALSE) { // if special option used - add it
      $first_id = $spec_id;
      $first_opt = $spec_option;
      $sel = $found === FALSE ? 'selected' : '';
      $selector = "<option $sel value='$spec_id'>$spec_option</option>\n" . $selector;
    }
    if ($found === FALSE) {
      $selected_id = array($first_id);
    }
    $_POST[$name] = $multi ? $selected_id : $selected_id[0];
    $selector = "<select " . ($multi ? "multiple" : '') . ($opts['height'] !== FALSE ? ' size="' . $opts['height'] . '"' :
      '') . "$disabled id='$name' name='$name" . ($multi ?
      '[]' : '') . "' class='combo' title='" . $opts['sel_hint'] . "'>" . $selector . "</select>\n";
    Ajax::i()->addUpdate($name, "_{$name}_sel", $selector);
    $selector = "<span id='_{$name}_sel'>" . $selector . "</span>\n";
    if ($select_submit != FALSE) { // if submit on change is used - add select button
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
  function submit($name, $value, $echo = TRUE, $title = FALSE, $atype = FALSE, $icon = FALSE) {
    $aspect = '';
    if ($atype === NULL) {
      $aspect = User::fallback() ? " data-aspect='fallback'" : " style='display:none;'";
    }
    elseif (!is_bool($atype)) { // necessary: switch uses '=='
      $aspect = " data-aspect='$atype' ";
      $types = explode(' ', $atype);
      foreach ($types as $type) {
        switch ($type) {
          case 'selector':
            $aspect = " data-aspect='selector' rel='$value'";
            $value = _("Select");
            if ($icon === FALSE) {
              $icon = ICON_SUBMIT;
            }
            break;
          case 'default':
            if ($icon === FALSE) {
              $icon = ICON_SUBMIT;
            }
            break;
          case 'cancel':
            if ($icon === FALSE) {
              $icon = ICON_ESCAPE;
            }
            break;
        }
      }
    }
    $submit_str = "<button class=\"" . (($atype === TRUE || $atype === FALSE) ? (($atype) ? 'ajaxsubmit' : 'inputsubmit') :
      $atype) . "\" type=\"submit\" " . $aspect . " name=\"$name\" id=\"$name\" value=\"$value\"" . ($title ? " title='$title'"
      : '') . ">" . ($icon ?
      "<img alt='$value' src='/themes/" . User::theme() . "/images/$icon' height='12'>" : '') . "<span>$value</span>" .
      "</button>\n";
    if ($echo) {
      echo $submit_str;
    }
    else {
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
  function submit_center($name, $value, $echo = TRUE, $title = FALSE, $async = FALSE, $icon = FALSE) {
    if ($echo) {
      echo "<div class='center'>";
    }
    submit($name, $value, $echo, $title, $async, $icon);
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
  function submit_center_first($name, $value, $title = FALSE, $async = FALSE, $icon = FALSE) {
    echo "<div class='center'>";
    submit($name, $value, TRUE, $title, $async, $icon);
    echo "&nbsp;";
  }

  /**
   * @param      $name
   * @param      $value
   * @param bool $title
   * @param bool $async
   * @param bool $icon
   */
  function submit_center_middle($name, $value, $title = FALSE, $async = FALSE, $icon = FALSE) {
    submit($name, $value, TRUE, $title, $async, $icon);
    echo "&nbsp;";
  }

  /**
   * @param      $name
   * @param      $value
   * @param bool $title
   * @param bool $async
   * @param bool $icon
   */
  function submit_center_last($name, $value, $title = FALSE, $async = FALSE, $icon = FALSE) {
    echo "&nbsp;";
    submit($name, $value, TRUE, $title, $async, $icon);
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
  function submit_add_or_update($add = TRUE, $title = FALSE, $async = FALSE, $clone = FALSE) {
    $cancel = $async;
    if ($async === 'both') {
      $async = 'default';
      $cancel = 'cancel';
    }
    else {
      if ($async === 'default') {
        $cancel = TRUE;
      }
      else {
        if ($async === 'cancel') {
          $async = TRUE;
        }
      }
    }
    if ($add) {
      submit(ADD_ITEM, _("Add new"), TRUE, $title, $async);
    }
    else {
      submit(UPDATE_ITEM, _("Update"), TRUE, _('Submit changes'), $async);
      if ($clone) {
        submit(MODE_CLONE, _("Clone"), TRUE, _('Edit new record with current data'), $async);
      }
      submit(MODE_RESET, _("Cancel"), TRUE, _('Cancel edition'), $cancel);
    }
  }

  /**
   * @param bool $add
   * @param bool $title
   * @param bool $async
   * @param bool $clone
   */
  function submit_add_or_update_center($add = TRUE, $title = FALSE, $async = FALSE, $clone = FALSE) {
    echo "<div class='center'>";
    submit_add_or_update($add, $title, $async, $clone);
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
  function submit_add_or_update_row($add = TRUE, $right = TRUE, $extra = "", $title = FALSE, $async = FALSE, $clone = FALSE) {
    echo "<tr>";
    if ($right) {
      echo "<td>&nbsp;</td>\n";
    }
    echo "<td $extra>";
    submit_add_or_update($add, $title, $async, $clone);
    echo "</td></tr>\n";
  }

  /**
   * @param        $name
   * @param        $value
   * @param string $extra
   * @param bool   $title
   * @param bool   $async
   */
  function submit_cells($name, $value, $extra = "", $title = FALSE, $async = FALSE) {
    echo "<td $extra>";
    submit($name, $value, TRUE, $title, $async);
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
  function submit_row($name, $value, $right = TRUE, $extra = "", $title = FALSE, $async = FALSE) {
    echo "<tr>";
    if ($right) {
      echo "<td>&nbsp;</td>\n";
    }
    submit_cells($name, $value, $extra, $title, $async);
    echo "</tr>\n";
  }

  /**
   * @param      $name
   * @param      $value
   * @param bool $title
   */
  function submit_return($name, $value, $title = FALSE) {
    if (Input::request('frame')) {
      submit($name, $value, TRUE, $title, 'selector');
    }
  }

  /**
   * @param $name
   * @param $msg
   */
  function submit_js_confirm($name, $msg) {
    JS::beforeload("_validate.$name=function(){ return confirm('" . strtr($msg, array("\n" => '\\n')) . "');};");
  }

  /**
   * @param      $icon
   * @param bool $title
   *
   * @return string
   */
  function set_icon($icon, $title = FALSE) {
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
  function button($name, $value, $title = FALSE, $icon = FALSE, $aspect = '') {
    // php silently changes dots,spaces,'[' and characters 128-159
    // to underscore in POST names, to maintain compatibility with register_globals
    $rel = '';
    if ($aspect == 'selector') {
      $rel = " rel='$value'";
      $value = _("Select");
    }
    $name = htmlentities(strtr($name, array('.' => '=2E', ' ' => '=20', '=' => '=3D', '[' => '=5B')));
    if (User::graphic_links() && $icon) {
      if ($value == _("Delete")) // Helper during implementation
      {
        $icon = ICON_DELETE;
      }
      return "<button type='submit' class='editbutton' id='" . $name . "' name='" . $name . "' value='1'" . ($title ?
        " title='$title'" :
        " title='$value'") . ($aspect ? " data-aspect='$aspect'" : '') . $rel . " />" . set_icon($icon) . "</button>\n";
    }
    else {
      return "<input type='submit' class='editbutton' id='" . $name . "' name='" . $name . "' value='$value'" . ($title ?
        " title='$title'" : '') . ($aspect ?
        " data-aspect='$aspect'" : '') . $rel . " />\n";
    }
  }

  /**
   * @param        $name
   * @param        $value
   * @param bool   $title
   * @param bool   $icon
   * @param string $aspect
   */
  function button_cell($name, $value, $title = FALSE, $icon = FALSE, $aspect = '') {
    echo "<td class='center'>";
    echo button($name, $value, $title, $icon, $aspect);
    echo "</td>";
  }

  /**
   * @param      $name
   * @param      $value
   * @param bool $title
   */
  function delete_button_cell($name, $value, $title = FALSE) {
    button_cell($name, $value, $title, ICON_DELETE);
  }

  /**
   * @param      $name
   * @param      $value
   * @param bool $title
   */
  function edit_button_cell($name, $value, $title = FALSE) {
    button_cell($name, $value, $title, ICON_EDIT);
  }

  /**
   * @param      $name
   * @param      $value
   * @param bool $title
   */
  function select_button_cell($name, $value, $title = FALSE) {
    button_cell($name, $value, $title, ICON_ADD, 'selector');
  }

  /**
   * @param $name
   *
   * @return int
   */
  function check_value($name) {
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
  function checkbox($label, $name, $value = NULL, $submit_on_change = FALSE, $title = FALSE) {
    $str = '';
    if ($label) {
      $str .= $label . " ";
    }
    if ($submit_on_change !== FALSE) {
      if ($submit_on_change === TRUE) {
        $submit_on_change = "JsHttpRequest.request(\"_{$name}_update\", this.form);";
      }
    }
    if ($value === NULL) {
      $value = get_post($name, 0);
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
  function check($label, $name, $value = NULL, $submit_on_change = FALSE, $title = FALSE) {
    echo checkbox($label, $name, $value, $submit_on_change, $title);
  }

  /**
   * @param        $label
   * @param        $name
   * @param null   $value
   * @param bool   $submit_on_change
   * @param bool   $title
   * @param string $params
   */
  function check_cells($label, $name, $value = NULL, $submit_on_change = FALSE, $title = FALSE, $params = '') {
    echo "<td $params>";
    if ($label != NULL) {
      echo "<label for=\"$name\"> $label</label>";
    }
    echo check(NULL, $name, $value, $submit_on_change, $title);
    echo "</td>";
  }

  /**
   * @param      $label
   * @param      $name
   * @param null $value
   * @param bool $submit_on_change
   * @param bool $title
   */
  function check_row($label, $name, $value = NULL, $submit_on_change = FALSE, $title = FALSE) {
    echo "<tr><td class='label'>$label</td>";
    echo check_cells(NULL, $name, $value, $submit_on_change, $title);
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
  function text_cells($label, $name, $value = NULL, $size = "", $max = "", $title = FALSE, $labparams = "", $post_label = "", $inparams = "") {
    if ($label != NULL) {
      Cell::label($label, $labparams);
    }
    echo "<td>";
    if ($value === NULL) {
      $value = get_post($name);
    }
    echo "<input $inparams type=\"text\" name=\"$name\" id=\"$name\" size=\"$size\" maxlength=\"$max\" value=\"$value\"" .
      ($title ? " title='$title'" : '') . ">";
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
  function text_cells_ex($label, $name, $size, $max = NULL, $init = NULL, $title = NULL, $labparams = NULL, $post_label = NULL, $submit_on_change = FALSE) {
    JS::default_focus($name);
    if (!isset($_POST[$name]) || $_POST[$name] == "") {
      if ($init !== NULL) {
        $_POST[$name] = $init;
      }
      else {
        $_POST[$name] = "";
      }
    }
    if ($label != NULL) {
      echo "<td class='label'><label for=\"$name\"> $label</label>";
    }
    else {
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
  function text_row($label, $name, $value, $size = FALSE, $max, $title = NULL, $params = "", $post_label = "") {
    echo "<tr><td class='label'><label for='$name'>$label</label></td>";
    text_cells(NULL, $name, $value, $size, $max, $title, $params, $post_label);
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
  function text_row_ex($label, $name, $size, $max = NULL, $title = NULL, $value = NULL, $params = NULL, $post_label = NULL, $params2 = '', $submit_on_change = FALSE) {
    echo "<tr {$params}><td class='label' {$params2}>$label</td>";
    text_cells_ex(NULL, $name, $size, $max, $value, $title, $params, $post_label, $submit_on_change);
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
  function email_row($label, $name, $value, $size, $max, $title = NULL, $params = "", $post_label = "") {
    if (get_post($name)) {
      $label = "<a href='Mailto:" . $_POST[$name] . "'>$label</a>";
    }
    text_row($label, $name, $value, $size, $max, $title, $params, $post_label);
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
  function email_row_ex($label, $name, $size, $max = NULL, $title = NULL, $value = NULL, $params = NULL, $post_label = NULL) {
    if (get_post($name)) {
      $label = "<a href='Mailto:" . $_POST[$name] . "'>$label</a>";
    }
    text_row_ex($label, $name, $size, $max, $title, $value, $params, $post_label);
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
  function link_row($label, $name, $value, $size, $max, $title = NULL, $params = "", $post_label = "") {
    $val = get_post($name);
    if ($val) {
      if (strpos($val, 'http://') === FALSE) {
        $val = 'http://' . $val;
      }
      $label = "<a href='$val' target='_blank'>$label</a>";
    }
    text_row($label, $name, $value, $size, $max, $title, $params, $post_label);
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
  function link_row_ex($label, $name, $size, $max = NULL, $title = NULL, $value = NULL, $params = NULL, $post_label = NULL) {
    $val = get_post($name);
    if ($val) {
      if (strpos($val, 'http://') === FALSE) {
        $val = 'http://' . $val;
      }
      $label = "<a href='$val' target='_blank'>$label</a>";
    }
    text_row_ex($label, $name, $size, $max, $title, $value, $params, $post_label);
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
  function date_cells($label, $name, $title = NULL, $check = NULL, $inc_days = 0, $inc_months = 0, $inc_years = 0, $params = NULL, $submit_on_change = FALSE, $options = array()) {
    if (!isset($_POST[$name]) || $_POST[$name] == "") {
      if ($inc_years == 1001) {
        $_POST[$name] = NULL;
      }
      else {
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
    if ($label != NULL) {
      echo "<td class='label'><label for=\"$name\"> $label</label>";
    }
    else {
      echo "<td >";
    }
    $class = $submit_on_change ? 'searchbox datepicker' : 'datepicker';
    $aspect = $check ? ' data-aspect="cdate"' : '';
    if ($check && (get_post($name) != Dates::today())) {
      $aspect .= ' style="color:#FF0000"';
    }
    echo "<input id='$name' type='text' name='$name' class='$class' $aspect size=\"10\" maxlength='10' value=\"" .
      $_POST[$name] . "\"" . ($title ?
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
  function date_row($label, $name, $title = NULL, $check = NULL, $inc_days = 0, $inc_months = 0, $inc_years = 0, $params = NULL, $submit_on_change = FALSE) {
    echo "<tr><td class='label'><label for='$name'> $label</label></td>";
    date_cells(NULL, $name, $title, $check, $inc_days, $inc_months, $inc_years, $params, $submit_on_change);
    echo "</tr>\n";
  }

  /**
   * @param $label
   * @param $name
   * @param $value
   */
  function password_row($label, $name, $value) {
    echo "<tr><td class='label'><label for='$name'>$label</label></td>";
    Cell::label("<input type='password' name='$name' id='$name' value='$value' />");
    echo "</tr>\n";
  }

  /**
   * @param        $label
   * @param        $name
   * @param string $id
   */
  function file_cells($label, $name, $id = "") {
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
  function file_row($label, $name, $id = "") {
    echo "<tr><td class='label'>$label</td>";
    file_cells(NULL, $name, $id);
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
  function ref_cells($label, $name, $title = NULL, $init = NULL, $params = NULL, $submit_on_change = FALSE) {
    text_cells_ex($label, $name, 9, 18, $init, $title, $params, NULL, $submit_on_change);
  }

  /**
   * @param      $label
   * @param      $name
   * @param null $title
   * @param null $init
   * @param bool $submit_on_change
   */
  function ref_row($label, $name, $title = NULL, $init = NULL, $submit_on_change = FALSE) {
    echo "<tr><td class='label'><label for='$name'> $label</label></td>";
    ref_cells(NULL, $name, $title, $init, NULL, $submit_on_change);
    echo "</tr>\n";
  }

  /**
   * @param        $label
   * @param        $name
   * @param null   $init
   * @param string $cellparams
   * @param string $inputparams
   */
  function percent_row($label, $name, $init = NULL, $cellparams = '', $inputparams = '') {
    if (!isset($_POST[$name]) || $_POST[$name] == "") {
      $_POST[$name] = ($init === NULL) ? '' : $init;
    }
    small_amount_row($label, $name, $_POST[$name], NULL, "%", User::percent_dec(), 0, $inputparams);
  }

  /**
   * @param        $label
   * @param        $name
   * @param null   $init
   * @param string $inputparams
   */
  function percent_cells($label, $name, $init = NULL, $inputparams = '') {
    if (!isset($_POST[$name]) || $_POST[$name] == "") {
      $_POST[$name] = ($init === NULL) ? '' : $init;
    }
    small_amount_cells($label, $name, NULL, NULL, "%", User::percent_dec(), $inputparams);
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
  function amount_cells_ex($label, $name, $size, $max = NULL, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL,
                           $id = NULL, $inputparams = '', $negatives = FALSE) {
    if (is_null($dec)) {
      $dec = User::price_dec();
    }
    if (!isset($_POST[$name]) || $_POST[$name] == "") {
      if ($init !== NULL) {
        $_POST[$name] = $init;
      }
      else {
        $_POST[$name] = 0;
      }
    }
    if ($label != NULL) {
      if ($params == NULL) {
        $params = " class='label'";
      }
      Cell::label($label, $params);
    }
    if (!isset($max)) {
      $max = $size;
    }
    if ($label != NULL) {
      echo "<td>";
    }
    else {
      echo "<td class='right'>";
    }
    echo "<input ";
    if ($id != NULL) {
      echo "id='$id'";
    }
    if ($name == 'freight') {
      echo "class='freight' ";
    }
    else {
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
  function amount_cells($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL, $id = NULL, $inputparams = '') {
    amount_cells_ex($label, $name, 10, 15, $init, $params, $post_label, $dec, $id, $inputparams);
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
  function unit_amount_cells($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL) {
    if (!isset($dec)) {
      $dec = User::price_dec() + 2;
    }
    amount_cells_ex($label, $name, 10, 15, $init, $params, $post_label, $dec + 2);
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
  function amount_row($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL, $inputparams = '') {
    echo "<tr>";
    amount_cells($label, $name, $init, $params, $post_label, $dec, $inputparams);
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
  function small_amount_row($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL, $leftfill = 0, $inputparams = '') {
    echo "<tr>";
    small_amount_cells($label, $name, $init, $params, $post_label, $dec, $inputparams);
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
  function qty_cells($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL) {
    if (!isset($dec)) {
      $dec = User::qty_dec();
    }
    amount_cells_ex($label, $name, 6, 15, $init, $params, $post_label, $dec, NULL, NULL, TRUE);
  }

  /**
   * @param      $label
   * @param      $name
   * @param null $init
   * @param null $params
   * @param null $post_label
   * @param null $dec
   */
  function qty_row($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL) {
    if (!isset($dec)) {
      $dec = User::qty_dec();
    }
    echo "<tr>";
    amount_cells($label, $name, $init, $params, $post_label, $dec);
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
  function small_qty_row($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL) {
    if (!isset($dec)) {
      $dec = User::qty_dec();
    }
    echo "<tr>";
    small_amount_cells($label, $name, $init, $params, $post_label, $dec, NULL, TRUE);
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
  function small_amount_cells($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL,
                              $inputparams = '', $negatives = FALSE) {
    amount_cells_ex($label, $name, 4, 12, $init, $params, $post_label, $dec, NULL, $inputparams, $negatives);
  }

  /**
   * @param      $label
   * @param      $name
   * @param null $init
   * @param null $params
   * @param null $post_label
   * @param null $dec
   */
  function small_qty_cells($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL) {
    if (!isset($dec)) {
      $dec = User::qty_dec();
    }
    amount_cells_ex($label, $name, 7, 12, $init, $params, $post_label, $dec, NULL, NULL, TRUE);
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
  function textarea_cells($label, $name, $value, $cols, $rows, $title = NULL, $params = "") {
    if ($label != NULL) {
      echo "<td $params>$label</td>\n";
    }
    if ($value === NULL) {
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
  function textarea_row($label, $name, $value, $cols, $rows, $title = NULL, $params = "", $labelparams = "") {
    echo "<tr><td class='label' $labelparams><label for='$name'>$label</label></td>";
    textarea_cells(NULL, $name, $value, $cols, $rows, $title, $params);
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
  function inactive_control_cell($id, $value, $table, $key) {
    $name = "Inactive" . $id;
    $value = $value ? 1 : 0;
    if (check_value('show_inactive')) {
      if (isset($_POST['LInact'][$id]) && (get_post('_Inactive' . $id . '_update') || get_post('Update')) && (check_value('Inactive' . $id) != $value)
      ) {
        DB::update_record_status($id, !$value, $table, $key);
      }
      echo '<td class="center">' . checkbox(NULL, $name, $value, TRUE, '', "class='center'") . hidden("LInact[$id]", $value, FALSE) . '</td>';
    }
  }

  /**
   *   Displays controls for optional display of inactive records
   *
   * @param $th
   */
  function inactive_control_row($th) {
    echo  "<tr><td colspan=" . (count($th)) . ">" . "<div style='float:left;'>" . checkbox(NULL, 'show_inactive', NULL, TRUE) . _("Show also Inactive") . "</div><div style='float:right;'>" . submit('Update', _('Update'), FALSE, '', NULL) . "</div></td></tr>";
  }

  /**
   *   Inserts additional column header when display of inactive records is on.
   *
   * @param $th
   */
  function inactive_control_column(&$th) {
    if (check_value('show_inactive')) {
      Arr::insert($th, count($th) - 2, _("Inactive"));
    }
    if (get_post('_show_inactive_update')) {
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
  function yesno_list($name, $selected_id = NULL, $name_yes = "", $name_no = "", $submit_on_change = FALSE) {
    $items = array();
    $items['0'] = strlen($name_no) ? $name_no : _("No");
    $items['1'] = strlen($name_yes) ? $name_yes : _("Yes");
    return array_selector($name, $selected_id, $items, array(
                                                            'select_submit' => $submit_on_change, 'async' => FALSE
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
  function yesno_list_cells($label, $name, $selected_id = NULL, $name_yes = "", $name_no = "", $submit_on_change = FALSE) {
    if ($label != NULL) {
      echo "<td>$label</td>\n";
    }
    echo "<td>";
    echo yesno_list($name, $selected_id, $name_yes, $name_no, $submit_on_change);
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
  function yesno_list_row($label, $name, $selected_id = NULL, $name_yes = "", $name_no = "", $submit_on_change = FALSE) {
    echo "<tr><td class='label'>$label</td>";
    yesno_list_cells(NULL, $name, $selected_id, $name_yes, $name_no, $submit_on_change);
    echo "</tr>\n";
  }

  /**
   * @param $label
   * @param $name
   */
  function record_status_list_row($label, $name) {
    return yesno_list_row($label, $name, NULL, _('Inactive'), _('Active'));
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
  function number_list($name, $selected, $from, $to, $no_option = FALSE) {
    $items = array();
    for ($i = $from; $i <= $to; $i++) {
      $items[$i] = "$i";
    }
    return array_selector($name, $selected, $items, array(
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
  function number_list_cells($label, $name, $selected, $from, $to, $no_option = FALSE) {
    if ($label != NULL) {
      Cell::label($label);
    }
    echo "<td>\n";
    echo number_list($name, $selected, $from, $to, $no_option);
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
  function number_list_row($label, $name, $selected, $from, $to, $no_option = FALSE) {
    echo "<tr><td class='label'>$label</td>";
    echo number_list_cells(NULL, $name, $selected, $from, $to, $no_option);
    echo "</tr>\n";
  }

  /**
   * @param      $label
   * @param      $name
   * @param null $value
   */
  function dateformats_list_row($label, $name, $value = NULL) {
    echo "<tr><td class='label'>$label</td>\n<td>";
    echo array_selector($name, $value, Config::get('date.formats'));
    echo "</td></tr>\n";
  }

  /**
   * @param      $label
   * @param      $name
   * @param null $value
   */
  function dateseps_list_row($label, $name, $value = NULL) {
    echo "<tr><td class='label'>$label</td>\n<td>";
    echo array_selector($name, $value, Config::get('date.separators'));
    echo "</td></tr>\n";
  }

  /**
   * @param      $label
   * @param      $name
   * @param null $value
   */
  function thoseps_list_row($label, $name, $value = NULL) {
    echo "<tr><td class='label'>$label</td>\n<td>";
    echo array_selector($name, $value, Config::get('separators_thousands'));
    echo "</td></tr>\n";
  }

  /**
   * @param      $label
   * @param      $name
   * @param null $value
   */
  function decseps_list_row($label, $name, $value = NULL) {
    echo "<tr><td class='label'>$label</td>\n<td>";
    echo array_selector($name, $value, Config::get('separators_decimal'));
    echo "</td></tr>\n";
  }

  /**
   * @param $row
   *
   * @return string
   */
  function _format_date($row) {
    return Dates::sql2date($row['reconciled']);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function _format_add_curr($row) {
    static $company_currency;
    if ($company_currency == NULL) {
      $company_currency = Bank_Currency::for_company();
    }
    return $row[1] . ($row[2] == $company_currency ? '' : ("&nbsp;-&nbsp;" . $row[2]));
  }

  /**
   * @param $row
   *
   * @return string
   */
  function _format_stock_items($row) {
    return (User::show_codes() ? ($row[0] . "&nbsp;-&nbsp;") : "") . $row[1];
  }

  /**
   * @param $row
   *
   * @return string
   */
  function _format_template_items($row) {
    return ($row[0] . "&nbsp;- &nbsp;" . _("Amount") . "&nbsp;" . $row[1]);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function _format_fiscalyears($row) {
    return Dates::sql2date($row[1]) . "&nbsp;-&nbsp;" . Dates::sql2date($row[2]) . "&nbsp;&nbsp;" . ($row[3] ? _('Closed') :
      _('Active')) . "</option>\n";
  }

  /**
   * @param $row
   *
   * @return string
   */
  function _format_account($row) {
    return $row[0] . "&nbsp;&nbsp;&nbsp;&nbsp;" . $row[1];
  }
