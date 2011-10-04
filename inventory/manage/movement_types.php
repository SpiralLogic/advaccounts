<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	$page_security = 'SA_INVENTORYMOVETYPE';

	include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");

	page(_($help_context = "Inventory Movement Types"));

	simple_page_mode(true);
	//-----------------------------------------------------------------------------------

	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {

		//initialise no input errors assumed initially before we test
		$input_error = 0;

		if (strlen($_POST['name']) == 0) {
			$input_error = 1;
			ui_msgs::display_error(_("The inventory movement type name cannot be empty."));
			ui_view::set_focus('name');
		}

		if ($input_error != 1) {
			if ($selected_id != -1) {
				update_movement_type($selected_id, $_POST['name']);
				ui_msgs::display_notification(_('Selected movement type has been updated'));
			}
			else
			{
				add_movement_type($_POST['name']);
				ui_msgs::display_notification(_('New movement type has been added'));
			}

			$Mode = 'RESET';
		}
	}

	//-----------------------------------------------------------------------------------

	function can_delete($selected_id) {
		$sql = "SELECT COUNT(*) FROM stock_moves
		WHERE type=" . ST_INVADJUST . " AND person_id=" . db_escape($selected_id);

		$result = db_query($sql, "could not query stock moves");
		$myrow = db_fetch_row($result);
		if ($myrow[0] > 0) {
			ui_msgs::display_error(_("Cannot delete this inventory movement type because item transactions have been created referring to it."));
			return false;
		}

		return true;
	}

	//-----------------------------------------------------------------------------------

	if ($Mode == 'Delete') {
		if (can_delete($selected_id)) {
			delete_movement_type($selected_id);
			ui_msgs::display_notification(_('Selected movement type has been deleted'));
		}
		$Mode = 'RESET';
	}

	if ($Mode == 'RESET') {
		$selected_id = -1;
		$sav = get_post('show_inactive');
		unset($_POST);
		$_POST['show_inactive'] = $sav;
	}
	//-----------------------------------------------------------------------------------

	$result = get_all_movement_type(check_value('show_inactive'));

	start_form();
	start_table(Config::get('tables.style') . "  width=30%");

	$th = array(_("Description"), "", "");
	inactive_control_column($th);
	table_header($th);
	$k = 0;
	while ($myrow = db_fetch($result))
	{

		alt_table_row_color($k);

		label_cell($myrow["name"]);
		inactive_control_cell($myrow["id"], $myrow["inactive"], 'movement_types', 'id');
		edit_button_cell("Edit" . $myrow['id'], _("Edit"));
		delete_button_cell("Delete" . $myrow['id'], _("Delete"));
		end_row();
	}
	inactive_control_row($th);
	end_table(1);

	//-----------------------------------------------------------------------------------

	start_table(Config::get('tables.style2'));

	if ($selected_id != -1) {
		if ($Mode == 'Edit') {
			//editing an existing status code

			$myrow = get_movement_type($selected_id);

			$_POST['name'] = $myrow["name"];
		}
		hidden('selected_id', $selected_id);
	}

	text_row(_("Description:"), 'name', null, 50, 50);

	end_table(1);

	submit_add_or_update_center($selected_id == -1, '', 'both');

	end_form();

	//------------------------------------------------------------------------------------

	end_page();

?>
