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
	$page_security = 'SA_VOIDTRANSACTION';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(800, 500);
	Page::start(_($help_context = "Void a Transaction"));
	//----------------------------------------------------------------------------------------
	function exist_transaction($type, $type_no)
	{
		$void_entry = Voiding::has($type, $type_no);
		if ($void_entry > 0) {
			return false;
		}
		switch ($type)
		{
		case ST_JOURNAL : // it's a journal entry
			if (!GL_Trans::exists($type, $type_no)) {
				return false;
			}
			break;
		case ST_BANKPAYMENT : // it's a payment
		case ST_BANKDEPOSIT : // it's a deposit
		case ST_BANKTRANSFER : // it's a transfer
			if (!Bank_Trans::exists($type, $type_no)) {
				return false;
			}
			break;
		case ST_SALESINVOICE : // it's a customer invoice
		case ST_CUSTCREDIT : // it's a customer credit note
		case ST_CUSTPAYMENT : // it's a customer payment
		case ST_CUSTREFUND : // it's a customer refund
		case ST_CUSTDELIVERY : // it's a customer dispatch
			if (!Sales_Trans::exists($type, $type_no)) {
				return false;
			}
			break;
		case ST_LOCTRANSFER : // it's a stock transfer
			if (Inv_Transfer::get_items( $type_no) == null) {
				return false;
			}
			break;
		case ST_INVADJUST : // it's a stock adjustment
			if (Inv_Adjustment::get_items($type_no) == null) {
				return false;
			}
			break;
		case ST_PURCHORDER : // it's a PO
		case ST_SUPPRECEIVE : // it's a GRN
			return false;
		case ST_SUPPINVOICE : // it's a suppler invoice
		case ST_SUPPCREDIT : // it's a supplier credit note
		case ST_SUPPAYMENT : // it's a supplier payment
			if (!Purch_Trans::exists($type, $type_no)) {
				return false;
			}
			break;
		case ST_WORKORDER : // it's a work order
			if (!get_work_order($type_no, true)) {
				return false;
			}
			break;
		case ST_MANUISSUE : // it's a work order issue
			if (!exists_work_order_issue($type_no)) {
				return false;
			}
			break;
		case ST_MANURECEIVE : // it's a work order production
			if (!exists_work_order_produce($type_no)) {
				return false;
			}
			break;
		case ST_SALESORDER: // it's a sales order
		case ST_SALESQUOTE: // it's a sales quotation
			return false;
		case ST_COSTUPDATE : // it's a stock cost update
			return false;
			break;
		}
		return true;
	}

	function voiding_controls()
	{
		start_form();
		start_table(Config::get('tables_style2'));
		systypes_list_row(_("Transaction Type:"), "filterType", null, true);
		text_row(_("Transaction #:"), 'trans_no', null, 12, 12);
		date_row(_("Voiding Date:"), 'date_');
		textarea_row(_("Memo:"), 'memo_', null, 30, 4);
		end_table(1);
		if (!isset($_POST['ProcessVoiding'])) {
			submit_center('ProcessVoiding', _("Void Transaction"), true, '', 'default');
		} else {
			if (!exist_transaction($_POST['filterType'], $_POST['trans_no'])) {
				Errors::error(_("The entered transaction does not exist or cannot be voided."));
				unset($_POST['trans_no']);
				unset($_POST['memo_']);
				unset($_POST['date_']);
				submit_center('ProcessVoiding', _("Void Transaction"), true, '', 'default');
			} else {
				Errors::warning(_("Are you sure you want to void this transaction ? This action cannot be undone."), 0, 1);
				if ($_POST['filterType'] == ST_JOURNAL) // GL transaction are not included in get_trans_view_str
				{
					$view_str = ui_view::get_gl_view_str($_POST['filterType'], $_POST['trans_no'], _("View Transaction"));
				} else {
					$view_str = ui_view::get_trans_view_str($_POST['filterType'], $_POST['trans_no'], _("View Transaction"));
				}
				Errors::warning($view_str);
				br();
				submit_center_first('ConfirmVoiding', _("Proceed"), '', true);
				submit_center_last('CancelVoiding', _("Cancel"), '', 'cancel');
			}
		}
		end_form();
	}

	//----------------------------------------------------------------------------------------
	function check_valid_entries()
	{
		if (DB_AuditTrail::is_closed_trans($_POST['filterType'], $_POST['trans_no'])) {
			Errors::error(_("The selected transaction was closed for edition and cannot be voided."));
			JS::set_focus('trans_no');
			return;
		}
		if (!Dates::is_date($_POST['date_'])) {
			Errors::error(_("The entered date is invalid."));
			JS::set_focus('date_');
			return false;
		}
		if (!Dates::is_date_in_fiscalyear($_POST['date_'])) {
			Errors::error(_("The entered date is not in fiscal year."));
			JS::set_focus('date_');
			return false;
		}
		if (!is_numeric($_POST['trans_no']) OR $_POST['trans_no'] <= 0) {
			Errors::error(_("The transaction number is expected to be numeric and greater than zero."));
			JS::set_focus('trans_no');
			return false;
		}
		return true;
	}

	//----------------------------------------------------------------------------------------
	function handle_void_transaction()
	{
		if (check_valid_entries() == true) {
			$void_entry = Voiding::get($_POST['filterType'], $_POST['trans_no']);
			if ($void_entry != null) {
				Errors::error(_("The selected transaction has already been voided."), true);
				unset($_POST['trans_no']);
				unset($_POST['memo_']);
				unset($_POST['date_']);
				JS::set_focus('trans_no');
				return;
			}
			$ret = Voiding::void(
				$_POST['filterType'], $_POST['trans_no'],
				$_POST['date_'], $_POST['memo_']
			);
			if ($ret) {
				Errors::notice(_("Selected transaction has been voided."));
				unset($_POST['trans_no']);
				unset($_POST['memo_']);
				unset($_POST['date_']);
			}
			else {
				Errors::error(_("The entered transaction does not exist or cannot be voided."));
				JS::set_focus('trans_no');
			}
		}
	}

	//----------------------------------------------------------------------------------------
	if (!isset($_POST['date_'])) {
		$_POST['date_'] = Dates::Today();
		if (!Dates::is_date_in_fiscalyear($_POST['date_'])) {
			$_POST['date_'] = Dates::end_fiscalyear();
		}
	}
	if (isset($_POST['ProcessVoiding'])) {
		if (!check_valid_entries()) {
			unset($_POST['ProcessVoiding']);
		}
		$Ajax->activate('_page_body');
	}
	if (isset($_POST['ConfirmVoiding'])) {
		handle_void_transaction();
		$Ajax->activate('_page_body');
	}
	if (isset($_POST['CancelVoiding'])) {
		$Ajax->activate('_page_body');
	}
	//----------------------------------------------------------------------------------------
	voiding_controls();
	end_page();

?>