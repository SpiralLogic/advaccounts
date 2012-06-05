<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Page::start(_($help_context = "Tax Types"), SA_TAXRATES);
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  if ($Mode == ADD_ITEM && Tax_Types::can_process($selected_id)) {
    Tax_Types::add($_POST['name'], $_POST['sales_gl_code'], $_POST['purchasing_gl_code'], Validation::input_num('rate', 0));
    Event::success(_('New tax type has been added'));
    $Mode = MODE_RESET;
  }
  if ($Mode == UPDATE_ITEM && Tax_Types::can_process($selected_id)) {
    Tax_Types::update($selected_id, $_POST['name'], $_POST['sales_gl_code'], $_POST['purchasing_gl_code'], Validation::input_num('rate'));
    Event::success(_('Selected tax type has been updated'));
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_DELETE) {
    Tax_Types::delete($selected_id);
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav = Form::getPost('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result = Tax_Types::get_all(Form::hasPost('show_inactive'));
  Form::start();
  Event::warning(_("To avoid problems with manual journal entry all tax types should have unique Sales/Purchasing GL accounts."));
  Table::start('tablestyle grid');
  $th = array(
    _("Description"), _("Default Rate (%)"), _("Sales GL Account"), _("Purchasing GL Account"), "", ""
  );
   Form::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {

    Cell::label($myrow["name"]);
    Cell::label(Num::percent_format($myrow["rate"]), "class='right'");
    Cell::label($myrow["sales_gl_code"] . "&nbsp;" . $myrow["SalesAccountName"]);
    Cell::label($myrow["purchasing_gl_code"] . "&nbsp;" . $myrow["PurchasingAccountName"]);
     Form::inactiveControlCell($myrow["id"], $myrow["inactive"], 'tax_types', 'id');
    Form::buttonEditCell("Edit" . $myrow["id"], _("Edit"));
    Form::buttonDeleteCell("Delete" . $myrow["id"], _("Delete"));
    Row::end();
  }
   Form::inactiveControlRow($th);
  Table::end(1);
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing status code
      $myrow = Tax_Types::get($selected_id);
      $_POST['name'] = $myrow["name"];
      $_POST['rate'] = Num::percent_format($myrow["rate"]);
      $_POST['sales_gl_code'] = $myrow["sales_gl_code"];
      $_POST['purchasing_gl_code'] = $myrow["purchasing_gl_code"];
    }
    Form::hidden('selected_id', $selected_id);
  }
   Form::textRowEx(_("Description:"), 'name', 50);
   Form::SmallAmountRow(_("Default Rate:"), 'rate', '0', "", "%", User::percent_dec());
  GL_UI::all_row(_("Sales GL Account:"), 'sales_gl_code', NULL);
  GL_UI::all_row(_("Purchasing GL Account:"), 'purchasing_gl_code', NULL);
  Table::end(1);
  Form::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Form::end();
  Page::end();
