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
	$page_security = 'SA_SALESTRANSVIEW';
	$path_to_root = "../..";
	include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/db_pager.inc");
	include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");
	include_once($_SERVER['DOCUMENT_ROOT'] . "/sales/includes/sales_ui.inc");
	include_once($_SERVER['DOCUMENT_ROOT'] . "/sales/includes/sales_db.inc");
	include_once($_SERVER['DOCUMENT_ROOT'] . "/reporting/includes/reporting.inc");
	$js = "";
	if ($use_popup_windows) {
		$js .= get_js_open_window(900, 500);
	}
	if ($use_date_picker) {
		$js .= get_js_date_picker();
	}
	page(_($help_context = "Customer Transactions"), false, false, "", $js);
	if (isset($_GET['customer_id'])) {
		$_POST['customer_id'] = $_GET['customer_id'];
	}
	//------------------------------------------------------------------------------------------------
	start_form();
	if (!isset($_POST['customer_id'])) {
		$_POST['customer_id'] = get_global_customer();
	}
	start_table("class='tablestyle_noborder'");
	start_row();
	ref_cells(_("Ref"), 'reference', '', null, '', true);
	customer_list_cells(_("Select a customer: "), 'customer_id', null, true);
	date_cells(_("From:"), 'TransAfterDate', '', null, -30);
	date_cells(_("To:"), 'TransToDate', '', null, 1);
	if (!isset($_POST['filterType'])) {
		$_POST['filterType'] = 0;
	}
	cust_allocations_list_cells(null, 'filterType', $_POST['filterType'], true);
	submit_cells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
	end_row();
	end_table();
	set_global_customer($_POST['customer_id']);
	//------------------------------------------------------------------------------------------------
	function display_customer_summary($customer_record) {
		global $table_style;
		$past1 = get_company_pref('past_due_days');
		$past2 = 2 * $past1;
		if ($customer_record["dissallow_invoices"] != 0) {
			echo "<center><font color=red size=4><b>" . _("CUSTOMER ACCOUNT IS ON HOLD") . "</font></b></center>";
		}
		$nowdue = "1-" . $past1 . " " . _('Days');
		$pastdue1 = $past1 + 1 . "-" . $past2 . " " . _('Days');
		$pastdue2 = _('Over') . " " . $past2 . " " . _('Days');
		start_table("width=80% $table_style");
		$th = array(_("Currency"), _("Terms"), _("Current"), $nowdue, $pastdue1, $pastdue2, _("Total Balance"));
		table_header($th);
		start_row();
		label_cell($customer_record["curr_code"]);
		label_cell($customer_record["terms"]);
		amount_cell($customer_record["Balance"] - $customer_record["Due"]);
		amount_cell($customer_record["Due"] - $customer_record["Overdue1"]);
		amount_cell($customer_record["Overdue1"] - $customer_record["Overdue2"]);
		amount_cell($customer_record["Overdue2"]);
		amount_cell($customer_record["Balance"]);
		end_row();
		end_table();
	}

	//------------------------------------------------------------------------------------------------
	div_start('totals_tbl');
	if ($_POST['customer_id'] != "" && $_POST['customer_id'] != ALL_TEXT && !isset($_POST['ajaxsearch'])) {
		$customer_record = get_customer_details($_POST['customer_id'], $_POST['TransToDate']);
		display_customer_summary($customer_record);
		echo "<br>";
	}
	div_end();
	if (get_post('RefreshInquiry')) {
		$Ajax->activate('totals_tbl');
	}
	//------------------------------------------------------------------------------------------------
	function systype_name($dummy, $type) {
		global $systypes_array;
		return $systypes_array[$type];
	}

	function order_view($row) {
		return $row['order_'] > 0 ? get_customer_trans_view_str(ST_SALESORDER, $row['order_']) : "";
	}

	function trans_view($trans) {
		return get_trans_view_str($trans["type"], $trans["trans_no"]);
	}

	function due_date($row) {
		return $row["type"] == ST_SALESINVOICE ? $row["due_date"] : '';
	}

	function gl_view($row) {
		return get_gl_view_str($row["type"], $row["trans_no"]);
	}

	function fmt_debit($row) {
		$value = $row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_CUSTREFUND ||
				$row['type'] == ST_BANKDEPOSIT ? -$row["TotalAmount"] : $row["TotalAmount"];
		return $value >= 0 ? price_format($value) : '';

	}

	function fmt_credit($row) {
		$value = !($row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTREFUND || $row['type'] == ST_CUSTPAYMENT ||
				$row['type'] == ST_BANKDEPOSIT) ? -$row["TotalAmount"] : $row["TotalAmount"];
		return $value > 0 ? price_format($value) : '';
	}

	function credit_link($row) {
		return $row['type'] == ST_SALESINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0
				? pager_link(_("Credit"), "/sales/customer_credit_invoice.php?InvoiceNumber=" .
						$row['trans_no'], ICON_CREDIT) : '';
	}

	function edit_link($row) {
		$str = '';
		switch ($row['type']) {
			case ST_SALESINVOICE:
				if (get_voided_entry(ST_SALESINVOICE, $row["trans_no"]) === false && $row['Allocated'] == 0) {
					$str = "/sales/customer_invoice.php?ModifyInvoice=" . $row['trans_no'];
				}
				break;
			case ST_CUSTCREDIT:
				if (get_voided_entry(ST_CUSTCREDIT, $row["trans_no"]) === false &&
						$row['Allocated'] == 0
				) // 2008-11-19 Joe Hunt
				{
					if ($row['order_'] == 0) // free-hand credit note
					{
						$str = "/sales/credit_note_entry.php?ModifyCredit=" . $row['trans_no'];
					}
					else // credit invoice
					{
						$str = "/sales/customer_credit_invoice.php?ModifyCredit=" . $row['trans_no'];
					}
				}
				break;
			case ST_CUSTDELIVERY:
				if (get_voided_entry(ST_CUSTDELIVERY, $row["trans_no"]) === false) {
					$str = "/sales/customer_delivery.php?ModifyDelivery=" . $row['trans_no'];
				}
				break;
		}
		if ($str != "" && !is_closed_trans($row['type'], $row["trans_no"])) {
			return pager_link(_('Edit'), $str, ICON_EDIT);
		}
		return '';
	}

	function prt_link($row) {
		if ($row['type'] != ST_CUSTPAYMENT && $row['type'] != ST_CUSTREFUND &&
				$row['type'] != ST_BANKDEPOSIT
		) // customer payment or bank deposit printout not defined yet.
		{
			return print_document_link($row['trans_no'] . "-" . $row['type'], _("Print"), true, $row['type'], ICON_PRINT, 'button');
		}
		else
		{
			return print_document_link(
				$row['trans_no'] . "-" . $row['type'], _("Receipt"), true, $row['type'], ICON_PRINT, 'button');
		}
	}

	$emailBoxEmails = array();
	function email_link($row) {
		static $first = true;
		global $emailBoxEmails;
		//if ($row['type'] != ST_CUSTPAYMENT && $row['type'] != ST_CUSTREFUND &&
		//$row['type'] != ST_BANKDEPOSIT) // customer payment or bank deposit printout not defined yet.
		$debtor = $row['debtor_no'];
		$trans = $row['trans_no'];
		$type = $row['type'];
		$id = $debtor . '-' . $type . '-' . $trans;
		$customer = new Customer($debtor);
		$emails = $customer->getEmailAddresses();
		if (count($emails) > 0 && !isset($emailBoxEmails [$id])) {
			switch ($type) {
				case ST_CUSTDELIVERY:
					$text = "Delivery Notice";
					break;
				case ST_SALESINVOICE:
					$text = "Tax Invoice";
					break;
				case ST_CUSTPAYMENT:
					$text = "Payment Receipt";
					break;
				case ST_CUSTREFUND:
					$text = "Refund Receipt";
					break;
				case ST_CUSTCREDIT:
					$text = "Credit Note";
					break;
			}

			$emailBoxEmails[$id] = submenu_email(_("Email This ").$text, $type, $trans, null, $emails, 0, true);
		} else {
			return;
		}
		if ($first) {
			$emailBox = new Dialog('Select Email Address:', 'emailBox', '');
			$emailBox->addButtons(array('Email' => '$("#EmailButton").trigger("click")', 'Close' => '$(this).dialog("close");'));
			$emailBox->setOptions(array('autoopen' => false, 'modal' => true, 'width' => '"500"', 'height' => '"300"', 'resizeable' => false));
			$emailBox->show();
			$action = <<<JS
	var emailID= $(this).data('emailid');
	\$emailBox.html(emailBoxEmails[emailID]);
	\$emailBox.dialog('open');
	$('#EmailButton').button().click(function() {
		Adv.loader.show();
		var email = $("#EmailSelect").val();
		$.get($(this).data('url') + "&Email="+email,function(responce) {
			$('#msgbox').append(responce);
			\$emailBox.dialog('close');
			Adv.loader.hide();
		});
	});
	return false;
JS;
			JS::addLiveEvent('.email-button', 'click', $action, '#wrapper');

			$first = false;
		}
		ob_start();
		UI::button(false, 'Email', array('class' => 'button email-button', 'data-emailid' => $id));
		return ob_get_clean();
	}

	function check_overdue($row) {
		return (isset($row['OverDue']) && $row['OverDue'] == 1) && (abs($row["TotalAmount"]) - $row["Allocated"] != 0);
	}

	//------------------------------------------------------------------------------------------------
	$date_after = date2sql($_POST['TransAfterDate']);
	$date_to = date2sql($_POST['TransToDate']);
	if (AJAX_REFERRER && isset($_POST['ajaxsearch'])) {
		$searchArray = trim($_POST['ajaxsearch']);
		$searchArray = explode(' ', $searchArray);
		unset($_POST['customer_id']);
		unset($_POST['filterType']);
		if ($searchArray[0] == 'd') {
			$filter = " AND type = " . ST_CUSTDELIVERY . " ";
		}
		elseif ($searchArray[0] == 'i') {
			$filter = " AND (type = " . ST_SALESINVOICE . " OR type = " . ST_BANKPAYMENT . ") ";
		}
		elseif ($searchArray[0] == 'p') {
			$filter = " AND (type = " . ST_CUSTPAYMENT . " OR type = " . ST_CUSTREFUND . " OR type = " . ST_BANKDEPOSIT . ") ";
		}

	}
	$sql = "SELECT
  		trans.type, 
		trans.trans_no, 
		trans.order_, 
		trans.reference,
		trans.tran_date, 
		trans.due_date, 
		debtor.name,
		debtor.debtor_no,
		branch.br_name,
		debtor.curr_code,
		(trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount)	AS TotalAmount, ";
	if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT) {
		$sql .= "@bal := @bal+(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount), ";
	}
	//	else
	//		$sql .= "IF(trans.type=".ST_CUSTDELIVERY.",'', IF(trans.type=".ST_SALESINVOICE." OR trans.type=".ST_BANKPAYMENT.",@bal := @bal+
	//			(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount), @bal := @bal-
	//			(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount))) , ";
	$sql .= "trans.alloc AS Allocated,
		((trans.type = " . ST_SALESINVOICE . ")
			AND trans.due_date < '" . date2sql(Today()) . "') AS OverDue
		FROM debtor_trans as trans, debtors_master as debtor, cust_branch as branch
		WHERE debtor.debtor_no = trans.debtor_no
		AND trans.branch_code = branch.branch_code";
	if (AJAX_REFERRER && !empty($_POST['ajaxsearch'])) {
		$sql = "SELECT * FROM debtor_trans_view WHERE ";
		foreach ($searchArray as $ajaxsearch) {
			if (empty($ajaxsearch)) {
				continue;
			}
			$sql .= ($ajaxsearch == $searchArray[0]) ? " (" : " AND (";
			if ($ajaxsearch[0] == "$") {
				if (substr($ajaxsearch, -1) == 0 && substr($ajaxsearch, -3, 1) == '.') {
					$ajaxsearch = (substr($ajaxsearch, 0, -1));
				}
				$sql .= "TotalAmount LIKE " . db_escape('%' . substr($ajaxsearch, 1) . '%') . ") ";
				continue;
			}
			;
			if (stripos($ajaxsearch, '/') > 0) {
				$sql .= " tran_date LIKE '%" . date2sql($ajaxsearch, false) . "%' OR";
			}
			$ajaxsearch = "%" . $ajaxsearch . "%";
			$sql .= " name LIKE " . db_escape($ajaxsearch);
			if (countFilter('debtor_trans_view', 'trans_no', $ajaxsearch) > 0) {
				$sql .= " OR trans_no LIKE " . db_escape($ajaxsearch);
				//	$_POST['OrderNumber'] = $ajaxsearch;
			}
			if (countFilter('debtor_trans_view', 'reference', $ajaxsearch) > 0) {
				$sql .= " OR reference LIKE " . db_escape($ajaxsearch);
				//	$_POST['reference'] = $ajaxsearch;
			}
			if (countFilter('debtor_trans_view', 'order_', $ajaxsearch) > 0) {
				$sql .= " OR order_ LIKE " . db_escape($ajaxsearch);
			}
			$sql .= " OR br_name LIKE " . db_escape($ajaxsearch);
			$sql .= ") ";
		}
		if (isset($filter) && $filter) {
			$sql .= $filter;
		}
	}
	else {
		$sql .= " AND trans.tran_date >= '$date_after'
			AND trans.tran_date <= '$date_to'";
	}
	if ($_POST['reference'] != ALL_TEXT) {
		$number_like = "%" . $_POST['reference'] . "%";
		$sql .= " AND trans.reference LIKE " . db_escape($number_like);
	}
	if (isset($_POST['customer_id']) && $_POST['customer_id'] != ALL_TEXT) {
		$sql .= " AND trans.debtor_no = " . db_escape($_POST['customer_id']);
	}
	if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT) {
		if ($_POST['filterType'] == '1') {
			$sql .= " AND (trans.type = " . ST_SALESINVOICE . " OR trans.type = " . ST_BANKPAYMENT . ") ";
		}
		elseif ($_POST['filterType'] == '2') {
			$sql .= " AND (trans.type = " . ST_SALESINVOICE . ") ";
		}
		elseif ($_POST['filterType'] == '3') {
			$sql .= " AND (trans.type = " . ST_CUSTPAYMENT . " OR trans.type = " . ST_CUSTREFUND . " OR trans.type = " . ST_BANKDEPOSIT . " OR trans.type = " . ST_BANKDEPOSIT . ") ";
		}
		elseif ($_POST['filterType'] == '4') {
			$sql .= " AND trans.type = " . ST_CUSTCREDIT . " ";
		}
		elseif ($_POST['filterType'] == '5') {
			$sql .= " AND trans.type = " . ST_CUSTDELIVERY . " ";
		} elseif ($_POST['filterType'] == '6') {
			$sql .= " AND trans.type = " . ST_SALESINVOICE . " ";
		}
		if ($_POST['filterType'] == '2') {
			$today = date2sql(Today());
			$sql .= " AND trans.due_date < '$today'
				AND (trans.ov_amount + trans.ov_gst + trans.ov_freight_tax + 
				trans.ov_freight + trans.ov_discount - trans.alloc > 0) ";
		}
	}
	//------------------------------------------------------------------------------------------------
	db_query("set @bal:=0");
	$cols = array(_("Type") => array('fun' => 'systype_name', 'ord' => ''),
		_("#") => array('fun' => 'trans_view', 'ord' => ''), _("Order") => array('fun' => 'order_view'),
		_("Reference") => array('ord' => ''),
		_("Date") => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'),
		_("Due Date") => array('type' => 'date', 'fun' => 'due_date'),
		_("Customer") => array('ord' => ''),
		_("customer_id") => 'skip',
		_("Branch") => array('ord' => ''), _("Currency") => array('align' => 'center'),
		_("Debit") => array('align' => 'right', 'fun' => 'fmt_debit'),
		_("Credit") => array('align' => 'right', 'insert' => true, 'fun' => 'fmt_credit'),
		_("RB") => array('align' => 'right', 'type' => 'amount'), array('insert' => true, 'fun' => 'gl_view'),
		array('insert' => true, 'align' => 'center', 'fun' => 'credit_link'),
		array('insert' => true, 'align' => 'center', 'fun' => 'edit_link'),
		array('insert' => true, 'align' => 'center', 'fun' => 'email_link'),
		array('insert' => true, 'align' => 'center', 'fun' => 'prt_link'));
	if (isset($_POST['customer_id']) && $_POST['customer_id'] != ALL_TEXT) {
		$cols[_("Customer")] = 'skip';
		$cols[_("Currency")] = 'skip';
	}
	if (isset($_POST['filterType']) && $_POST['filterType'] == ALL_TEXT || !empty($_POST['ajaxsearch'])) {
		$cols[_("RB")] = 'skip';
	}
	$table =& new_db_pager('trans_tbl', $sql, $cols);
	$table->set_marker('check_overdue', _("Marked items are overdue."));
	$table->width = "80%";
	display_db_pager($table);
	JS::beforeload('var emailBoxEmails=' . json_encode($emailBoxEmails) . ';');
	end_form();
	end_page();

?>
