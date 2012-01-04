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
	$page_security = SA_CRSTATUS;

	Page::start(_($help_context = "Credit Status"));
	Page::simple_mode(true);
	function can_process() {
		if (strlen($_POST['reason_description']) == 0) {
			Errors::error(_("The credit status description cannot be empty."));
			JS::set_focus('reason_description');
			return false;
		}
		return true;
	}

	if ($Mode == ADD_ITEM && can_process()) {
		Sales_CreditStatus::add($_POST['reason_description'], $_POST['DisallowInvoices']);
		Errors::notice(_('New credit status has been added'));
		$Mode = MODE_RESET;
	}
	if ($Mode == UPDATE_ITEM && can_process()) {
		Errors::notice(_('Selected credit status has been updated'));
		Sales_CreditStatus::update($selected_id, $_POST['reason_description'], $_POST['DisallowInvoices']);
		$Mode = MODE_RESET;
	}

	if ($Mode == MODE_DELETE) {
		if (can_delete($selected_id)) {
			Sales_CreditStatus::delete($selected_id);
			Errors::notice(_('Selected credit status has been deleted'));
		}
		$Mode = MODE_RESET;
	}
	if ($Mode == MODE_RESET) {
		$selected_id = -1;
		$sav = get_post('show_inactive');
		unset($_POST);
		$_POST['show_inactive'] = $sav;
	}
	$result = Sales_CreditStatus::get_all(check_value('show_inactive'));
	start_form();
	start_table('tablestyle width40');
	$th = array(_("Description"), _("Dissallow Invoices"), '', '');
	inactive_control_column($th);
	table_header($th);
	$k = 0;
	while ($myrow = DB::fetch($result)) {
		alt_table_row_color($k);
		if ($myrow["dissallow_invoices"] == 0) {
			$disallow_text = _("Invoice OK");
		}
		else {
			$disallow_text = "<span class='bold'>" . _("NO INVOICING") . "</span>";
		}
		label_cell($myrow["reason_description"]);
		label_cell($disallow_text);
		inactive_control_cell($myrow["id"], $myrow["inactive"], 'credit_status', 'id');
		edit_button_cell("Edit" . $myrow['id'], _("Edit"));
		delete_button_cell("Delete" . $myrow['id'], _("Delete"));
		end_row();
	}
	inactive_control_row($th);
	end_table();
	echo '<br>';
	start_table('tablestyle2');
	if ($selected_id != -1) {
		if ($Mode == MODE_EDIT) {
			//editing an existing status code
			$myrow = Sales_CreditStatus::get($selected_id);
			$_POST['reason_description'] = $myrow["reason_description"];
			$_POST['DisallowInvoices'] = $myrow["dissallow_invoices"];
		}
		hidden('selected_id', $selected_id);
	}
	text_row_ex(_("Description:"), 'reason_description', 50);
	yesno_list_row(_("Dissallow invoicing ?"), 'DisallowInvoices', null);
	end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	Page::end();
	function can_delete($selected_id) {
			$sql = "SELECT COUNT(*) FROM debtors
			WHERE credit_status=" . DB::escape($selected_id);
			$result = DB::query($sql, "could not query customers");
			$myrow = DB::fetch_row($result);
			if ($myrow[0] > 0) {
				Errors::error(_("Cannot delete this credit status because customer accounts have been created referring to it."));
				return false;
			}
			return true;
		}

?>
