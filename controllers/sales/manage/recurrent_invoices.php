<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  JS::open_window(900, 600);
  Page::start(_($help_context = "Recurrent Invoices"), SA_SRECURRENT);
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    $input_error = 0;
    if (strlen($_POST['description']) == 0) {
      $input_error = 1;
      Event::error(_("The area description cannot be empty."));
      JS::set_focus('description');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        $sql
          = "UPDATE recurrent_invoices SET
 			description=" . DB::escape($_POST['description']) . ",
 			order_no=" . DB::escape($_POST['order_no']) . ",
 			debtor_id=" . DB::escape($_POST['debtor_id']) . ",
 			group_no=" . DB::escape($_POST['group_no']) . ",
 			days=" . Validation::input_num('days', 0) . ",
 			monthly=" . Validation::input_num('monthly', 0) . ",
 			begin='" . Dates::date2sql($_POST['begin']) . "',
 			end='" . Dates::date2sql($_POST['end']) . "'
 			WHERE id = " . DB::escape($selected_id);
        $note = _('Selected recurrent invoice has been updated');
      }
      else {
        $sql
          = "INSERT INTO recurrent_invoices (description, order_no, debtor_id,
 			group_no, days, monthly, begin, end, last_sent) VALUES (" . DB::escape($_POST['description']) . ", " . DB::escape($_POST['order_no']) . ", " . DB::escape($_POST['debtor_id']) . ", " . DB::escape($_POST['group_no']) . ", " . Validation::input_num('days',
          0) . ", " . Validation::input_num('monthly', 0) . ", '" . Dates::date2sql($_POST['begin']) . "', '" . Dates::date2sql($_POST['end']) . "', '" . Dates::date2sql(Add_Years($_POST['begin'], -5)) . "')";
        $note = _('New recurrent invoice has been added');
      }
      DB::query($sql, "The recurrent invoice could not be updated or added");
      Event::notice($note);
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    $cancel_delete = 0;
    if ($cancel_delete == 0) {
      $sql = "DELETE FROM recurrent_invoices WHERE id=" . DB::escape($selected_id);
      DB::query($sql, "could not delete recurrent invoice");
      Event::notice(_('Selected recurrent invoice has been deleted'));
    } //end if Delete area
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    unset($_POST);
  }
  $sql = "SELECT * FROM recurrent_invoices ORDER BY description, group_no, debtor_id";
  $result = DB::query($sql, "could not get recurrent invoices");
  start_form();
  Table::start('tablestyle grid width70');
  $th = array(
    _("Description"), _("Template No"), _("Customer"), _("Branch") . "/" . _("Group"), _("Days"), _("Monthly"), _("Begin"),
    _("End"), _("Last Created"), "", ""
  );
  Table::header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {
    $begin = Dates::sql2date($myrow["begin"]);
    $end = Dates::sql2date($myrow["end"]);
    $last_sent = Dates::sql2date($myrow["last_sent"]);

    Cell::label($myrow["description"]);
    Cell::label(Debtor::trans_view(ST_SALESORDER, $myrow["order_no"]));
    if ($myrow["debtor_id"] == 0) {
      Cell::label("");
      Cell::label(Sales_Group::get_name($myrow["group_no"]));
    }
    else {
      Cell::label(Debtor::get_name($myrow["debtor_id"]));
      Cell::label(Sales_Branch::get_name($myrow['group_no']));
    }
    Cell::label($myrow["days"]);
    Cell::label($myrow['monthly']);
    Cell::label($begin);
    Cell::label($end);
    Cell::label($last_sent);
    edit_button_cell("Edit" . $myrow["id"], _("Edit"));
    delete_button_cell("Delete" . $myrow["id"], _("Delete"));
    Row::end();
  }
  Table::end();
  end_form();
  echo '<br>';
  start_form();
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing area
      $sql = "SELECT * FROM recurrent_invoices WHERE id=" . DB::escape($selected_id);
      $result = DB::query($sql, "could not get recurrent invoice");
      $myrow = DB::fetch($result);
      $_POST['description'] = $myrow["description"];
      $_POST['order_no'] = $myrow["order_no"];
      $_POST['debtor_id'] = $myrow["debtor_id"];
      $_POST['group_no'] = $myrow["group_no"];
      $_POST['days'] = $myrow["days"];
      $_POST['monthly'] = $myrow["monthly"];
      $_POST['begin'] = Dates::sql2date($myrow["begin"]);
      $_POST['end'] = Dates::sql2date($myrow["end"]);
    }
    hidden("selected_id", $selected_id);
  }
  text_row_ex(_("Description:"), 'description', 50);
  Sales_UI::templates_row(_("Template:"), 'order_no');
  Debtor::row(_("Customer:"), 'debtor_id', NULL, " ", TRUE);
  if ($_POST['debtor_id'] > 0) {
    Debtor_Branch::row(_("Branch:"), $_POST['debtor_id'], 'group_no', NULL, FALSE);
  }
  else {
    Sales_UI::groups_row(_("Sales Group:"), 'group_no', NULL, " ");
  }
  small_amount_row(_("Days:"), 'days', 0, NULL, NULL, 0);
  small_amount_row(_("Monthly:"), 'monthly', 0, NULL, NULL, 0);
  date_row(_("Begin:"), 'begin');
  date_row(_("End:"), 'end', NULL, NULL, 0, 0, 5);
  Table::end(1);
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  Page::end();
