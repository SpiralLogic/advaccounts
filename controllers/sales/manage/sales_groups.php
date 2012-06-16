<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Page::start(_($help_context = "Sales Groups"), SA_SALESGROUP);
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
        $sql = "UPDATE groups SET description=" . DB::escape($_POST['description']) . " WHERE id = " . DB::escape($selected_id);
        $note = _('Selected sales group has been updated');
      }
      else {
        $sql = "INSERT INTO groups (description) VALUES (" . DB::escape($_POST['description']) . ")";
        $note = _('New sales group has been added');
      }
      DB::query($sql, "The sales group could not be updated or added");
      Event::success($note);
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    $cancel_delete = 0;
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'debtors'
    $sql = "SELECT COUNT(*) FROM branches WHERE group_no=" . DB::escape($selected_id);
    $result = DB::query($sql, "check failed");
    $myrow = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      $cancel_delete = 1;
      Event::error(_("Cannot delete this group because customers have been created using this group."));
    }
    if ($cancel_delete == 0) {
      $sql = "DELETE FROM groups WHERE id=" . DB::escape($selected_id);
      DB::query($sql, "could not delete sales group");
      Event::notice(_('Selected sales group has been deleted'));
    } //end if Delete area
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav = Input::post('show_inactive');
    unset($_POST);
    if ($sav) {
      $_POST['show_inactive'] = 1;
    }
  }
  $sql = "SELECT * FROM groups";
  if (!Forms::hasPost('show_inactive')) {
    $sql .= " WHERE !inactive";
  }
  $sql .= " ORDER BY description";
  $result = DB::query($sql, "could not get groups");
  Forms::start();
  Table::start('tablestyle grid width30');
  $th = array(_("Group Name"), "", "");
   Forms::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {

    Cell::label($myrow["description"]);
     Forms::inactiveControlCell($myrow["id"], $myrow["inactive"], 'groups', 'id');
    Forms::buttonEditCell("Edit" . $myrow["id"], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow["id"], _("Delete"));
    Row::end();
  }
   Forms::inactiveControlRow($th);
  Table::end();
  echo '<br>';
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing area
      $sql = "SELECT * FROM groups WHERE id=" . DB::escape($selected_id);
      $result = DB::query($sql, "could not get group");
      $myrow = DB::fetch($result);
      $_POST['description'] = $myrow["description"];
    }
    Forms::hidden("selected_id", $selected_id);
  }
   Forms::textRowEx(_("Group Name:"), 'description', 30);
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();

