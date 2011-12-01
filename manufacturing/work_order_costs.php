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
	$page_security = 'SA_WORKORDERCOST';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(900, 500);
	Page::start(_($help_context = "Work Order Additional Costs"));
	if (isset($_GET['trans_no']) && $_GET['trans_no'] != "") {
		$_POST['selected_id'] = $_GET['trans_no'];
	}

	if (isset($_GET['AddedID'])) {
		$id = $_GET['AddedID'];
		$stype = ST_WORKORDER;
		Errors::notice(_("The additional cost has been entered."));
		Display::note(ui_view::get_trans_view_str($stype, $id, _("View this Work Order")));
		Display::note(ui_view::get_gl_view_str($stype, $id, _("View the GL Journal Entries for this Work Order")), 1);
		hyperlink_params("work_order_costs.php", _("Enter another additional cost."), "trans_no=$id");
		hyperlink_no_params("search_work_orders.php", _("Select another &Work Order to Process"));
		end_page();
		exit;
	}

	$wo_details = WO_WorkOrder::get($_POST['selected_id']);
	if (strlen($wo_details[0]) == 0) {
		Errors::error(_("The order number sent is not valid."));
		exit;
	}

	function can_process()
		{
			global $wo_details;
			if (!Validation::is_num('costs', 0)) {
				Errors::error(_("The amount entered is not a valid number or less then zero."));
				JS::set_focus('costs');
				return false;
			}
			if (!Dates::is_date($_POST['date_'])) {
				Errors::error(_("The entered date is invalid."));
				JS::set_focus('date_');
				return false;
			} elseif (!Dates::is_date_in_fiscalyear($_POST['date_'])) {
				Errors::error(_("The entered date is not in fiscal year."));
				JS::set_focus('date_');
				return false;
			}
			if (Dates::date_diff2(Dates::sql2date($wo_details["released_date"]), $_POST['date_'], "d") > 0) {
				Errors::error(_("The additional cost date cannot be before the release date of the work order."));
				JS::set_focus('date_');
				return false;
			}
			return true;
		}


	if (isset($_POST['process']) && can_process() == true) {
		DB::begin_transaction();
		GL_Trans::add_std_cost(ST_WORKORDER, $_POST['selected_id'], $_POST['date_'], $_POST['cr_acc'], 0, 0,
			$wo_cost_types[$_POST['PaymentType']], -input_num('costs'), PT_WORKORDER, $_POST['PaymentType']);
		$is_bank_to = Banking::is_bank_account($_POST['cr_acc']);
		if ($is_bank_to) {
			Bank_Trans::add(ST_WORKORDER, $_POST['selected_id'], $is_bank_to, "", $_POST['date_'], -input_num('costs'), PT_WORKORDER,
				$_POST['PaymentType'], Banking::get_company_currency(), "Cannot insert a destination bank transaction");
		}
		GL_Trans::add_std_cost(ST_WORKORDER, $_POST['selected_id'], $_POST['date_'], $_POST['db_acc'], $_POST['dim1'], $_POST['dim2'],
			$wo_cost_types[$_POST['PaymentType']], input_num('costs'), PT_WORKORDER, $_POST['PaymentType']);
		DB::commit_transaction();
		meta_forward($_SERVER['PHP_SELF'], "AddedID=" . $_POST['selected_id']);
	}

	WO_Cost::display($_POST['selected_id']);

	start_form();
	hidden('selected_id', $_POST['selected_id']);
	//hidden('WOReqQuantity', $_POST['WOReqQuantity']);
	start_table(Config::get('tables_style2'));
	br();
	yesno_list_row(_("Type:"), 'PaymentType', null, $wo_cost_types[WO_OVERHEAD], $wo_cost_types[WO_LABOUR]);
	date_row(_("Date:"), 'date_');
	$item_accounts = Item::get_gl_code($wo_details['stock_id']);
	$_POST['db_acc'] = $item_accounts['assembly_account'];
	$sql = "SELECT DISTINCT account_code FROM bank_accounts";
	$rs = DB::query($sql, "could not get bank accounts");
	$r = DB::fetch_row($rs);
	$_POST['cr_acc'] = $r[0];
	amount_row(_("Additional Costs:"), 'costs');
	gl_all_accounts_list_row(_("Debit Account"), 'db_acc', null);
	gl_all_accounts_list_row(_("Credit Account"), 'cr_acc', null);
	end_table(1);
	hidden('dim1', $item_accounts["dimension_id"]);
	hidden('dim2', $item_accounts["dimension2_id"]);
	submit_center('process', _("Process Additional Cost"), true, '', true);
	end_form();
	end_page();

?>