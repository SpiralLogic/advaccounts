<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Page::start(_($help_context = "Item Tax Types"), SA_ITEMTAXTYPE);
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    $input_error = 0;
    if (strlen($_POST['name']) == 0) {
      $input_error = 1;
      Event::error(_("The item tax type description cannot be empty."));
      JS::set_focus('name');
    }
    if ($input_error != 1) {
      // create an array of the exemptions
      $exempt_from = array();
      $tax_types   = Tax_Types::get_all_simple();
      $i           = 0;
      while ($myrow = DB::fetch($tax_types)) {
        if (Form::hasPost('ExemptTax' . $myrow["id"])) {
          $exempt_from[$i] = $myrow["id"];
          $i++;
        }
      }
      if ($selected_id != -1) {
        Tax_ItemType::update($selected_id, $_POST['name'], $_POST['exempt'], $exempt_from);
        Event::success(_('Selected item tax type has been updated'));
      } else {
        Tax_ItemType::add($_POST['name'], $_POST['exempt'], $exempt_from);
        Event::success(_('New item tax type has been added'));
      }
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    Tax_ItemType::delete($selected_id);
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav         = Input::post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result2 = $result = Tax_ItemType::get_all(Form::hasPost('show_inactive'));
  Form::start();
  Table::start('tablestyle grid width30');
  $th = array(_("Name"), _("Tax exempt"), '', '');
   Form::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::fetch($result2)) {

    if ($myrow["exempt"] == 0) {
      $disallow_text = _("No");
    } else {
      $disallow_text = _("Yes");
    }
    Cell::label($myrow["name"]);
    Cell::label($disallow_text);
     Form::inactiveControlCell($myrow["id"], $myrow["inactive"], 'item_tax_types', 'id');
    Form::buttonEditCell("Edit" . $myrow["id"], _("Edit"));
    Form::buttonDeleteCell("Delete" . $myrow["id"], _("Delete"));
    Row::end();
  }
   Form::inactiveControlRow($th);
  Table::end(1);
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      $myrow = Tax_ItemType::get($selected_id);
      unset($_POST); // clear exemption checkboxes
      $_POST['name']   = $myrow["name"];
      $_POST['exempt'] = $myrow["exempt"];
      // read the exemptions and check the ones that are on
      $exemptions = Tax_ItemType::get_exemptions($selected_id);
      if (DB::num_rows($exemptions) > 0) {
        while ($exmp = DB::fetch($exemptions)) {
          $_POST['ExemptTax' . $exmp["tax_type_id"]] = 1;
        }
      }
    }
    Form::hidden('selected_id', $selected_id);
  }
   Form::textRowEx(_("Description:"), 'name', 50);
   Form::yesnoListRow(_("Is Fully Tax-exempt:"), 'exempt', NULL, "", "", TRUE);
  Table::end(1);
  if (!isset($_POST['exempt']) || $_POST['exempt'] == 0) {
    Event::warning(_("Select which taxes this item tax type is exempt from."), 0, 1);
    Table::start('tablestyle2 grid');
    $th = array(_("Tax Name"), _("Rate"), _("Is exempt"));
    Table::header($th);
    $tax_types = Tax_Types::get_all_simple();
    while ($myrow = DB::fetch($tax_types)) {

      Cell::label($myrow["name"]);
      Cell::label(Num::percent_format($myrow["rate"]) . " %", ' class="right nowrap"');
       Form::checkCells("", 'ExemptTax' . $myrow["id"], NULL);
      Row::end();
    }
    Table::end(1);
  }
  Form::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Form::end();
  Page::end();
/**
 * @param $selected_id
 *
 * @return bool
 */



