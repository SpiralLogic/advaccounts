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
	$page_security = 'SA_SUPPLIERCREDIT';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	include_once(APP_PATH . "purchasing/includes/purchasing_ui.php");
	$js = "";
	if (Config::get('ui.windows.popups'))
		$js .= ui_view::get_js_open_window(900, 500);

	page(_($help_context = "Supplier Credit Note"), false, false, "", $js);

	//----------------------------------------------------------------------------------------

	Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));

	//---------------------------------------------------------------------------------------------------------------

	if (isset($_GET['AddedID'])) {
		$invoice_no = $_GET['AddedID'];
		$trans_type = ST_SUPPCREDIT;

		echo "<center>";
		ui_msgs::display_notification_centered(_("Supplier credit note has been processed."));
		ui_msgs::display_warning(ui_view::get_trans_view_str($trans_type, $invoice_no, _("View this Credit Note")));

		ui_msgs::display_warning(ui_view::get_gl_view_str($trans_type, $invoice_no, _("View the GL Journal Entries for this Credit Note")), 1);

		hyperlink_params($_SERVER['PHP_SELF'], _("Enter Another Credit Note"), "New=1");
		hyperlink_params("/admin/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$invoice_no");

		ui_view::display_footer_exit();
	}

	//---------------------------------------------------------------------------------------------------

	if (isset($_GET['New'])) {
		if (isset($_SESSION['supp_trans'])) {
			unset ($_SESSION['supp_trans']->grn_items);
			unset ($_SESSION['supp_trans']->gl_codes);
			unset ($_SESSION['supp_trans']);
		}

		$_SESSION['supp_trans'] = new suppTrans;
		$_SESSION['supp_trans']->is_invoice = false;
		if (isset($_GET['invoice_no'])) {
			$_SESSION['supp_trans']->supp_reference = $_POST['invoice_no'] = $_GET['invoice_no'];
		}
	}

	function clear_fields() {
		$Ajax = Ajax::instance();

		unset($_POST['gl_code']);
		unset($_POST['dimension_id']);
		unset($_POST['dimension2_id']);
		unset($_POST['amount']);
		unset($_POST['memo_']);
		unset($_POST['AddGLCodeToTrans']);
		$Ajax->activate('gl_items');
		ui_view::set_focus('gl_code');
	}

	//------------------------------------------------------------------------------------------------
	//	GL postings are often entered in the same form to two accounts
	//  so fileds are cleared only on user demand.
	//
	if (isset($_POST['ClearFields'])) {
		clear_fields();
	}

	if (isset($_POST['AddGLCodeToTrans'])) {

		$Ajax->activate('gl_items');
		$input_error = false;

		$sql = "SELECT account_code, account_name FROM chart_master WHERE account_code=" . DBOld::escape(
			$_POST['gl_code']);
		$result = DBOld::query($sql, "get account information");
		if (DBOld::num_rows($result) == 0) {
			ui_msgs::display_error(_("The account code entered is not a valid code, this line cannot be added to the transaction."));
			ui_view::set_focus('gl_code');
			$input_error = true;
		}
		else
		{
			$myrow = DBOld::fetch_row($result);
			$gl_act_name = $myrow[1];
			if (!Validation::is_num('amount')) {
				ui_msgs::display_error(_("The amount entered is not numeric. This line cannot be added to the transaction."));
				ui_view::set_focus('amount');
				$input_error = true;
			}
		}

		if (!Tax_Types::is_tax_gl_unique(get_post('gl_code'))) {
			ui_msgs::display_error(_("Cannot post to GL account used by more than one tax type."));
			ui_view::set_focus('gl_code');
			$input_error = true;
		}

		if ($input_error == false) {
			$_SESSION['supp_trans']->add_gl_codes_to_trans($_POST['gl_code'], $gl_act_name,
				$_POST['dimension_id'], $_POST['dimension2_id'],
				input_num('amount'), $_POST['memo_']);
			ui_view::set_focus('gl_code');
		}
	}

	//---------------------------------------------------------------------------------------------------

	function check_data() {
		global $total_grn_value, $total_gl_value;

		if (!$_SESSION['supp_trans']->is_valid_trans_to_post()) {
			ui_msgs::display_error(_("The credit note cannot be processed because the there are no items or values on the invoice.  Credit notes are expected to have a charge."));
			ui_view::set_focus('');
			return false;
		}

		if (!Refs::is_valid($_SESSION['supp_trans']->reference)) {
			ui_msgs::display_error(_("You must enter an credit note reference."));
			ui_view::set_focus('reference');
			return false;
		}

		if (!is_new_reference($_SESSION['supp_trans']->reference, ST_SUPPCREDIT)) {
			ui_msgs::display_error(_("The entered reference is already in use."));
			ui_view::set_focus('reference');
			return false;
		}

		if (!Refs::is_valid($_SESSION['supp_trans']->supp_reference)) {
			ui_msgs::display_error(_("You must enter a supplier's credit note reference."));
			ui_view::set_focus('supp_reference');
			return false;
		}

		if (!Dates::is_date($_SESSION['supp_trans']->tran_date)) {
			ui_msgs::display_error(_("The credit note as entered cannot be processed because the date entered is not valid."));
			ui_view::set_focus('tran_date');
			return false;
		}
		elseif (!Dates::is_date_in_fiscalyear($_SESSION['supp_trans']->tran_date))
		{
			ui_msgs::display_error(_("The entered date is not in fiscal year."));
			ui_view::set_focus('tran_date');
			return false;
		}
		if (!Dates::is_date($_SESSION['supp_trans']->due_date)) {
			ui_msgs::display_error(_("The invoice as entered cannot be processed because the due date is in an incorrect format."));
			ui_view::set_focus('due_date');
			return false;
		}

		if ($_SESSION['supp_trans']->ov_amount < ($total_gl_value + $total_grn_value)) {
			ui_msgs::display_error(_("The credit note total as entered is less than the sum of the the general ledger entires (if any) and the charges for goods received. There must be a mistake somewhere, the credit note as entered will not be processed."));
			return false;
		}

		return true;
	}

	//---------------------------------------------------------------------------------------------------

	function handle_commit_credit_note() {
		copy_to_trans($_SESSION['supp_trans']);

		if (!check_data())
			return;

		if (isset($_POST['invoice_no']))
			$invoice_no = add_supp_invoice($_SESSION['supp_trans'], $_POST['invoice_no']);
		else
			$invoice_no = add_supp_invoice($_SESSION['supp_trans']);

		$_SESSION['supp_trans']->clear_items();
		unset($_SESSION['supp_trans']);

		meta_forward($_SERVER['PHP_SELF'], "AddedID=$invoice_no");
	}

	//--------------------------------------------------------------------------------------------------

	if (isset($_POST['PostCreditNote'])) {
		handle_commit_credit_note();
	}

	function check_item_data($n) {
		if (!Validation::is_num('This_QuantityCredited' . $n, 0)) {
			ui_msgs::display_error(_("The quantity to credit must be numeric and greater than zero."));
			ui_view::set_focus('This_QuantityCredited' . $n);
			return false;
		}

		if (!Validation::is_num('ChgPrice' . $n, 0)) {
			ui_msgs::display_error(_("The price is either not numeric or negative."));
			ui_view::set_focus('ChgPrice' . $n);
			return false;
		}

		return true;
	}

	function commit_item_data($n) {
		if (check_item_data($n)) {
			$complete = False;

			$_SESSION['supp_trans']->add_grn_to_trans($n,
				$_POST['po_detail_item' . $n], $_POST['item_code' . $n],
				$_POST['description' . $n], $_POST['qty_recd' . $n],
				$_POST['prev_quantity_inv' . $n], input_num('This_QuantityCredited' . $n),
				$_POST['order_price' . $n], input_num('ChgPrice' . $n), $complete,
				$_POST['std_cost_unit' . $n], "");
		}
	}

	//-----------------------------------------------------------------------------------------

	$id = find_submit('grn_item_id');
	if ($id != -1) {
		commit_item_data($id);
	}

	if (isset($_POST['InvGRNAll'])) {
		foreach ($_POST as $postkey => $postval)
		{
			if (strpos($postkey, "qty_recd") === 0) {
				$id = substr($postkey, strlen("qty_recd"));
				$id = (int)$id;
				commit_item_data($id);
			}
		}
	}

	//--------------------------------------------------------------------------------------------------
	$id3 = find_submit('Delete');
	if ($id3 != -1) {
		$_SESSION['supp_trans']->remove_grn_from_trans($id3);
		$Ajax->activate('grn_items');
		$Ajax->activate('inv_tot');
	}

	$id4 = find_submit('Delete2');
	if ($id4 != -1) {
		$_SESSION['supp_trans']->remove_gl_codes_from_trans($id4);
		clear_fields();
		$Ajax->activate('gl_items');
		$Ajax->activate('inv_tot');
	}
	if (isset($_POST['RefreshInquiry'])) {
		$Ajax->activate('grn_items');
		$Ajax->activate('inv_tot');
	}

	if (isset($_POST['go'])) {
		$Ajax->activate('gl_items');
		ui_view::display_quick_entries($_SESSION['supp_trans'], $_POST['qid'], input_num('totamount'), QE_SUPPINV);
		$_POST['totamount'] = price_format(0);
		$Ajax->activate('totamount');
		$Ajax->activate('inv_tot');
	}

	//--------------------------------------------------------------------------------------------------

	start_form();

	invoice_header($_SESSION['supp_trans']);
	if ($_POST['supplier_id'] == '')
		ui_msgs::display_error('No supplier found for entered search text');
	else {
		$total_grn_value = display_grn_items($_SESSION['supp_trans'], 1);

		$total_gl_value = display_gl_items($_SESSION['supp_trans'], 1);

		div_start('inv_tot');
		invoice_totals($_SESSION['supp_trans']);
		div_end();
	}

	if ($id != -1) {
		$Ajax->activate('grn_items');
		$Ajax->activate('inv_tot');
	}

	if (get_post('AddGLCodeToTrans'))
		$Ajax->activate('inv_tot');

	br();
	submit_center('PostCreditNote', _("Enter Credit Note"), true, '', 'default');
	br();

	end_form();
	end_page();
?>
