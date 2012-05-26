<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "GL Account Groups"), SA_GLACCOUNTGROUP);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    if (can_process($selected_id)) {
      if ($selected_id != -1) {
        if (GL_Type::update($selected_id, $_POST['name'], $_POST['class_id'], $_POST['parent'])) {
          Event::success(_('Selected account type has been updated'));
        }
      } else {
        if (GL_Type::add($_POST['id'], $_POST['name'], $_POST['class_id'], $_POST['parent'])) {
          Event::success(_('New account type has been added'));
          $Mode = MODE_RESET;
        }
      }
    }
  }
  if ($Mode == MODE_DELETE) {
    if (can_delete($selected_id)) {
      GL_Type::delete($selected_id);
      Event::notice(_('Selected account group has been deleted'));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $_POST['id'] = $_POST['name'] = '';
    unset($_POST['parent'], $_POST['class_id']);
  }
  $result = GL_Type::get_all(check_value('show_inactive'));
  start_form();
  Table::start('tablestyle grid');
  $th = array(_("ID"), _("Name"), _("Subgroup Of"), _("Class Type"), "", "");
  inactive_control_column($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {
    $bs_text = GL_Class::get_name($myrow["class_id"]);
    if ($myrow["parent"] == ANY_NUMERIC) {
      $parent_text = "";
    } else {
      $parent_text = GL_Type::get_name($myrow["parent"]);
    }
    Cell::label($myrow["id"]);
    Cell::label($myrow["name"]);
    Cell::label($parent_text);
    Cell::label($bs_text);
    inactive_control_cell($myrow["id"], $myrow["inactive"], 'chart_types', 'id');
    edit_button_cell("Edit" . $myrow["id"], _("Edit"));
    delete_button_cell("Delete" . $myrow["id"], _("Delete"));
    Row::end();
  }
  inactive_control_row($th);
  Table::end(1);
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing status code
      $myrow             = GL_Type::get($selected_id);
      $_POST['id']       = $myrow["id"];
      $_POST['name']     = $myrow["name"];
      $_POST['parent']   = $myrow["parent"];
      $_POST['class_id'] = $myrow["class_id"];
      hidden('selected_id', $selected_id);
    }
    hidden('id');
    Row::label(_("ID:"), $_POST['id']);
  } else {
    text_row_ex(_("ID:"), 'id', 10);
  }
  text_row_ex(_("Name:"), 'name', 50);
  GL_Type::row(_("Subgroup Of:"), 'parent', null, _("None"), true);
  GL_Class::row(_("Class Type:"), 'class_id', null);
  Table::end(1);
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  Page::end();
  /**
   * @param $selected_id
   *
   * @return bool
   */
  function can_delete($selected_id)
  {
    if ($selected_id == -1) {
      return false;
    }
    $type = DB::escape($selected_id);
    $sql
            = "SELECT COUNT(*) FROM chart_master
        WHERE account_type=$type";
    $result = DB::query($sql, "could not query chart master");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this account group because GL accounts have been created referring to it."));

      return false;
    }
    $sql
            = "SELECT COUNT(*) FROM chart_types
        WHERE parent=$type";
    $result = DB::query($sql, "could not query chart types");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this account group because GL account groups have been created referring to it."));

      return false;
    }

    return true;
  }

  /**
   * @param $selected_id
   *
   * @return bool
   */
  function can_process(&$selected_id)
  {
    if (!Validation::input_num('id')) {
      Event::error(_("The account id must be an integer and cannot be empty."));
      JS::set_focus('id');

      return false;
    }
    if (strlen($_POST['name']) == 0) {
      Event::error(_("The account group name cannot be empty."));
      JS::set_focus('name');

      return false;
    }
    if (isset($selected_id) && ($selected_id == $_POST['parent'])) {
      Event::error(_("You cannot set an account group to be a subgroup of itself."));

      return false;
    }

    return true;
  }
