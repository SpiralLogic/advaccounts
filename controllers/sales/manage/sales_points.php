<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Page::start(_($help_context = "POS settings"), SA_POSSETUP);
  list($Mode, $selected_id) = Page::simple_mode(TRUE);

  if ($Mode == ADD_ITEM && Sales_Point::can_process()) {
    Sales_Point::add($_POST['name'], $_POST['location'], $_POST['account'], Form::hasPost('cash'), Form::hasPost('credit'));
    Event::success(_('New point of sale has been added'));
    $Mode = MODE_RESET;
  }
  if ($Mode == UPDATE_ITEM && Sales_Point::can_process()) {
    Sales_Point::update($selected_id, $_POST['name'], $_POST['location'], $_POST['account'], Form::hasPost('cash'), Form::hasPost('credit'));
    Event::success(_('Selected point of sale has been updated'));
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_DELETE) {
    $sql = "SELECT * FROM users WHERE pos=" . DB::escape($selected_id);
    $res = DB::query($sql, "canot check pos usage");
    if (DB::num_rows($res)) {
      Event::error(_("Cannot delete this POS because it is used in users setup."));
    }
    else {
      Sales_Point::delete($selected_id);
      Event::notice(_('Selected point of sale has been deleted'));
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav = Input::post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result = Sales_Point::get_all(Form::hasPost('show_inactive'));
  Form::start();
  Table::start('tablestyle grid');
  $th = array(
    _('POS Name'), _('Credit sale'), _('Cash sale'), _('location'), _('Default account'), '', ''
  );
   Form::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {

    Cell::label($myrow["pos_name"], ' class="nowrap"');
    Cell::label($myrow['credit_sale'] ? _('Yes') : _('No'));
    Cell::label($myrow['cash_sale'] ? _('Yes') : _('No'));
    Cell::label($myrow["location_name"], "");
    Cell::label($myrow["bank_account_name"], "");
     Form::inactiveControlCell($myrow["id"], $myrow["inactive"], "sales_pos", 'id');
    Form::buttonEditCell("Edit" . $myrow['id'], _("Edit"));
    Form::buttonDeleteCell("Delete" . $myrow['id'], _("Delete"));
    Row::end();
  }
   Form::inactiveControlRow($th);
  Table::end(1);
  $cash = Validation::check(Validation::CASH_ACCOUNTS);
  if (!$cash) {
    Event::warning(_("To have cash POS first define at least one cash bank account."));
  }
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      $myrow = Sales_Point::get($selected_id);
      $_POST['name'] = $myrow["pos_name"];
      $_POST['location'] = $myrow["pos_location"];
      $_POST['account'] = $myrow["pos_account"];
      if ($myrow["credit_sale"]) {
        $_POST['credit_sale'] = 1;
      }
      if ($myrow["cash_sale"]) {
        $_POST['cash_sale'] = 1;
      }
    }
    Form::hidden('selected_id', $selected_id);
  }
   Form::textRowEx(_("Point of Sale Name") . ':', 'name', 20, 30);
  if ($cash) {
     Form::checkRow(_('Allowed credit sale'), 'credit', Form::hasPost('credit_sale'));
     Form::checkRow(_('Allowed cash sale'), 'cash', Form::hasPost('cash_sale'));
    Bank_UI::cash_accounts_row(_("Default cash account") . ':', 'account');
  }
  else {
    Form::hidden('credit', 1);
    Form::hidden('account', 0);
  }
  Inv_Location::row(_("POS location") . ':', 'location');
  Table::end(1);
  Form::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Form::end();
  Page::end();


