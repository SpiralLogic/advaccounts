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
	$page_security = 'SA_POSSETUP';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "POS settings"));
	Page::simple_mode(true);

	function can_process()
		{
			if (strlen($_POST['name']) == 0) {
				Errors::error(_("The POS name cannot be empty."));
				JS::set_focus('pos_name');
				return false;
			}
			if (!check_value('cash') && !check_value('credit')) {
				Errors::error(_("You must allow cash or credit sale."));
				JS::set_focus('credit');
				return false;
			}
			return true;
		}


	if ($Mode == 'ADD_ITEM' && can_process()) {
		Sales_Point::add($_POST['name'], $_POST['location'], $_POST['account'], check_value('cash'), check_value('credit'));
		Errors::notice(_('New point of sale has been added'));
		$Mode = 'RESET';
	}

	if ($Mode == 'UPDATE_ITEM' && can_process()) {
		Sales_Point::update($selected_id, $_POST['name'], $_POST['location'], $_POST['account'], check_value('cash'),
			check_value('credit'));
		Errors::notice(_('Selected point of sale has been updated'));
		$Mode = 'RESET';
	}

	if ($Mode == 'Delete') {
		$sql = "SELECT * FROM users WHERE pos=" . DB::escape($selected_id);
		$res = DB::query($sql, "canot check pos usage");
		if (DB::num_rows($res)) {
			Errors::error(_("Cannot delete this POS because it is used in users setup."));
		} else {
			Sales_Point::delete($selected_id);
			Errors::notice(_('Selected point of sale has been deleted'));
			$Mode = 'RESET';
		}
	}
	if ($Mode == 'RESET') {
		$selected_id = -1;
		$sav = Display::get_post('show_inactive');
		unset($_POST);
		$_POST['show_inactive'] = $sav;
	}

	$result = Sales_Point::get_all(check_value('show_inactive'));
	Display::start_form();
	Display::start_table('tablestyle');
	$th = array(
		_('POS Name'), _('Credit sale'), _('Cash sale'), _('Location'), _('Default account'), '', '');
	inactive_control_column($th);
	Display::table_header($th);
	$k = 0;
	while ($myrow = DB::fetch($result)) {
		Display::alt_table_row_color($k);
		label_cell($myrow["pos_name"], "nowrap");
		label_cell($myrow['credit_sale'] ? _('Yes') : _('No'));
		label_cell($myrow['cash_sale'] ? _('Yes') : _('No'));
		label_cell($myrow["location_name"], "");
		label_cell($myrow["bank_account_name"], "");
		inactive_control_cell($myrow["id"], $myrow["inactive"], "sales_pos", 'id');
		edit_button_cell("Edit" . $myrow['id'], _("Edit"));
		delete_button_cell("Delete" . $myrow['id'], _("Delete"));
		Display::end_row();
	}
	inactive_control_row($th);
	Display::end_table(1);

	$cash = Validation::check(Validation::CASH_ACCOUNTS);
	if (!$cash) {
		Errors::warning(_("To have cash POS first define at least one cash bank account."));
	}
	Display::start_table('tablestyle2');
	if ($selected_id != -1) {
		if ($Mode == 'Edit') {
			$myrow = Sales_Point::get($selected_id);
			$_POST['name'] = $myrow["pos_name"];
			$_POST['location'] = $myrow["pos_location"];
			$_POST['account'] = $myrow["pos_account"];
			if ($myrow["credit_sale"]) {
				$_POST['credit_sale'] = 1;
			}
			if ($myrow["cash_sale"]) {
				$_POST['cash_sale'] = 1;
			}
		}
		hidden('selected_id', $selected_id);
	}
	text_row_ex(_("Point of Sale Name") . ':', 'name', 20, 30);
	if ($cash) {
		check_row(_('Allowed credit sale'), 'credit', check_value('credit_sale'));
		check_row(_('Allowed cash sale'), 'cash', check_value('cash_sale'));
		Bank_UI::cash_accounts_row(_("Default cash account") . ':', 'account');
	} else {
		hidden('credit', 1);
		hidden('account', 0);
	}
	locations_list_row(_("POS location") . ':', 'location');
	Display::end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	Display::end_form();
	end_page();

?>
