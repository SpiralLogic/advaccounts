<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

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
      } else {
        Event::success(_('New unit has been added'));
      }
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'stock_master'
    if (Item_Unit::used($selected_id)) {
      Event::error(_("Cannot delete this unit of measure because items have been created using this unit."));
    } else {
      Item_Unit::delete($selected_id);
      Event::notice(_('Selected unit has been deleted'));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = '';
    $sav         = Form::getPost('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result = Item_Unit::get_all(Form::hasPost('show_inactive'));
  Form::start();
  Table::start('tablestyle grid width40');
  $th = array(_('Unit'), _('Description'), _('Decimals'), "", "");
   Form::inactiveControlCol($th);
  Table::header($th);
  $k = 0; //row colour counter
  while ($myrow = DB::fetch($result)) {

    Cell::label($myrow["abbr"]);
    Cell::label($myrow["name"]);
    Cell::label(($myrow["decimals"] == -1 ? _("User Quantity Decimals") : $myrow["decimals"]));
     Form::inactiveControlCell($myrow["abbr"], $myrow["inactive"], 'item_units', 'abbr');
    Form::buttonEditCell("Edit" . $myrow["abbr"], _("Edit"));
    Form::buttonDeleteCell("Delete" . $myrow["abbr"], _("Delete"));
    Row::end();
  }
   Form::inactiveControlRow($th);
  Table::end(1);
  Table::start('tablestyle2');
  if ($selected_id != '') {
    if ($Mode == MODE_EDIT) {
      //editing an existing item category
      $myrow                = Item_Unit::get($selected_id);
      $_POST['abbr']        = $myrow["abbr"];
      $_POST['description'] = $myrow["name"];
      $_POST['decimals']    = $myrow["decimals"];
    }
    Form::hidden('selected_id', $selected_id);
  }
  if ($selected_id != '' && Item_Unit::used($selected_id)) {
    Row::label(_("Unit Abbreviation:"), $_POST['abbr']);
    Form::hidden('abbr', $_POST['abbr']);
  } else {
     Form::textRow(_("Unit Abbreviation:"), 'abbr', NULL, 20, 20);
  }
   Form::textRow(_("Descriptive Name:"), 'description', NULL, 40, 40);
   Form::numberListRow(_("Decimal Places:"), 'decimals', NULL, 0, 6, _("User Quantity Decimals"));
  Table::end(1);
  Form::submitAddUpdateCenter($selected_id == '', '', 'both');
  Form::end();
  Page::end();


