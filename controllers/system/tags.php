<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/

  // For tag constants
  // Set up page security based on what type of tags we're working with
  if (Input::get('type') == "account" || get_post('type') == TAG_ACCOUNT) {
    Page::set_security(SA_GLACCOUNTTAGS);
  }
  else {
    if (Input::get('type') == "dimension" || get_post('type') == TAG_DIMENSION) {
      Page::set_security(SA_DIMTAGS);
    }
  }
  // We use Input::post('type') throughout this script, so convert $_GET vars
  // if Input::post('type') is not set.
  if (!Input::post('type')) {
    if (Input::get('type') == "account") {
      $_POST['type'] = TAG_ACCOUNT;
    }
    elseif (Input::get('type') == "dimension") {
      $_POST['type'] = TAG_DIMENSION;
    }
    else {
      die(_("Unspecified tag type"));
    }
  }
  // Set up page based on what type of tags we're working with
  switch (Input::post('type')) {
    case TAG_ACCOUNT:
      // Account tags
      $_SESSION['page_title'] = _($help_context = "Account Tags");
      break;
    case TAG_DIMENSION:
      // Dimension tags
      $_SESSION['page_title'] = _($help_context = "Dimension Tags");
  }
  Page::start($_SESSION['page_title']);
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    if (can_process()) {
      if ($selected_id != -1) {
        if ($ret = Tags::update($selected_id, $_POST['name'], $_POST['description'])) {
          Event::success(_('Selected tag settings have been updated'));
        }
      }
      else {
        if ($ret = Tags::add(Input::post('type'), $_POST['name'], $_POST['description'])) {
          Event::success(_('New tag has been added'));
        }
      }
      if ($ret) {
        $Mode = MODE_RESET;
      }
    }
  }
  if ($Mode == MODE_DELETE) {
    if (can_delete($selected_id)) {
      Tags::delete($selected_id);
      Event::notice(_('Selected tag has been deleted'));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $_POST['name'] = $_POST['description'] = '';
  }
  $result = Tags::get_all(Input::post('type'), check_value('show_inactive'));
  start_form();
  start_table('tablestyle');
  $th = array(_("Tag Name"), _("Tag Description"), "", "");
  inactive_control_column($th);
  table_header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {
    alt_table_row_color($k);
    label_cell($myrow['name']);
    label_cell($myrow['description']);
    inactive_control_cell($myrow["id"], $myrow["inactive"], 'tags', 'id');
    edit_button_cell("Edit" . $myrow["id"], _("Edit"));
    delete_button_cell("Delete" . $myrow["id"], _("Delete"));
    end_row();
  }
  inactive_control_row($th);
  end_table(1);
  start_table('tablestyle2');
  if ($selected_id != -1) // We've selected a tag
  {
    if ($Mode == MODE_EDIT) {
      // Editing an existing tag
      $myrow = Tags::get($selected_id);
      $_POST['name'] = $myrow["name"];
      $_POST['description'] = $myrow["description"];
    }
    // Note the selected tag
    hidden('selected_id', $selected_id);
  }
  text_row_ex(_("Tag Name:"), 'name', 15, 30);
  text_row_ex(_("Tag Description:"), 'description', 40, 60);
  hidden('type');
  end_table(1);
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  Page::end();
  /**
   * @return bool
   */
  function can_process() {
    if (strlen($_POST['name']) == 0) {
      Event::error(_("The tag name cannot be empty."));
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
    $result = Tags::get_associated_records($selected_id);
    if (DB::num_rows($result) > 0) {
      Event::error(_("Cannot delete this tag because records have been created referring to it."));
      return FALSE;
    }
    return TRUE;
  }


