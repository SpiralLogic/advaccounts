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
	$page_security = 'SA_UOM';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	page(_($help_context = "Units of Measure"));

	include_once(APP_PATH . "inventory/includes/db/items_units_db.inc");

	simple_page_mode(false);
	//----------------------------------------------------------------------------------

	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {

		//initialise no input errors assumed initially before we test
		$input_error = 0;

		if (strlen($_POST['abbr']) == 0) {
			$input_error = 1;
			ui_msgs::display_error(_("The unit of measure code cannot be empty."));
			ui_view::set_focus('abbr');
		}
		if (strlen(DBOld::escape($_POST['abbr'])) > (20 + 2)) {
			$input_error = 1;
			ui_msgs::display_error(_("The unit of measure code is too long."));
			ui_view::set_focus('abbr');
		}
		if (strlen($_POST['description']) == 0) {
			$input_error = 1;
			ui_msgs::display_error(_("The unit of measure description cannot be empty."));
			ui_view::set_focus('description');
		}

		if ($input_error != 1) {
			write_item_unit(htmlentities($selected_id), $_POST['abbr'], $_POST['description'], $_POST['decimals']);
			if ($selected_id != '')
				ui_msgs::display_notification(_('Selected unit has been updated'));
			else
				ui_msgs::display_notification(_('New unit has been added'));
			$Mode = 'RESET';
		}
	}

	//----------------------------------------------------------------------------------

	if ($Mode == 'Delete') {

		// PREVENT DELETES IF DEPENDENT RECORDS IN 'stock_master'

		if (item_unit_used($selected_id)) {
			ui_msgs::display_error(_("Cannot delete this unit of measure because items have been created using this unit."));
		}
		else
		{
			delete_item_unit($selected_id);
			ui_msgs::display_notification(_('Selected unit has been deleted'));
		}
		$Mode = 'RESET';
	}

	if ($Mode == 'RESET') {
		$selected_id = '';
		$sav = get_post('show_inactive');
		unset($_POST);
		$_POST['show_inactive'] = $sav;
	}

	//----------------------------------------------------------------------------------

	$result = get_all_item_units(check_value('show_inactive'));

	start_form();
	start_table(Config::get('tables.style') . "  width=40%");
	$th = array(_('Unit'), _('Description'), _('Decimals'), "", "");
	inactive_control_column($th);

	table_header($th);
	$k = 0; //row colour counter

	while ($myrow = DBOld::fetch($result))
	{

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

	//----------------------------------------------------------------------------------

	start_table(Config::get('tables.style2'));

	if ($selected_id != '') {
		if ($Mode == 'Edit') {
			//editing an existing item category

			$myrow = get_item_unit($selected_id);

			$_POST['abbr'] = $myrow["abbr"];
			$_POST['description'] = $myrow["name"];
			$_POST['decimals'] = $myrow["decimals"];
		}
		hidden('selected_id', $selected_id);
	}
	if ($selected_id != '' && item_unit_used($selected_id)) {
		label_row(_("Unit Abbreviation:"), $_POST['abbr']);
		hidden('abbr', $_POST['abbr']);
	} else
		text_row(_("Unit Abbreviation:"), 'abbr', null, 20, 20);
	text_row(_("Descriptive Name:"), 'description', null, 40, 40);

	number_list_row(_("Decimal Places:"), 'decimals', null, 0, 6, _("User Quantity Decimals"));

	end_table(1);

	submit_add_or_update_center($selected_id == '', '', 'both');

	end_form();

	end_page();

?>
