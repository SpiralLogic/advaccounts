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
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  Page::start(_($help_context = "Quick Entries"), SA_QUICKENTRY);
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  list($Mode2, $selected_id2) = simple_page_mode2(TRUE);

  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    if (can_process()) {
      if ($selected_id != -1) {
        GL_QuickEntry::update($selected_id, $_POST['description'], $_POST['type'], Validation::input_num('base_amount'), $_POST['base_desc']);
        Event::success(_('Selected quick entry has been updated'));
      }
      else {
        GL_QuickEntry::add($_POST['description'], $_POST['type'], Validation::input_num('base_amount'), $_POST['base_desc']);
        Event::success(_('New quick entry has been added'));
      }
      $Mode = MODE_RESET;
    }
  }
  if ($Mode2 == 'ADD_ITEM2' || $Mode2 == 'UPDATE_ITEM2') {
    if ($selected_id2 != -1) {
      GL_QuickEntry::update_line($selected_id2, $selected_id, $_POST['actn'], $_POST['dest_id'], Validation::input_num('amount', 0), $_POST['dimension_id'], $_POST['dimension2_id']);
      Event::success(_('Selected quick entry line has been updated'));
    }
    else {
      GL_QuickEntry::add_line($selected_id, $_POST['actn'], $_POST['dest_id'], Validation::input_num('amount', 0), $_POST['dimension_id'], $_POST['dimension2_id']);
      Event::success(_('New quick entry line has been added'));
    }
    $Mode2 = 'RESET2';
  }
  if ($Mode == MODE_DELETE) {
    if (!GL_QuickEntry::has_lines($selected_id)) {
      GL_QuickEntry::delete($selected_id);
      Event::notice(_('Selected quick entry has been deleted'));
      $Mode = MODE_RESET;
    }
    else {
      Event::error(_("The Quick Entry has Quick Entry Lines. Cannot be deleted."));
      JS::set_focus('description');
    }
  }
  if ($Mode2 == 'BDel') {
    GL_QuickEntry::delete_line($selected_id2);
    Event::notice(_('Selected quick entry line has been deleted'));
    $Mode2 = 'RESET2';
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $_POST['description'] = $_POST['type'] = '';
    $_POST['base_desc'] = _('Base Amount');
    $_POST['base_amount'] = Num::price_format(0);
  }
  if ($Mode2 == 'RESET2') {
    $selected_id2 = -1;
    $_POST['actn'] = $_POST['dest_id'] = $_POST['amount'] = $_POST['dimension_id'] = $_POST['dimension2_id'] = '';
  }
  $result = GL_QuickEntry::get_all();
  start_form();
  start_table('tablestyle');
  $th = array(_("Description"), _("Type"), "", "");
  table_header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {
    alt_table_row_color($k);
    $type_text = $quick_entry_types[$myrow["type"]];
    label_cell($myrow['description']);
    label_cell($type_text);
    edit_button_cell("Edit" . $myrow["id"], _("Edit"));
    delete_button_cell("Delete" . $myrow["id"], _("Delete"));
    end_row();
  }
  end_table(1);
  end_form();
  start_form();
  start_table('tablestyle2');
  if ($selected_id != -1) {
    //if ($Mode == MODE_EDIT)
    //{
    //editing an existing status code
    $myrow = GL_QuickEntry::get($selected_id);
    $_POST['id'] = $myrow["id"];
    $_POST['description'] = $myrow["description"];
    $_POST['type'] = $myrow["type"];
    $_POST['base_desc'] = $myrow["base_desc"];
    $_POST['base_amount'] = Num::price_format($myrow["base_amount"]);
    hidden('selected_id', $selected_id);
    //}
  }
  text_row_ex(_("Description") . ':', 'description', 50, 60);
  GL_QuickEntry::types(_("Entry Type") . ':', 'type');
  text_row_ex(_("Base Amount Description") . ':', 'base_desc', 50, 60, '', _('Base Amount'));
  amount_row(_("Default Base Amount") . ':', 'base_amount', Num::price_format(0));
  end_table(1);
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  if ($selected_id != -1) {
    Display::heading(_("Quick Entry Lines") . " - " . $_POST['description']);
    $result = GL_QuickEntry::get_lines($selected_id);
    start_form();
    start_table('tablestyle2');
    $dim = DB_Company::get_pref('use_dimension');
    if ($dim == 2) {
      $th = array(_("Post"), _("Account/Tax Type"), _("Amount"), _("Dimension"), _("Dimension") . " 2", "", "");
    }
    else {
      if ($dim == 1) {
        $th = array(_("Post"), _("Account/Tax Type"), _("Amount"), _("Dimension"), "", "");
      }
      else {
        $th = array(_("Post"), _("Account/Tax Type"), _("Amount"), "", "");
      }
    }
    table_header($th);
    $k = 0;
    while ($myrow = DB::fetch($result)) {
      alt_table_row_color($k);
      label_cell($quick_actions[$myrow['action']]);
      $act_type = strtolower(substr($myrow['action'], 0, 1));
      if ($act_type == 't') {
        label_cells($myrow['tax_name'], '');
      }
      else {
        label_cell($myrow['dest_id'] . ' ' . $myrow['account_name']);
        if ($act_type == '=') {
          label_cell('');
        }
        elseif ($act_type == '%') {
          label_cell(Num::format($myrow['amount'], User::exrate_dec()), ' class="right nowrap"');
        }
        else {
          amount_cell($myrow['amount']);
        }
      }
      if ($dim >= 1) {
        label_cell(Dimensions::get_string($myrow['dimension_id'], TRUE));
      }
      if ($dim > 1) {
        label_cell(Dimensions::get_string($myrow['dimension2_id'], TRUE));
      }
      edit_button_cell("BEd" . $myrow["id"], _("Edit"));
      delete_button_cell("BDel" . $myrow["id"], _("Delete"));
      end_row();
    }
    end_table(1);
    hidden('selected_id', $selected_id);
    hidden('selected_id2', $selected_id2);
    hidden('description', $_POST['description']);
    hidden('type', $_POST['type']);
    end_form();
    start_form();
    Display::div_start('edit_line');
    start_table('tablestyle2');
    if ($selected_id2 != -1) {
      if ($Mode2 == 'BEd') {
        //editing an existing status code
        $myrow = GL_QuickEntry::has_line($selected_id2);
        $_POST['id'] = $myrow["id"];
        $_POST['dest_id'] = $myrow["dest_id"];
        $_POST['actn'] = $myrow["action"];
        $_POST['amount'] = $myrow["amount"];
        $_POST['dimension_id'] = $myrow["dimension_id"];
        $_POST['dimension2_id'] = $myrow["dimension2_id"];
      }
    }
    GL_QuickEntry::actions(_("Posted") . ":", 'actn', NULL, TRUE);
    if (list_updated('actn')) {
      Ajax::i()->activate('edit_line');
    }
    $actn = strtolower(substr($_POST['actn'], 0, 1));
    if ($actn == 't') {
      //Tax_ItemType::row(_("Item Tax Type").":",'dest_id', null);
      Tax_Types::row(_("Tax Type") . ":", 'dest_id', NULL);
    }
    else {
      GL_UI::all_row(_("Account") . ":", 'dest_id', NULL, $_POST['type'] == QE_DEPOSIT || $_POST['type'] == QE_PAYMENT);
      if ($actn != '=') {
        if ($actn == '%') {
          small_amount_row(_("Part") . ":", 'amount', Num::price_format(0), NULL, "%", User::exrate_dec());
        }
        else {
          amount_row(_("Amount") . ":", 'amount', Num::price_format(0));
        }
      }
    }
    if ($dim >= 1) {
      Dimensions::select_row(_("Dimension") . ":", 'dimension_id', NULL, TRUE, " ", FALSE, 1);
    }
    if ($dim > 1) {
      Dimensions::select_row(_("Dimension") . " 2:", 'dimension2_id', NULL, TRUE, " ", FALSE, 2);
    }
    end_table(1);
    if ($dim < 2) {
      hidden('dimension2_id', 0);
    }
    if ($dim < 1) {
      hidden('dimension_id', 0);
    }
    Display::div_end();
    hidden('selected_id', $selected_id);
    hidden('selected_id2', $selected_id2);
    hidden('description', $_POST['description']);
    hidden('type', $_POST['type']);
    submit_add_or_update_center2($selected_id2 == -1, '', TRUE);
    end_form();
  }
  Page::end();
  function simple_page_mode2($numeric_id = TRUE) {
    $default = $numeric_id ? -1 : '';
    $selected_id2 = get_post('selected_id2', $default);
    foreach (array('ADD_ITEM2', 'UPDATE_ITEM2', 'RESET2') as $m) {
      if (isset($_POST[$m])) {
        Ajax::i()->activate('_page_body');
        if ($m == 'RESET2') {
          $selected_id2 = $default;
        }
        return array($m, $selected_id2);
      }
    }
    foreach (array('BEd', 'BDel') as $m) {
      foreach ($_POST as $p => $pvar) {
        if (strpos($p, $m) === 0) {
          //				$selected_id2 = strtr(substr($p, strlen($m)), array('%2E'=>'.'));
          unset($_POST['_focus']); // focus on first form entry
          $selected_id2 = quoted_printable_decode(substr($p, strlen($m)));
          Ajax::i()->activate('_page_body');
          return array($m, $selected_id2);
        }
      }
    }
    return array('', $selected_id2);
  }

  function submit_add_or_update_center2($add = TRUE, $title = FALSE, $async = FALSE) {
    echo "<div class='center'>";
    if ($add) {
      submit('ADD_ITEM2', _("Add new"), TRUE, $title, $async);
    }
    else {
      submit('UPDATE_ITEM2', _("Update"), TRUE, $title, $async);
      submit('RESET2', _("Cancel"), TRUE, $title, $async);
    }
    echo "</div>";
  }

  function can_process() {
    if (strlen($_POST['description']) == 0) {
      Event::error(_("The Quick Entry description cannot be empty."));
      JS::set_focus('description');
      return FALSE;
    }
    if (strlen($_POST['base_desc']) == 0) {
      Event::error(_("The base amount description cannot be empty."));
      JS::set_focus('base_desc');
      return FALSE;
    }
    return TRUE;
  }
