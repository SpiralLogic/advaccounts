<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "GL Account Classes"), SA_GLACCOUNTCLASS);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    if (can_process()) {
      if ($selected_id != -1) {
        if (GL_Class::update($selected_id, $_POST['name'], $_POST['ctype'])) {
          Event::success(_('Selected account class settings has been updated'));
        }
      } else {
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
  $result = GL_Class::getAll(Input::hasPost('show_inactive'));
  Forms::start();
  Table::start('tablestyle grid');
  $th = array(_("class ID"), _("class Name"), _("class Type"), "", "");
  Forms::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {
    Cell::label($myrow["cid"]);
    Cell::label($myrow['class_name']);
    Cell::label($class_types[$myrow["ctype"]]);
    Forms::inactiveControlCell($myrow["cid"], $myrow["inactive"], 'chart_class', 'cid');
    Forms::buttonEditCell("Edit" . $myrow["cid"], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow["cid"], _("Delete"));
    Row::end();
  }
  Forms::inactiveControlRow($th);
  Table::end(1);
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing status code
      $myrow          = GL_Class::get($selected_id);
      $_POST['id']    = $myrow["cid"];
      $_POST['name']  = $myrow["class_name"];
      $_POST['ctype'] = $myrow["ctype"];
      Forms::hidden('selected_id', $selected_id);
    }
    Forms::hidden('id');
    Row::label(_("Class ID:"), $_POST['id']);
  } else {
    Forms::textRowEx(_("Class ID:"), 'id', 3);
  }
  Forms::textRowEx(_("Class Name:"), 'name', 50, 60);
  GL_Class::types_row(_("Class Type:"), 'ctype', null);
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();
  /**
   * @return bool
   */
  function can_process()
  {
    if (!is_numeric($_POST['id'])) {
      Event::error(_("The account class ID must be numeric."));
      JS::setFocus('id');

      return false;
    }
    if (strlen($_POST['name']) == 0) {
      Event::error(_("The account class name cannot be empty."));
      JS::setFocus('name');

      return false;
    }

    return true;
  }

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
    $sql
            = "SELECT COUNT(*) FROM chart_types
        WHERE class_id=$selected_id";
    $result = DB::query($sql, "could not query chart master");
    $myrow  = DB::fetchRow($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this account class because GL account types have been created referring to it."));

      return false;
    }

    return true;
  }
