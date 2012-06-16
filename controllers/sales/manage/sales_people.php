<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Page::start(_($help_context = "Sales Persons"), SA_SALESMAN);
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    //initialise no input errors assumed initially before we test
    $input_error = 0;
    if (strlen($_POST['salesman_name']) == 0) {
      $input_error = 1;
      Event::error(_("The sales person name cannot be empty."));
      JS::set_focus('salesman_name');
    }
    $pr1 = Validation::post_num('provision', 0, 100);
    if (!$pr1 || !Validation::post_num('provision2', 0, 100)) {
      $input_error = 1;
      Event::error(_("Salesman provision cannot be less than 0 or more than 100%."));
      JS::set_focus(!$pr1 ? 'provision' : 'provision2');
    }
    if (!Validation::post_num('break_pt', 0)) {
      $input_error = 1;
      Event::error(_("Salesman provision breakpoint must be numeric and not less than 0."));
      JS::set_focus('break_pt');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        /*selected_id could also exist if submit had not been clicked this code would not run in this case cos submit is false of course see the delete code below*/
        $sql = "UPDATE salesman SET salesman_name=" . DB::escape($_POST['salesman_name']) . ",
 			user_id=" . DB::escape($_POST['user_id']) . ",
 			salesman_phone=" . DB::escape($_POST['salesman_phone']) . ",
 			salesman_fax=" . DB::escape($_POST['salesman_fax']) . ",
 			salesman_email=" . DB::escape($_POST['salesman_email']) . ",
 			provision=" . Validation::input_num('provision') . ",
 			break_pt=" . Validation::input_num('break_pt') . ",
 			provision2=" . Validation::input_num('provision2') . "
 			WHERE salesman_code = " . DB::escape($selected_id);
      }
      else {
        /*Selected group is null cos no item selected on first time round so must be adding a record must be submitting new entries in the new Sales-person form */
        $sql
          = "INSERT INTO salesman (salesman_name, user_id, salesman_phone, salesman_fax, salesman_email,
 			provision, break_pt, provision2)
 			VALUES (" . DB::escape($_POST['salesman_name']) . ", " . DB::escape($_POST['user_id']) . ", " . DB::escape($_POST['salesman_phone']) . ", " . DB::escape($_POST['salesman_fax']) . ", " . DB::escape($_POST['salesman_email']) . ", " . Validation::input_num('provision') . ", " . Validation::input_num('break_pt') . ", " . Validation::input_num('provision2') . ")";
      }
      //run the sql from either of the above possibilites
      DB::query($sql, "The insert or update of the sales person failed");
      if ($selected_id != -1) {
        Event::success(_('Selected sales person data have been updated'));
      }
      else {
        Event::success(_('New sales person data have been added'));
      }
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    //the link to delete a selected record was clicked instead of the submit button
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'debtors'
    $sql = "SELECT COUNT(*) FROM branches WHERE salesman=" . DB::escape($selected_id);
    $result = DB::query($sql, "check failed");
    $myrow = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error("Cannot delete this sales-person because branches are set up referring to this sales-person - first alter the branches concerned.");
    }
    else {
      $sql = "DELETE FROM salesman WHERE salesman_code=" . DB::escape($selected_id);
      DB::query($sql, "The sales-person could not be deleted");
      Event::notice(_('Selected sales person data have been deleted'));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav = Input::post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $sql = "SELECT s.*,u.user_id,u.id FROM salesman s, users u WHERE s.user_id=u.id";
  if (!Forms::hasPost('show_inactive')) {
    $sql .= " AND !s.inactive";
  }
  $result = DB::query($sql, "could not get sales persons");
  Forms::start();
  Table::start('tablestyle grid nowrap width80');
  $th = array(
    _("Name"), _("User"), _("Phone"), _("Fax"), _("Email"), _("Provision"), _("Break Pt."), _("Provision") . " 2", "", ""
  );
   Forms::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {

    Cell::label($myrow["salesman_name"]);
    Cell::label($myrow["user_id"]);
    Cell::label($myrow["salesman_phone"]);
    Cell::label($myrow["salesman_fax"]);
    Cell::email($myrow["salesman_email"]);
    Cell::label(Num::percent_format($myrow["provision"]) . " %", ' class="right nowrap"');
    Cell::amount($myrow["break_pt"]);
    Cell::label(Num::percent_format($myrow["provision2"]) . " %", ' class="right nowrap"');
     Forms::inactiveControlCell($myrow["salesman_code"], $myrow["inactive"], 'salesman', 'salesman_code');
    Forms::buttonEditCell("Edit" . $myrow["salesman_code"], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow["salesman_code"], _("Delete"));
    Row::end();
  } //END WHILE LIST LOOP
   Forms::inactiveControlRow($th);
  Table::end();
  echo '<br>';
  $_POST['salesman_email'] = "";
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing Sales-person
      $sql = "SELECT * FROM salesman WHERE salesman_code=" . DB::escape($selected_id);
      $result = DB::query($sql, "could not get sales person");
      $myrow = DB::fetch($result);
      $_POST['user_id'] = $myrow["user_id"];
      $_POST['salesman_name'] = $myrow["salesman_name"];
      $_POST['salesman_phone'] = $myrow["salesman_phone"];
      $_POST['salesman_fax'] = $myrow["salesman_fax"];
      $_POST['salesman_email'] = $myrow["salesman_email"];
      $_POST['provision'] = Num::percent_format($myrow["provision"]);
      $_POST['break_pt'] = Num::price_format($myrow["break_pt"]);
      $_POST['provision2'] = Num::percent_format($myrow["provision2"]);
    }
    Forms::hidden('selected_id', $selected_id);
  }
  elseif ($Mode != ADD_ITEM) {
    $_POST['provision'] = Num::percent_format(0);
    $_POST['break_pt'] = Num::price_format(0);
    $_POST['provision2'] = Num::percent_format(0);
  }
  Table::start('tablestyle2');
  Users::row(_('User:'), 'user_id');
   Forms::textRowEx(_("Sales person name:"), 'salesman_name', 30);
   Forms::textRowEx(_("Telephone number:"), 'salesman_phone', 20);
   Forms::textRowEx(_("Fax number:"), 'salesman_fax', 20);
   Forms::emailRowEx(_("E-mail:"), 'salesman_email', 40);
   Forms::percentRow(_("Provision") . ':', 'provision');
   Forms::AmountRow(_("Break Pt.:"), 'break_pt');
   Forms::percentRow(_("Provision") . " 2:", 'provision2');
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();


