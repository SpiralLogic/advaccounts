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
	//--------------------------------------------------------------------------------------
	function display_dimension_balance($id, $from, $to) {
		$from = Dates::date2sql($from);
		$to = Dates::date2sql($to);
		$sql
		 = "SELECT account, chart_master.account_name, sum(amount) AS amt FROM
		gl_trans,chart_master WHERE
		gl_trans.account = chart_master.account_code AND
		(dimension_id = $id OR dimension2_id = $id) AND
		tran_date >= '$from' AND tran_date <= '$to' GROUP BY account";
		$result = DB::query($sql, "Transactions could not be calculated");
		if (DB::num_rows($result) == 0) {
			Errors::warning(_("There are no transactions for this dimension for the selected period."));
		} else {
			Display::heading(_("Balance for this Dimension"));
			br();
			start_table(Config::get('tables_style'));
			$th = array(_("Account"), _("Debit"), _("Credit"));
			table_header($th);
			$total = $k = 0;
			while ($myrow = DB::fetch($result))
			{
				alt_table_row_color($k);
				label_cell($myrow["account"] . " " . $myrow['account_name']);
				Display::debit_or_credit_cells($myrow["amt"]);
				$total += $myrow["amt"];
				end_row();
			}
			start_row();
			label_cell("<b>" . _("Balance") . "</b>");
			if ($total >= 0) {
				amount_cell($total, true);
				label_cell("");
} else {
				label_cell("");
				amount_cell(abs($total), true);
			}
			end_row();
			end_table();
		}
	}

	//--------------------------------------------------------------------------------------
?>