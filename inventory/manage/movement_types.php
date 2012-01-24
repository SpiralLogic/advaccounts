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

Page::start(_($help_context = "Inventory Movement Types"), SA_INVENTORYMOVETYPE);
	list($Mode,$selected_id) = Page::simple_mode(true);
	if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
		//initialise no input errors assumed initially before we test
		$input_error = 0;
		if (strlen($_POST['name']) == 0) {
			$input_error = 1;
			Event::error(_("The inventory movement type name cannot be empty."));
			JS::set_focus('name');
		}
		if ($input_error != 1) {
			if ($selected_id != -1) {
				Inv_Movement::update_type($selected_id, $_POST['name']);
				Event::notice(_('Selected movement type has been updated'));
			}
			else {
				Inv_Movement::add_type($_POST['name']);
				Event::notice(_('New movement type has been added'));
			}
			$Mode = MODE_RESET;
		}
	}
	function can_delete($selected_id) {
		$sql = "SELECT COUNT(*) FROM stock_moves
		WHERE type=" . ST_INVADJUST . " AND person_id=" . DB::escape($selected_id);
		$result = DB::query($sql, "could not query stock moves");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Event::error(_("Cannot delete this inventory movement type because item transactions have been created referring to it."));
			return false;
		}
		return true;
	}

	if ($Mode == MODE_DELETE) {
		if (can_delete($selected_id)) {
			Inv_Movement::delete($selected_id);
			Event::notice(_('Selected movement type has been deleted'));
		}
		$Mode = MODE_RESET;
	}
	if ($Mode == MODE_RESET) {
		$selected_id = -1;
		$sav = get_post('show_inactive');
		unset($_POST);
		$_POST['show_inactive'] = $sav;
	}
	$result = Inv_Movement::get_all_types(check_value('show_inactive'));
	start_form();
	start_table('tablestyle width30');
	$th = array(_("Description"), "", "");
	inactive_control_column($th);
	table_header($th);
	$k = 0;
	while ($myrow = DB::fetch($result)) {
		alt_table_row_color($k);
		label_cell($myrow["name"]);
		inactive_control_cell($myrow["id"], $myrow["inactive"], 'movement_types', 'id');
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
			$myrow = Inv_Movement::get_type($selected_id);
			$_POST['name'] = $myrow["name"];
		}
		hidden('selected_id', $selected_id);
	}
	text_row(_("Description:"), 'name', null, 50, 50);
	end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	Page::end();

?>
