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
	$page_security = 'SA_SALESALLOC';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(900, 500);
	Page::start(_($help_context = "Customer Allocation Inquiry"));
	if (isset($_GET['customer_id'])) {
		$_POST['customer_id'] = $_GET['customer_id'];
	}

	if (!isset($_POST['customer_id'])) {
		$_POST['customer_id'] = Session::i()->global_customer;
	}
	Display::start_form();
	Display::start_table('tablestyle_noborder');
	Display::start_row();
	Debtor_UI::select_cells(_("Select a customer: "), 'customer_id', $_POST['customer_id'], true);
	date_cells(_("from:"), 'TransAfterDate', '', null, -30);
	date_cells(_("to:"), 'TransToDate', '', null, 1);
	Debtor_UI::allocations_select(_("Type:"), 'filterType', null);
	check_cells(" " . _("show settled:"), 'showSettled', null);
	submit_cells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
	Session::i()->global_customer = $_POST['customer_id'];
	Display::end_row();
	Display::end_table();

	function check_overdue($row)
		{
			return ($row['OverDue'] == 1 && (abs($row["TotalAmount"]) - $row["Allocated"] != 0));
		}

	function order_link($row)
		{
			return $row['order_'] > 0 ? Debtor_UI::trans_view(ST_SALESORDER, $row['order_']) : "";
		}

	function systype_name($dummy, $type)
		{
			global $systypes_array;
			return $systypes_array[$type];
		}

	function view_link($trans)
		{
			return GL_UI::trans_view($trans["type"], $trans["trans_no"]);
		}

	function due_date($row)
		{
			return $row["type"] == 10 ? $row["due_date"] : '';
		}

	function fmt_balance($row)
		{
			return $row["TotalAmount"] - $row["Allocated"];
		}

	function alloc_link($row)
		{
			$link = DB_Pager::link(_("Allocation"),
			 "/sales/allocations/customer_allocate.php?trans_no=" . $row["trans_no"] . "&trans_type=" . $row["type"], ICON_MONEY);
			if ($row["type"] == ST_CUSTCREDIT && $row['TotalAmount'] > 0) {
				/*its a credit note which could have an allocation */
				return $link;
			} elseif (($row["type"] == ST_CUSTPAYMENT || $row["type"] == ST_CUSTREFUND || $row["type"] == ST_BANKDEPOSIT) && ($row['TotalAmount'] - $row['Allocated']) > 0
			) {
				/*its a receipt  which could have an allocation*/
				return $link;
			} elseif ($row["type"] == ST_CUSTPAYMENT || $row["type"] == ST_CUSTREFUND && $row['TotalAmount'] < 0) {
				/*its a negative receipt */
				return '';
			}
		}

	function fmt_debit($row)
		{
			$value = $row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_CUSTREFUND || $row['type'] == ST_BANKDEPOSIT ?
			 -$row["TotalAmount"] : $row["TotalAmount"];
			return $value >= 0 ? Num::price_format($value) : '';
		}

	function fmt_credit($row)
		{
			$value = !($row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_CUSTREFUND || $row['type'] == ST_BANKDEPOSIT) ?
			 -$row["TotalAmount"] : $row["TotalAmount"];
			return $value > 0 ? Num::price_format($value) : '';
		}


	$data_after = Dates::date2sql($_POST['TransAfterDate']);
	$date_to = Dates::date2sql($_POST['TransToDate']);
	$sql = "SELECT
  		trans.type,
		trans.trans_no,
		trans.reference,
		trans.order_,
		trans.tran_date,
		trans.due_date,
		debtor.name,
		debtor.curr_code,
    	(trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount)	AS TotalAmount,
		trans.alloc AS Allocated,
		((trans.type = " . ST_SALESINVOICE . ")
			AND trans.due_date < '" . Dates::date2sql(Dates::Today()) . "') AS OverDue
    	FROM debtor_trans as trans, debtors_master as debtor
    	WHERE debtor.debtor_no = trans.debtor_no
			AND (trans.ov_amount + trans.ov_gst + trans.ov_freight 
				+ trans.ov_freight_tax + trans.ov_discount != 0)
    		AND trans.tran_date >= '$data_after'
    		AND trans.tran_date <= '$date_to'";
	if ($_POST['customer_id'] != ALL_TEXT) {
		$sql .= " AND trans.debtor_no = " . DB::escape($_POST['customer_id'], false, false);
	}
	if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT) {
		if ($_POST['filterType'] == '1' || $_POST['filterType'] == '2') {
			$sql .= " AND trans.type = " . ST_SALESINVOICE . " ";
		} elseif ($_POST['filterType'] == '3') {
			$sql .= " AND (trans.type = " . ST_CUSTPAYMENT . " OR trans.type = " . ST_CUSTREFUND . ")";
		} elseif ($_POST['filterType'] == '4') {
			$sql .= " AND trans.type = " . ST_CUSTCREDIT . " ";
		}
		if ($_POST['filterType'] == '2') {
			$today = Dates::date2sql(Dates::Today());
			$sql .= " AND trans.due_date < '$today'
				AND (round(abs(trans.ov_amount + " . "trans.ov_gst + trans.ov_freight + " . "trans.ov_freight_tax + trans.ov_discount) - trans.alloc,6) > 0) ";
		}
	} else {
		$sql .= " AND trans.type <> " . ST_CUSTDELIVERY . " ";
	}
	if (!check_value('showSettled')) {
		$sql .= " AND (round(abs(trans.ov_amount + trans.ov_gst + " . "trans.ov_freight + trans.ov_freight_tax + " . "trans.ov_discount) - trans.alloc,6) != 0) ";
	}

	$cols = array(
		_("Type") => array('fun' => 'systype_name'),
		_("#") => array('fun' => 'view_link'),
		_("Reference"),
		_("Order") => array('fun' => 'order_link'),
		_("Date") => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'asc'),
		_("Due Date") => array('type' => 'date', 'fun' => 'due_date'),
		_("Customer"),
		_("Currency") => array('align' => 'center'),
		_("Debit") => array('align' => 'right', 'fun' => 'fmt_debit'),
		_("Credit") => array('align' => 'right', 'insert' => true, 'fun' => 'fmt_credit'),
		_("Allocated") => 'amount',
		_("Balance") => array('type' => 'amount', 'insert' => true, 'fun' => 'fmt_balance'),
		array('insert' => true, 'fun' => 'alloc_link'));
	$table =& db_pager::new_db_pager('doc_tbl', $sql, $cols);
	$table->set_marker('check_overdue', _("Marked items are overdue."));
	$table->width = "80%";
	DB_Pager::display($table);
	Display::end_form();
	end_page();
?>
