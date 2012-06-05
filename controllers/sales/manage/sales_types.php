<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Sales Types"), SA_SALESTYPES);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM && Sales_Type::can_process()) {
    Sales_Type::add($_POST['sales_type'], isset($_POST['tax_included']) ? 1 : 0, Validation::input_num('factor'));
    Event::success(_('New sales type has been added'));
    $Mode = MODE_RESET;
  }
  if ($Mode == UPDATE_ITEM && Sales_Type::can_process()) {
    Sales_Type::update($selected_id, $_POST['sales_type'], isset($_POST['tax_included']) ? 1 :
      0, Validation::input_num('factor'));
    Event::success(_('Selected sales type has been updated'));
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_DELETE) {
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'debtor_trans'
    $sql    = "SELECT COUNT(*) FROM debtor_trans WHERE tpe=" . DB::escape($selected_id);
    $result = DB::query($sql, "The number of transactions using this Sales type record could not be retrieved");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this sale type because customer transactions have been created using this sales type."));
    } else {
      $sql    = "SELECT COUNT(*) FROM debtors WHERE sales_type=" . DB::escape($selected_id);
      $result = DB::query($sql, "The number of customers using this Sales type record could not be retrieved");
      $myrow  = DB::fetch_row($result);
      if ($myrow[0] > 0) {
        Event::error(_("Cannot delete this sale type because customers are currently set up to use this sales type."));
      } else {
        Sales_Type::delete($selected_id);
        Event::notice(_('Selected sales type has been deleted'));
      }
    } //end if sales type used in debtor transactions or in customers set up
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav         = Form::getPost('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result = Sales_Type::get_all(Form::hasPost('show_inactive'));
  Form::start();
  Table::start('tablestyle grid width30');
  $th = array(_('Type Name'), _('Factor'), _('Tax Incl'), '', '');
   Form::inactiveControlCol($th);
  Table::header($th);
  $k          = 0;
  $base_sales = DB_Company::get_base_sales_type();
  while ($myrow = DB::fetch($result)) {
    if ($myrow["id"] == $base_sales) {
      Row::start("class='overduebg'");
    } else {
    }
    Cell::label($myrow["sales_type"]);
    $f = Num::format($myrow["factor"], 4);
    if ($myrow["id"] == $base_sales) {
      $f = "<I>" . _('Base') . "</I>";
    }
    Cell::label($f);
    Cell::label($myrow["tax_included"] ? _('Yes') : _('No'), 'class=center');
     Form::inactiveControlCell($myrow["id"], $myrow["inactive"], 'sales_types', 'id');
    Form::buttonEditCell("Edit" . $myrow['id'], _("Edit"));
    Form::buttonDeleteCell("Delete" . $myrow['id'], _("Delete"));
    Row::end();
  }
   Form::inactiveControlRow($th);
  Table::end();
  Event::warning(_("Marked sales type is the company base pricelist for prices calculations."), 0, 0, "class='overduefg'");
  if (!isset($_POST['tax_included'])) {
    $_POST['tax_included'] = 0;
  }
  if (!isset($_POST['base'])) {
    $_POST['base'] = 0;
  }
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      $myrow                 = Sales_Type::get($selected_id);
      $_POST['sales_type']   = $myrow["sales_type"];
      $_POST['tax_included'] = $myrow["tax_included"];
      $_POST['factor']       = Num::format($myrow["factor"], 4);
    }
    Form::hidden('selected_id', $selected_id);
  } else {
    $_POST['factor'] = Num::format(1, 4);
  }
   Form::textRowEx(_("Sales Type Name") . ':', 'sales_type', 20);
   Form::AmountRow(_("Calculation factor") . ':', 'factor', null, null, null, 4);
   Form::checkRow(_("Tax included") . ':', 'tax_included', $_POST['tax_included']);
  Table::end(1);
  Form::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Form::end();
  Page::end();

