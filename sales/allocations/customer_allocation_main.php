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
	$page_security = SA_SALESALLOC;

	JS::open_window(900, 500);
	Page::start(_($help_context = "Customer Allocations"));
	start_form();
	/* show all outstanding receipts and credits to be allocated */
	if (!isset($_POST['customer_id'])) {
		$_POST['customer_id'] = Session::i()->global_customer;
	}
	echo "<div class='center'>" . _("Select a customer: ") . "&nbsp;&nbsp;";
	echo Debtor::select('customer_id', $_POST['customer_id'], true, true);
	echo "<br>";
	check(_("Show Settled Items:"), 'ShowSettled', null, true);
	echo "</div><br><br>";
	Session::i()->global_customer = $_POST['customer_id'];
	if (isset($_POST['customer_id']) && ($_POST['customer_id'] == ALL_TEXT)) {
		unset($_POST['customer_id']);
	}
	/*if (isset($_POST['customer_id'])) {
				 $custCurr = Bank_Currency::for_debtor($_POST['customer_id']);
				 if (!Bank_Currency::is_company($custCurr))
					 echo _("Customer Currency:") . $custCurr;
			 }*/
	$settled = false;
	if (check_value('ShowSettled')) {
		$settled = true;
	}
	$customer_id = null;
	if (isset($_POST['customer_id'])) {
		$customer_id = $_POST['customer_id'];
	}
	function systype_name($dummy, $type) {
		global $systypes_array;
		return $systypes_array[$type];
	}

	function trans_view($trans) {
		return GL_UI::trans_view($trans["type"], $trans["trans_no"]);
	}

	function alloc_link($row) {
		return DB_Pager::link(_("Allocate"), "/sales/allocations/customer_allocate.php?trans_no=" . $row["trans_no"] . "&trans_type=" . $row["type"], ICON_MONEY);
	}

	function amount_left($row) {
		return Num::price_format($row["Total"] - $row["alloc"]);
	}

	function check_settled($row) {
		return $row['settled'] == 1;
	}

	$sql = Sales_Allocation::get_allocatable_sql($customer_id, $settled);
	$cols = array(
		_("Transaction Type") => array('fun' => 'systype_name'), _("#") => array('fun' => 'trans_view'), _("Reference"), _("Date") => array(
			'name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'
		), _("Customer") => array('ord' => ''), _("Currency") => array('align' => 'center'), _("Total") => 'amount', _("Left to Allocate") => array(
			'align' => 'right', 'insert' => true, 'fun' => 'amount_left'
		), array(
			'insert' => true, 'fun' => 'alloc_link'
		)
	);
	if (isset($_POST['customer_id'])) {
		$cols[_("Customer")] = 'skip';
		$cols[_("Currency")] = 'skip';
	}
	$table =& db_pager::new_db_pager('alloc_tbl', $sql, $cols);
	$table->set_marker('check_settled', _("Marked items are settled."), 'settledbg', 'settledfg');
	$table->width = "75%";
	DB_Pager::display($table);
	end_form();
	Page::end();
?>