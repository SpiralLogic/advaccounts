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
	$page_security = 'SA_WORKCENTRES';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Work Centres"));
	Page::simple_mode(true);
	if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
		//initialise no input errors assumed initially before we test
		$input_error = 0;
		if (strlen($_POST['name']) == 0) {
			$input_error = 1;
			Errors::error(_("The work centre name cannot be empty."));
			JS::set_focus('name');
		}
		if ($input_error != 1) {
			if ($selected_id != -1) {
				WO_WorkCentre::update($selected_id, $_POST['name'], $_POST['description']);
				Errors::notice(_('Selected work center has been updated'));
			}
			else {
				WO_WorkCentre::add($_POST['name'], $_POST['description']);
				Errors::notice(_('New work center has been added'));
			}
			$Mode = MODE_RESET;
		}
	}
	function can_delete($selected_id) {
		$sql = "SELECT COUNT(*) FROM bom WHERE workcentre_added=" . DB::escape($selected_id);
		$result = DB::query($sql, "check can delete work centre");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this work centre because BOMs have been created referring to it."));
			return false;
		}
		$sql = "SELECT COUNT(*) FROM wo_requirements WHERE workcentre=" . DB::escape($selected_id);
		$result = DB::query($sql, "check can delete work centre");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this work centre because work order requirements have been created referring to it."));
			return false;
		}
		return true;
	}

	if ($Mode == MODE_DELETE) {
		if (can_delete($selected_id)) {
			WO_WorkCentre::delete($selected_id);
			Errors::notice(_('Selected work center has been deleted'));
		}
		$Mode = MODE_RESET;
	}
	if ($Mode == MODE_RESET) {
		$selected_id = -1;
		$sav = get_post('show_inactive');
		unset($_POST);
		$_POST['show_inactive'] = $sav;
	}
	$result = WO_WorkCentre::get_all(check_value('show_inactive'));
	start_form();
	start_table('tablestyle width50');
	$th = array(_("Name"), _("description"), "", "");
	inactive_control_column($th);
	table_header($th);
	$k = 0;
	while ($myrow = DB::fetch($result)) {
		alt_table_row_color($k);
		label_cell($myrow["name"]);
		label_cell($myrow["description"]);
		inactive_control_cell($myrow["id"], $myrow["inactive"], 'workcentres', 'id');
		edit_button_cell("Edit" . $myrow['id'], _("Edit"));
		delete_button_cell("Delete" . $myrow['id'], _("Delete"));
		end_row();
	}
	inactive_control_row($th);
	end_table(1);
	start_table('tablestyle2');
	if ($selected_id != -1) {
		if ($Mode == MODE_EDIT) {
			//editing an existing status code
			$myrow = WO_WorkCentre::get($selected_id);
			$_POST['name'] = $myrow["name"];
			$_POST['description'] = $myrow["description"];
		}
		hidden('selected_id', $selected_id);
	}
	text_row_ex(_("Name:"), 'name', 40);
	text_row_ex(_("Description:"), 'description', 50);
	end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	Page::end();

?>
