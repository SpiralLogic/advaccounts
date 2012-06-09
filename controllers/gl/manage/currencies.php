<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Currencies"), SA_CURRENCY);
  list($Mode, $selected_id) = Page::simple_mode(false);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    handle_submit($Mode, $selected_id);
  }
  if ($Mode == MODE_DELETE) {
    handle_delete();
  }
  if ($Mode == MODE_RESET) {
    $selected_id            = '';
    $_POST['Abbreviation']  = $_POST['Symbol'] = '';
    $_POST['CurrencyName']  = $_POST['country'] = '';
    $_POST['hundreds_name'] = '';
  }
  Form::start();
  display_currencies();
  display_currency_edit($Mode, $selected_id);
  Form::end();
  Page::end();
  /**
   * @return bool
   */
  function check_data()
  {
    if (strlen($_POST['Abbreviation']) == 0) {
      Event::error(_("The currency abbreviation must be entered."));
      JS::set_focus('Abbreviation');

      return false;
    } elseif (strlen($_POST['CurrencyName']) == 0) {
      Event::error(_("The currency name must be entered."));
      JS::set_focus('CurrencyName');

      return false;
    } elseif (strlen($_POST['Symbol']) == 0) {
      Event::error(_("The currency symbol must be entered."));
      JS::set_focus('Symbol');

      return false;
    } elseif (strlen($_POST['hundreds_name']) == 0) {
      Event::error(_("The hundredths name must be entered."));
      JS::set_focus('hundreds_name');

      return false;
    }

    return true;
  }

  /**
   * @param $Mode
   * @param $selected_id
   *
   * @return bool
   */
  function handle_submit(&$Mode, $selected_id)
  {
    if (!check_data()) {
      return false;
    }
    if ($selected_id != "") {
      GL_Currency::update($_POST['Abbreviation'], $_POST['Symbol'], $_POST['CurrencyName'], $_POST['country'], $_POST['hundreds_name'], Form::hasPost('auto_update'));
      Event::success(_('Selected currency settings has been updated'));
    } else {
      GL_Currency::add($_POST['Abbreviation'], $_POST['Symbol'], $_POST['CurrencyName'], $_POST['country'], $_POST['hundreds_name'], Form::hasPost('auto_update'));
      Event::success(_('New currency has been added'));
    }
    $Mode = MODE_RESET;
  }

  /**
   * @param $selected_id
   *
   * @return bool
   */
  function check_can_delete($selected_id)
  {
    if ($selected_id == "") {
      return false;
    }
    $curr = DB::escape($selected_id);
    // PREVENT DELETES IF DEPENDENT RECORDS IN debtors
    $sql    = "SELECT COUNT(*) FROM debtors WHERE curr_code = $curr";
    $result = DB::query($sql);
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this currency, because customer accounts have been created referring to this currency."));

      return false;
    }
    $sql    = "SELECT COUNT(*) FROM suppliers WHERE curr_code = $curr";
    $result = DB::query($sql);
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this currency, because supplier accounts have been created referring to this currency."));

      return false;
    }
    $sql    = "SELECT COUNT(*) FROM company WHERE curr_default = $curr";
    $result = DB::query($sql);
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this currency, because the company preferences uses this currency."));

      return false;
    }
    // see if there are any bank accounts that use this currency
    $sql    = "SELECT COUNT(*) FROM bank_accounts WHERE bank_curr_code = $curr";
    $result = DB::query($sql);
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this currency, because thre are bank accounts that use this currency."));

      return false;
    }

    return true;
  }

  /**
   * @param $Mode
   * @param $selected_id
   */
  function handle_delete(&$Mode, $selected_id)
  {
    if (check_can_delete($selected_id)) {
      //only delete if used in neither customer or supplier, comp prefs, bank trans accounts
      GL_Currency::delete($selected_id);
      Event::notice(_('Selected currency has been deleted'));
    }
    $Mode = MODE_RESET;
  }

  function display_currencies()
  {
    $company_currency = Bank_Currency::for_company();
    $result           = GL_Currency::get_all(Form::hasPost('show_inactive'));
    Table::start('tablestyle grid');
    $th = array(
      _("Abbreviation"), _("Symbol"), _("Currency Name"), _("Hundredths name"), _("Country"), _("Auto update"), "", ""
    );
     Form::inactiveControlCol($th);
    Table::header($th);
    $k = 0; //row colour counter
    while ($myrow = DB::fetch($result)) {
      if ($myrow[1] == $company_currency) {
        Row::start("class='currencybg'");
      } else {
      }
      Cell::label($myrow["curr_abrev"]);
      Cell::label($myrow["curr_symbol"]);
      Cell::label($myrow["currency"]);
      Cell::label($myrow["hundreds_name"]);
      Cell::label($myrow["country"]);
      Cell::label($myrow[1] == $company_currency ? '-' : ($myrow["auto_update"] ? _('Yes') : _('No')), "class='center'");
       Form::inactiveControlCell($myrow["curr_abrev"], $myrow["inactive"], 'currencies', 'curr_abrev');
      Form::buttonEditCell("Edit" . $myrow["curr_abrev"], _("Edit"));
      if ($myrow["curr_abrev"] != $company_currency) {
        Form::buttonDeleteCell("Delete" . $myrow["curr_abrev"], _("Delete"));
      } else {
        Cell::label('');
      }
      Row::end();
    } //END WHILE LIST LOOP
     Form::inactiveControlRow($th);
    Table::end();
    Event::warning(_("The marked currency is the home currency which cannot be deleted."), 0, 0, "class='currentfg'");
  }

  /**
   * @param $Mode
   * @param $selected_id
   */
  function display_currency_edit($Mode, $selected_id)
  {
    Table::start('tablestyle2');
    if ($selected_id != '') {
      if ($Mode == MODE_EDIT) {
        //editing an existing currency
        $myrow                  = GL_Currency::get($selected_id);
        $_POST['Abbreviation']  = $myrow["curr_abrev"];
        $_POST['Symbol']        = $myrow["curr_symbol"];
        $_POST['CurrencyName']  = $myrow["currency"];
        $_POST['country']       = $myrow["country"];
        $_POST['hundreds_name'] = $myrow["hundreds_name"];
        $_POST['auto_update']   = $myrow["auto_update"];
      }
      Form::hidden('Abbreviation');
      Form::hidden('selected_id', $selected_id);
      Row::label(_("Currency Abbreviation:"), $_POST['Abbreviation']);
    } else {
      $_POST['auto_update'] = 1;
       Form::textRowEx(_("Currency Abbreviation:"), 'Abbreviation', 4, 3);
    }
     Form::textRowEx(_("Currency Symbol:"), 'Symbol', 10);
     Form::textRowEx(_("Currency Name:"), 'CurrencyName', 20);
     Form::textRowEx(_("Hundredths Name:"), 'hundreds_name', 15);
     Form::textRowEx(_("Country:"), 'country', 40);
     Form::checkRow(_("Automatic exchange rate update:"), 'auto_update', Input::post('auto_update'));
    Table::end(1);
    Form::submitAddUpdateCenter($selected_id == '', '', 'both');
  }
