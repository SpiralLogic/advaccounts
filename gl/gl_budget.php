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
	$page_security = 'SA_BUDGETENTRY';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	JS::headerFile('budget.js');

	page(_($help_context = "Budget Entry"));

	check_db_has_gl_account_groups(_("There are no account groups defined. Please define at least one account group before entering accounts."));

	//-------------------------------------------------------------------------------------

	function exists_gl_budget($date_, $account, $dimension, $dimension2) {
		$sql = "SELECT account FROM budget_trans WHERE account=" . DBOld::escape($account)
		 . " AND tran_date='$date_' AND
		dimension_id=" . DBOld::escape($dimension) . " AND dimension2_id=" . DBOld::escape($dimension2);
		$result = DBOld::query($sql, "Cannot retreive a gl transaction");

		return (DBOld::num_rows($result) > 0);
	}

	function add_update_gl_budget_trans($date_, $account, $dimension, $dimension2, $amount) {
		$date = Dates::date2sql($date_);

		if (exists_gl_budget($date, $account, $dimension, $dimension2))
			$sql = "UPDATE budget_trans SET amount=" . DBOld::escape($amount)
			 . " WHERE account=" . DBOld::escape($account)
			 . " AND dimension_id=" . DBOld::escape($dimension)
			 . " AND dimension2_id=" . DBOld::escape($dimension2)
			 . " AND tran_date='$date'";
		else
			$sql = "INSERT INTO budget_trans (tran_date,
			account, dimension_id, dimension2_id, amount, memo_) VALUES ('$date',
			" . DBOld::escape($account) . ", " . DBOld::escape($dimension) . ", "
			 . DBOld::escape($dimension2) . ", " . DBOld::escape($amount) . ", '')";

		DBOld::query($sql, "The GL budget transaction could not be saved");
	}

	function delete_gl_budget_trans($date_, $account, $dimension, $dimension2) {
		$date = Dates::date2sql($date_);

		$sql = "DELETE FROM budget_trans WHERE account=" . DBOld::escape($account)
		 . " AND dimension_id=" . DBOld::escape($dimension)
		 . " AND dimension2_id=" . DBOld::escape($dimension2)
		 . " AND tran_date='$date'";
		DBOld::query($sql, "The GL budget transaction could not be deleted");
	}

	function get_only_budget_trans_from_to($from_date, $to_date, $account, $dimension = 0, $dimension2 = 0) {

		$from = Dates::date2sql($from_date);
		$to = Dates::date2sql($to_date);

		$sql = "SELECT SUM(amount) FROM budget_trans
		WHERE account=" . DBOld::escape($account)
		 . " AND tran_date >= '$from' AND tran_date <= '$to'
		 AND dimension_id = " . DBOld::escape($dimension)
		 . " AND dimension2_id = " . DBOld::escape($dimension2);
		$result = DBOld::query($sql, "No budget accounts were returned");

		$row = DBOld::fetch_row($result);
		return $row[0];
	}

	//-------------------------------------------------------------------------------------

	if (isset($_POST['add']) || isset($_POST['delete'])) {
		DBOld::begin_transaction();

		for ($i = 0, $da = $_POST['begin']; Dates::date1_greater_date2($_POST['end'], $da); $i++)
		{
			if (isset($_POST['add']))
				add_update_gl_budget_trans($da, $_POST['account'], $_POST['dim1'],
					$_POST['dim2'], input_num('amount' . $i));
			else
				delete_gl_budget_trans($da, $_POST['account'], $_POST['dim1'], $_POST['dim2']);
			$da = Dates::add_months($da, 1);
		}
		DBOld::commit_transaction();

		if (isset($_POST['add']))
			ui_msgs::display_notification_centered(_("The Budget has been saved."));
		else
			ui_msgs::display_notification_centered(_("The Budget has been deleted."));

		//meta_forward($_SERVER['PHP_SELF']);
		$Ajax->activate('budget_tbl');
	}
	if (isset($_POST['submit']) || isset($_POST['update']))
		$Ajax->activate('budget_tbl');

	//-------------------------------------------------------------------------------------

	start_form();

	if (db_has_gl_accounts()) {
		$dim = DB_Company::get_pref('use_dimension');
		start_table(Config::get('tables.style2'));
		fiscalyears_list_row(_("Fiscal Year:"), 'fyear', null);
		gl_all_accounts_list_row(_("Account Code:"), 'account', null);
		if (!isset($_POST['dim1']))
			$_POST['dim1'] = 0;
		if (!isset($_POST['dim2']))
			$_POST['dim2'] = 0;
		if ($dim == 2) {
			dimensions_list_row(_("Dimension") . " 1", 'dim1', $_POST['dim1'], true, null, false, 1);
			dimensions_list_row(_("Dimension") . " 2", 'dim2', $_POST['dim2'], true, null, false, 2);
		}
		else if ($dim == 1) {
			dimensions_list_row(_("Dimension"), 'dim1', $_POST['dim1'], true, null, false, 1);
			hidden('dim2', 0);
		}
		else
		{
			hidden('dim1', 0);
			hidden('dim2', 0);
		}
		submit_row('submit', _("Get"), true, '', '', true);
		end_table(1);
		div_start('budget_tbl');
		start_table(Config::get('tables.style2'));
		$showdims = (($dim == 1 && $_POST['dim1'] == 0) ||
		 ($dim == 2 && $_POST['dim1'] == 0 && $_POST['dim2'] == 0));
		if ($showdims)
			$th = array(_("Period"), _("Amount"), _("Dim. incl."), _("Last Year"));
		else
			$th = array(_("Period"), _("Amount"), _("Last Year"));
		table_header($th);
		$year = $_POST['fyear'];
		if (get_post('update') == '') {
			$sql = "SELECT * FROM fiscal_year WHERE id=" . DBOld::escape($year);

			$result = DBOld::query($sql, "could not get current fiscal year");

			$fyear = DBOld::fetch($result);
			$_POST['begin'] = Dates::sql2date($fyear['begin']);
			$_POST['end'] = Dates::sql2date($fyear['end']);
		}
		hidden('begin');
		hidden('end');
		$total = $btotal = $ltotal = 0;
		for ($i = 0, $date_ = $_POST['begin']; Dates::date1_greater_date2($_POST['end'], $date_); $i++)
		{
			start_row();
			if (get_post('update') == '')
				$_POST['amount' . $i] = number_format2(get_only_budget_trans_from_to(
						$date_, $date_, $_POST['account'], $_POST['dim1'],
						$_POST['dim2']), 0);

			label_cell($date_);
			amount_cells(null, 'amount' . $i, null, 15, null, 0);
			if ($showdims) {
				$d = get_budget_trans_from_to($date_, $date_, $_POST['account'], $_POST['dim1'], $_POST['dim2']);
				label_cell(number_format2($d, 0), "nowrap align=right");
				$btotal += $d;
			}
			$lamount = get_gl_trans_from_to(Dates::add_years($date_, -1), Dates::add_years(Dates::end_month($date_), -1), $_POST['account'],
				$_POST['dim1'], $_POST['dim2']);
			$total += input_num('amount' . $i);
			$ltotal += $lamount;
			label_cell(number_format2($lamount, 0), "nowrap align=right");
			$date_ = Dates::add_months($date_, 1);
			end_row();
		}
		start_row();
		label_cell("<b>" . _("Total") . "</b>");
		label_cell(number_format2($total, 0), 'align=right style="font-weight:bold"', 'Total');
		if ($showdims)
			label_cell("<b>" . number_format2($btotal, 0) . "</b>", "nowrap align=right");
		label_cell("<b>" . number_format2($ltotal, 0) . "</b>", "nowrap align=right");
		end_row();
		end_table(1);
		div_end();
		submit_center_first('update', _("Update"), '', null);
		submit('add', _("Save"), true, '', 'default');
		submit_center_last('delete', _("Delete"), '', true);
	}
	end_form();

	end_page();

?>
