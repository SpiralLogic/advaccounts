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
    $sav = Input::post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result = Tax_Types::get_all(Forms::hasPost('show_inactive'));
  Forms::start();
  Event::warning(_("To avoid problems with manual journal entry all tax types should have unique Sales/Purchasing GL accounts."));
  Table::start('tablestyle grid');
  $th = array(
    _("Description"), _("Default Rate (%)"), _("Sales GL Account"), _("Purchasing GL Account"), "", ""
  );
   Forms::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {

    Cell::label($myrow["name"]);
    Cell::label(Num::percent_format($myrow["rate"]), "class='right'");
    Cell::label($myrow["sales_gl_code"] . "&nbsp;" . $myrow["SalesAccountName"]);
    Cell::label($myrow["purchasing_gl_code"] . "&nbsp;" . $myrow["PurchasingAccountName"]);
     Forms::inactiveControlCell($myrow["id"], $myrow["inactive"], 'tax_types', 'id');
    Forms::buttonEditCell("Edit" . $myrow["id"], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow["id"], _("Delete"));
    Row::end();
  }
   Forms::inactiveControlRow($th);
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
    Forms::hidden('selected_id', $selected_id);
  }
   Forms::textRowEx(_("Description:"), 'name', 50);
   Forms::SmallAmountRow(_("Default Rate:"), 'rate', '0', "", "%", User::percent_dec());
  GL_UI::all_row(_("Sales GL Account:"), 'sales_gl_code', NULL);
  GL_UI::all_row(_("Purchasing GL Account:"), 'purchasing_gl_code', NULL);
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();
