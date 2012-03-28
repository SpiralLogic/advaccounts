<?php
  /**********************************************************************
  Copyright (C) Advanced Group PTY LTD
  Released under the terms of the GNU General Public License, GPL,
  as published by the Free Software Foundation, either version 3
  of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
   ***********************************************************************/
  function start_form($multi = FALSE, $action = "", $name = "") {
    if ($name != "") {
      $name = "name='$name' id='$name'";
    }
    if ($action == "") {
      $action = $_SERVER['PHP_SELF'];
    }
    if ($multi) {
      echo "<form enctype='multipart/form-data' method='post' action='$action' $name>\n";
    }
    else {
      echo "<form method='post' action='$action' $name>\n";
    }
  }

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
    $selector = "<select " . ($multi ? "multiple" : '') . ($opts['height'] !== FALSE ? ' size="' . $opts['height'] . '"' : '') . "$disabled name='$name" . ($multi ?
      '[]' : '') . "' class='combo' title='" . $opts['sel_hint'] . "'>" . $selector . "</select>\n";
    Ajax::i()->addUpdate($name, "_{$name}_sel", $selector);
    $selector = "<span id='_{$name}_sel'>" . $selector . "</span>\n";
    if ($select_submit != FALSE) { // if submit on change is used - add select button
      $_select_button = "<input %s type='submit' class='combo_select' style='border:0;background:url
			(/themes/%s/images/button_ok.png) no-repeat;%s' data-aspect='fallback' name='%s' value=' ' title='" . _("Select") . "'> ";
      $selector .= sprintf($_select_button, $disabled, User::theme(), (User::fallback() ? '' : 'display:none;'), '_' . $name . '_update') . "\n";
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

  function submit_center($name, $value, $echo = TRUE, $title = FALSE, $async = FALSE, $icon = FALSE) {
    if ($echo) {
      echo "<div class='center'>";
    }
    submit($name, $value, $echo, $title, $async, $icon);
    if ($echo) {
      echo "</div>";
    }
  }

  function submit_center_first($name, $value, $title = FALSE, $async = FALSE, $icon = FALSE) {
    echo "<div class='center'>";
    submit($name, $value, TRUE, $title, $async, $icon);
    echo "&nbsp;";
  }

  function submit_center_middle($name, $value, $title = FALSE, $async = FALSE, $icon = FALSE) {
    submit($name, $value, TRUE, $title, $async, $icon);
    echo "&nbsp;";
  }

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

  function submit_add_or_update_center($add = TRUE, $title = FALSE, $async = FALSE, $clone = FALSE) {
    echo "<div class='center'>";
    submit_add_or_update($add, $title, $async, $clone);
    echo "</div>";
  }

  function submit_add_or_update_row($add = TRUE, $right = TRUE, $extra = "", $title = FALSE, $async = FALSE, $clone = FALSE) {
    echo "<tr>";
    if ($right) {
      echo "<td>&nbsp;</td>\n";
    }
    echo "<td $extra>";
    submit_add_or_update($add, $title, $async, $clone);
    echo "</td></tr>\n";
  }

  function submit_cells($name, $value, $extra = "", $title = FALSE, $async = FALSE) {
    echo "<td $extra>";
    submit($name, $value, TRUE, $title, $async);
    echo "</td>\n";
  }

  function submit_row($name, $value, $right = TRUE, $extra = "", $title = FALSE, $async = FALSE) {
    echo "<tr>";
    if ($right) {
      echo "<td>&nbsp;</td>\n";
    }
    submit_cells($name, $value, $extra, $title, $async);
    echo "</tr>\n";
  }

  function submit_return($name, $value, $title = FALSE) {
    if (Input::request('frame')) {
      submit($name, $value, TRUE, $title, 'selector');
    }
  }

  function submit_js_confirm($name, $msg) {
    JS::beforeload("_validate.$name=function(){ return confirm('" . strtr($msg, array("\n" => '\\n')) . "');};");
  }

  function set_icon($icon, $title = FALSE) {
    return "<img src='/themes/" . User::theme() . "/images/$icon' style='width:12' height='12' " . ($title ? " title='$title'" : "") . " />\n";
  }

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

      return "<button type='submit' class='editbutton' id='" . $name . "' name='" . $name . "' value='1'" . ($title ? " title='$title'" :
        " title='$value'") . ($aspect ? " data-aspect='$aspect'" : '') . $rel . " />" . set_icon($icon) . "</button>\n";
    }
    else {
      return "<input type='submit' class='editbutton' id='" . $name . "' name='" . $name . "' value='$value'" . ($title ? " title='$title'" : '') . ($aspect ?
        " data-aspect='$aspect'" : '') . $rel . " />\n";
    }
  }

  function button_cell($name, $value, $title = FALSE, $icon = FALSE, $aspect = '') {
    echo "<td class='center'>";
    echo button($name, $value, $title, $icon, $aspect);
    echo "</td>";
  }

  function delete_button_cell($name, $value, $title = FALSE) {
    button_cell($name, $value, $title, ICON_DELETE);
  }

  function edit_button_cell($name, $value, $title = FALSE) {
    button_cell($name, $value, $title, ICON_EDIT);
  }

  function select_button_cell($name, $value, $title = FALSE) {
    button_cell($name, $value, $title, ICON_ADD, 'selector');
  }

  function check_value($name) {
    if (!isset($_POST[$name])) {
      return 0;
    }
    return 1;
  }

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
    $str .= "<input" . ($value == 1 ? ' checked' : '') . " type='checkbox' name='$name' id='$name' value='1'" . ($submit_on_change ? " onclick='$submit_on_change'" :
      '') . ($title ? " title='$title'" : '') . " >\n";
    Ajax::i()->addUpdate($name, $name, $value);
    return $str;
  }

  function check($label, $name, $value = NULL, $submit_on_change = FALSE, $title = FALSE) {
    echo checkbox($label, $name, $value, $submit_on_change, $title);
  }

  function check_cells($label, $name, $value = NULL, $submit_on_change = FALSE, $title = FALSE, $params = '') {
    if ($label != NULL) {
      echo "<td>$label</td>\n";
    }
    echo "<td $params>";
    echo check(NULL, $name, $value, $submit_on_change, $title);
    echo "</td>";
  }

  function check_row($label, $name, $value = NULL, $submit_on_change = FALSE, $title = FALSE) {
    echo "<tr><td class='label'>$label</td>";
    echo check_cells(NULL, $name, $value, $submit_on_change, $title);
    echo "</tr>\n";
  }

  function text_cells($label, $name, $value = NULL, $size = "", $max = "", $title = FALSE, $labparams = "", $post_label = "", $inparams = "") {
    if ($label != NULL) {
      label_cell($label, $labparams);
    }
    echo "<td>";
    if ($value === NULL) {
      $value = get_post($name);
    }
    echo "<input $inparams type=\"text\" name=\"$name\" size=\"$size\" maxlength=\"$max\" value=\"$value\"" . ($title ? " title='$title'" : '') . ">";
    if ($post_label != "") {
      echo " " . $post_label;
    }
    echo "</td>\n";
    Ajax::i()->addUpdate($name, $name, $value);
  }

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
      echo "<td class='label'>$label</td>";
    }
    if (!isset($max)) {
      $max = $size;
    }
    echo "<td>";
    $class = $submit_on_change ? 'class="searchbox"' : '';
    echo "<input $class type=\"text\" name=\"$name\" size=\"$size\" maxlength=\"$max\" value=\"" . $_POST[$name] . "\"" . ($title ? " title='$title'" : '') . " >";
    if ($post_label) {
      echo " " . $post_label;
    }
    echo "</td>\n";
    Ajax::i()->addUpdate($name, $name, $_POST[$name]);
  }

  function text_row($label, $name, $value, $size = FALSE, $max, $title = NULL, $params = "", $post_label = "") {
    echo "<tr><td class='label'>$label</td>";
    text_cells(NULL, $name, $value, $size, $max, $title, $params, $post_label);
    echo "</tr>\n";
  }

  function text_row_ex($label, $name, $size, $max = NULL, $title = NULL, $value = NULL, $params = NULL, $post_label = NULL, $params2 = '', $submit_on_change = FALSE) {
    echo "<tr {$params}><td class='label' {$params2}>$label</td>";
    text_cells_ex(NULL, $name, $size, $max, $value, $title, $params, $post_label, $submit_on_change);
    echo "</tr>\n";
  }

  function email_row($label, $name, $value, $size, $max, $title = NULL, $params = "", $post_label = "") {
    if (get_post($name)) {
      $label = "<a href='Mailto:" . $_POST[$name] . "'>$label</a>";
    }
    text_row($label, $name, $value, $size, $max, $title, $params, $post_label);
  }

  function email_row_ex($label, $name, $size, $max = NULL, $title = NULL, $value = NULL, $params = NULL, $post_label = NULL) {
    if (get_post($name)) {
      $label = "<a href='Mailto:" . $_POST[$name] . "'>$label</a>";
    }
    text_row_ex($label, $name, $size, $max, $title, $value, $params, $post_label);
  }

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
      label_cell($label, $params);
    }
    echo "<td>";
    $class = $submit_on_change ? 'searchbox datepicker' : 'datepicker';
    $aspect = $check ? ' data-aspect="cdate"' : '';
    if ($check && (get_post($name) != Dates::today())) {
      $aspect .= ' style="color:#FF0000"';
    }
    echo "<input id='$name' type='date' name='$name' class='$class' $aspect size=\"10\" maxlength='10' value=\"" .
      $_POST[$name] . "\"" . ($title ?
      " title='$title'" : '') . " > $post_label";
    echo "</td>\n";
    Ajax::i()->addUpdate($name, $name, $_POST[$name]);
  }

  function date_row($label, $name, $title = NULL, $check = NULL, $inc_days = 0, $inc_months = 0, $inc_years = 0, $params = NULL, $submit_on_change = FALSE) {
    echo "<tr><td class='label'>$label</td>";
    date_cells(NULL, $name, $title, $check, $inc_days, $inc_months, $inc_years, $params, $submit_on_change);
    echo "</tr>\n";
  }

  function password_row($label, $name, $value) {
    echo "<tr><td class='label'>$label</td>";
    label_cell("<input type='password' name='$name' value='$value' />");
    echo "</tr>\n";
  }

  function file_cells($label, $name, $id = "") {
    if ($id != "") {
      $id = "id='$id'";
    }
    label_cells($label, "<input type='file' name='$name' $id />");
  }

  function file_row($label, $name, $id = "") {
    echo "<tr><td class='label'>$label</td>";
    file_cells(NULL, $name, $id);
    echo "</tr>\n";
  }

  function ref_cells($label, $name, $title = NULL, $init = NULL, $params = NULL, $submit_on_change = FALSE) {
    text_cells_ex($label, $name, 13, 18, $init, $title, $params, NULL, $submit_on_change);
  }

  function ref_row($label, $name, $title = NULL, $init = NULL, $submit_on_change = FALSE) {
    echo "<tr><td class='label'>$label</td>";
    ref_cells(NULL, $name, $title, $init, NULL, $submit_on_change);
    echo "</tr>\n";
  }

  function percent_row($label, $name, $init = NULL, $cellparams = '', $inputparams = '') {
    if (!isset($_POST[$name]) || $_POST[$name] == "") {
      $_POST[$name] = ($init === NULL) ? '' : $init;
    }
    $inputparams .= ' max=100 min=0 step=1';
    small_amount_row($label, $name, $_POST[$name], NULL, "%", User::percent_dec(), 0, $inputparams);
  }

  function percent_cells($label, $name, $init = NULL, $inputparams = '') {
    if (!isset($_POST[$name]) || $_POST[$name] == "") {
      $_POST[$name] = ($init === NULL) ? '' : $init;
    }
    $inputparams .= ' max=100 min=0 step=1';
    small_amount_cells($label, $name, NULL, NULL, "%", User::percent_dec(), $inputparams);
  }

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
      label_cell($label, $params);
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

  function amount_row($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL, $inputparams = '') {
    echo "<tr>";
    amount_cells($label, $name, $init, $params, $post_label, $dec, $inputparams);
    echo "</tr>\n";
  }

  function small_amount_row($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL, $leftfill = 0, $inputparams = '') {
    echo "<tr>";
    small_amount_cells($label, $name, $init, $params, $post_label, $dec, $inputparams);
    if ($leftfill != 0) {
      echo "<td colspan=$leftfill></td>";
    }
    echo "</tr>\n";
  }

  function qty_cells($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL) {
    if (!isset($dec)) {
      $dec = User::qty_dec();
    }
    amount_cells_ex($label, $name, 10, 15, $init, $params, $post_label, $dec, NULL, NULL, TRUE);
  }

  function qty_row($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL) {
    if (!isset($dec)) {
      $dec = User::qty_dec();
    }
    echo "<tr>";
    amount_cells($label, $name, $init, $params, $post_label, $dec);
    echo "</tr>\n";
  }

  function small_qty_row($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL) {
    if (!isset($dec)) {
      $dec = User::qty_dec();
    }
    echo "<tr>";
    small_amount_cells($label, $name, $init, $params, $post_label, $dec, NULL, TRUE);
    echo "</tr>\n";
  }

  function small_amount_cells($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL,
                              $inputparams = '', $negatives = FALSE) {
    amount_cells_ex($label, $name, 7, 12, $init, $params, $post_label, $dec, NULL, $inputparams, $negatives);
  }

  function small_qty_cells($label, $name, $init = NULL, $params = NULL, $post_label = NULL, $dec = NULL) {
    if (!isset($dec)) {
      $dec = User::qty_dec();
    }
    amount_cells_ex($label, $name, 7, 12, $init, $params, $post_label, $dec, NULL, NULL, TRUE);
  }

  function textarea_cells($label, $name, $value, $cols, $rows, $title = NULL, $params = "") {
    if ($label != NULL) {
      echo "<td $params>$label</td>\n";
    }
    if ($value === NULL) {
      $value = (!isset($_POST[$name]) ? "" : $_POST[$name]);
    }
    echo "<td><textarea id='$name' name='$name' cols='" . ($cols + 2) . "' rows='$rows'" . ($title ? " title='$title'" : '') . ">$value</textarea></td>\n";
    Ajax::i()->addUpdate($name, $name, $value);
  }

  function textarea_row($label, $name, $value, $cols, $rows, $title = NULL, $params = "", $labelparams = "") {
    echo "<tr><td class='label' $labelparams>$label</td>";
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

  function yesno_list($name, $selected_id = NULL, $name_yes = "", $name_no = "", $submit_on_change = FALSE) {
    $items = array();
    $items['0'] = strlen($name_no) ? $name_no : _("No");
    $items['1'] = strlen($name_yes) ? $name_yes : _("Yes");
    return array_selector($name, $selected_id, $items, array(
      'select_submit' => $submit_on_change, 'async' => FALSE
    )); // FIX?
  }

  function yesno_list_cells($label, $name, $selected_id = NULL, $name_yes = "", $name_no = "", $submit_on_change = FALSE) {
    if ($label != NULL) {
      echo "<td>$label</td>\n";
    }
    echo "<td>";
    echo yesno_list($name, $selected_id, $name_yes, $name_no, $submit_on_change);
    echo "</td>\n";
  }

  function yesno_list_row($label, $name, $selected_id = NULL, $name_yes = "", $name_no = "", $submit_on_change = FALSE) {
    echo "<tr><td class='label'>$label</td>";
    yesno_list_cells(NULL, $name, $selected_id, $name_yes, $name_no, $submit_on_change);
    echo "</tr>\n";
  }

  function record_status_list_row($label, $name) {
    return yesno_list_row($label, $name, NULL, _('Inactive'), _('Active'));
  }

  function number_list($name, $selected, $from, $to, $no_option = FALSE) {
    $items = array();
    for ($i = $from; $i <= $to; $i++) {
      $items[$i] = "$i";
    }
    return array_selector($name, $selected, $items, array(
      'spec_option' => $no_option, 'spec_id' => ALL_NUMERIC
    ));
  }

  function number_list_cells($label, $name, $selected, $from, $to, $no_option = FALSE) {
    if ($label != NULL) {
      label_cell($label);
    }
    echo "<td>\n";
    echo number_list($name, $selected, $from, $to, $no_option);
    echo "</td>\n";
  }

  function number_list_row($label, $name, $selected, $from, $to, $no_option = FALSE) {
    echo "<tr><td class='label'>$label</td>";
    echo number_list_cells(NULL, $name, $selected, $from, $to, $no_option);
    echo "</tr>\n";
  }

  function dateformats_list_row($label, $name, $value = NULL) {
    echo "<tr><td class='label'>$label</td>\n<td>";
    echo array_selector($name, $value, Config::get('date.formats'));
    echo "</td></tr>\n";
  }

  function dateseps_list_row($label, $name, $value = NULL) {
    echo "<tr><td class='label'>$label</td>\n<td>";
    echo array_selector($name, $value, Config::get('date.separators'));
    echo "</td></tr>\n";
  }

  function thoseps_list_row($label, $name, $value = NULL) {
    echo "<tr><td class='label'>$label</td>\n<td>";
    echo array_selector($name, $value, Config::get('separators_thousands'));
    echo "</td></tr>\n";
  }

  function decseps_list_row($label, $name, $value = NULL) {
    echo "<tr><td class='label'>$label</td>\n<td>";
    echo array_selector($name, $value, Config::get('separators_decimal'));
    echo "</td></tr>\n";
  }

  function _format_date($row) {
    return Dates::sql2date($row['reconciled']);
  }

  function _format_add_curr($row) {
    static $company_currency;
    if ($company_currency == NULL) {
      $company_currency = Bank_Currency::for_company();
    }
    return $row[1] . ($row[2] == $company_currency ? '' : ("&nbsp;-&nbsp;" . $row[2]));
  }

  function _format_stock_items($row) {
    return (User::show_codes() ? ($row[0] . "&nbsp;-&nbsp;") : "") . $row[1];
  }

  function _format_template_items($row) {
    return ($row[0] . "&nbsp;- &nbsp;" . _("Amount") . "&nbsp;" . $row[1]);
  }

  function _format_fiscalyears($row) {
    return Dates::sql2date($row[1]) . "&nbsp;-&nbsp;" . Dates::sql2date($row[2]) . "&nbsp;&nbsp;" . ($row[3] ? _('Closed') : _('Active')) . "</option>\n";
  }

  function _format_account($row) {
    return $row[0] . "&nbsp;&nbsp;&nbsp;&nbsp;" . $row[1];
  }
