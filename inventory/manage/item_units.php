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

  Page::start(_($help_context = "Units of Measure"), SA_UOM);
  list($Mode, $selected_id) = Page::simple_mode(FALSE);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    //initialise no input errors assumed initially before we test
    $input_error = 0;
    if (strlen($_POST['abbr']) == 0) {
      $input_error = 1;
      Event::error(_("The unit of measure code cannot be empty."));
      JS::set_focus('abbr');
    }
    if (strlen($_POST['abbr']) > (20 + 2)) {
      $input_error = 1;
      Event::error(_("The unit of measure code is too long."));
      JS::set_focus('abbr');
    }
    if (strlen($_POST['description']) == 0) {
      $input_error = 1;
      Event::error(_("The unit of measure description cannot be empty."));
      JS::set_focus('description');
    }
    if ($input_error != 1) {
      Item_Unit::write(htmlentities($selected_id), $_POST['abbr'], $_POST['description'], $_POST['decimals']);
      if ($selected_id != '') {
        Event::success(_('Selected unit has been updated'));
      }
      else {
        Event::success(_('New unit has been added'));
      }
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'stock_master'
    if (Item_Unit::used($selected_id)) {
      Event::error(_("Cannot delete this unit of measure because items have been created using this unit."));
    }
    else {
      Item_Unit::delete($selected_id);
      Event::notice(_('Selected unit has been deleted'));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = '';
    $sav = get_post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result = Item_Unit::get_all(check_value('show_inactive'));
  start_form();
  start_table('tablestyle width40');
  $th = array(_('Unit'), _('Description'), _('Decimals'), "", "");
  inactive_control_column($th);
  table_header($th);
  $k = 0; //row colour counter
  while ($myrow = DB::fetch($result)) {
    alt_table_row_color($k);
    label_cell($myrow["abbr"]);
    label_cell($myrow["name"]);
    label_cell(($myrow["decimals"] == -1 ? _("User Quantity Decimals") : $myrow["decimals"]));
    inactive_control_cell($myrow["abbr"], $myrow["inactive"], 'item_units', 'abbr');
    edit_button_cell("Edit" . $myrow["abbr"], _("Edit"));
    delete_button_cell("Delete" . $myrow["abbr"], _("Delete"));
    end_row();
  }
  inactive_control_row($th);
  end_table(1);
  start_table('tablestyle2');
  if ($selected_id != '') {
    if ($Mode == MODE_EDIT) {
      //editing an existing item category
      $myrow = Item_Unit::get($selected_id);
      $_POST['abbr'] = $myrow["abbr"];
      $_POST['description'] = $myrow["name"];
      $_POST['decimals'] = $myrow["decimals"];
    }
    hidden('selected_id', $selected_id);
  }
  if ($selected_id != '' && Item_Unit::used($selected_id)) {
    label_row(_("Unit Abbreviation:"), $_POST['abbr']);
    hidden('abbr', $_POST['abbr']);
  }
  else {
    text_row(_("Unit Abbreviation:"), 'abbr', NULL, 20, 20);
  }
  text_row(_("Descriptive Name:"), 'description', NULL, 40, 40);
  number_list_row(_("Decimal Places:"), 'decimals', NULL, 0, 6, _("User Quantity Decimals"));
  end_table(1);
  submit_add_or_update_center($selected_id == '', '', 'both');
  end_form();
  Page::end();


