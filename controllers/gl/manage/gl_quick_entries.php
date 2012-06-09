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
      GL_QuickEntry::update_line($selected_id2, $selected_id, $_POST['actn'], $_POST['dest_id'], Validation::input_num('amount', 0), $_POST['dimension_id'], $_POST['dimension2_id']);
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
      JS::set_focus('description');
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
    $_POST['base_amount'] = Num::price_format(0);
  }
  if ($Mode2 == 'RESET2') {
    $selected_id2  = -1;
    $_POST['actn'] = $_POST['dest_id'] = $_POST['amount'] = $_POST['dimension_id'] = $_POST['dimension2_id'] = '';
  }
  $result = GL_QuickEntry::get_all();
  Form::start();
  Table::start('tablestyle grid');
  $th = array(_("Description"), _("Type"), "", "");
  Table::header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {
    $type_text = $quick_entry_types[$myrow["type"]];
    Cell::label($myrow['description']);
    Cell::label($type_text);
    Form::buttonEditCell("Edit" . $myrow["id"], _("Edit"));
    Form::buttonDeleteCell("Delete" . $myrow["id"], _("Delete"));
    Row::end();
  }
  Table::end(1);
  Form::end();
  Form::start();
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    //if ($Mode == MODE_EDIT)
    //{
    //editing an existing status code
    $myrow                = GL_QuickEntry::get($selected_id);
    $_POST['id']          = $myrow["id"];
    $_POST['description'] = $myrow["description"];
    $_POST['type']        = $myrow["type"];
    $_POST['base_desc']   = $myrow["base_desc"];
    $_POST['base_amount'] = Num::price_format($myrow["base_amount"]);
    Form::hidden('selected_id', $selected_id);
    //}
  }
   Form::textRowEx(_("Description") . ':', 'description', 50, 60);
  GL_QuickEntry::types(_("Entry Type") . ':', 'type');
   Form::textRowEx(_("Base Amount Description") . ':', 'base_desc', 50, 60, '', _('Base Amount'));
   Form::AmountRow(_("Default Base Amount") . ':', 'base_amount', Num::price_format(0));
  Table::end(1);
  Form::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Form::end();
  if ($selected_id != -1) {
    Display::heading(_("Quick Entry Lines") . " - " . $_POST['description']);
    $result = GL_QuickEntry::get_lines($selected_id);
    Form::start();
    Table::start('tablestyle2 grid');
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
    while ($myrow = DB::fetch($result)) {
      Cell::label($quick_actions[$myrow['action']]);
      $act_type = strtolower(substr($myrow['action'], 0, 1));
      if ($act_type == 't') {
        Cell::labels($myrow['tax_name'], '');
      } else {
        Cell::label($myrow['dest_id'] . ' ' . $myrow['account_name']);
        if ($act_type == '=') {
          Cell::label('');
        } elseif ($act_type == '%') {
          Cell::label(Num::format($myrow['amount'], User::exrate_dec()), ' class="right nowrap"');
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
      Form::buttonEditCell("BEd" . $myrow["id"], _("Edit"));
      Form::buttonDeleteCell("BDel" . $myrow["id"], _("Delete"));
      Row::end();
    }
    Table::end(1);
    Form::hidden('selected_id', $selected_id);
    Form::hidden('selected_id2', $selected_id2);
    Form::hidden('description', $_POST['description']);
    Form::hidden('type', $_POST['type']);
    Form::end();
    Form::start();
    Display::div_start('edit_line');
    Table::start('tablestyle2');
    if ($selected_id2 != -1) {
      if ($Mode2 == 'BEd') {
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
    if (Form::isListUpdated('actn')) {
      Ajax::i()->activate('edit_line');
    }
    $actn = strtolower(substr($_POST['actn'], 0, 1));
    if ($actn == 't') {
      //Tax_ItemType::row(_("Item Tax Type").":",'dest_id', null);
      Tax_Types::row(_("Tax Type") . ":", 'dest_id', null);
    } else {
      GL_UI::all_row(_("Account") . ":", 'dest_id', null, $_POST['type'] == QE_DEPOSIT || $_POST['type'] == QE_PAYMENT);
      if ($actn != '=') {
        if ($actn == '%') {
           Form::SmallAmountRow(_("Part") . ":", 'amount', Num::price_format(0), null, "%", User::exrate_dec());
        } else {
           Form::AmountRow(_("Amount") . ":", 'amount', Num::price_format(0));
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
      Form::hidden('dimension2_id', 0);
    }
    if ($dim < 1) {
      Form::hidden('dimension_id', 0);
    }
    Display::div_end();
    Form::hidden('selected_id', $selected_id);
    Form::hidden('selected_id2', $selected_id2);
    Form::hidden('description', $_POST['description']);
    Form::hidden('type', $_POST['type']);
    submit_add_or_update_center2($selected_id2 == -1, '', true);
    Form::end();
  }
  Page::end();
  /**
   * @param bool $numeric_id
   *
   * @return array
   */
  function simple_page_mode2($numeric_id = true)
  {
    $default      = $numeric_id ? -1 : '';
    $selected_id2 = Input::post('selected_id2',null,$default);
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

  /**
   * @param bool $add
   * @param bool $title
   * @param bool $async
   */
  function submit_add_or_update_center2($add = true, $title = false, $async = false)
  {
    echo "<div class='center'>";
    if ($add) {
      Form::submit('ADD_ITEM2', _("Add new"), true, $title, $async);
    } else {
      Form::submit('UPDATE_ITEM2', _("Update"), true, $title, $async);
      Form::submit('RESET2', _("Cancel"), true, $title, $async);
    }
    echo "</div>";
  }

  /**
   * @return bool
   */
  function can_process()
  {
    if (strlen($_POST['description']) == 0) {
      Event::error(_("The Quick Entry description cannot be empty."));
      JS::set_focus('description');

      return false;
    }
    if (strlen($_POST['base_desc']) == 0) {
      Event::error(_("The base amount description cannot be empty."));
      JS::set_focus('base_desc');

      return false;
    }

    return true;
  }
