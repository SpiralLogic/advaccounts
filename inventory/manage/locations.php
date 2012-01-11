<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

Page::start(_($help_context = "Inventory Locations"), SA_INVENTORYLOCATION);
	list($Mode,$selected_id) = Page::simple_mode(true);
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
			Errors::error(_("The location code must be five characters or less long (including converted special chars)."));
			JS::set_focus('loc_code');
		}
		elseif (strlen($_POST['location_name']) == 0) {
			$input_error = 1;
			Errors::error(_("The location name must be entered."));
			JS::set_focus('location_name');
		}
		if ($input_error != 1) {
			if ($selected_id != -1) {
				Inv_Location::update($_POST['loc_code'], $_POST['location_name'], $_POST['delivery_address'], $_POST['phone'], $_POST['phone2'], $_POST['fax'], $_POST['email'], $_POST['contact']);
				Errors::notice(_('Selected location has been updated'));
			}
			else {
				/*selected_id is null cos no item selected on first time round so must be adding a	record must be submitting new entries in the new Location form */
				Inv_Location::add($_POST['loc_code'], $_POST['location_name'], $_POST['delivery_address'], $_POST['phone'], $_POST['phone2'], $_POST['fax'], $_POST['email'], $_POST['contact']);
				Errors::notice(_('New location has been added'));
			}
			$Mode = MODE_RESET;
		}
	}
	function can_delete($selected_id) {
		$sql = "SELECT COUNT(*) FROM stock_moves WHERE loc_code=" . DB::escape($selected_id);
		$result = DB::query($sql, "could not query stock moves");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this location because item movements have been created using this location."));
			return false;
		}
		$sql = "SELECT COUNT(*) FROM workorders WHERE loc_code=" . DB::escape($selected_id);
		$result = DB::query($sql, "could not query work orders");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this location because it is used by some work orders records."));
			return false;
		}
		$sql = "SELECT COUNT(*) FROM branches WHERE default_location='$selected_id'";
		$result = DB::query($sql, "could not query customer branches");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this location because it is used by some branch records as the default location to deliver from."));
			return false;
		}
		$sql = "SELECT COUNT(*) FROM bom WHERE loc_code=" . DB::escape($selected_id);
		$result = DB::query($sql, "could not query bom");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this location because it is used by some related records in other tables."));
			return false;
		}
		$sql = "SELECT COUNT(*) FROM grn_batch WHERE loc_code=" . DB::escape($selected_id);
		$result = DB::query($sql, "could not query grn batch");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this location because it is used by some related records in other tables."));
			return false;
		}
		$sql = "SELECT COUNT(*) FROM purch_orders WHERE into_stock_location=" . DB::escape($selected_id);
		$result = DB::query($sql, "could not query purch orders");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this location because it is used by some related records in other tables."));
			return false;
		}
		$sql = "SELECT COUNT(*) FROM sales_orders WHERE from_stk_loc=" . DB::escape($selected_id);
		$result = DB::query($sql, "could not query sales orders");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this location because it is used by some related records in other tables."));
			return false;
		}
		$sql = "SELECT COUNT(*) FROM sales_pos WHERE pos_location=" . DB::escape($selected_id);
		$result = DB::query($sql, "could not query sales pos");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this location because it is used by some related records in other tables."));
			return false;
		}
		return true;
	}

	if ($Mode == MODE_DELETE) {
		if (can_delete($selected_id)) {
			Inv_Location::delete($selected_id);
			Errors::notice(_('Selected location has been deleted'));
		} //end if Delete Location
		$Mode = MODE_RESET;
	}
	if ($Mode == MODE_RESET) {
		$selected_id = -1;
		$sav = get_post('show_inactive');
		unset($_POST);
		$_POST['show_inactive'] = $sav;
	}
	$sql = "SELECT * FROM locations";
	if (!check_value('show_inactive')) {
		$sql .= " WHERE !inactive";
	}
	$result = DB::query($sql, "could not query locations");
	;
	start_form();
	start_table('tablestyle');
	$th = array(_("Location Code"), _("Location Name"), _("Address"), _("Phone"), _("Secondary Phone"), "", "");
	inactive_control_column($th);
	table_header($th);
	$k = 0; //row colour counter
	while ($myrow = DB::fetch($result)) {
		alt_table_row_color($k);
		label_cell($myrow["loc_code"]);
		label_cell($myrow["location_name"]);
		label_cell($myrow["delivery_address"]);
		label_cell($myrow["phone"]);
		label_cell($myrow["phone2"]);
		inactive_control_cell($myrow["loc_code"], $myrow["inactive"], 'locations', 'loc_code');
		edit_button_cell("Edit" . $myrow["loc_code"], _("Edit"));
		delete_button_cell("Delete" . $myrow["loc_code"], _("Delete"));
		end_row();
	}
	//END WHILE LIST LOOP
	inactive_control_row($th);
	end_table();
	echo '<br>';
	start_table('tablestyle2');
	$_POST['email'] = "";
	if ($selected_id != -1) {
		//editing an existing Location
		if ($Mode == MODE_EDIT) {
			$myrow = Inv_Location::get($selected_id);
			$_POST['loc_code'] = $myrow["loc_code"];
			$_POST['location_name'] = $myrow["location_name"];
			$_POST['delivery_address'] = $myrow["delivery_address"];
			$_POST['contact'] = $myrow["contact"];
			$_POST['phone'] = $myrow["phone"];
			$_POST['phone2'] = $myrow["phone2"];
			$_POST['fax'] = $myrow["fax"];
			$_POST['email'] = $myrow["email"];
		}
		hidden("selected_id", $selected_id);
		hidden("loc_code");
		label_row(_("Location Code:"), $_POST['loc_code']);
	}
	else { //end of if $selected_id only do the else when a new record is being entered
		text_row(_("Location Code:"), 'loc_code', null, 5, 5);
	}
	text_row_ex(_("Location Name:"), 'location_name', 50, 50);
	text_row_ex(_("Contact for deliveries:"), 'contact', 30, 30);
	textarea_row(_("Address:"), 'delivery_address', null, 35, 5);
	text_row_ex(_("Telephone No:"), 'phone', 32, 30);
	text_row_ex(_("Secondary Phone Number:"), 'phone2', 32, 30);
	text_row_ex(_("Facsimile No:"), 'fax', 32, 30);
	email_row_ex(_("E-mail:"), 'email', 30);
	end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	Page::end();

?>
