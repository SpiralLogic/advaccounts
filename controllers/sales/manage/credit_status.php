<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Credit Status"), SA_CRSTATUS);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM && Sales_CreditStatus::can_process()) {
    Sales_CreditStatus::add($_POST['reason_description'], $_POST['DisallowInvoices']);
    Event::success(_('New credit status has been added'));
    $Mode = MODE_RESET;
  }
  if ($Mode == UPDATE_ITEM && Sales_CreditStatus::can_process()) {
    Event::success(_('Selected credit status has been updated'));
    Sales_CreditStatus::update($selected_id, $_POST['reason_description'], $_POST['DisallowInvoices']);
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_DELETE) {
    if (Sales_CreditStatus::can_delete($selected_id)) {
      Sales_CreditStatus::delete($selected_id);
      Event::notice(_('Selected credit status has been deleted'));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav         = get_post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result = Sales_CreditStatus::get_all(check_value('show_inactive'));
  start_form();
  Table::start('tablestyle grid width40');
  $th = array(_("Description"), _("Dissallow Invoices"), '', '');
  inactive_control_column($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {
    if ($myrow["dissallow_invoices"] == 0) {
      $disallow_text = _("Invoice OK");
    } else {
      $disallow_text = "<span class='bold'>" . _("NO INVOICING") . "</span>";
    }
    Cell::label($myrow["reason_description"]);
    Cell::label($disallow_text);
    inactive_control_cell($myrow["id"], $myrow["inactive"], 'credit_status', 'id');
    edit_button_cell("Edit" . $myrow['id'], _("Edit"));
    delete_button_cell("Delete" . $myrow['id'], _("Delete"));
    Row::end();
  }
  inactive_control_row($th);
  Table::end();
  echo '<br>';
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing status code
      $myrow                       = Sales_CreditStatus::get($selected_id);
      $_POST['reason_description'] = $myrow["reason_description"];
      $_POST['DisallowInvoices']   = $myrow["dissallow_invoices"];
    }
    hidden('selected_id', $selected_id);
  }
  text_row_ex(_("Description:"), 'reason_description', 50);
  yesno_list_row(_("Dissallow invoicing ?"), 'DisallowInvoices', null);
  Table::end(1);
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  Page::end();
