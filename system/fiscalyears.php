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
	$page_security = 'SA_FISCALYEARS';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$js = "";
	Page::start(_($help_context = "Fiscal Years"));
	Page::simple_mode(true);
	//---------------------------------------------------------------------------------------------
	function is_date_in_fiscalyears($date)
	{
		$date = Dates::date2sql($date);
		$sql = "SELECT * FROM fiscal_year WHERE '$date' >= begin AND '$date' <= end";
		$result = DB::query($sql, "could not get all fiscal years");
		return DB::fetch($result) !== false;
	}

	function is_bad_begin_date($date)
	{
		$bdate = Dates::date2sql($date);
		$sql = "SELECT MAX(end) FROM fiscal_year WHERE begin < '$bdate'";
		$result = DB::query($sql, "could not retrieve last fiscal years");
		$row = DB::fetch_row($result);
		if ($row[0] === null) {
			return false;
		}
		$max = Dates::add_days(Dates::sql2date($row[0]), 1);
		return ($max !== $date);
	}

	function check_years_before($date, $closed = false)
	{
		$date = Dates::date2sql($date);
		$sql = "SELECT COUNT(*) FROM fiscal_year WHERE begin < '$date'";
		if (!$closed) {
			$sql .= " AND closed=0";
		}
		$result = DB::query($sql, "could not check fiscal years before");
		$row = DB::fetch_row($result);
		return ($row[0] > 0);
	}

	function check_data()
	{
		if (!Dates::is_date($_POST['from_date']) || is_date_in_fiscalyears($_POST['from_date'])
		 || is_bad_begin_date(
			 $_POST['from_date']
		 )
		) {
			Errors::error(_("Invalid BEGIN date in fiscal year."));
			JS::set_focus('from_date');
			return false;
		}
		if (!Dates::is_date($_POST['to_date']) || is_date_in_fiscalyears($_POST['to_date'])) {
			Errors::error(_("Invalid END date in fiscal year."));
			JS::set_focus('to_date');
			return false;
		}
		if (Dates::date1_greater_date2($_POST['from_date'], $_POST['to_date'])) {
			Errors::error(_("BEGIN date bigger than END date."));
			JS::set_focus('from_date');
			return false;
		}
		return true;
	}

	//---------------------------------------------------------------------------------------------
	function close_year($year)
	{
		$co = DB_Company::get_prefs();
		if (GL_Account::get($co['retained_earnings_act']) == false || GL_Account::get($co['profit_loss_year_act']) == false) {
			Errors::error(_("The Retained Earnings Account or the Profit and Loss Year Account has not been set in System and General GL Setup"));
			return false;
		}
		DB::begin_transaction();
		$myrow = DB_Company::get_fiscalyear($year);
		$to = $myrow['end'];
		// retrieve total balances from balance sheet accounts
		$sql
		 = "SELECT SUM(amount) FROM gl_trans INNER JOIN chart_master ON account=account_code
    	INNER JOIN chart_types ON account_type=id INNER JOIN chart_class ON class_id=cid
		WHERE ctype>=" . CL_ASSETS . " AND ctype <=" . CL_EQUITY . " AND tran_date <= '$to'";
		$result = DB::query($sql, "The total balance could not be calculated");
		$row = DB::fetch_row($result);
		$balance = Num::round($row[0], User::price_dec());
		$to = Dates::sql2date($to);
		if ($balance != 0.0) {
			$trans_type = ST_JOURNAL;
			$trans_id = SysTypes::get_next_trans_no($trans_type);
			GL_Trans::add(
				$trans_type, $trans_id, $to, $co['retained_earnings_act'],
				0, 0, _("Closing Year"), -$balance
			);
			GL_Trans::add(
				$trans_type, $trans_id, $to, $co['profit_loss_year_act'],
				0, 0, _("Closing Year"), $balance
			);
		}
		DB_AuditTrail::close_transactions($to);
		DB::commit_transaction();
		return true;
	}

	function open_year($year)
	{
		$myrow = DB_Company::get_fiscalyear($year);
		$from = Dates::sql2date($myrow['begin']);
		DB::begin_transaction();
		DB_AuditTrail::open_transactions($from);
		DB::commit_transaction();
	}

	function handle_submit()
	{
		global $selected_id, $Mode;
		$ok = true;
		if ($selected_id != -1) {
			if ($_POST['closed'] == 1) {
				if (check_years_before($_POST['from_date'], false)) {
					Errors::error(_("Cannot CLOSE this year because there are open fiscal years before"));
					JS::set_focus('closed');
					return false;
				}
				$ok = close_year($selected_id);
			} else {
				open_year($selected_id);
			}
			if ($ok) {
				DB_Company::update_fiscalyear($selected_id, $_POST['closed']);
				Errors::notice(_('Selected fiscal year has been updated'));
			}
		} else {
			if (!check_data()) {
				return false;
			}
			DB_Company::add_fiscalyear($_POST['from_date'], $_POST['to_date'], $_POST['closed']);
			Errors::notice(_('New fiscal year has been added'));
		}
		$Mode = 'RESET';
	}

	//---------------------------------------------------------------------------------------------
	function check_can_delete($selected_id)
	{
		$myrow = DB_Company::get_fiscalyear($selected_id);
		// PREVENT DELETES IF DEPENDENT RECORDS IN gl_trans
		if (check_years_before(Dates::sql2date($myrow['begin']), true)) {
			Errors::error(_("Cannot delete this fiscal year because thera are fiscal years before."));
			return false;
		}
		if ($myrow['closed'] == 0) {
			Errors::error(_("Cannot delete this fiscal year because the fiscal year is not closed."));
			return false;
		}
		return true;
	}

	//---------------------------------------------------------------------------------------------
	function delete_attachments_and_comments($type_no, $trans_no)
	{
		$sql = "SELECT * FROM attachments WHERE type_no = $type_no AND trans_no = $trans_no";
		$result = DB::query($sql, "Could not retrieve attachments");
		while ($row = DB::fetch($result))
		{
			$dir = COMPANY_PATH . "/attachments";
			if (file_exists($dir . "/" . $row['unique_name'])) {
				unlink($dir . "/" . $row['unique_name']);
			}
			$sql = "DELETE FROM attachments WHERE  type_no = $type_no AND trans_no = $trans_no";
			DB::query($sql, "Could not delete attachment");
		}
		$sql = "DELETE FROM comments WHERE  type = $type_no AND id = $trans_no";
		DB::query($sql, "Could not delete comments");
		$sql = "DELETE FROM refs WHERE  type = $type_no AND id = $trans_no";
		DB::query($sql, "Could not delete refs");
	}

	function delete_this_fiscalyear($selected_id)
	{
		DB_Utils::backup(Config::get('db.' . User::get()->company), 'Security backup before Fiscal Year Removal');
		DB::begin_transaction();
		$ref = _("Open Balance");
		$myrow = DB_Company::get_fiscalyear($selected_id);
		$to = $myrow['end'];
		$sql = "SELECT order_no, trans_type FROM sales_orders WHERE ord_date <= '$to' AND type <> 1"; // don't take the templates
		$result = DB::query($sql, "Could not retrieve sales orders");
		while ($row = DB::fetch($result))
		{
			$sql = "SELECT SUM(qty_sent), SUM(quantity) FROM sales_order_details WHERE order_no = {$row['order_no']} AND trans_type = {$row['trans_type']}";
			$res = DB::query($sql, "Could not retrieve sales order details");
			$row2 = DB::fetch_row($res);
			if ($row2[0] == $row2[1]) {
				$sql = "DELETE FROM sales_order_details WHERE order_no = {$row['order_no']} AND trans_type = {$row['trans_type']}";
				DB::query($sql, "Could not delete sales order details");
				$sql = "DELETE FROM sales_orders WHERE order_no = {$row['order_no']} AND trans_type = {$row['trans_type']}";
				DB::query($sql, "Could not delete sales order");
				delete_attachments_and_comments($row['trans_type'], $row['order_no']);
			}
		}
		$sql = "SELECT order_no FROM purch_orders WHERE ord_date <= '$to'";
		$result = DB::query($sql, "Could not retrieve purchase orders");
		while ($row = DB::fetch($result))
		{
			$sql = "SELECT SUM(quantity_ordered), SUM(quantity_received) FROM purch_order_details WHERE order_no = {$row['order_no']}";
			$res = DB::query($sql, "Could not retrieve purchase order details");
			$row2 = DB::fetch_row($res);
			if ($row2[0] == $row2[1]) {
				$sql = "DELETE FROM purch_order_details WHERE order_no = {$row['order_no']}";
				DB::query($sql, "Could not delete purchase order details");
				$sql = "DELETE FROM purch_orders WHERE order_no = {$row['order_no']}";
				DB::query($sql, "Could not delete purchase order");
				delete_attachments_and_comments(ST_PURCHORDER, $row['order_no']);
			}
		}
		$sql = "SELECT id FROM grn_batch WHERE delivery_date <= '$to'";
		$result = DB::query($sql, "Could not retrieve grn batch");
		while ($row = DB::fetch($result))
		{
			$sql = "DELETE FROM grn_items WHERE grn_batch_id = {$row['id']}";
			DB::query($sql, "Could not delete grn items");
			$sql = "DELETE FROM grn_batch WHERE id = {$row['id']}";
			DB::query($sql, "Could not delete grn batch");
			delete_attachments_and_comments(25, $row['id']);
		}
		$sql
		 = "SELECT trans_no, type FROM debtor_trans WHERE tran_date <= '$to' AND
		(ov_amount + ov_gst + ov_freight + ov_freight_tax + ov_discount) = alloc";
		$result = DB::query($sql, "Could not retrieve debtor trans");
		while ($row = DB::fetch($result))
		{
			if ($row['type'] == ST_SALESINVOICE) {
				$deliveries = Sales_Trans::get_parent(ST_SALESINVOICE, $row['trans_no']);
				foreach (
					$deliveries as $delivery
				)
				{
					$sql = "DELETE FROM debtor_trans_details WHERE debtor_trans_no = $delivery AND debtor_trans_type = " . ST_CUSTDELIVERY;
					DB::query($sql, "Could not delete debtor trans details");
					$sql = "DELETE FROM debtor_trans WHERE trans_no = $delivery AND type = " . ST_CUSTDELIVERY;
					DB::query($sql, "Could not delete debtor trans");
					delete_attachments_and_comments(ST_CUSTDELIVERY, $delivery);
				}
			}
			$sql = "DELETE FROM cust_allocations WHERE trans_no_from = {$row['trans_no']} AND trans_type_from = {$row['type']}";
			DB::query($sql, "Could not delete cust allocations");
			$sql = "DELETE FROM debtor_trans_details WHERE debtor_trans_no = {$row['trans_no']} AND debtor_trans_type = {$row['type']}";
			DB::query($sql, "Could not delete debtor trans details");
			$sql = "DELETE FROM debtor_trans WHERE trans_no = {$row['trans_no']} AND type = {$row['type']}";
			DB::query($sql, "Could not delete debtor trans");
			delete_attachments_and_comments($row['type'], $row['trans_no']);
		}
		$sql
		 = "SELECT trans_no, type FROM supp_trans WHERE tran_date <= '$to' AND
		ABS(ov_amount + ov_gst + ov_discount) = alloc";
		$result = DB::query($sql, "Could not retrieve supp trans");
		while ($row = DB::fetch($result))
		{
			$sql = "DELETE FROM supp_allocations WHERE trans_no_from = {$row['trans_no']} AND trans_type_from = {$row['type']}";
			DB::query($sql, "Could not delete supp allocations");
			$sql = "DELETE FROM supp_invoice_items WHERE supp_trans_no = {$row['trans_no']} AND supp_trans_type = {$row['type']}";
			DB::query($sql, "Could not delete supp invoice items");
			$sql = "DELETE FROM supp_trans WHERE trans_no = {$row['trans_no']} AND type = {$row['type']}";
			DB::query($sql, "Could not delete supp trans");
			delete_attachments_and_comments($row['type'], $row['trans_no']);
		}
		$sql = "SELECT id FROM workorders WHERE released_date <= '$to' AND closed=1";
		$result = DB::query($sql, "Could not retrieve supp trans");
		while ($row = DB::fetch($result))
		{
			$sql = "SELECT issue_no FROM wo_issues WHERE workorder_id = {$row['id']}";
			$res = DB::query($sql, "Could not retrieve wo issues");
			while ($row2 = DB::fetch_row($res))
			{
				$sql = "DELETE FROM wo_issue_items WHERE issue_id = {$row2[0]}";
				DB::query($sql, "Could not delete wo issue items");
			}
			delete_attachments_and_comments(ST_MANUISSUE, $row['id']);
			$sql = "DELETE FROM wo_issues WHERE workorder_id = {$row['id']}";
			DB::query($sql, "Could not delete wo issues");
			$sql = "DELETE FROM wo_manufacture WHERE workorder_id = {$row['id']}";
			DB::query($sql, "Could not delete wo manufacture");
			$sql = "DELETE FROM wo_requirements WHERE workorder_id = {$row['id']}";
			DB::query($sql, "Could not delete wo requirements");
			$sql = "DELETE FROM workorders WHERE id = {$row['id']}";
			DB::query($sql, "Could not delete workorders");
			delete_attachments_and_comments(ST_WORKORDER, $row['id']);
		}
		$sql
		 = "SELECT loc_code, stock_id, SUM(qty) AS qty, SUM(qty*standard_cost) AS std_cost FROM stock_moves WHERE tran_date <= '$to' GROUP by
		loc_code, stock_id";
		$result = DB::query($sql, "Could not retrieve supp trans");
		while ($row = DB::fetch($result))
		{
			$sql = "DELETE FROM stock_moves WHERE tran_date <= '$to' AND loc_code = '{$row['loc_code']}' AND stock_id = '{$row['stock_id']}'";
			DB::query($sql, "Could not delete stock moves");
			$qty = $row['qty'];
			$std_cost = ($qty == 0 ? 0 : Num::round($row['std_cost'] / $qty, User::price_dec()));
			$sql
			 = "INSERT INTO stock_moves (stock_id, loc_code, tran_date, reference, qty, standard_cost) VALUES
			('{$row['stock_id']}', '{$row['loc_code']}', '$to', '$ref', $qty, $std_cost)";
			DB::query($sql, "Could not insert stock move");
		}
		$sql = "DELETE FROM voided WHERE date_ <= '$to'";
		DB::query($sql, "Could not delete voided items");
		$sql = "DELETE FROM trans_tax_details WHERE tran_date <= '$to'";
		DB::query($sql, "Could not delete trans tax details");
		$sql = "DELETE FROM exchange_rates WHERE date_ <= '$to'";
		DB::query($sql, "Could not delete exchange rates");
		$sql = "DELETE FROM budget_trans WHERE tran_date <= '$to'";
		DB::query($sql, "Could not delete exchange rates");
		$sql = "SELECT account, SUM(amount) AS amount FROM gl_trans WHERE tran_date <= '$to' GROUP by account";
		$result = DB::query($sql, "Could not retrieve gl trans");
		while ($row = DB::fetch($result))
		{
			$sql = "DELETE FROM gl_trans WHERE tran_date <= '$to' AND account = '{$row['account']}'";
			DB::query($sql, "Could not delete gl trans");
			if (GL_Account::is_balancesheet($row['account'])) {
				$trans_no = SysTypes::get_next_trans_no(ST_JOURNAL);
				$sql
				 = "INSERT INTO gl_trans (type, type_no, tran_date, account, memo_, amount) VALUES
				(" . ST_JOURNAL . ", $trans_no, '$to', '{$row['account']}', '$ref', {$row['amount']})";
				DB::query($sql, "Could not insert gl trans");
			}
		}
		$sql = "SELECT bank_act, SUM(amount) AS amount FROM bank_trans WHERE trans_date <= '$to' GROUP BY bank_act";
		$result = DB::query($sql, "Could not retrieve bank trans");
		while ($row = DB::fetch($result))
		{
			$sql = "DELETE FROM bank_trans WHERE trans_date <= '$to' AND bank_act = '{$row['bank_act']}'";
			DB::query($sql, "Could not delete bank trans");
			$sql
			 = "INSERT INTO bank_trans (type, trans_no, trans_date, bank_act, ref, amount) VALUES
			(0, 0, '$to', '{$row['bank_act']}', '$ref', {$row['amount']})";
			DB::query($sql, "Could not insert bank trans");
		}
		$sql = "DELETE FROM audit_trail WHERE gl_date <= '$to'";
		DB::query($sql, "Could not delete audit trail");
		$sql = "SELECT type, id FROM comments WHERE type != " . ST_SALESQUOTE . " AND type != " . ST_SALESORDER . " AND type != " . ST_PURCHORDER;
		$result = DB::query($sql, "Could not retrieve comments");
		while ($row = DB::fetch($result))
		{
			$sql = "SELECT count(*) FROM gl_trans WHERE type = {$row['type']} AND type_no = {$row['id']}";
			$res = DB::query($sql, "Could not retrieve gl_trans");
			$row2 = DB::fetch_row($res);
			if ($row2[0] == 0) // if no link, then delete comments
			{
				$sql = "DELETE FROM comments WHERE type = {$row['type']} AND id = {$row['id']}";
				DB::query($sql, "Could not delete comments");
			}
		}
		$sql = "SELECT type, id FROM refs WHERE type != " . ST_SALESQUOTE . " AND type != " . ST_SALESORDER . " AND type != " . ST_PURCHORDER;
		$result = DB::query($sql, "Could not retrieve refs");
		while ($row = DB::fetch($result))
		{
			$sql = "SELECT count(*) FROM gl_trans WHERE type = {$row['type']} AND type_no = {$row['id']}";
			$res = DB::query($sql, "Could not retrieve gl_trans");
			$row2 = DB::fetch_row($res);
			if ($row2[0] == 0) // if no link, then delete refs
			{
				$sql = "DELETE FROM refs WHERE type = {$row['type']} AND id = {$row['id']}";
				DB::query($sql, "Could not delete refs");
			}
		}
		DB_Company::delete_fiscalyear($selected_id);
		DB::commit_transaction();
	}

	function handle_delete()
	{
		global $selected_id, $Mode;
		if (check_can_delete($selected_id)) {
			//only delete if used in neither customer or supplier, comp prefs, bank trans accounts
			delete_this_fiscalyear($selected_id);
			Errors::notice(_('Selected fiscal year has been deleted'));
		}
		$Mode = 'RESET';
	}

	//---------------------------------------------------------------------------------------------
	function display_fiscalyears()
	{
		$company_year = DB_Company::get_pref('f_year');
		$result = DB_Company::get_all_fiscalyears();
		start_form();
		Errors::warning(
			_(
				"Warning: Deleting a fiscal year all transactions
		are removed and converted into relevant balances. This process is irreversible!"
			),
			0, 0, "class='currentfg'"
		);
		start_table(Config::get('tables_style'));
		$th = array(_("Fiscal Year Begin"), _("Fiscal Year End"), _("Closed"), "", "");
		table_header($th);
		$k = 0;
		while ($myrow = DB::fetch($result))
		{
			if ($myrow['id'] == $company_year) {
				start_row("class='stockmankobg'");
			} else {
				alt_table_row_color($k);
			}
			$from = Dates::sql2date($myrow["begin"]);
			$to = Dates::sql2date($myrow["end"]);
			if ($myrow["closed"] == 0) {
				$closed_text = _("No");
			} else {
				$closed_text = _("Yes");
			}
			label_cell($from);
			label_cell($to);
			label_cell($closed_text);
			edit_button_cell("Edit" . $myrow['id'], _("Edit"));
			if ($myrow["id"] != $company_year) {
				delete_button_cell("Delete" . $myrow['id'], _("Delete"));
				submit_js_confirm(
					"Delete" . $myrow['id'],
					sprintf(_("Are you sure you want to delete fiscal year %s - %s? All transactions are deleted and converted into relevant balances. Do you want to continue ?"), $from, $to)
				);
			} else
			{
				label_cell('');
			}
			end_row();
		}
		end_table();
		end_form();
		Display::note(_("The marked fiscal year is the current fiscal year which cannot be deleted."), 0, 0, "class='currentfg'");
	}

	//---------------------------------------------------------------------------------------------
	function display_fiscalyear_edit($selected_id)
	{
		global $Mode;
		start_form();
		start_table(Config::get('tables_style2'));
		if ($selected_id != -1) {
			if ($Mode == 'Edit') {
				$myrow = DB_Company::get_fiscalyear($selected_id);
				$_POST['from_date'] = Dates::sql2date($myrow["begin"]);
				$_POST['to_date'] = Dates::sql2date($myrow["end"]);
				$_POST['closed'] = $myrow["closed"];
			}
			hidden('from_date');
			hidden('to_date');
			label_row(_("Fiscal Year Begin:"), $_POST['from_date']);
			label_row(_("Fiscal Year End:"), $_POST['to_date']);
		} else {
			date_row(_("Fiscal Year Begin:"), 'from_date', '', null, 0, 0, 1001);
			date_row(_("Fiscal Year End:"), 'to_date', '', null, 0, 0, 1001);
		}
		hidden('selected_id', $selected_id);
		yesno_list_row(_("Is Closed:"), 'closed', null, "", "", false);
		end_table(1);
		submit_add_or_update_center($selected_id == -1, '', 'both');
		end_form();
	}

	//---------------------------------------------------------------------------------------------
	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
		handle_submit();
	}
	//---------------------------------------------------------------------------------------------
	if ($Mode == 'Delete') {
		global $selected_id;
		handle_delete($selected_id);
	}
	if ($Mode == 'RESET') {
		$selected_id = -1;
	}
	//---------------------------------------------------------------------------------------------
	display_fiscalyears();
	echo '<br>';
	display_fiscalyear_edit($selected_id);
	//---------------------------------------------------------------------------------------------
	end_page();

?>
