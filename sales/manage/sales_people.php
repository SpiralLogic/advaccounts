<?php
  /**********************************************************************
  Copyright (C) Advanced Group PTY LTD
  Released under the terms of the GNU General Public License, GPL,
  as published by the Free Software Foundation, either version 3
  of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
   ***********************************************************************/
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
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
    $pr1 = Validation::is_num('provision', 0, 100);
    if (!$pr1 || !Validation::is_num('provision2', 0, 100)) {
      $input_error = 1;
      Event::error(_("Salesman provision cannot be less than 0 or more than 100%."));
      JS::set_focus(!$pr1 ? 'provision' : 'provision2');
    }
    if (!Validation::is_num('break_pt', 0)) {
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
    $sav = get_post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $sql = "SELECT s.*,u.user_id,u.id FROM salesman s, users u WHERE s.user_id=u.id";
  if (!check_value('show_inactive')) {
    $sql .= " AND !s.inactive";
  }
  $result = DB::query($sql, "could not get sales persons");
  start_form();
  start_table('tablestyle nowrap width80');
  $th = array(
    _("Name"), _("User"), _("Phone"), _("Fax"), _("Email"), _("Provision"), _("Break Pt."), _("Provision") . " 2", "", ""
  );
  inactive_control_column($th);
  table_header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {
    alt_table_row_color($k);
    label_cell($myrow["salesman_name"]);
    label_cell($myrow["user_id"]);
    label_cell($myrow["salesman_phone"]);
    label_cell($myrow["salesman_fax"]);
    email_cell($myrow["salesman_email"]);
    label_cell(Num::percent_format($myrow["provision"]) . " %", ' class="right nowrap"');
    amount_cell($myrow["break_pt"]);
    label_cell(Num::percent_format($myrow["provision2"]) . " %", ' class="right nowrap"');
    inactive_control_cell($myrow["salesman_code"], $myrow["inactive"], 'salesman', 'salesman_code');
    edit_button_cell("Edit" . $myrow["salesman_code"], _("Edit"));
    delete_button_cell("Delete" . $myrow["salesman_code"], _("Delete"));
    end_row();
  } //END WHILE LIST LOOP
  inactive_control_row($th);
  end_table();
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
    hidden('selected_id', $selected_id);
  }
  elseif ($Mode != ADD_ITEM) {
    $_POST['provision'] = Num::percent_format(0);
    $_POST['break_pt'] = Num::price_format(0);
    $_POST['provision2'] = Num::percent_format(0);
  }
  start_table('tablestyle2');
  Users::row(_('User:'), 'user_id');
  text_row_ex(_("Sales person name:"), 'salesman_name', 30);
  text_row_ex(_("Telephone number:"), 'salesman_phone', 20);
  text_row_ex(_("Fax number:"), 'salesman_fax', 20);
  email_row_ex(_("E-mail:"), 'salesman_email', 40);
  percent_row(_("Provision") . ':', 'provision');
  amount_row(_("Break Pt.:"), 'break_pt');
  percent_row(_("Provision") . " 2:", 'provision2');
  end_table(1);
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  Page::end();

?>
