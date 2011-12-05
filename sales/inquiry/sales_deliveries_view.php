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
	$page_security = 'SA_SALESINVOICE';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(900, 600);
	if (isset($_GET['OutstandingOnly']) && ($_GET['OutstandingOnly'] == true)) {
		$_POST['OutstandingOnly'] = true;
		Page::start(_($help_context = "Search Not Invoiced Deliveries"));
	} else {
		$_POST['OutstandingOnly'] = false;
		Page::start(_($help_context = "Search All Deliveries"));
	}
	if (isset($_GET['selected_customer'])) {
		$selected_customer = $_GET['selected_customer'];
	} elseif (isset($_POST['selected_customer'])) {
		$selected_customer = $_POST['selected_customer'];
	} else {
		$selected_customer = -1;
	}
	if (isset($_POST['BatchInvoice'])) {
		// checking batch integrity
		$del_count = 0;
		foreach ($_POST['Sel_'] as $delivery => $branch) {
			$checkbox = 'Sel_' . $delivery;
			if (check_value($checkbox)) {
				if (!$del_count) {
					$del_branch = $branch;
				} else {
					if ($del_branch != $branch) {
						$del_count = 0;
						break;
					}
				}
				$selected[] = $delivery;
				$del_count++;
			}
		}
		if (!$del_count) {
			Errors::error(_('For batch invoicing you should select at least one delivery. All items must be dispatched to the same customer branch.'));
		} else {
			$_SESSION['DeliveryBatch'] = $selected;
			Display::meta_forward('/sales/customer_invoice.php', 'BatchInvoice=Yes');
		}
	}

	if (get_post('_DeliveryNumber_changed')) {
		$disable = get_post('DeliveryNumber') !== '';
		$Ajax->addDisable(true, 'DeliveryAfterDate', $disable);
		$Ajax->addDisable(true, 'DeliveryToDate', $disable);
		$Ajax->addDisable(true, 'StockLocation', $disable);
		$Ajax->addDisable(true, '_SelectStockFromList_edit', $disable);
		$Ajax->addDisable(true, 'SelectStockFromList', $disable);
		// if search is not empty rewrite table
		if ($disable) {
			$Ajax->addFocus(true, 'DeliveryNumber');
		} else {
			$Ajax->addFocus(true, 'DeliveryAfterDate');
		}
		$Ajax->activate('deliveries_tbl');
	}

	start_form(false, $_SERVER['PHP_SELF'] . "?OutstandingOnly=" . $_POST['OutstandingOnly']);
	start_table('tablestyle_noborder');
	start_row();
	ref_cells(_("#:"), 'DeliveryNumber', '', null, '', true);
	date_cells(_("from:"), 'DeliveryAfterDate', '', null, -30);
	date_cells(_("to:"), 'DeliveryToDate', '', null, 1);
	Inv_Location::cells(_("Location:"), 'StockLocation', null, true);
	Item::cells(_("Item:"), 'SelectStockFromList', null, true, false, false, false, false);
	submit_cells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
	hidden('OutstandingOnly', $_POST['OutstandingOnly']);
	end_row();
	end_table();

	if (isset($_POST['SelectStockFromList']) && ($_POST['SelectStockFromList'] != "") && ($_POST['SelectStockFromList'] != ALL_TEXT)
	) {
		$selected_stock_item = $_POST['SelectStockFromList'];
	} else {
		unset($selected_stock_item);
	}

	function trans_view($trans, $trans_no)
		{
			return Debtor_UI::trans_view(ST_CUSTDELIVERY, $trans['trans_no']);
		}

	function batch_checkbox($row)
		{
			$name = "Sel_" . $row['trans_no'];
			return $row['Done'] ? '' :
			 "<input type='checkbox' name='$name' value='1' >" // add also trans_no => branch code for checking after 'Batch' submit
				. "<input name='Sel_[" . $row['trans_no'] . "]' type='hidden' value='" . $row['branch_code'] . "'>\n";
		}

	function edit_link($row)
		{
			return $row["Outstanding"] == 0 ? '' :
			 DB_Pager::link(_('Edit'), "/sales/customer_delivery.php?ModifyDelivery=" . $row['trans_no'], ICON_EDIT);
		}

	function prt_link($row)
		{
			return Reporting::print_doc_link($row['trans_no'], _("Print"), true, ST_CUSTDELIVERY, ICON_PRINT);
		}

	function invoice_link($row)
		{
			return $row["Outstanding"] == 0 ? '' :
			 DB_Pager::link(_('Invoice'), "/sales/customer_invoice.php?DeliveryNumber=" . $row['trans_no'], ICON_DOC);
		}

	function check_overdue($row)
		{
			return Dates::date1_greater_date2(Dates::Today(), Dates::sql2date($row["due_date"])) && $row["Outstanding"] != 0;
		}


	$sql = "SELECT trans.trans_no,
		debtor.name,
		branch.branch_code,
		
		sorder.contact_name,
		sorder.deliver_to,
		trans.reference,
		sorder.customer_ref,
		trans.tran_date,
		trans.due_date,
		(ov_amount+ov_gst+ov_freight+ov_freight_tax) AS DeliveryValue,
		debtor.curr_code,
		Sum(line.quantity-line.qty_done) AS Outstanding,
		Sum(line.qty_done) AS Done
	FROM sales_orders as sorder, debtor_trans as trans, debtor_trans_details as line, debtors_master as debtor, cust_branch as branch
		WHERE
		sorder.order_no = trans.order_ AND
		trans.debtor_no = debtor.debtor_no
			AND trans.type = " . ST_CUSTDELIVERY . "
			AND line.debtor_trans_no = trans.trans_no
			AND line.debtor_trans_type = trans.type
			AND trans.branch_code = branch.branch_code
			AND trans.debtor_no = branch.debtor_no ";
	if ($_POST['OutstandingOnly'] == true) {
		$sql .= " AND line.qty_done < line.quantity ";
	}
	//figure out the sql required from the inputs available
	if (isset($_POST['DeliveryNumber']) && $_POST['DeliveryNumber'] != "") {
		$delivery = "%" . $_POST['DeliveryNumber'];
		$sql .= " AND trans.trans_no LIKE " . DB::escape($delivery, false, false);
		$sql .= " GROUP BY trans.trans_no";
	} else {
		$sql .= " AND trans.tran_date >= '" . Dates::date2sql($_POST['DeliveryAfterDate']) . "'";
		$sql .= " AND trans.tran_date <= '" . Dates::date2sql($_POST['DeliveryToDate']) . "'";
		if ($selected_customer != -1) {
			$sql .= " AND trans.debtor_no=" . DB::escape($selected_customer, false, false) . " ";
		}
		if (isset($selected_stock_item)) {
			$sql .= " AND line.stock_id=" . DB::escape($selected_stock_item, false, false) . " ";
		}
		if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT) {
			$sql .= " AND sorder.from_stk_loc = " . DB::escape($_POST['StockLocation'], false, false) . " ";
		}
		$sql .= " GROUP BY trans.trans_no ";
	} //end no delivery number selected
	$cols = array(
		_("Delivery #") => array('fun' => 'trans_view'), _("Customer"), _("branch_code") => 'skip', _("Contact"), _("Address"), _("Reference"), _("Cust Ref"), _("Delivery Date") => array(
			'type' => 'date', 'ord' => ''), _("Due By") => array('type' => 'date'), _("Delivery Total") => array(
			'type' => 'amount', 'ord' => ''), _("Currency") => array('align' => 'center'), submit('BatchInvoice', _("Batch"), false,
			_("Batch Invoicing")) => array(
			'insert' => true, 'fun' => 'batch_checkbox', 'align' => 'center'), array(
			'insert' => true, 'fun' => 'edit_link'), array(
			'insert' => true, 'fun' => 'invoice_link'), array(
			'insert' => true, 'fun' => 'prt_link'));

	if (isset($_SESSION['Batch'])) {
		foreach ($_SESSION['Batch'] as $trans => $del) {
			unset($_SESSION['Batch'][$trans]);
		}
		unset($_SESSION['Batch']);
	}
	$table =& db_pager::new_db_pager('deliveries_tbl', $sql, $cols);
	$table->set_marker('check_overdue', _("Marked items are overdue."));
	//$table->width = "92%";
	DB_Pager::display($table);
	end_form();
	end_page();

?>

