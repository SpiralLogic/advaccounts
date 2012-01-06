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
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$page_security = SA_SALESGROUP;
	Page::start(_($help_context = "Sales Groups"));
	list($Mode,$selected_id) = Page::simple_mode(true);
	if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
		$input_error = 0;
		if (strlen($_POST['description']) == 0) {
			$input_error = 1;
			Errors::error(_("The area description cannot be empty."));
			JS::set_focus('description');
		}
		if ($input_error != 1) {
			if ($selected_id != -1) {
				$sql = "UPDATE groups SET description=" . DB::escape($_POST['description']) . " WHERE id = " . DB::escape($selected_id);
				$note = _('Selected sales group has been updated');
			}
			else {
				$sql = "INSERT INTO groups (description) VALUES (" . DB::escape($_POST['description']) . ")";
				$note = _('New sales group has been added');
			}
			DB::query($sql, "The sales group could not be updated or added");
			Errors::notice($note);
			$Mode = MODE_RESET;
		}
	}
	if ($Mode == MODE_DELETE) {
		$cancel_delete = 0;
		// PREVENT DELETES IF DEPENDENT RECORDS IN 'debtors'
		$sql = "SELECT COUNT(*) FROM branches WHERE group_no=" . DB::escape($selected_id);
		$result = DB::query($sql, "check failed");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			$cancel_delete = 1;
			Errors::error(_("Cannot delete this group because customers have been created using this group."));
		}
		if ($cancel_delete == 0) {
			$sql = "DELETE FROM groups WHERE id=" . DB::escape($selected_id);
			DB::query($sql, "could not delete sales group");
			Errors::notice(_('Selected sales group has been deleted'));
		} //end if Delete area
		$Mode = MODE_RESET;
	}
	if ($Mode == MODE_RESET) {
		$selected_id = -1;
		$sav = get_post('show_inactive');
		unset($_POST);
		if ($sav) {
			$_POST['show_inactive'] = 1;
		}
	}
	$sql = "SELECT * FROM groups";
	if (!check_value('show_inactive')) {
		$sql .= " WHERE !inactive";
	}
	$sql .= " ORDER BY description";
	$result = DB::query($sql, "could not get groups");
	start_form();
	start_table('tablestyle width30');
	$th = array(_("Group Name"), "", "");
	inactive_control_column($th);
	table_header($th);
	$k = 0;
	while ($myrow = DB::fetch($result)) {
		alt_table_row_color($k);
		label_cell($myrow["description"]);
		inactive_control_cell($myrow["id"], $myrow["inactive"], 'groups', 'id');
		edit_button_cell("Edit" . $myrow["id"], _("Edit"));
		delete_button_cell("Delete" . $myrow["id"], _("Delete"));
		end_row();
	}
	inactive_control_row($th);
	end_table();
	echo '<br>';
	start_table('tablestyle2');
	if ($selected_id != -1) {
		if ($Mode == MODE_EDIT) {
			//editing an existing area
			$sql = "SELECT * FROM groups WHERE id=" . DB::escape($selected_id);
			$result = DB::query($sql, "could not get group");
			$myrow = DB::fetch($result);
			$_POST['description'] = $myrow["description"];
		}
		hidden("selected_id", $selected_id);
	}
	text_row_ex(_("Group Name:"), 'description', 30);
	end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	Page::end();
?>
