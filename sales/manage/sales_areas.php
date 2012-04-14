<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  //require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  Page::start(_($help_context = "Sales Areas"), SA_SALESAREA);
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
        $sql = "UPDATE areas SET description=" . DB::escape($_POST['description']) . " WHERE area_code = " . DB::escape($selected_id);
        $note = _('Selected sales area has been updated');
      }
      else {
        $sql = "INSERT INTO areas (description) VALUES (" . DB::escape($_POST['description']) . ")";
        $note = _('New sales area has been added');
      }
      DB::query($sql, "The sales area could not be updated or added");
      Event::success($note);
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    $cancel_delete = 0;
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'debtors'
    $sql = "SELECT COUNT(*) FROM branches WHERE area=" . DB::escape($selected_id);
    $result = DB::query($sql, "check failed");
    $myrow = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      $cancel_delete = 1;
      Event::error(_("Cannot delete this area because customer branches have been created using this area."));
    }
    if ($cancel_delete == 0) {
      $sql = "DELETE FROM areas WHERE area_code=" . DB::escape($selected_id);
      DB::query($sql, "could not delete sales area");
      Event::notice(_('Selected sales area has been deleted'));
    } //end if Delete area
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav = get_post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $sql = "SELECT * FROM areas";
  if (!check_value('show_inactive')) {
    $sql .= " WHERE !inactive";
  }
  $result = DB::query($sql, "could not get areas");
  start_form();
  start_table('tablestyle width30');
  $th = array(_("Area Name"), "", "");
  inactive_control_column($th);
  table_header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {
    alt_table_row_color($k);
    label_cell($myrow["description"]);
    inactive_control_cell($myrow["area_code"], $myrow["inactive"], 'areas', 'area_code');
    edit_button_cell("Edit" . $myrow["area_code"], _("Edit"));
    delete_button_cell("Delete" . $myrow["area_code"], _("Delete"));
    end_row();
  }
  inactive_control_row($th);
  end_table();
  echo '<br>';
  start_table('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing area
      $sql = "SELECT * FROM areas WHERE area_code=" . DB::escape($selected_id);
      $result = DB::query($sql, "could not get area");
      $myrow = DB::fetch($result);
      $_POST['description'] = $myrow["description"];
    }
    hidden("selected_id", $selected_id);
  }
  text_row_ex(_("Area Name:"), 'description', 30);
  end_table(1);
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  Page::end();

