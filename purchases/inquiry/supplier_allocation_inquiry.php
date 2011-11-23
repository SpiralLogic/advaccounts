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
	$page_security = 'SA_SUPPLIERALLOC';
	//
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(900, 500);
	Page::start(_($help_context = "Supplier Allocation Inquiry"));
	if (isset($_GET['supplier_id'])) {
		$_POST['supplier_id'] = $_GET['supplier_id'];
	}
	if (isset($_GET['FromDate'])) {
		$_POST['TransAfterDate'] = $_GET['FromDate'];
	}
	if (isset($_GET['ToDate'])) {
		$_POST['TransToDate'] = $_GET['ToDate'];
	}
	//------------------------------------------------------------------------------------------------
	start_form();
	if (!isset($_POST['supplier_id'])) {
		$_POST['supplier_id'] = Session::get()->supplier_id;
	}
	start_table("class='tablestyle_noborder'");
	start_row();
	supplier_list_cells(_("Select a supplier: "), 'supplier_id', $_POST['supplier_id'], true);
	date_cells(_("From:"), 'TransAfterDate', '', null, -90);
	date_cells(_("To:"), 'TransToDate', '', null, 1);
	supp_allocations_list_cell("filterType", null);
	check_cells(_("show settled:"), 'showSettled', null);
	submit_cells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
	Session::get()->supplier_id = $_POST['supplier_id'];
	end_row();
	end_table();
	//------------------------------------------------------------------------------------------------
	function check_overdue($row)
		{
			return ($row['TotalAmount'] > $row['Allocated']) && $row['OverDue'] == 1;
		}

	function systype_name($dummy, $type)
		{
			global $systypes_array;
			return $systypes_array[$type];
		}

	function view_link($trans)
		{
			return ui_view::get_trans_view_str($trans["type"], $trans["trans_no"]);
		}

	function due_date($row)
		{
			return (($row["type"] == ST_SUPPINVOICE) || ($row["type"] == ST_SUPPCREDIT)) ? $row["due_date"] : "";
		}

	function fmt_balance($row)
		{
			$value = ($row["type"] == ST_BANKPAYMENT || $row["type"] == ST_SUPPCREDIT || $row["type"] == ST_SUPPAYMENT) ?
			 -$row["TotalAmount"] - $row["Allocated"] : $row["TotalAmount"] - $row["Allocated"];
			return $value;
		}

	function alloc_link($row)
		{
			$link = pager_link(_("Allocations"),
			 "/purchases/allocations/supplier_allocate.php?trans_no=" . $row["trans_no"] . "&trans_type=" . $row["type"], ICON_MONEY);
			return (($row["type"] == ST_BANKPAYMENT || $row["type"] == ST_SUPPCREDIT || $row["type"] == ST_SUPPAYMENT) && (-$row["TotalAmount"] - $row["Allocated"]) > 0) ?
			 $link : '';
		}

	function fmt_debit($row)
		{
			$value = -$row["TotalAmount"];
			return $value >= 0 ? Num::price_format($value) : '';
		}

	function fmt_credit($row)
		{
			$value = $row["TotalAmount"];
			return $value > 0 ? Num::price_format($value) : '';
		}

	//------------------------------------------------------------------------------------------------
	$date_after = Dates::date2sql($_POST['TransAfterDate']);
	$date_to = Dates::date2sql($_POST['TransToDate']);
	// Sherifoz 22.06.03 Also get the description
	$sql = "SELECT
		trans.type, 
		trans.trans_no,
		trans.reference, 
		supplier.supp_name,
		supplier.supplier_id as id,
		trans.supp_reference,
    	trans.tran_date, 
		trans.due_date,
		supplier.curr_code, 
    	(trans.ov_amount + trans.ov_gst  + trans.ov_discount) AS TotalAmount, 
		trans.alloc AS Allocated,
		((trans.type = " . ST_SUPPINVOICE . " OR trans.type = " . ST_SUPPCREDIT . ") AND trans.due_date < '" . Dates::date2sql(Dates::Today()) . "') AS OverDue
    	FROM supp_trans as trans, suppliers as supplier
    	WHERE supplier.supplier_id = trans.supplier_id
     	AND trans.tran_date >= '$date_after'
    	AND trans.tran_date <= '$date_to'";
	if ($_POST['supplier_id'] != ALL_TEXT) {
		$sql .= " AND trans.supplier_id = " . DB::escape($_POST['supplier_id'], false, false);
	}
	if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT) {
		if (($_POST['filterType'] == '1') || ($_POST['filterType'] == '2')) {
			$sql .= " AND trans.type = " . ST_SUPPINVOICE . " ";
		} elseif ($_POST['filterType'] == '3') {
			$sql .= " AND trans.type = " . ST_SUPPAYMENT . " ";
		} elseif (($_POST['filterType'] == '4') || ($_POST['filterType'] == '5')) {
			$sql .= " AND trans.type = " . ST_SUPPCREDIT . " ";
		}
		if (($_POST['filterType'] == '2') || ($_POST['filterType'] == '5')) {
			$today = Dates::date2sql(Dates::Today());
			$sql .= " AND trans.due_date < '$today' ";
		}
	}
	if (!check_value('showSettled')) {
		$sql .= " AND (round(abs(ov_amount + ov_gst + ov_discount) - alloc,6) != 0) ";
	}
	$cols = array(
		_("Type") => array('fun' => 'systype_name'), _("#") => array(
			'fun' => 'view_link', 'ord' => ''), _("Reference"), _("Supplier") => array(
			'ord' => '', 'type' => 'id'), _("Supplier ID") => array('skip'), _("Supp Reference"), _("Date") => array(
			'name' => 'tran_date', 'type' => 'date', 'ord' => 'asc'), _("Due Date") => array('fun' => 'due_date'), _("Currency") => array('align' => 'center'), _("Debit") => array(
			'align' => 'right', 'fun' => 'fmt_debit'), _("Credit") => array(
			'align' => 'right', 'insert' => true, 'fun' => 'fmt_credit'), _("Allocated") => 'amount', _("Balance") => array(
			'type' => 'amount', 'insert' => true, 'fun' => 'fmt_balance'), array(
			'insert' => true, 'fun' => 'alloc_link'));
	if ($_POST['supplier_id'] != ALL_TEXT) {
		$cols[_("Supplier")] = 'skip';
		$cols[_("Currency")] = 'skip';
	}
	//------------------------------------------------------------------------------------------------
	$table =& db_pager::new_db_pager('doc_tbl', $sql, $cols);
	$table->set_marker('check_overdue', _("Marked items are overdue."));
	$table->width = "90%";
	display_db_pager($table);
	Contacts_Supplier::addInfoDialog('.pagerclick');
	end_form();
	end_page();
?>
