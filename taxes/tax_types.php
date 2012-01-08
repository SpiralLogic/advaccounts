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
	Page::start(_($help_context = "Tax Types"), SA_TAXRATES);
	list($Mode, $selected_id) = Page::simple_mode(true);
	if ($Mode == ADD_ITEM && can_process($selected_id)) {
		Tax_Types::add($_POST['name'], $_POST['sales_gl_code'], $_POST['purchasing_gl_code'], Validation::input_num('rate', 0));
		Errors::notice(_('New tax type has been added'));
		$Mode = MODE_RESET;
	}
	if ($Mode == UPDATE_ITEM && can_process($selected_id)) {
		Tax_Types::update($selected_id, $_POST['name'], $_POST['sales_gl_code'], $_POST['purchasing_gl_code'], Validation::input_num('rate'));
		Errors::notice(_('Selected tax type has been updated'));
		$Mode = MODE_RESET;
	}
	if ($Mode == MODE_DELETE) {
		if (can_delete($selected_id)) {
			Tax_Types::delete($selected_id);
			Errors::notice(_('Selected tax type has been deleted'));
		}
		$Mode = MODE_RESET;
	}
	if ($Mode == MODE_RESET) {
		$selected_id = -1;
		$sav = get_post('show_inactive');
		unset($_POST);
		$_POST['show_inactive'] = $sav;
	}
	$result = Tax_Types::get_all(check_value('show_inactive'));
	start_form();
	Errors::warning(_("To avoid problems with manual journal entry all tax types should have unique Sales/Purchasing GL accounts."));
	start_table('tablestyle');
	$th = array(
		_("Description"), _("Default Rate (%)"), _("Sales GL Account"), _("Purchasing GL Account"), "", ""
	);
	inactive_control_column($th);
	table_header($th);
	$k = 0;
	while ($myrow = DB::fetch($result)) {
		alt_table_row_color($k);
		label_cell($myrow["name"]);
		label_cell(Num::percent_format($myrow["rate"]), "class=right");
		label_cell($myrow["sales_gl_code"] . "&nbsp;" . $myrow["SalesAccountName"]);
		label_cell($myrow["purchasing_gl_code"] . "&nbsp;" . $myrow["PurchasingAccountName"]);
		inactive_control_cell($myrow["id"], $myrow["inactive"], 'tax_types', 'id');
		edit_button_cell("Edit" . $myrow["id"], _("Edit"));
		delete_button_cell("Delete" . $myrow["id"], _("Delete"));
		end_row();
	}
	inactive_control_row($th);
	end_table(1);
	start_table('tablestyle2');
	if ($selected_id != -1) {
		if ($Mode == MODE_EDIT) {
			//editing an existing status code
			$myrow = Tax_Types::get($selected_id);
			$_POST['name'] = $myrow["name"];
			$_POST['rate'] = Num::percent_format($myrow["rate"]);
			$_POST['sales_gl_code'] = $myrow["sales_gl_code"];
			$_POST['purchasing_gl_code'] = $myrow["purchasing_gl_code"];
		}
		hidden('selected_id', $selected_id);
	}
	text_row_ex(_("Description:"), 'name', 50);
	small_amount_row(_("Default Rate:"), 'rate', '0', "", "%", User::percent_dec());
	GL_UI::all_row(_("Sales GL Account:"), 'sales_gl_code', null);
	GL_UI::all_row(_("Purchasing GL Account:"), 'purchasing_gl_code', null);
	end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	Page::end();
	function can_delete($selected_id) {
		$sql = "SELECT COUNT(*) FROM tax_group_items	WHERE tax_type_id=" . DB::escape($selected_id);
		$result = DB::query($sql, "could not query tax groups");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this tax type because tax groups been created referring to it."));
			return false;
		}
		return true;
	}

	function can_process($selected_id) {
		if (strlen($_POST['name']) == 0) {
			Errors::error(_("The tax type name cannot be empty."));
			JS::set_focus('name');
			return false;
		}
		elseif (!Validation::is_num('rate', 0)) {
			Errors::error(_("The default tax rate must be numeric and not less than zero."));
			JS::set_focus('rate');
			return false;
		}
		if (!Tax_Types::is_tax_gl_unique(get_post('sales_gl_code'), get_post('purchasing_gl_code'), $selected_id)) {
			Errors::error(_("Selected GL Accounts cannot be used by another tax type."));
			JS::set_focus('sales_gl_code');
			return false;
		}
		return true;
	}

?>
