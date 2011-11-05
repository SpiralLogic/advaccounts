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
	$page_security = 'SA_SUPPLIERALLOC';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::get_js_open_window(900, 500);
	Page::start(_($help_context = "Supplier Allocations"));
	//--------------------------------------------------------------------------------
	start_form();
	/* show all outstanding receipts and credits to be allocated */
	if (!isset($_POST['supplier_id'])) {
		$_POST['supplier_id'] = Session::get()->supplier_id;
	}
	echo "<center>" . _("Select a Supplier: ") . "&nbsp;&nbsp;";
	echo supplier_list('supplier_id', $_POST['supplier_id'], true, true);
	echo "<br>";
	check(_("Show Settled Items:"), 'ShowSettled', null, true);
	echo "</center><br><br>";
	Session::get()->supplier_id = $_POST['supplier_id'];
	if (isset($_POST['supplier_id']) && ($_POST['supplier_id'] == ALL_TEXT)) {
		unset($_POST['supplier_id']);
	}
	$settled = false;
	if (check_value('ShowSettled')) {
		$settled = true;
	}
	$supplier_id = null;
	if (isset($_POST['supplier_id'])) {
		$supplier_id = $_POST['supplier_id'];
	}
	//--------------------------------------------------------------------------------
	function systype_name($dummy, $type)
	{
		global $systypes_array;
		return $systypes_array[$type];
	}

	function trans_view($trans)
	{
		return ui_view::get_trans_view_str($trans["type"], $trans["trans_no"]);
	}

	function alloc_link($row)
	{
		return pager_link(
			_("Allocate"),
		 "/purchasing/allocations/supplier_allocate.php?trans_no="
			. $row["trans_no"] . "&trans_type=" . $row["type"], ICON_MONEY
		);
	}

	function amount_left($row)
	{
		return Num::price_format(-$row["Total"] - $row["alloc"]);
	}

	function amount_total($row)
	{
		return Num::price_format(-$row["Total"]);
	}

	function check_settled($row)
	{
		return $row['settled'] == 1;
	}

	$sql = get_allocatable_from_supp_sql($supplier_id, $settled);
	$cols = array(
		_("Transaction Type") => array('fun' => 'systype_name'),
		_("#") => array('fun' => 'trans_view'),
		_("Reference"),
		_("Date") => array(
			'name' => 'tran_date',
			'type' => 'date',
			'ord' => 'asc'
		),
		_("Supplier") => array('ord' => ''),
		_("Currency") => array('align' => 'center'),
		_("Total") => array(
			'align' => 'right',
			'fun' => 'amount_total'
		),
		_("Left to Allocate") => array(
			'align' => 'right',
			'insert' => true,
			'fun' => 'amount_left'
		),
		array(
			'insert' => true,
			'fun' => 'alloc_link'
		)
	);
	if (isset($_POST['customer_id'])) {
		$cols[_("Supplier")] = 'skip';
		$cols[_("Currency")] = 'skip';
	}
	$table =& db_pager::new_db_pager('alloc_tbl', $sql, $cols);
	$table->set_marker('check_settled', _("Marked items are settled."), 'settledbg', 'settledfg');
	$table->width = "80%";
	display_db_pager($table);
	end_form();
	end_page();
?>