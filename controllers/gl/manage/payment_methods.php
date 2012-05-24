<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Page::start(_($help_context = "Payment Methods"), SA_BANKACCOUNT);
  list($Mode, $selected_id) = Page::simple_mode();
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    //initialise no input errors assumed initially before we test
    $input_error = 0;
    //first off validate inputs sensible
    if (Input::post('id')) {
      $input_error = 1;
      Event::error(_("The payment method cannot be empty."));
      JS::set_focus('name');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        GL_PaymentMethod::update($selected_id, $_POST['name'], $_POST['undeposited']);
        Event::success(_('Payment method has been updated'));
      }
      else {
        GL_PaymentMethod::add($_POST['name'], $_POST['undeposited']);
        Event::success(_('New payment method has been added'));
      }
      $Mode = MODE_RESET;
    }
  }
  elseif ($Mode == MODE_DELETE) {
    //the link to delete a selected record was clicked instead of the submit button
    $cancel_delete  = 0;
    $payment_method = DB::escape($selected_id);
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'bank_trans'
    $sql    = "SELECT COUNT(*) FROM payment_methods WHERE id=$payment_method";
    $result = DB::query($sql, "check failed");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      $cancel_delete = 1;
      Event::error(_("Cannot delete this payment method because transactions have been created using this account."));
    }
    $sql    = "SELECT COUNT(*) FROM bank_trans WHERE payment_method=$payment_method";
    $result = DB::query($sql, "check failed");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      $cancel_delete = 1;
      Event::error(_("Cannot delete this payment method because transactions have been created using this account."));
    }
    if (!$cancel_delete) {
      GL_PaymentMethod::delete($selected_id);
      Event::notice(_('Selected payment method has been deleted'));
    } //end if Delete bank account
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id          = -1;
    $_POST['undeposited'] = $_POST['name'] = '';
  }
  /* Always show the list of accounts */
  start_form();
  Table::start('tablestyle grid width80');
  $sql = "SELECT * FROM payment_methods";
  if (!check_value('show_inactive')) {
    $sql .= " AND !inactive";
  }
  $sql .= " ORDER BY name";
  $result = DB::query($sql, "could not get payment methods");
  $th     = array(_("Payment Method"), _("Goes To Undeposited"), '', '');
  inactive_control_column($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {

    Cell::label($myrow["name"], ' class="nowrap"');
    Cell::label($myrow["undeposited"], ' class="nowrap"');
    inactive_control_cell($myrow["id"], $myrow["inactive"], 'payment_methods', 'id');
    edit_button_cell("Edit" . $myrow["id"], _("Edit"));
    delete_button_cell("Delete" . $myrow["id"], _("Delete"));
    Row::end();
  }
  inactive_control_row($th);
  Table::end(1);
  $is_editing = $selected_id != -1;
  Table::start('tablestyle2');
  if ($is_editing) {
    if ($Mode == MODE_EDIT) {
      $myrow                = GL_PaymentMethod::get($selected_id);
      $_POST['name']        = $myrow["name"];
      $_POST['undeposited'] = $myrow["undeposited"];
      $_POST['inactive']    = $myrow["inactive"];
    }
    hidden('id', $selected_id);
    JS::set_focus('name');
  }
  text_row(_("Payment Method Name:"), 'name', NULL, 50, 100);
  yesno_list_row(_("Goes to Undeposited Funds:"), 'undeposited');
  Table::end(1);
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  Page::end();
