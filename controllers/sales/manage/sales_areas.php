<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Page::start(_($help_context = "Sales Areas"), SA_SALESAREA);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    $input_error = 0;
    if (strlen($_POST['description']) == 0) {
      $input_error = 1;
      Event::error(_("The area description cannot be empty."));
      JS::_setFocus('description');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        $sql  = "UPDATE areas SET description=" . DB::_escape($_POST['description']) . " WHERE area_code = " . DB::_escape($selected_id);
        $note = _('Selected sales area has been updated');
      } else {
        $sql  = "INSERT INTO areas (description) VALUES (" . DB::_escape($_POST['description']) . ")";
        $note = _('New sales area has been added');
      }
      DB::_query($sql, "The sales area could not be updated or added");
      Event::success($note);
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    $cancel_delete = 0;
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'debtors'
    $sql    = "SELECT COUNT(*) FROM branches WHERE area=" . DB::_escape($selected_id);
    $result = DB::_query($sql, "check failed");
    $myrow  = DB::_fetchRow($result);
    if ($myrow[0] > 0) {
      $cancel_delete = 1;
      Event::error(_("Cannot delete this area because customer branches have been created using this area."));
    }
    if ($cancel_delete == 0) {
      $sql = "DELETE FROM areas WHERE area_code=" . DB::_escape($selected_id);
      DB::_query($sql, "could not delete sales area");
      Event::notice(_('Selected sales area has been deleted'));
    } //end if Delete area
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav         = Input::_post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $sql = "SELECT * FROM areas";
  if (!Input::_hasPost('show_inactive')) {
    $sql .= " WHERE !inactive";
  }
  $result = DB::_query($sql, "could not get areas");
  Forms::start();
  Table::start('tablestyle grid width30');
  $th = array(_("Area Name"), "", "");
  Forms::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::_fetch($result)) {
    Cell::label($myrow["description"]);
    Forms::inactiveControlCell($myrow["area_code"], $myrow["inactive"], 'areas', 'area_code');
    Forms::buttonEditCell("Edit" . $myrow["area_code"], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow["area_code"], _("Delete"));
    Row::end();
  }
  Forms::inactiveControlRow($th);
  Table::end();
  echo '<br>';
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing area
      $sql                  = "SELECT * FROM areas WHERE area_code=" . DB::_escape($selected_id);
      $result               = DB::_query($sql, "could not get area");
      $myrow                = DB::_fetch($result);
      $_POST['description'] = $myrow["description"];
    }
    Forms::hidden("selected_id", $selected_id);
  }
  Forms::textRowEx(_("Area Name:"), 'description', 30);
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();

