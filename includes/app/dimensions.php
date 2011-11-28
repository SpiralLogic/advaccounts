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
	class Dimensions
	{
		public static function add($reference, $name, $type_, $date_, $due_date, $memo_)
			{
				DB::begin_transaction();
				$date = Dates::date2sql($date_);
				$duedate = Dates::date2sql($due_date);
				$sql = "INSERT INTO dimensions (reference, name, type_, date_, due_date)
		VALUES (" . DB::escape($reference) . ", " . DB::escape($name) . ", " . DB::escape($type_) . ", '$date', '$duedate')";
				DB::query($sql, "could not add dimension");
				$id = DB::insert_id();
				DB_Comments::add(ST_DIMENSION, $id, $date_, $memo_);
				Refs::save(ST_DIMENSION, $id, $reference);
				DB::commit_transaction();
				return $id;
			}

		public static function update($id, $name, $type_, $date_, $due_date, $memo_)
			{
				DB::begin_transaction();
				$date = Dates::date2sql($date_);
				$duedate = Dates::date2sql($due_date);
				$sql = "UPDATE dimensions SET name=" . DB::escape($name) . ",
		type_ = " . DB::escape($type_) . ",
		date_='$date',
		due_date='$duedate'
		WHERE id = " . DB::escape($id);
				DB::query($sql, "could not update dimension");
				DB_Comments::update(ST_DIMENSION, $id, null, $memo_);
				DB::commit_transaction();
				return $id;
			}

		public static function delete($id)
			{
				DB::begin_transaction();
				// delete the actual dimension
				$sql = "DELETE FROM dimensions WHERE id=" . DB::escape($id);
				DB::query($sql, "The dimension could not be deleted");
				DB_Comments::delete(ST_DIMENSION, $id);
				DB::commit_transaction();
			}

		//--------------------------------------------------------------------------------------
		public static function get($id, $allow_null = false)
			{
				$sql = "SELECT * FROM dimensions	WHERE id=" . DB::escape($id);
				$result = DB::query($sql, "The dimension could not be retrieved");
				if (!$allow_null && DB::num_rows($result) == 0) {
					Errors::show_db_error("Could not find dimension $id", $sql);
				}
				return DB::fetch($result);
			}

		//--------------------------------------------------------------------------------------
		public static function get_string($id, $html = false, $space = ' ')
			{
				if ($id <= 0) {
					if ($html) {
						$dim = "&nbsp;";
					} else {
						$dim = "";
					}
				} else {
					$row = Dimensions::get($id, true);
					$dim = $row['reference'] . $space . $row['name'];
				}
				return $dim;
			}

		//--------------------------------------------------------------------------------------
		public static function get_all()
			{
				$sql = "SELECT * FROM dimensions ORDER BY date_";
				return DB::query($sql, "The dimensions could not be retrieved");
			}

		//--------------------------------------------------------------------------------------
		public static function has_deposits($id)
			{
				return Dimensions::has_payments($id);
			}

		//--------------------------------------------------------------------------------------
		public static function has_payments($id)
			{
				$sql = "SELECT SUM(amount) FROM gl_trans WHERE dimension_id = " . DB::escape($id);
				$res = DB::query($sql, "Transactions could not be calculated");
				$row = DB::fetch_row($res);
				return ($row[0] != 0.0);
			}

		public static function is_closed($id)
			{
				$result = Dimensions::get($id);
				return ($result['closed'] == '1');
			}

		//--------------------------------------------------------------------------------------
		public static function close($id)
			{
				$sql = "UPDATE dimensions SET closed='1' WHERE id = " . DB::escape($id);
				DB::query($sql, "could not close dimension");
			}

		//--------------------------------------------------------------------------------------
		public static function reopen($id)
			{
				$sql = "UPDATE dimensions SET closed='0' WHERE id = $id";
				DB::query($sql, "could not reopen dimension");
			}

		//--------------------------------------------------------------------------------------
		function display_balance($id, $from, $to)
			{
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
	}

?>