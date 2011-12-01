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
	$page_security = 'SA_SALESTYPES';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Sales Types"));
	Page::simple_mode(true);

	function can_process()
	{
		if (strlen($_POST['sales_type']) == 0) {
			Errors::error(_("The sales type description cannot be empty."));
			JS::set_focus('sales_type');
			return false;
		}
		if (!Validation::is_num('factor', 0)) {
			Errors::error(_("Calculation factor must be valid positive number."));
			JS::set_focus('factor');
			return false;
		}
		return true;
	}


	if ($Mode == 'ADD_ITEM' && can_process()) {
		Sales_Type::add($_POST['sales_type'], isset($_POST['tax_included']) ? 1 : 0, input_num('factor'));
		Errors::notice(_('New sales type has been added'));
		$Mode = 'RESET';
	}

	if ($Mode == 'UPDATE_ITEM' && can_process()) {
		Sales_Type::update($selected_id, $_POST['sales_type'], isset($_POST['tax_included']) ? 1 : 0, input_num('factor'));
		Errors::notice(_('Selected sales type has been updated'));
		$Mode = 'RESET';
	}

	if ($Mode == 'Delete') {
		// PREVENT DELETES IF DEPENDENT RECORDS IN 'debtor_trans'
		$sql = "SELECT COUNT(*) FROM debtor_trans WHERE tpe=" . DB::escape($selected_id);
		$result = DB::query($sql, "check failed");
		Errors::check_db_error("The number of transactions using this Sales type record could not be retrieved", $sql);
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this sale type because customer transactions have been created using this sales type."));
		} else {
			$sql = "SELECT COUNT(*) FROM debtors_master WHERE sales_type=" . DB::escape($selected_id);
			$result = DB::query($sql, "check failed");
			Errors::check_db_error("The number of customers using this Sales type record could not be retrieved", $sql);
			$myrow = DB::fetch_row($result);
			if ($myrow[0] > 0) {
				Errors::error(_("Cannot delete this sale type because customers are currently set up to use this sales type."));
			} else {
				Sales_Type::delete($selected_id);
				Errors::notice(_('Selected sales type has been deleted'));
			}
		} //end if sales type used in debtor transactions or in customers set up
		$Mode = 'RESET';
	}
	if ($Mode == 'RESET') {
		$selected_id = -1;
		$sav = get_post('show_inactive');
		unset($_POST);
		$_POST['show_inactive'] = $sav;
	}

	$result = Sales_Type::get_all(check_value('show_inactive'));
	start_form();
	start_table(Config::get('tables_style') . "  width=30%");
	$th = array(_('Type Name'), _('Factor'), _('Tax Incl'), '', '');
	inactive_control_column($th);
	table_header($th);
	$k = 0;
	$base_sales = DB_Company::get_base_sales_type();
	while ($myrow = DB::fetch($result)) {
		if ($myrow["id"] == $base_sales) {
			start_row("class='overduebg'");
		} else {
			alt_table_row_color($k);
		}
		label_cell($myrow["sales_type"]);
		$f = Num::format($myrow["factor"], 4);
		if ($myrow["id"] == $base_sales) {
			$f = "<I>" . _('Base') . "</I>";
		}
		label_cell($f);
		label_cell($myrow["tax_included"] ? _('Yes') : _('No'), 'align=center');
		inactive_control_cell($myrow["id"], $myrow["inactive"], 'sales_types', 'id');
		edit_button_cell("Edit" . $myrow['id'], _("Edit"));
		delete_button_cell("Delete" . $myrow['id'], _("Delete"));
		end_row();
	}
	inactive_control_row($th);
	end_table();
	Errors::warning(_("Marked sales type is the company base pricelist for prices calculations."), 0, 0, "class='overduefg'");

	if (!isset($_POST['tax_included'])) {
		$_POST['tax_included'] = 0;
	}
	if (!isset($_POST['base'])) {
		$_POST['base'] = 0;
	}
	start_table(Config::get('tables_style2'));
	if ($selected_id != -1) {
		if ($Mode == 'Edit') {
			$myrow = Sales_Type::get($selected_id);
			$_POST['sales_type'] = $myrow["sales_type"];
			$_POST['tax_included'] = $myrow["tax_included"];
			$_POST['factor'] = Num::format($myrow["factor"], 4);
		}
		hidden('selected_id', $selected_id);
	} else {
		$_POST['factor'] = Num::format(1, 4);
	}
	text_row_ex(_("Sales Type Name") . ':', 'sales_type', 20);
	amount_row(_("Calculation factor") . ':', 'factor', null, null, null, 4);
	check_row(_("Tax included") . ':', 'tax_included', $_POST['tax_included']);
	end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	end_page();

?>
