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
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

	JS::open_window(900, 500);
Page::start(_($help_context = "Supplier Credit Note"), SA_SUPPLIERCREDIT);
	Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
	if (isset($_GET['AddedID'])) {
		$invoice_no = $_GET['AddedID'];
		$trans_type = ST_SUPPCREDIT;
		echo "<div class='center'>";
		Errors::notice(_("Supplier credit note has been processed."));
		Display::note(GL_UI::trans_view($trans_type, $invoice_no, _("View this Credit Note")));
		Display::note(GL_UI::view($trans_type, $invoice_no, _("View the GL Journal Entries for this Credit Note")), 1);
		Display::link_params($_SERVER['PHP_SELF'], _("Enter Another Credit Note"), "New=1");
		Display::link_params("/system/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$invoice_no");
		Page::footer_exit();
	}
	if (isset($_GET['New'])) {
		Creditor_Trans::i(true)->is_invoice = false;
		if (isset($_GET['invoice_no'])) {
			Creditor_Trans::i()->supp_reference = $_POST['invoice_no'] = $_GET['invoice_no'];
		}
	}
	//	GL postings are often entered in the same form to two accounts
	// so fileds are cleared only on user demand.
	//
	if (isset($_POST['ClearFields'])) {
		clear_fields();
	}
	if (isset($_POST['AddGLCodeToTrans'])) {
		Ajax::i()->activate('gl_items');
		$input_error = false;
		$sql = "SELECT account_code, account_name FROM chart_master WHERE account_code=" . DB::escape($_POST['gl_code']);
		$result = DB::query($sql, "get account information");
		if (DB::num_rows($result) == 0) {
			Errors::error(_("The account code entered is not a valid code, this line cannot be added to the transaction."));
			JS::set_focus('gl_code');
			$input_error = true;
		}
		else {
			$myrow = DB::fetch_row($result);
			$gl_act_name = $myrow[1];
			if (!Validation::is_num('amount')) {
				Errors::error(_("The amount entered is not numeric. This line cannot be added to the transaction."));
				JS::set_focus('amount');
				$input_error = true;
			}
		}
		if (!Tax_Types::is_tax_gl_unique(get_post('gl_code'))) {
			Errors::error(_("Cannot post to GL account used by more than one tax type."));
			JS::set_focus('gl_code');
			$input_error = true;
		}
		if ($input_error == false) {
			Creditor_Trans::i()
			 ->add_gl_codes_to_trans($_POST['gl_code'], $gl_act_name, $_POST['dimension_id'], $_POST['dimension2_id'], Validation::input_num('amount'), $_POST['memo_']);
			JS::set_focus('gl_code');
		}
	}
	if (isset($_POST['PostCreditNote'])) {
		handle_commit_credit_note();
	}
	$id = find_submit('grn_item_id');
	if ($id != -1) {
		commit_item_data($id);
	}
	if (isset($_POST['InvGRNAll'])) {
		foreach ($_POST as $postkey => $postval) {
			if (strpos($postkey, "qty_recd") === 0) {
				$id = substr($postkey, strlen("qty_recd"));
				$id = (int)$id;
				commit_item_data($id);
			}
		}
	}
	$id3 = find_submit(MODE_DELETE);
	if ($id3 != -1) {
		Creditor_Trans::i()->remove_grn_from_trans($id3);
		Ajax::i()->activate('grn_items');
		Ajax::i()->activate('inv_tot');
	}
	$id4 = find_submit('Delete2');
	if ($id4 != -1) {
		Creditor_Trans::i()->remove_gl_codes_from_trans($id4);
		clear_fields();
		Ajax::i()->activate('gl_items');
		Ajax::i()->activate('inv_tot');
	}
	if (isset($_POST['RefreshInquiry'])) {
		Ajax::i()->activate('grn_items');
		Ajax::i()->activate('inv_tot');
	}
	if (isset($_POST['go'])) {
		Ajax::i()->activate('gl_items');
		GL_QuickEntry::show_menu(Creditor_Trans::i(), $_POST['qid'], Validation::input_num('total_amount'), QE_SUPPINV);
		$_POST['total_amount'] = Num::price_format(0);
		Ajax::i()->activate('total_amount');
		Ajax::i()->activate('inv_tot');
	}
	start_form();
	Purch_Invoice::header(Creditor_Trans::i());
	if ($_POST['supplier_id'] != '') {
		$total_grn_value = Purch_GRN::display_items(Creditor_Trans::i(), 1);
		$total_gl_value = Purch_GLItem::display_items(Creditor_Trans::i(), 1);
		Display::div_start('inv_tot');
		Purch_Invoice::totals(Creditor_Trans::i());
		Display::div_end();
	}
	if ($id != -1) {
		Ajax::i()->activate('grn_items');
		Ajax::i()->activate('inv_tot');
	}
	if (get_post('AddGLCodeToTrans')) {
		Ajax::i()->activate('inv_tot');
	}
	Display::br();
	submit_center('PostCreditNote', _("Enter Credit Note"), true, '', 'default');
	Display::br();
	end_form();
	Page::end();
	/**
	 * @return bool
	 */
	function check_data() {
		global $total_grn_value, $total_gl_value;
		if (!Creditor_Trans::i()->is_valid_trans_to_post()) {
			Errors::error(_("The credit note cannot be processed because the there are no items or values on the invoice. Credit notes are expected to have a charge."));
			JS::set_focus('');
			return false;
		}
		if (!Ref::is_valid(Creditor_Trans::i()->reference)) {
			Errors::error(_("You must enter an credit note reference."));
			JS::set_focus('reference');
			return false;
		}
		if (!Ref::is_new(Creditor_Trans::i()->reference, ST_SUPPCREDIT)) {
			Creditor_Trans::i()->reference = Ref::get_next(ST_SUPPCREDIT);
		}
		if (!Ref::is_valid(Creditor_Trans::i()->supp_reference)) {
			Errors::error(_("You must enter a supplier's credit note reference."));
			JS::set_focus('supp_reference');
			return false;
		}
		if (!Dates::is_date(Creditor_Trans::i()->tran_date)) {
			Errors::error(_("The credit note as entered cannot be processed because the date entered is not valid."));
			JS::set_focus('tran_date');
			return false;
		}
		elseif (!Dates::is_date_in_fiscalyear(Creditor_Trans::i()->tran_date)) {
			Errors::error(_("The entered date is not in fiscal year."));
			JS::set_focus('tran_date');
			return false;
		}
		if (!Dates::is_date(Creditor_Trans::i()->due_date)) {
			Errors::error(_("The invoice as entered cannot be processed because the due date is in an incorrect format."));
			JS::set_focus('due_date');
			return false;
		}
		if (Creditor_Trans::i()->ov_amount < ($total_gl_value + $total_grn_value)) {
			Errors::error(_("The credit note total as entered is less than the sum of the the general ledger entires (if any) and the charges for goods received. There must be a mistake somewhere, the credit note as entered will not be processed."));
			return false;
		}
		return true;
	}

	/**
	 * @return mixed
	 */
	function handle_commit_credit_note() {
		Purch_Invoice::copy_to_trans(Creditor_Trans::i());
		if (!check_data()) {
			return;
		}
		if (isset($_POST['invoice_no'])) {
			$invoice_no = Purch_Invoice::add(Creditor_Trans::i(), $_POST['invoice_no']);
		}
		else {
			$invoice_no = Purch_Invoice::add(Creditor_Trans::i());
		}
		Creditor_Trans::i()->clear_items();
		Creditor_Trans::killInstance();
		Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$invoice_no");
	}

	/**
	 *
	 */
	function clear_fields() {
		unset($_POST['gl_code']);
		unset($_POST['dimension_id']);
		unset($_POST['dimension2_id']);
		unset($_POST['amount']);
		unset($_POST['memo_']);
		unset($_POST['AddGLCodeToTrans']);
		Ajax::i()->activate('gl_items');
		JS::set_focus('gl_code');
	}

	/**
	 * @param $n
	 *
	 * @return bool
	 */
	function check_item_data($n) {
		if (!Validation::is_num('This_QuantityCredited' . $n, 0)) {
			Errors::error(_("The quantity to credit must be numeric and greater than zero."));
			JS::set_focus('This_QuantityCredited' . $n);
			return false;
		}
		if (!Validation::is_num('ChgPrice' . $n, 0)) {
			Errors::error(_("The price is either not numeric or negative."));
			JS::set_focus('ChgPrice' . $n);
			return false;
		}
		return true;
	}

	/**
	 * @param $n
	 */
	function commit_item_data($n) {
		if (check_item_data($n)) {
			$complete = False;
			Creditor_Trans::i()
			 ->add_grn_to_trans($n, $_POST['po_detail_item' . $n], $_POST['item_code' . $n], $_POST['description' . $n], $_POST['qty_recd' . $n], $_POST['prev_quantity_inv' . $n], Validation::input_num('This_QuantityCredited' . $n), $_POST['order_price' . $n], Validation::input_num('ChgPrice' . $n), $complete, $_POST['std_cost_unit' . $n], "");
		}
	}

?>
