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
    Sales_Point::add($_POST['name'], $_POST['location'], $_POST['account'], check_value('cash'), check_value('credit'));
    Event::success(_('New point of sale has been added'));
    $Mode = MODE_RESET;
  }
  if ($Mode == UPDATE_ITEM && Sales_Point::can_process()) {
    Sales_Point::update($selected_id, $_POST['name'], $_POST['location'], $_POST['account'], check_value('cash'), check_value('credit'));
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
    $sav = get_post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result = Sales_Point::get_all(check_value('show_inactive'));
  start_form();
  start_table('tablestyle');
  $th = array(
    _('POS Name'), _('Credit sale'), _('Cash sale'), _('location'), _('Default account'), '', ''
  );
  inactive_control_column($th);
  table_header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {
    alt_table_row_color($k);
    label_cell($myrow["pos_name"], ' class="nowrap"');
    label_cell($myrow['credit_sale'] ? _('Yes') : _('No'));
    label_cell($myrow['cash_sale'] ? _('Yes') : _('No'));
    label_cell($myrow["location_name"], "");
    label_cell($myrow["bank_account_name"], "");
    inactive_control_cell($myrow["id"], $myrow["inactive"], "sales_pos", 'id');
    edit_button_cell("Edit" . $myrow['id'], _("Edit"));
    delete_button_cell("Delete" . $myrow['id'], _("Delete"));
    end_row();
  }
  inactive_control_row($th);
  end_table(1);
  $cash = Validation::check(Validation::CASH_ACCOUNTS);
  if (!$cash) {
    Event::warning(_("To have cash POS first define at least one cash bank account."));
  }
  start_table('tablestyle2');
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
    hidden('selected_id', $selected_id);
  }
  text_row_ex(_("Point of Sale Name") . ':', 'name', 20, 30);
  if ($cash) {
    check_row(_('Allowed credit sale'), 'credit', check_value('credit_sale'));
    check_row(_('Allowed cash sale'), 'cash', check_value('cash_sale'));
    Bank_UI::cash_accounts_row(_("Default cash account") . ':', 'account');
  }
  else {
    hidden('credit', 1);
    hidden('account', 0);
  }
  Inv_Location::row(_("POS location") . ':', 'location');
  end_table(1);
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  Page::end();


