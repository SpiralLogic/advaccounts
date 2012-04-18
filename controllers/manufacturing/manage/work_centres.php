<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/


  Page::start(_($help_context = "Work Centres"), SA_WORKCENTRES);
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    //initialise no input errors assumed initially before we test
    $input_error = 0;
    if (strlen($_POST['name']) == 0) {
      $input_error = 1;
      Event::error(_("The work centre name cannot be empty."));
      JS::set_focus('name');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        WO_WorkCentre::update($selected_id, $_POST['name'], $_POST['description']);
        Event::success(_('Selected work center has been updated'));
      }
      else {
        WO_WorkCentre::add($_POST['name'], $_POST['description']);
        Event::success(_('New work center has been added'));
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
    $sql = "SELECT COUNT(*) FROM bom WHERE workcentre_added=" . DB::escape($selected_id);
    $result = DB::query($sql, "check can delete work centre");
    $myrow = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this work centre because BOMs have been created referring to it."));
      return FALSE;
    }
    $sql = "SELECT COUNT(*) FROM wo_requirements WHERE workcentre=" . DB::escape($selected_id);
    $result = DB::query($sql, "check can delete work centre");
    $myrow = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this work centre because work order requirements have been created referring to it."));
      return FALSE;
    }
    return TRUE;
  }

  if ($Mode == MODE_DELETE) {
    if (can_delete($selected_id)) {
      WO_WorkCentre::delete($selected_id);
      Event::notice(_('Selected work center has been deleted'));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav = get_post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result = WO_WorkCentre::get_all(check_value('show_inactive'));
  start_form();
  start_table('tablestyle width50');
  $th = array(_("Name"), _("description"), "", "");
  inactive_control_column($th);
  table_header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {
    alt_table_row_color($k);
    label_cell($myrow["name"]);
    label_cell($myrow["description"]);
    inactive_control_cell($myrow["id"], $myrow["inactive"], 'workcentres', 'id');
    edit_button_cell("Edit" . $myrow['id'], _("Edit"));
    delete_button_cell("Delete" . $myrow['id'], _("Delete"));
    end_row();
  }
  inactive_control_row($th);
  end_table(1);
  start_table('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing status code
      $myrow = WO_WorkCentre::get($selected_id);
      $_POST['name'] = $myrow["name"];
      $_POST['description'] = $myrow["description"];
    }
    hidden('selected_id', $selected_id);
  }
  text_row_ex(_("Name:"), 'name', 40);
  text_row_ex(_("Description:"), 'description', 50);
  end_table(1);
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  Page::end();


