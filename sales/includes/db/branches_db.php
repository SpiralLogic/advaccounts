<?php

	/*     * ********************************************************************
			 Copyright (C) FrontAccounting, LLC.
			 Released under the terms of the GNU General Public License, GPL,
			 as published by the Free Software Foundation, either version 3
			 of the License, or (at your option) any later version.
			 This program is distributed in the hope that it will be useful,
			 but WITHOUT ANY WARRANTY; without even the implied warranty of
			 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
			 See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
			* ********************************************************************* */

	function get_branch($branch_id) {
		$sql = "SELECT cust_branch.*,salesman.salesman_name
		FROM cust_branch, salesman
		WHERE cust_branch.salesman=salesman.salesman_code
		AND branch_code=" . DBOld::escape($branch_id);

		$result = DBOld::query($sql, "Cannot retreive a customer branch");

		return DBOld::fetch($result);
	}

	function get_branch_accounts($branch_id) {
		$sql = "SELECT receivables_account,sales_account, sales_discount_account, payment_discount_account
		FROM cust_branch WHERE branch_code=" . DBOld::escape($branch_id);

		$result = DBOld::query($sql, "Cannot retreive a customer branch");

		return DBOld::fetch($result);
	}

	function get_branch_name($branch_id) {
		$sql = "SELECT br_name FROM cust_branch
		WHERE branch_code = " . DBOld::escape($branch_id);

		$result = DBOld::query($sql, "could not retreive name for branch" . $branch_id);

		$myrow = DBOld::fetch_row($result);
		return $myrow[0];
	}

	function get_cust_branches_from_group($group_no) {
		$sql = "SELECT branch_code, debtor_no FROM cust_branch
		WHERE group_no = " . DBOld::escape($group_no);

		return DBOld::query($sql, "could not retreive branches for group " . $group_no);
	}

	function get_main_branch($customer_no) {
		$sql = "SELECT *
            FROM cust_branch
            WHERE debtor_no={$customer_no}
            ORDER BY branch_code ";
		$result = DBOld::query($sql, "Could not retrieve any branches");
		$myrow = DBOld::fetch_assoc($result);
		return $myrow;
	}