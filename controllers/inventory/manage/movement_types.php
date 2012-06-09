<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/


  Page::start(_($help_context = "Inventory Movement Types"), SA_INVENTORYMOVETYPE);
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    //initialise no input errors assumed initially before we test
    $input_error = 0;
    if (strlen($_POST['name']) == 0) {
      $input_error = 1;
      Event::error(_("The inventory movement type name cannot be empty."));
      JS::set_focus('name');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        Inv_Movement::update_type($selected_id, $_POST['name']);
        Event::success(_('Selected movement type has been updated'));
      }
      else {
        Inv_Movement::add_type($_POST['name']);
        Event::success(_('New movement type has been added'));
      }
      $Mode = MODE_RESET;
    }
  }
  /**
   * @param $selected_id
   *
   * @return bool
   */
  function can_delete($selected_id) {
    $sql = "SELECT COUNT(*) FROM stock_moves
		WHERE type=" . ST_INVADJUST . " AND person_id=" . DB::escape($selected_id);
    $result = DB::query($sql, "could not query stock moves");
    $myrow = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this inventory movement type because item transactions have been created referring to it."));
      return FALSE;
    }
    return TRUE;
  }

  if ($Mode == MODE_DELETE) {
    if (can_delete($selected_id)) {
      Inv_Movement::delete($selected_id);
      Event::notice(_('Selected movement type has been deleted'));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav = Input::post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result = Inv_Movement::get_all_types(Form::hasPost('show_inactive'));
  Form::start();
  Table::start('tablestyle grid width30');
  $th = array(_("Description"), "", "");
   Form::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {

    Cell::label($myrow["name"]);
     Form::inactiveControlCell($myrow["id"], $myrow["inactive"], 'movement_types', 'id');
    Form::buttonEditCell("Edit" . $myrow['id'], _("Edit"));
    Form::buttonDeleteCell("Delete" . $myrow['id'], _("Delete"));
    Row::end();
  }
   Form::inactiveControlRow($th);
  Table::end(1);
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing status code
      $myrow = Inv_Movement::get_type($selected_id);
      $_POST['name'] = $myrow["name"];
    }
    Form::hidden('selected_id', $selected_id);
  }
   Form::textRow(_("Description:"), 'name', NULL, 50, 50);
  Table::end(1);
  Form::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Form::end();
  Page::end();


