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
	$page_security = isset($_GET['NewPayment']) || (isset($_SESSION['pay_items']) && $_SESSION['pay_items']->trans_type == ST_BANKPAYMENT)? 'SA_PAYMENT' : 'SA_DEPOSIT';
	$js = '';
	JS::open_window(800, 500);
	if (isset($_GET['NewPayment'])) {
		$_SESSION['page_title'] = _($help_context = "Bank Account Payment Entry");
		handle_new_order(ST_BANKPAYMENT);
	} else if (isset($_GET['NewDeposit'])) {
		$_SESSION['page_title'] = _($help_context = "Bank Account Deposit Entry");
		handle_new_order(ST_BANKDEPOSIT);
	}
	Page::start($_SESSION['page_title']);
	Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
	if (list_updated('PersonDetailID')) {
		$br = Sales_Branch::get(get_post('PersonDetailID'));
		$_POST['person_id'] = $br['debtor_no'];
		Ajax::i()->activate('person_id');
	}
	function line_start_focus() {

		Ajax::i()->activate('items_table');
		JS::set_focus('_code_id_edit');
	}

	if (isset($_GET['AddedID'])) {
		$trans_no = $_GET['AddedID'];
		$trans_type = ST_BANKPAYMENT;
		Errors::notice(_("Payment $trans_no has been entered"));
		Display::note(GL_UI::view($trans_type, $trans_no, _("&View the GL Postings for this Payment")));
		Display::link_params($_SERVER['PHP_SELF'], _("Enter Another &Payment"), "NewPayment=yes");
		Display::link_params($_SERVER['PHP_SELF'], _("Enter A &Deposit"), "NewDeposit=yes");
		Page::footer_exit();
	}
	if (isset($_GET['AddedDep'])) {
		$trans_no = $_GET['AddedDep'];
		$trans_type = ST_BANKDEPOSIT;
		Errors::notice(_("Deposit $trans_no has been entered"));
		Display::note(GL_UI::view($trans_type, $trans_no, _("View the GL Postings for this Deposit")));
		Display::link_params($_SERVER['PHP_SELF'], _("Enter Another Deposit"), "NewDeposit=yes");
		Display::link_params($_SERVER['PHP_SELF'], _("Enter A Payment"), "NewPayment=yes");
		Page::footer_exit();
	}
	if (isset($_POST['_date__changed'])) {
		Ajax::i()->activate('_ex_rate');
	}
	function handle_new_order($type) {
		if (isset($_SESSION['pay_items'])) {
			unset ($_SESSION['pay_items']);
		}
		//session_register("pay_items");
		$_SESSION['pay_items'] = new Item_Order($type);
		$_POST['date_'] = Dates::new_doc_date();
		if (!Dates::is_date_in_fiscalyear($_POST['date_'])) {
			$_POST['date_'] = Dates::end_fiscalyear();
		}
		$_SESSION['pay_items']->tran_date = $_POST['date_'];
	}

	if (isset($_POST['Process'])) {
		$input_error = 0;
		if ($_SESSION['pay_items']->count_gl_items() < 1) {
			Errors::error(_("You must enter at least one payment line."));
			JS::set_focus('code_id');
			$input_error = 1;
		}
		if ($_SESSION['pay_items']->gl_items_total() == 0.0) {
			Errors::error(_("The total bank amount cannot be 0."));
			JS::set_focus('code_id');
			$input_error = 1;
		}
		if (!Ref::is_valid($_POST['ref'])) {
			Errors::error(_("You must enter a reference."));
			JS::set_focus('ref');
			$input_error = 1;
		}
		elseif (!Ref::is_new($_POST['ref'], $_SESSION['pay_items']->trans_type)) {
			$_POST['ref'] = Ref::get_next($_SESSION['pay_items']->trans_type);
					}
		if (!Dates::is_date($_POST['date_'])) {
			Errors::error(_("The entered date for the payment is invalid."));
			JS::set_focus('date_');
			$input_error = 1;
		}
		elseif (!Dates::is_date_in_fiscalyear($_POST['date_']))
		{
			//	Errors::error(_("The entered date is not in fiscal year."));
			//	JS::set_focus('date_');
			//	$input_error = 1;
		}
		if ($input_error == 1) {
			unset($_POST['Process']);
		}
	}
	if (isset($_POST['Process'])) {
		$trans = GL_Bank::add_bank_transaction(
			$_SESSION['pay_items']->trans_type, $_POST['bank_account'],
			$_SESSION['pay_items'], $_POST['date_'],
			$_POST['PayType'], $_POST['person_id'], get_post('PersonDetailID'),
			$_POST['ref'], $_POST['memo_']
		);
		$trans_type = $trans[0];
		$trans_no = $trans[1];
		Dates::new_doc_date($_POST['date_']);
		$_SESSION['pay_items']->clear_items();
		unset($_SESSION['pay_items']);
		Display::meta_forward(
			$_SERVER['PHP_SELF'], $trans_type == ST_BANKPAYMENT ?
			 "AddedID=$trans_no" : "AddedDep=$trans_no"
		);
	} /*end of process credit note */
	function check_item_data() {
		//if (!Validation::is_num('amount', 0))
		//{
		//	Errors::error( _("The amount entered is not a valid number or is less than zero."));
		//	JS::set_focus('amount');
		//	return false;
		//}
		if ($_POST['code_id'] == $_POST['bank_account']) {
			Errors::error(_("The source and destination accouts cannot be the same."));
			JS::set_focus('code_id');
			return false;
		}
		//if (Bank_Account::is($_POST['code_id']))
		//{
		//	if ($_SESSION['pay_items']->trans_type == ST_BANKPAYMENT)
		//		Errors::error( _("You cannot make a payment to a bank account. Please use the transfer funds facility for this."));
		//	else
		//		Errors::error( _("You cannot make a deposit from a bank account. Please use the transfer funds facility for this."));
		//	JS::set_focus('code_id') ;
		//	return false;
		//}
		return true;
	}

	function handle_update_item() {
		$amount = ($_SESSION['pay_items']->trans_type == ST_BANKPAYMENT ? 1 : -1) * Validation::input_num('amount');
		if ($_POST['UpdateItem'] != "" && check_item_data()) {
			$_SESSION['pay_items']->update_gl_item(
				$_POST['Index'], $_POST['code_id'],
				$_POST['dimension_id'], $_POST['dimension2_id'], $amount,
				$_POST['LineMemo']
			);
		}
		line_start_focus();
	}

	function handle_delete_item($id) {
		$_SESSION['pay_items']->remove_gl_item($id);
		line_start_focus();
	}

	function handle_new_item() {
		if (!check_item_data()) {
			return;
		}
		$amount = ($_SESSION['pay_items']->trans_type == ST_BANKPAYMENT ? 1 : -1) * Validation::input_num('amount');
		$_SESSION['pay_items']->add_gl_item(
			$_POST['code_id'], $_POST['dimension_id'],
			$_POST['dimension2_id'], $amount, $_POST['LineMemo']
		);
		line_start_focus();
	}

	$id = find_submit(MODE_DELETE);
	if ($id != -1) {
		handle_delete_item($id);
	}
	if (isset($_POST['AddItem'])) {
		handle_new_item();
	}
	if (isset($_POST['UpdateItem'])) {
		handle_update_item();
	}
	if (isset($_POST['CancelItemChanges'])) {
		line_start_focus();
	}
	if (isset($_POST['go'])) {
		GL_QuickEntry::show_menu(
			$_SESSION['pay_items'], $_POST['person_id'], Validation::input_num('total_amount'),
			$_SESSION['pay_items']->trans_type == ST_BANKPAYMENT ? QE_PAYMENT : QE_DEPOSIT
		);
		$_POST['total_amount'] = Num::price_format(0);
		Ajax::i()->activate('total_amount');
		line_start_focus();
	}
	start_form();
	Bank_UI::header($_SESSION['pay_items']);
	start_table('tablesstyle2 width90 pad10');
	start_row();
	echo "<td>";
	Bank_UI::items(
		$_SESSION['pay_items']->trans_type == ST_BANKPAYMENT ?
		 _("Payment Items") : _("Deposit Items"), $_SESSION['pay_items']
	);
	Bank_UI::option_controls();
	echo "</td>";
	end_row();
	end_table(1);
	submit_center_first('Update', _("Update"), '', null);
	submit_center_last(
		'Process', $_SESSION['pay_items']->trans_type == ST_BANKPAYMENT ?
		 _("Process Payment") : _("Process Deposit"), '', 'default'
	);
	end_form();
	Renderer::end_page();

?>
