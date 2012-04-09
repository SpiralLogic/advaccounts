<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  Page::start(_($help_context = "GL Account Classes"), SA_GLACCOUNTCLASS);
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    if (can_process()) {
      if ($selected_id != -1) {
        if (GL_Class::update($selected_id, $_POST['name'], $_POST['ctype'])) {
          Event::success(_('Selected account class settings has been updated'));
        }
      }
      else {
        if (GL_Class::add($_POST['id'], $_POST['name'], $_POST['ctype'])) {
          Event::success(_('New account class has been added'));
          $Mode = MODE_RESET;
        }
      }
    }
  }
  if ($Mode == MODE_DELETE) {
    if (can_delete($selected_id)) {
      GL_Class::delete($selected_id);
      Event::notice(_('Selected account class has been deleted'));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $_POST['id'] = $_POST['name'] = $_POST['ctype'] = '';
  }
  $result = GL_Class::get_all(check_value('show_inactive'));
  start_form();
  start_table('tablestyle');
  $th = array(_("class ID"), _("class Name"), _("class Type"), "", "");
  inactive_control_column($th);
  table_header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {
    alt_table_row_color($k);
    label_cell($myrow["cid"]);
    label_cell($myrow['class_name']);
      label_cell($class_types[$myrow["ctype"]]);
    inactive_control_cell($myrow["cid"], $myrow["inactive"], 'chart_class', 'cid');
    edit_button_cell("Edit" . $myrow["cid"], _("Edit"));
    delete_button_cell("Delete" . $myrow["cid"], _("Delete"));
    end_row();
  }
  inactive_control_row($th);
  end_table(1);
  start_table('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing status code
      $myrow = GL_Class::get($selected_id);
      $_POST['id'] = $myrow["cid"];
      $_POST['name'] = $myrow["class_name"];
        $_POST['ctype'] = $myrow["ctype"];
      hidden('selected_id', $selected_id);
    }
    hidden('id');
    label_row(_("Class ID:"), $_POST['id']);
  }
  else {
    text_row_ex(_("Class ID:"), 'id', 3);
  }
  text_row_ex(_("Class Name:"), 'name', 50, 60);
    GL_Class::types_row(_("Class Type:"), 'ctype', NULL);
  end_table(1);
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  Page::end();
  /**
   * @return bool
   */
  function can_process() {
    if (!is_numeric($_POST['id'])) {
      Event::error(_("The account class ID must be numeric."));
      JS::set_focus('id');
      return FALSE;
    }
    if (strlen($_POST['name']) == 0) {
      Event::error(_("The account class name cannot be empty."));
      JS::set_focus('name');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @param $selected_id
   *
   * @return bool
   */
  function can_delete($selected_id) {
    if ($selected_id == -1) {
      return FALSE;
    }
    $sql = "SELECT COUNT(*) FROM chart_types
		WHERE class_id=$selected_id";
    $result = DB::query($sql, "could not query chart master");
    $myrow = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this account class because GL account types have been created referring to it."));
      return FALSE;
    }
    return TRUE;
  }
