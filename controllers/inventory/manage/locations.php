<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Inventory Locations"), SA_INVENTORYLOCATION);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    //initialise no input errors assumed initially before we test
    $input_error = 0;
    /* actions to take once the user has clicked the submit button
                ie the page has called itself with some user input */
    //first off validate inputs sensible
    $_POST['loc_code'] = strtoupper($_POST['loc_code']);
    if (strlen(DB::escape($_POST['loc_code'])) > 7) //check length after conversion
    {
      $input_error = 1;
      Event::error(_("The location code must be five characters or less long (including converted special chars)."));
      JS::set_focus('loc_code');
    } elseif (strlen($_POST['location_name']) == 0) {
      $input_error = 1;
      Event::error(_("The location name must be entered."));
      JS::set_focus('location_name');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        Inv_Location::update($_POST['loc_code'], $_POST['location_name'], $_POST['delivery_address'], $_POST['phone'], $_POST['phone2'], $_POST['fax'], $_POST['email'], $_POST['contact']);
        Event::success(_('Selected location has been updated'));
      } else {
        /*selected_id is null cos no item selected on first time round so must be adding a	record must be submitting new entries in the new Location form */
        Inv_Location::add($_POST['loc_code'], $_POST['location_name'], $_POST['delivery_address'], $_POST['phone'], $_POST['phone2'], $_POST['fax'], $_POST['email'], $_POST['contact']);
        Event::success(_('New location has been added'));
      }
      $Mode = MODE_RESET;
    }
  }
  /**
   * @param $selected_id
   *
   * @return bool
   */
  function can_delete($selected_id)
  {
    $sql    = "SELECT COUNT(*) FROM stock_moves WHERE loc_code=" . DB::escape($selected_id);
    $result = DB::query($sql, "could not query stock moves");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this location because item movements have been created using this location."));

      return false;
    }
    $sql    = "SELECT COUNT(*) FROM workorders WHERE loc_code=" . DB::escape($selected_id);
    $result = DB::query($sql, "could not query work orders");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this location because it is used by some work orders records."));

      return false;
    }
    $sql    = "SELECT COUNT(*) FROM branches WHERE default_location='$selected_id'";
    $result = DB::query($sql, "could not query customer branches");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this location because it is used by some branch records as the default location to deliver from."));

      return false;
    }
    $sql    = "SELECT COUNT(*) FROM bom WHERE loc_code=" . DB::escape($selected_id);
    $result = DB::query($sql, "could not query bom");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this location because it is used by some related records in other tables."));

      return false;
    }
    $sql    = "SELECT COUNT(*) FROM grn_batch WHERE loc_code=" . DB::escape($selected_id);
    $result = DB::query($sql, "could not query grn batch");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this location because it is used by some related records in other tables."));

      return false;
    }
    $sql    = "SELECT COUNT(*) FROM purch_orders WHERE into_stock_location=" . DB::escape($selected_id);
    $result = DB::query($sql, "could not query purch orders");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this location because it is used by some related records in other tables."));

      return false;
    }
    $sql    = "SELECT COUNT(*) FROM sales_orders WHERE from_stk_loc=" . DB::escape($selected_id);
    $result = DB::query($sql, "could not query sales orders");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this location because it is used by some related records in other tables."));

      return false;
    }
    $sql    = "SELECT COUNT(*) FROM sales_pos WHERE pos_location=" . DB::escape($selected_id);
    $result = DB::query($sql, "could not query sales pos");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this location because it is used by some related records in other tables."));

      return false;
    }

    return true;
  }

  if ($Mode == MODE_DELETE) {
    if (can_delete($selected_id)) {
      Inv_Location::delete($selected_id);
      Event::notice(_('Selected location has been deleted'));
    } //end if Delete Location
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav         = Form::getPost('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $sql = "SELECT * FROM locations";
  if (!Form::hasPost('show_inactive')) {
    $sql .= " WHERE !inactive";
  }
  $result = DB::query($sql, "could not query locations");
  ;
  Form::start();
  Table::start('tablestyle grid');
  $th = array(_("Location Code"), _("Location Name"), _("Address"), _("Phone"), _("Secondary Phone"), "", "");
   Form::inactiveControlCol($th);
  Table::header($th);
  $k = 0; //row colour counter
  while ($myrow = DB::fetch($result)) {
    Cell::label($myrow["loc_code"]);
    Cell::label($myrow["location_name"]);
    Cell::label($myrow["delivery_address"]);
    Cell::label($myrow["phone"]);
    Cell::label($myrow["phone2"]);
     Form::inactiveControlCell($myrow["loc_code"], $myrow["inactive"], 'locations', 'loc_code');
    Form::buttonEditCell("Edit" . $myrow["loc_code"], _("Edit"));
    Form::buttonDeleteCell("Delete" . $myrow["loc_code"], _("Delete"));
    Row::end();
  }
  //END WHILE LIST LOOP
   Form::inactiveControlRow($th);
  Table::end();
  echo '<br>';
  Table::start('tablestyle2');
  $_POST['email'] = "";
  if ($selected_id != -1) {
    //editing an existing Location
    if ($Mode == MODE_EDIT) {
      $myrow                     = Inv_Location::get($selected_id);
      $_POST['loc_code']         = $myrow["loc_code"];
      $_POST['location_name']    = $myrow["location_name"];
      $_POST['delivery_address'] = $myrow["delivery_address"];
      $_POST['contact']          = $myrow["contact"];
      $_POST['phone']            = $myrow["phone"];
      $_POST['phone2']           = $myrow["phone2"];
      $_POST['fax']              = $myrow["fax"];
      $_POST['email']            = $myrow["email"];
    }
    Form::hidden("selected_id", $selected_id);
    Form::hidden("loc_code");
    Row::label(_("Location Code:"), $_POST['loc_code']);
  } else { //end of if $selected_id only do the else when a new record is being entered
     Form::textRow(_("Location Code:"), 'loc_code', null, 5, 5);
  }
   Form::textRowEx(_("Location Name:"), 'location_name', 50, 50);
   Form::textRowEx(_("Contact for deliveries:"), 'contact', 30, 30);
   Form::textareaRow(_("Address:"), 'delivery_address', null, 35, 5);
   Form::textRowEx(_("Telephone No:"), 'phone', 32, 30);
   Form::textRowEx(_("Secondary Phone Number:"), 'phone2', 32, 30);
   Form::textRowEx(_("Facsimile No:"), 'fax', 32, 30);
   Form::emailRowEx(_("E-mail:"), 'email', 30);
  Table::end(1);
  Form::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Form::end();
  Page::end();

