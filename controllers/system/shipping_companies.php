<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Shipping Company"), SA_SHIPPING);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM && can_process()) {
    $sql
      = "INSERT INTO shippers (shipper_name, contact, phone, phone2, address)
        VALUES (" . DB::escape($_POST['shipper_name']) . ", " . DB::escape($_POST['contact']) . ", " . DB::escape($_POST['phone']) . ", " . DB::escape($_POST['phone2']) . ", " . DB::escape($_POST['address']) . ")";
    DB::query($sql, "The Shipping Company could not be added");
    Event::success(_('New shipping company has been added'));
    $Mode = MODE_RESET;
  }
  if ($Mode == UPDATE_ITEM && can_process()) {
    $sql = "UPDATE shippers SET shipper_name=" . DB::escape($_POST['shipper_name']) . " ,
        contact =" . DB::escape($_POST['contact']) . " ,
        phone =" . DB::escape($_POST['phone']) . " ,
        phone2 =" . DB::escape($_POST['phone2']) . " ,
        address =" . DB::escape($_POST['address']) . "
        WHERE shipper_id = " . DB::escape($selected_id);
    DB::query($sql, "The shipping company could not be updated");
    Event::success(_('Selected shipping company has been updated'));
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_DELETE) {
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'sales_orders'
    $sql    = "SELECT COUNT(*) FROM sales_orders WHERE ship_via=" . DB::escape($selected_id);
    $result = DB::query($sql, "check failed");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      $cancel_delete = 1;
      Event::error(_("Cannot delete this shipping company because sales orders have been created using this shipper."));
    } else {
      // PREVENT DELETES IF DEPENDENT RECORDS IN 'debtor_trans'
      $sql    = "SELECT COUNT(*) FROM debtor_trans WHERE ship_via=" . DB::escape($selected_id);
      $result = DB::query($sql, "check failed");
      $myrow  = DB::fetch_row($result);
      if ($myrow[0] > 0) {
        $cancel_delete = 1;
        Event::error(_("Cannot delete this shipping company because invoices have been created using this shipping company."));
      } else {
        $sql = "DELETE FROM shippers WHERE shipper_id=" . DB::escape($selected_id);
        DB::query($sql, "could not delete shipper");
        Event::notice(_('Selected shipping company has been deleted'));
      }
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav         = Input::post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $sql = "SELECT * FROM shippers";
  if (!Form::hasPost('show_inactive')) {
    $sql .= " WHERE !inactive";
  }
  $sql .= " ORDER BY shipper_id";
  $result = DB::query($sql, "could not get shippers");
  Form::start();
  Table::start('tablestyle grid');
  $th = array(_("Name"), _("Contact Person"), _("Phone Number"), _("Secondary Phone"), _("Address"), "", "");
   Form::inactiveControlCol($th);
  Table::header($th);
  $k = 0; //row colour counter
  while ($myrow = DB::fetch($result)) {
    Cell::label($myrow["shipper_name"]);
    Cell::label($myrow["contact"]);
    Cell::label($myrow["phone"]);
    Cell::label($myrow["phone2"]);
    Cell::label($myrow["address"]);
     Form::inactiveControlCell($myrow["shipper_id"], $myrow["inactive"], 'shippers', 'shipper_id');
    Form::buttonEditCell("Edit" . $myrow["shipper_id"], _("Edit"));
    Form::buttonDeleteCell("Delete" . $myrow["shipper_id"], _("Delete"));
    Row::end();
  }
   Form::inactiveControlRow($th);
  Table::end(1);
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing Shipper
      $sql                   = "SELECT * FROM shippers WHERE shipper_id=" . DB::escape($selected_id);
      $result                = DB::query($sql, "could not get shipper");
      $myrow                 = DB::fetch($result);
      $_POST['shipper_name'] = $myrow["shipper_name"];
      $_POST['contact']      = $myrow["contact"];
      $_POST['phone']        = $myrow["phone"];
      $_POST['phone2']       = $myrow["phone2"];
      $_POST['address']      = $myrow["address"];
    }
    Form::hidden('selected_id', $selected_id);
  }
   Form::textRowEx(_("Name:"), 'shipper_name', 40);
   Form::textRowEx(_("Contact Person:"), 'contact', 30);
   Form::textRowEx(_("Phone Number:"), 'phone', 32, 30);
   Form::textRowEx(_("Secondary Phone Number:"), 'phone2', 32, 30);
   Form::textRowEx(_("Address:"), 'address', 50);
  Table::end(1);
  Form::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Form::end();
  Page::end();
  /**
   * @return bool
   */
  function can_process()
  {
    if (strlen($_POST['shipper_name']) == 0) {
      Event::error(_("The shipping company name cannot be empty."));
      JS::set_focus('shipper_name');

      return false;
    }

    return true;
  }

