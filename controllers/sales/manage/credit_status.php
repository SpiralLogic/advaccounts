<?php

  use ADV\App\Forms;

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
    $sav         = Input::_post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result = Sales_CreditStatus::getAll(Input::_hasPost('show_inactive'));
  Forms::start();
  Table::start('tablestyle grid width40');
  $th = array(_("Description"), _("Dissallow Invoices"), '', '');
  Forms::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::_fetch($result)) {

    if ($myrow["dissallow_invoices"] == 0) {
      $disallow_text = _("Invoice OK");
    } else {
      $disallow_text = "<span class='bold'>" . _("NO INVOICING") . "</span>";
    }
    Cell::label($myrow["reason_description"]);
    Cell::label($disallow_text);
    Forms::inactiveControlCell($myrow["id"], $myrow["inactive"], 'credit_status', 'id');

    Forms::buttonEditCell("Edit" . $myrow['id'], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow['id'], _("Delete"));
    Row::end();
  }
  Forms::inactiveControlRow($th);
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
    Forms::hidden('selected_id', $selected_id);
  }
  Forms::textRowEx(_("Description:"), 'reason_description', 50);
  Forms::yesnoListRow(_("Dissallow invoicing ?"), 'DisallowInvoices', null);
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();
