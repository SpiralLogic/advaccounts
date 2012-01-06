<?php

	/* * ********************************************************************
					 Copyright (C) Advanced Group PTY LTD
					 Released under the terms of the GNU General Public License, GPL,
					 as published by the Free Software Foundation, either version 3
					 of the License, or (at your option) any later version.
					 This program is distributed in the hope that it will be useful,
					 but WITHOUT ANY WARRANTY; without even the implied warranty of
					 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
					 See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
					* ********************************************************************* */
	class Sales_Branch
	{
		public static function get($branch_id)
			{
				$sql
				 = "SELECT branches.*,salesman.salesman_name
		FROM branches, salesman
		WHERE branches.salesman=salesman.salesman_code
		AND branch_id=" . DB::escape($branch_id);
				$result = DB::query($sql, "Cannot retreive a customer branch");
				return DB::fetch($result);
			}

		public static function get_accounts($branch_id)
			{
				$sql
				 = "SELECT receivables_account,sales_account, sales_discount_account, payment_discount_account
		FROM branches WHERE branch_id=" . DB::escape($branch_id);
				$result = DB::query($sql, "Cannot retreive a customer branch");
				return DB::fetch($result);
			}

		public static function get_name($branch_id)
			{
				$sql
				 = "SELECT br_name FROM branches
		WHERE branch_id = " . DB::escape($branch_id);
				$result = DB::query($sql, "could not retreive name for branch" . $branch_id);
				$myrow = DB::fetch_row($result);
				return $myrow[0];
			}

		public static function get_from_group($group_no)
			{
				$sql
				 = "SELECT branch_id, debtor_no FROM branches
		WHERE group_no = " . DB::escape($group_no);
				return DB::query($sql, "could not retreive branches for group " . $group_no);
			}

		public static function get_main($customer_no)
			{
				$sql
				 = "SELECT *
 FROM branches
 WHERE debtor_no={$customer_no}
 ORDER BY branch_id ";
				$result = DB::query($sql, "Could not retrieve any branches");
				$myrow = DB::fetch_assoc($result);
				return $myrow;
			}
	}