<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 28/03/12
   * Time: 8:36 AM
   * To change this template use File | Settings | File Templates.
   */
  class SelectBox {

    protected $where = array(); // additional constraints
    protected $order; // list sort order
    // special option parameters
    protected $spec_option = FALSE; // option text or false
    protected $spec_id = 0; // option id
    protected $cache = FALSE; // option id
    // submit on select parameters
    protected $default = ''; // default value when $_POST is not set
    protected $multi = FALSE; // multiple select
    protected $select_submit = FALSE; //submit on select: true/false
    protected $async = TRUE; // select update via ajax (true) vs _page_body reload
    // search box parameters
    protected $sel_hint = NULL;
    protected $search_box = FALSE; // name or true/false
    protected $type = 0; // type of extended selector:
    // 0 - with (optional) visible search box, search by id
    // 1 - with hidden search box, search by option text
    // 2 - TODO reverse: box with hidden selector available via enter; this
    // would be convenient for optional ad hoc adding of new item
    protected $search_submit = TRUE; //search submit button: true/false
    protected $size = 8; // size and max of box tag
    protected $max = 50;
    protected $height = FALSE; // number of lines in select box
    protected $cells = FALSE; // combo displayed as 2 <td></td> cells
    protected $search = array(); // sql field names to search
    protected $format = NULL; // format functions for regular options
    protected $disabled = FALSE;
    protected $box_hint = NULL; // box/selectors hints; null = std see below
    protected $category = FALSE; // category column name or false
    protected $show_inactive = FALSE; // show inactive records.
    protected $editable = FALSE; // false, or length of editable entry field
    protected $name;
    protected $selected_id;
    protected $sql;
    protected $valfield;
    protected $namefield;

    function __construct($name, $selected_id = NULL, $sql, $valfield, $namefield, $options = array()) {
      $this->name = $name;
      $this->order = $namefield;
      $this->selected_id = $selected_id;
      $this->sql = $sql;
      $this->valfield = $valfield;
      $this->namefield = $namefield;
      $options = (array) $options;
      foreach ($options as $option => $value) {
        if (property_exists($this, $option)) {
          $this->$option = $value;
        }
      }
      if (!is_array($this->where)) {
        $this->where = array($this->where);
      }
    }
    function create() {

      // ------ merge options with defaults ----------

      $search_box = $this->search_box === TRUE ? '_' . $this->name . '_edit' : $this->search_box;
      // select content filtered by search field:
      $search_submit = $this->search_submit === TRUE ? '_' . $this->name . '_button' : $this->search_submit;
      // select set by select content field
      $search_button = $this->editable ? '_' . $this->name . '_button' : ($search_box ? $search_submit : FALSE);
      $select_submit = $this->select_submit;
      $spec_id = $this->spec_id;
      $spec_option = $this->spec_option;
      $by_id = ($this->type == 0);
      $class = $by_id ? 'combo' : 'combo2';
      $disabled = $this->disabled ? "disabled" : '';
      $multi = $this->multi;
      if (!count($this->search)) {
        $this->search = array($by_id ? $this->valfield : $this->namefield);
      }
      if ($this->sel_hint === NULL) {
        $this->sel_hint = $by_id || $search_box == FALSE ? '' : _('Press Space tab for search pattern entry');
      }
      if ($this->box_hint === NULL) {
        $this->box_hint = $search_box && $search_submit != FALSE ?
          ($by_id ? _('Enter code fragment to search or * for all') : _('Enter description fragment to search or * for all')) : '';
      }
      if ($this->selected_id == NULL) {
        $this->selected_id = get_post($this->name, (string) $this->default);
      }
      if (!is_array($this->selected_id)) {
        $this->selected_id = array((string) $this->selected_id);
      } // code is generalized for multiple selection support
      $txt = get_post($search_box);
      $rel = '';
      $limit = '';
      if (isset($_POST['_' . $this->name . '_update'])) { // select list or search box change
        if ($by_id) {
          $txt = $_POST[$this->name];
        }
        if (!$this->async) {
          Ajax::i()->activate('_page_body');
        }
        else {
          Ajax::i()->activate($this->name);
        }
      }
      if (isset($_POST[$search_button])) {
        if (!$this->async) {
          Ajax::i()->activate('_page_body');
        }
        else {
          Ajax::i()->activate($this->name);
        }
      }
      if ($search_box) {
        // search related sql modifications
        $rel = "rel='$search_box'"; // set relation to list
        if ($this->search_submit) {
          if (isset($_POST[$search_button])) {
            $this->selected_id = array(); // ignore selected_id while search
            if (!$this->async) {
              Ajax::i()->activate('_page_body');
            }
            else {
              Ajax::i()->activate($this->name);
            }
          }
          if ($txt == '') {
            if ($spec_option === FALSE && $this->selected_id == array()) {
              $limit = ' LIMIT 1';
            }
            else {
              $this->where[] = $this->valfield . "='" . get_post($this->name, $spec_id) . "'";
            }
          }
          else {
            if ($txt != '*') {
              $texts = explode(" ", trim($txt));
              foreach ($texts as $text) {
                if (empty($text)) {
                  continue;
                }
                $search_fields = $this->search;
                foreach ($search_fields as $i => $s) {
                  $search_fields[$i] = $s . ' LIKE ' . DB::escape("%$text%");
                }
                $this->where[] = '(' . implode($search_fields, ' OR ') . ')';
              }
            }
          }
        }
      }
      // sql completion
      if (count($this->where)) {
        $where = strpos($this->sql, 'WHERE') == FALSE ? ' WHERE ' : ' AND ';
        $where .= '(' . implode($this->where, ' AND ') . ')';
        $group_pos = strpos($this->sql, 'GROUP BY');
        if ($group_pos) {
          $group = substr($this->sql, $group_pos);
          $this->sql = substr($this->sql, 0, $group_pos) . $where . ' ' . $group;
        }
        else {
          $this->sql .= $where;
        }
      }
      if ($this->order != FALSE) {
        if (!is_array($this->order)) {
          $this->order = array($this->order);
        }
        $this->sql .= ' ORDER BY ' . implode(',', $this->order);
      }
      $this->sql .= $limit;
      // ------ make selector ----------
      $selector = $first_opt = '';
      $first_id = FALSE;
      $found = FALSE;
      $lastcat = NULL;
      $edit = FALSE;
      //if($this->name=='stock_id')	Event::notice('<pre>'.print_r($_POST, true).'</pre>');
      //if($this->name=='curr_default') Event::notice($this->search_submit);
      if ($result = DB::query($this->sql)) {
        while ($contact_row = DB::fetch($result)) {
          $value = $contact_row[0];
          $descr = $this->format == NULL ? $contact_row[1] : call_user_func($this->format, $contact_row);
          $sel = '';
          if (get_post($search_button) && ($txt == $value)) {
            $this->selected_id[] = $value;
          }
          if (in_array((string) $value, $this->selected_id, TRUE)) {
            $sel = 'selected';
            $found = $value;
            $edit = $this->editable && $contact_row['editable'] && (Input::post($search_box) == $value) ? $contact_row[1] :
              FALSE; // get non-formatted description
            if ($edit) {
              break; // selected field is editable - abandon list construction
            }
          }
          // show selected option even if inactive
          if ((!isset($this->show_inactive) || !$this->show_inactive) && isset($contact_row['inactive']) && @$contact_row['inactive'] && $sel === '') {
            continue;
          }
          else {
            $optclass = (isset($contact_row['inactive']) && $contact_row['inactive']) ? "class='inactive'" : '';
          }
          if ($first_id === FALSE) {
            $first_id = $value;
          }
          $cat = $contact_row[$this->category];
          if ($this->category !== FALSE && $cat != $lastcat) {
            if (isset($lastcat)) {
              $selector .= "</optgroup>";
            }
            $selector .= "<optgroup label='" . $cat . "'>\n";
            $lastcat = $cat;
          }
          $selector .= "<option $sel $optclass value='$value'>$descr</option>\n";
        }
        DB::free_result($result);
      }
      // Prepend special option.
      if ($spec_option !== FALSE) { // if special option used - add it
        $first_id = $spec_id;
        $first_opt = $spec_option;
        //	}
        //	if($first_id !== false) {
        $sel = $found === FALSE ? 'selected' : '';
        $optclass = @$contact_row['inactive'] ? "class='inactive'" : '';
        $selector = "<option $sel value='$first_id'>$first_opt</option>\n" . $selector;
      }
      if (isset($lastcat)) {
        $selector .= '</optgroup>';
      }
      if ($found === FALSE) {
        $this->selected_id = array($first_id);
      }
      $_POST[$this->name] = $multi ? $this->selected_id : $this->selected_id[0];

      $selector = "<select id='$this->name' " . ($multi ? "multiple" : '') . ($this->height !== FALSE ? ' size="' . $this->height . '"' :
        '') . "$disabled name='$this->name" . ($multi ? '[]' : '') . "' class='$class' title='" . $this->sel_hint . "' $rel>" . $selector . "</select>\n";
      if ($by_id && ($search_box != FALSE || $this->editable)) {
        // on first display show selector list
        if (isset($_POST[$search_box]) && $this->editable && $edit) {
          $selector = "<input type='hidden' name='$this->name' value='" . $_POST[$this->name] . "'>";
          if (isset($contact_row['long_description'])) {
            $selector .= "<textarea name='{$this->name}_text' cols='{$this->max}' id='{$this->name}_text' $rel rows='2'>{$contact_row['long_description']}</textarea></td>\n";
          }
          else {
            $selector .= "<input type='text' $disabled name='{$this->name}_text' id='{$this->name}_text' size='" . $this->editable . "' maxlength='" . $this->max . "' $rel value='$edit'>\n";
          }
          JS::set_focus($this->name . '_text'); // prevent lost focus
        }
        else {
          if (get_post($search_submit ? $search_submit : "_{$this->name}_button")) {
            JS::set_focus($this->name);
          }
        } // prevent lost focus
        if (!$this->editable) {
          $txt = $found;
        }
        Ajax::i()->addUpdate($this->name, $search_box, $txt ? $txt : '');
      }
      Ajax::i()->addUpdate($this->name, "_{$this->name}_sel", $selector);
      // span for select list/input field update
      $selector = "<span id='_{$this->name}_sel'>" . $selector . "</span>\n";
      // if selectable or editable list is used - add select button
      if ($select_submit != FALSE || $search_button) {
        $_select_button = "<input %s type='submit' class='combo_select' style='border:0;background:url(/themes/%s/images/button_ok.png) no-repeat;%s' data-aspect='fallback' name='%s' value=' ' title='" . _("Select") . "'> "; // button class selects form reload/ajax selector update
        $selector .= sprintf($_select_button, $disabled, User::theme(), (User::fallback() ? '' : 'display:none;'), '_' . $this->name . '_update') . "\n";
      }
      // ------ make combo ----------
      $edit_entry = '';
      if ($search_box != FALSE) {
        $edit_entry = "<input $disabled type='text' name='$search_box' id='$search_box' size='" . $this->size . "' maxlength='" . $this->max . "' value='$txt' class='$class' rel='$this->name' autocomplete='off' title='" . $this->box_hint . "'" . (!User::fallback() && !$by_id ?
          " style=display:none;" : '') . ">\n";
        if ($search_submit != FALSE || $this->editable) {
          $_search_button = "<input %s type='submit' class='combo_submit' style='border:0;background:url(/themes/%s/images/locate.png) no-repeat;%s' data-aspect='fallback' name='%s' value=' ' title='" . _("Set filter") . "'> ";
          $edit_entry .= sprintf($_search_button, $disabled, User::theme(), (User::fallback() ? '' : 'display:none;'), $search_submit ? $search_submit :
            "_{$this->name}_button") . "\n";
        }
      }
      JS::default_focus(($search_box && $by_id) ? $search_box : $this->name);
      if ($search_box && $this->cells) {
        $str = ($edit_entry != '' ? "<td>$edit_entry</td>" : '') . "<td>$selector</td>";
      }
      else {
        $str = $edit_entry . $selector;
      }
      return $str;
    }
  }
