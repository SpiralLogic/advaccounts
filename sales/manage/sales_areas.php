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
	$page_security = 'SA_SALESAREA';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Sales Areas"));
	Page::simple_mode(true);
	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
		$input_error = 0;
		if (strlen($_POST['description']) == 0) {
			$input_error = 1;
			Errors::error(_("The area description cannot be empty."));
			JS::set_focus('description');
		}
		if ($input_error != 1) {
			if ($selected_id != -1) {
				$sql = "UPDATE areas SET description=" . DB::escape($_POST['description']) . " WHERE area_code = " . DB::escape($selected_id);
				$note = _('Selected sales area has been updated');
			} else {
				$sql = "INSERT INTO areas (description) VALUES (" . DB::escape($_POST['description']) . ")";
				$note = _('New sales area has been added');
			}
			DB::query($sql, "The sales area could not be updated or added");
			Errors::notice($note);
			$Mode = 'RESET';
		}
	}
	if ($Mode == 'Delete') {
		$cancel_delete = 0;
		// PREVENT DELETES IF DEPENDENT RECORDS IN 'debtors_master'
		$sql = "SELECT COUNT(*) FROM cust_branch WHERE area=" . DB::escape($selected_id);
		$result = DB::query($sql, "check failed");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			$cancel_delete = 1;
			Errors::error(_("Cannot delete this area because customer branches have been created using this area."));
		}
		if ($cancel_delete == 0) {
			$sql = "DELETE FROM areas WHERE area_code=" . DB::escape($selected_id);
			DB::query($sql, "could not delete sales area");
			Errors::notice(_('Selected sales area has been deleted'));
		} //end if Delete area
		$Mode = 'RESET';
	}
	if ($Mode == 'RESET') {
		$selected_id = -1;
		$sav = Display::get_post('show_inactive');
		unset($_POST);
		$_POST['show_inactive'] = $sav;
	}

	$sql = "SELECT * FROM areas";
	if (!check_value('show_inactive')) {
		$sql .= " WHERE !inactive";
	}
	$result = DB::query($sql, "could not get areas");
	Display::start_form();
	Display::start_table('tablestyle width30');
	$th = array(_("Area Name"), "", "");
	inactive_control_column($th);
	Display::table_header($th);
	$k = 0;
	while ($myrow = DB::fetch($result)) {
		Display::alt_table_row_color($k);
		label_cell($myrow["description"]);
		inactive_control_cell($myrow["area_code"], $myrow["inactive"], 'areas', 'area_code');
		edit_button_cell("Edit" . $myrow["area_code"], _("Edit"));
		delete_button_cell("Delete" . $myrow["area_code"], _("Delete"));
		Display::end_row();
	}
	inactive_control_row($th);
	Display::end_table();
	echo '<br>';

	Display::start_table('tablestyle2');
	if ($selected_id != -1) {
		if ($Mode == 'Edit') {
			//editing an existing area
			$sql = "SELECT * FROM areas WHERE area_code=" . DB::escape($selected_id);
			$result = DB::query($sql, "could not get area");
			$myrow = DB::fetch($result);
			$_POST['description'] = $myrow["description"];
		}
		hidden("selected_id", $selected_id);
	}
	text_row_ex(_("Area Name:"), 'description', 30);
	Display::end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	Display::end_form();
	end_page();
?>
