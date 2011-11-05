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
	$page_security = 'SA_EXCHANGERATE';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$js = "";
	Page::start(_($help_context = "Exchange Rates"));
	Page::simple_mode(false);
	//---------------------------------------------------------------------------------------------
	function check_data()
	{
		if (!Dates::is_date($_POST['date_'])) {
			Errors::error(_("The entered date is invalid."));
			JS::set_focus('date_');
			return false;
		}
		if (input_num('BuyRate') <= 0) {
			Errors::error(_("The exchange rate cannot be zero or a negative number."));
			JS::set_focus('BuyRate');
			return false;
		}
		if (get_date_exchange_rate($_POST['curr_abrev'], $_POST['date_'])) {
			Errors::error(_("The exchange rate for the date is already there."));
			JS::set_focus('date_');
			return false;
		}
		return true;
	}

	//---------------------------------------------------------------------------------------------
	function handle_submit()
	{
		global $selected_id;
		if (!check_data()) {
			return false;
		}
		if ($selected_id != "") {
			update_exchange_rate(
				$_POST['curr_abrev'], $_POST['date_'],
				input_num('BuyRate'), input_num('BuyRate')
			);
		} else {
			add_exchange_rate(
				$_POST['curr_abrev'], $_POST['date_'],
				input_num('BuyRate'), input_num('BuyRate')
			);
		}
		$selected_id = '';
		clear_data();
	}

	//---------------------------------------------------------------------------------------------
	function handle_delete()
	{
		global $selected_id;
		if ($selected_id == "") {
			return;
		}
		delete_exchange_rate($selected_id);
		$selected_id = '';
		clear_data();
	}

	//---------------------------------------------------------------------------------------------
	function edit_link($row)
	{
		return button('Edit' . $row["id"], _("Edit"), true, ICON_EDIT);
	}

	function del_link($row)
	{
		return button('Delete' . $row["id"], _("Delete"), true, ICON_DELETE);
	}

	function display_rates($curr_code)
	{
	}

	//---------------------------------------------------------------------------------------------
	function display_rate_edit()
	{
		global $selected_id;
		$Ajax = Ajax::instance();
		start_table(Config::get('tables_style2'));
		if ($selected_id != "") {
			//editing an existing exchange rate
			$myrow = get_exchange_rate($selected_id);
			$_POST['date_'] = Dates::sql2date($myrow["date_"]);
			$_POST['BuyRate'] = Num::exrate_format($myrow["rate_buy"]);
			hidden('selected_id', $selected_id);
			hidden('date_', $_POST['date_']);
			label_row(_("Date to Use From:"), $_POST['date_']);
		} else {
			$_POST['date_'] = Dates::Today();
			$_POST['BuyRate'] = '';
			date_row(_("Date to Use From:"), 'date_');
		}
		if (isset($_POST['get_rate'])) {
			$_POST['BuyRate']
			 = Num::exrate_format(retrieve_exrate($_POST['curr_abrev'], $_POST['date_']));
			$Ajax->activate('BuyRate');
		}
		small_amount_row(
			_("Exchange Rate:"), 'BuyRate', null, '',
			submit('get_rate', _("Get"), false, _('Get current ECB rate'), true),
			User::exrate_dec()
		);
		end_table(1);
		submit_add_or_update_center($selected_id == '', '', 'both');
		Errors::warning(_("Exchange rates are entered against the company currency."), 1);
	}

	//---------------------------------------------------------------------------------------------
	function clear_data()
	{
		unset($_POST['selected_id']);
		unset($_POST['date_']);
		unset($_POST['BuyRate']);
	}

	//---------------------------------------------------------------------------------------------
	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
		handle_submit();
	}
	//---------------------------------------------------------------------------------------------
	if ($Mode == 'Delete') {
		handle_delete();
	}
	//---------------------------------------------------------------------------------------------
	start_form();
	if (!isset($_POST['curr_abrev'])) {
		$_POST['curr_abrev'] = Session::get()->global_curr_code;
	}
	echo "<center>";
	echo _("Select a currency :") . "  ";
	echo currencies_list('curr_abrev', null, true);
	echo "</center>";
	// if currency sel has changed, clear the form
	if ($_POST['curr_abrev'] != Session::get()->global_curr_code) {
		clear_data();
		$selected_id = "";
	}
	Session::get()->global_curr_code = $_POST['curr_abrev'];
	$sql = "SELECT date_, rate_buy, id FROM exchange_rates "
	 . "WHERE curr_code=" . DB::escape($_POST['curr_abrev']) . "
	 ORDER BY date_ DESC";
	$cols = array(
		_("Date to Use From") => 'date',
		_("Exchange Rate") => 'rate',
		array(
			'insert' => true,
			'fun' => 'edit_link'
		),
		array(
			'insert' => true,
			'fun' => 'del_link'
		),
	);
	$table =& db_pager::new_db_pager('orders_tbl', $sql, $cols);
	if (Banking::is_company_currency($_POST['curr_abrev'])) {
		Errors::warning(_("The selected currency is the company currency."), 2);
		Errors::warning(_("The company currency is the base currency so exchange rates cannot be set for it."), 1);
	} else {
		br(1);
		$table->width = "40%";
		if ($table->rec_count == 0) {
			$table->ready = false;
		}
		display_db_pager($table);
		br(1);
		display_rate_edit();
	}
	end_form();
	end_page();

?>
