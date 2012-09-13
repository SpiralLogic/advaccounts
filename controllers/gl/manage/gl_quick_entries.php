<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Quick Entries"), SA_QUICKENTRY);
  list($Mode, $selected_id) = Page::simple_mode(true);
  list($Mode2, $selected_id2) = simple_page_mode2(true);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    if (can_process()) {
      if ($selected_id != -1) {
        GL_QuickEntry::update($selected_id, $_POST['description'], $_POST['type'], Validation::input_num('base_amount'), $_POST['base_desc']);
        Event::success(_('Selected quick entry has been updated'));
      } else {
        GL_QuickEntry::add($_POST['description'], $_POST['type'], Validation::input_num('base_amount'), $_POST['base_desc']);
        Event::success(_('New quick entry has been added'));
      }
      $Mode = MODE_RESET;
    }
  }
  if ($Mode2 == 'ADD_ITEM2' || $Mode2 == 'UPDATE_ITEM2') {
    if ($selected_id2 != -1) {
      GL_QuickEntry::update_line(
        $selected_id2,
        $selected_id,
        $_POST['actn'],
        $_POST['dest_id'],
        Validation::input_num('amount', 0),
        $_POST['dimension_id'],
        $_POST['dimension2_id']
      );
      Event::success(_('Selected quick entry line has been updated'));
    } else {
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
    } else {
      Event::error(_("The Quick Entry has Quick Entry Lines. Cannot be deleted."));
      JS::_setFocus('description');
    }
  }
  if ($Mode2 == 'BDel') {
    GL_QuickEntry::delete_line($selected_id2);
    Event::notice(_('Selected quick entry line has been deleted'));
    $Mode2 = 'RESET2';
  }
  if ($Mode == MODE_RESET) {
    $selected_id          = -1;
    $_POST['description'] = $_POST['type'] = '';
    $_POST['base_desc']   = _('Base Amount');
    $_POST['base_amount'] = Num::_priceFormat(0);
  }
  if ($Mode2 == 'RESET2') {
    $selected_id2  = -1;
    $_POST['actn'] = $_POST['dest_id'] = $_POST['amount'] = $_POST['dimension_id'] = $_POST['dimension2_id'] = '';
  }
  $result = GL_QuickEntry::getAll();
  Forms::start();
  Table::start('padded grid');
  $th = array(_("Description"), _("Type"), "", "");
  Table::header($th);
  $k = 0;
  while ($myrow = DB::_fetch($result)) {
    $type_text = GL_QuickEntry::$types[$myrow["type"]];
    Cell::label($myrow['description']);
    Cell::label($type_text);
    Forms::buttonEditCell("Edit" . $myrow["id"], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow["id"], _("Delete"));
    echo '</tr>';
  }
  Table::end(1);
  Forms::end();
  Forms::start();
  Table::start('standard');
  if ($selected_id != -1) {
    //if ($Mode == MODE_EDIT)
    //{
    //editing an existing status code
    $myrow                = GL_QuickEntry::get($selected_id);
    $_POST['id']          = $myrow["id"];
    $_POST['description'] = $myrow["description"];
    $_POST['type']        = $myrow["type"];
    $_POST['base_desc']   = $myrow["base_desc"];
    $_POST['base_amount'] = Num::_priceFormat($myrow["base_amount"]);
    Forms::hidden('selected_id', $selected_id);
    //}
  }
  Forms::textRowEx(_("Description") . ':', 'description', 50, 60);
  GL_QuickEntry::types(_("Entry Type") . ':', 'type');
  Forms::textRowEx(_("Base Amount Description") . ':', 'base_desc', 50, 60, '', _('Base Amount'));
  Forms::AmountRow(_("Default Base Amount") . ':', 'base_amount', Num::_priceFormat(0));
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  if ($selected_id != -1) {
    Display::heading(_("Quick Entry Lines") . " - " . $_POST['description']);
    $result = GL_QuickEntry::get_lines($selected_id);
    Forms::start();
    Table::start('standard grid');
    $dim = DB_Company::get_pref('use_dimension');
    if ($dim == 2) {
      $th = array(_("Post"), _("Account/Tax Type"), _("Amount"), _("Dimension"), _("Dimension") . " 2", "", "");
    } else {
      if ($dim == 1) {
        $th = array(_("Post"), _("Account/Tax Type"), _("Amount"), _("Dimension"), "", "");
      } else {
        $th = array(_("Post"), _("Account/Tax Type"), _("Amount"), "", "");
      }
    }
    Table::header($th);
    $k = 0;
    while ($myrow = DB::_fetch($result)) {
      Cell::label(GL_QuickEntry::$actions[$myrow['action']]);
      $act_type = strtolower(substr($myrow['action'], 0, 1));
      if ($act_type == 't') {
        Cell::labels($myrow['tax_name'], '');
      } else {
        Cell::label($myrow['dest_id'] . ' ' . $myrow['account_name']);
        if ($act_type == '=') {
          Cell::label('');
        } elseif ($act_type == '%') {
          Cell::label(Num::_format($myrow['amount'], User::exrate_dec()), ' class="alignright nowrap"');
        } else {
          Cell::amount($myrow['amount']);
        }
      }
      if ($dim >= 1) {
        Cell::label(Dimensions::get_string($myrow['dimension_id'], true));
      }
      if ($dim > 1) {
        Cell::label(Dimensions::get_string($myrow['dimension2_id'], true));
      }
      Forms::buttonEditCell("BEdit" . $myrow["id"], _("Edit"));
      Forms::buttonDeleteCell("BDel" . $myrow["id"], _("Delete"));
      echo '</tr>';
    }
    Table::end(1);
    Forms::hidden('selected_id', $selected_id);
    Forms::hidden('selected_id2', $selected_id2);
    Forms::hidden('description', $_POST['description']);
    Forms::hidden('type', $_POST['type']);
    Forms::end();
    Forms::start();
    Display::div_start('edit_line');
    Table::start('standard');
    if ($selected_id2 != -1) {
      if ($Mode2 == 'BEdit') {
        //editing an existing status code
        $myrow                  = GL_QuickEntry::has_line($selected_id2);
        $_POST['id']            = $myrow["id"];
        $_POST['dest_id']       = $myrow["dest_id"];
        $_POST['actn']          = $myrow["action"];
        $_POST['amount']        = $myrow["amount"];
        $_POST['dimension_id']  = $myrow["dimension_id"];
        $_POST['dimension2_id'] = $myrow["dimension2_id"];
      }
    }
    GL_QuickEntry::actions(_("Posted") . ":", 'actn', null, true);
    if (Forms::isListUpdated('actn')) {
      Ajax::_activate('edit_line');
    }
    $actn = strtolower(substr($_POST['actn'], 0, 1));
    if ($actn == 't') {
      //Tax_ItemType::row(_("Item Tax Type").":",'dest_id', null);
      Tax_Types::row(_("Tax Type") . ":", 'dest_id', null);
    } else {
      GL_UI::all_row(_("Account") . ":", 'dest_id', null, $_POST['type'] == QE_DEPOSIT || $_POST['type'] == QE_PAYMENT);
      if ($actn != '=') {
        if ($actn == '%') {
          Forms::SmallAmountRow(_("Part") . ":", 'amount', Num::_priceFormat(0), null, "%", User::exrate_dec());
        } else {
          Forms::AmountRow(_("Amount") . ":", 'amount', Num::_priceFormat(0));
        }
      }
    }
    if ($dim >= 1) {
      Dimensions::select_row(_("Dimension") . ":", 'dimension_id', null, true, " ", false, 1);
    }
    if ($dim > 1) {
      Dimensions::select_row(_("Dimension") . " 2:", 'dimension2_id', null, true, " ", false, 2);
    }
    Table::end(1);
    if ($dim < 2) {
      Forms::hidden('dimension2_id', 0);
    }
    if ($dim < 1) {
      Forms::hidden('dimension_id', 0);
    }
    Display::div_end();
    Forms::hidden('selected_id', $selected_id);
    Forms::hidden('selected_id2', $selected_id2);
    Forms::hidden('description', $_POST['description']);
    Forms::hidden('type', $_POST['type']);
    submit_add_or_update_center2($selected_id2 == -1, '', true);
    Forms::end();
  }
  Page::end();
  /**
   * @param bool $numeric_id
   *
   * @return array
   */
  function simple_page_mode2($numeric_id = true) {
    $default      = $numeric_id ? -1 : '';
    $selected_id2 = Input::_post('selected_id2', null, $default);
    foreach (array('ADD_ITEM2', 'UPDATE_ITEM2', 'RESET2') as $m) {
      if (isset($_POST[$m])) {
        Ajax::_activate('_page_body');
        if ($m == 'RESET2') {
          $selected_id2 = $default;
        }
        return array($m, $selected_id2);
      }
    }
    foreach (array('BEdit', 'BDel') as $m) {
      foreach ($_POST as $p => $pvar) {
        if (strpos($p, $m) === 0) {
          //				$selected_id2 = strtr(substr($p, strlen($m)), array('%2E'=>'.'));
          unset($_POST['_focus']); // focus on first form entry
          $selected_id2 = quoted_printable_decode(substr($p, strlen($m)));
          Ajax::_activate('_page_body');
          return array($m, $selected_id2);
        }
      }
    }
    return array('', $selected_id2);
  }

  /**
   * @param bool $add
   * @param bool $title
   * @param bool $async
   */
  function submit_add_or_update_center2($add = true, $title = false, $async = false) {
    echo "<div class='center'>";
    if ($add) {
      Forms::submit('ADD_ITEM2', _("Add new"), true, $title, $async);
    } else {
      Forms::submit('UPDATE_ITEM2', _("Update"), true, $title, $async);
      Forms::submit('RESET2', _("Cancel"), true, $title, $async);
    }
    echo "</div>";
  }

  /**
   * @return bool
   */
  function can_process() {
    if (strlen($_POST['description']) == 0) {
      Event::error(_("The Quick Entry description cannot be empty."));
      JS::_setFocus('description');
      return false;
    }
    if (strlen($_POST['base_desc']) == 0) {
      Event::error(_("The base amount description cannot be empty."));
      JS::_setFocus('base_desc');
      return false;
    }
    return true;
  }
