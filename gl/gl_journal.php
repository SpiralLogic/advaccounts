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
	$page_security = 'SA_JOURNALENTRY';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	include_once(APP_PATH . "gl/includes/ui/gl_journal_ui.php");
	$js = '';
	JS::get_js_open_window(800, 500);
	if (isset($_GET['ModifyGL'])) {
		$_SESSION['page_title'] = sprintf(
			_("Modifying Journal Transaction # %d."),
			$_GET['trans_no']
		);
		$help_context           = "Modifying Journal Entry";
	} else
	{
		$_SESSION['page_title'] = _($help_context = "Journal Entry");
	}
	Page::start($_SESSION['page_title']);
	//--------------------------------------------------------------------------------------------------
	function line_start_focus()
	{
		$Ajax = Ajax::instance();
		$Ajax->activate('items_table');
		JS::set_focus('_code_id_edit');
	}

	//-----------------------------------------------------------------------------------------------
	if (isset($_GET['AddedID'])) {
		$trans_no   = $_GET['AddedID'];
		$trans_type = ST_JOURNAL;
		ui_msgs::display_notification_centered(_("Journal entry has been entered") . " #$trans_no");
		ui_msgs::display_note(ui_view::get_gl_view_str($trans_type, $trans_no, _("&View this Journal Entry")));
		ui_view::reset_focus();
		hyperlink_params($_SERVER['PHP_SELF'], _("Enter &New Journal Entry"), "NewJournal=Yes");
		ui_view::display_footer_exit();
	} elseif (isset($_GET['UpdatedID']))
	{
		$trans_no   = $_GET['UpdatedID'];
		$trans_type = ST_JOURNAL;
		ui_msgs::display_notification_centered(_("Journal entry has been updated") . " #$trans_no");
		ui_msgs::display_note(ui_view::get_gl_view_str($trans_type, $trans_no, _("&View this Journal Entry")));
		hyperlink_no_params(PATH_TO_ROOT . "/gl/inquiry/journal_inquiry.php", _("Return to Journal &Inquiry"));
		ui_view::display_footer_exit();
	}
	//--------------------------------------------------------------------------------------------------
	if (isset($_GET['NewJournal'])) {
		create_cart(0, 0);
	}
	elseif (isset($_GET['ModifyGL']))
	{
		if (!isset($_GET['trans_type']) || $_GET['trans_type'] != 0) {
			ui_msgs::display_error(_("You can edit directly only journal entries created via Journal Entry page."));
			hyperlink_params("/gl/gl_journal.php", _("Entry &New Journal Entry"), "NewJournal=Yes");
			ui_view::display_footer_exit();
		}
		create_cart($_GET['trans_type'], $_GET['trans_no']);
	}
	function create_cart($type = 0, $trans_no = 0)
	{
		if (isset($_SESSION['journal_items'])) {
			unset ($_SESSION['journal_items']);
		}
		$cart           = new itemsCart($type);
		$cart->order_id = $trans_no;
		if ($trans_no) {
			$result = get_gl_trans($type, $trans_no);
			if ($result) {
				while ($row = DBOld::fetch($result)) {
					if ($row['amount'] == 0) {
						continue;
					}
					$date = $row['tran_date'];
					$cart->add_gl_item(
						$row['account'], $row['dimension_id'],
						$row['dimension2_id'], $row['amount'], $row['memo_']
					);
				}
			}
			$cart->memo_           = ui_view::get_comments_string($type, $trans_no);
			$cart->tran_date       = Dates::sql2date($date);
			$cart->reference       = Refs::get($type, $trans_no);
			$_POST['ref_original'] = $cart->reference; // Store for comparison when updating
		} else {
			$cart->reference = Refs::get_next(0);
			$cart->tran_date = Dates::new_doc_date();
			if (!Dates::is_date_in_fiscalyear($cart->tran_date)) {
				$cart->tran_date = Dates::end_fiscalyear();
			}
			$_POST['ref_original'] = -1;
		}
		$_POST['memo_'] = $cart->memo_;
		$_POST['ref']   = $cart->reference;
		$_POST['date_'] = $cart->tran_date;
		$_SESSION['journal_items'] = &$cart;
	}

	//-----------------------------------------------------------------------------------------------
	if (isset($_POST['Process'])) {
		$input_error = 0;
		if ($_SESSION['journal_items']->count_gl_items() < 1) {
			ui_msgs::display_error(_("You must enter at least one journal line."));
			JS::set_focus('code_id');
			$input_error = 1;
		}
		if (abs($_SESSION['journal_items']->gl_items_total()) > 0.0001) {
			ui_msgs::display_error(_("The journal must balance (debits equal to credits) before it can be processed."));
			JS::set_focus('code_id');
			$input_error = 1;
		}
		if (!Dates::is_date($_POST['date_'])) {
			ui_msgs::display_error(_("The entered date is invalid."));
			JS::set_focus('date_');
			$input_error = 1;
		}
		elseif (!Dates::is_date_in_fiscalyear($_POST['date_']))
		{
			ui_msgs::display_error(_("The entered date is not in fiscal year."));
			JS::set_focus('date_');
			$input_error = 1;
		}
		if (!Refs::is_valid($_POST['ref'])) {
			ui_msgs::display_error(_("You must enter a reference."));
			JS::set_focus('ref');
			$input_error = 1;
		}
		elseif (Refs::exists(ST_JOURNAL, $_POST['ref']))
		{
			// The reference can exist already so long as it's the same as the original (when modifying)
			if ($_POST['ref'] != $_POST['ref_original']) {
				ui_msgs::display_error(_("The entered reference is already in use."));
				JS::set_focus('ref');
				$input_error = 1;
			}
		}
		if ($input_error == 1) {
			unset($_POST['Process']);
		}
	}
	if (isset($_POST['Process'])) {
		$cart = $_SESSION['journal_items'];
		$new  = $cart->order_id == 0;
		$cart->reference = $_POST['ref'];
		$cart->memo_     = $_POST['memo_'];
		$cart->tran_date = $_POST['date_'];
		$trans_no = write_journal_entries($cart, check_value('Reverse'));
		$cart->clear_items();
		Dates::new_doc_date($_POST['date_']);
		unset($_SESSION['journal_items']);
		if ($new) {
			meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");
		}
		else
		{
			meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$trans_no");
		}
	}
	//-----------------------------------------------------------------------------------------------
	function check_item_data()
	{
		if (isset($_POST['dimension_id']) && $_POST['dimension_id'] != 0 && dimension_is_closed($_POST['dimension_id'])) {
			ui_msgs::display_error(_("Dimension is closed."));
			JS::set_focus('dimension_id');
			return false;
		}
		if (isset($_POST['dimension2_id']) && $_POST['dimension2_id'] != 0
		 && dimension_is_closed(
			 $_POST['dimension2_id']
		 )
		) {
			ui_msgs::display_error(_("Dimension is closed."));
			JS::set_focus('dimension2_id');
			return false;
		}
		if (!(input_num('AmountDebit') != 0 ^ input_num('AmountCredit') != 0)) {
			ui_msgs::display_error(_("You must enter either a debit amount or a credit amount."));
			JS::set_focus('AmountDebit');
			return false;
		}
		if (strlen($_POST['AmountDebit']) && !Validation::is_num('AmountDebit', 0)) {
			ui_msgs::display_error(_("The debit amount entered is not a valid number or is less than zero."));
			JS::set_focus('AmountDebit');
			return false;
		} elseif (strlen($_POST['AmountCredit']) && !Validation::is_num('AmountCredit', 0))
		{
			ui_msgs::display_error(_("The credit amount entered is not a valid number or is less than zero."));
			JS::set_focus('AmountCredit');
			return false;
		}
		if (!Tax_Types::is_tax_gl_unique(get_post('code_id'))) {
			ui_msgs::display_error(_("Cannot post to GL account used by more than one tax type."));
			JS::set_focus('code_id');
			return false;
		}
		if (!CurrentUser::instance()->can_access('SA_BANKJOURNAL') && Banking::is_bank_account($_POST['code_id'])) {
			ui_msgs::display_error(_("You cannot make a journal entry for a bank account. Please use one of the banking functions for bank transactions."));
			JS::set_focus('code_id');
			return false;
		}
		return true;
	}

	//-----------------------------------------------------------------------------------------------
	function handle_update_item()
	{
		if ($_POST['UpdateItem'] != "" && check_item_data()) {
			if (input_num('AmountDebit') > 0) {
				$amount = input_num('AmountDebit');
			}
			else
			{
				$amount = -input_num('AmountCredit');
			}
			$_SESSION['journal_items']->update_gl_item(
				$_POST['Index'], $_POST['code_id'],
				$_POST['dimension_id'], $_POST['dimension2_id'], $amount,
				$_POST['LineMemo']
			);
		}
		line_start_focus();
	}

	//-----------------------------------------------------------------------------------------------
	function handle_delete_item($id)
	{
		$_SESSION['journal_items']->remove_gl_item($id);
		line_start_focus();
	}

	//-----------------------------------------------------------------------------------------------
	function handle_new_item()
	{
		if (!check_item_data()) {
			return;
		}
		if (input_num('AmountDebit') > 0) {
			$amount = input_num('AmountDebit');
		}
		else
		{
			$amount = -input_num('AmountCredit');
		}
		$_SESSION['journal_items']->add_gl_item(
			$_POST['code_id'], $_POST['dimension_id'],
			$_POST['dimension2_id'], $amount, $_POST['LineMemo']
		);
		line_start_focus();
	}

	//-----------------------------------------------------------------------------------------------
	$id = find_submit('Delete');
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
		ui_view::display_quick_entries($_SESSION['journal_items'], $_POST['person_id'], input_num('totamount'), QE_JOURNAL);
		$_POST['totamount'] = price_format(0);
		$Ajax->activate('totamount');
		line_start_focus();
	}
	//-----------------------------------------------------------------------------------------------
	start_form();
	display_order_header($_SESSION['journal_items']);
	start_table(Config::get('tables_style2') . " width=90%", 10);
	start_row();
	echo "<td>";
	display_gl_items(_("Rows"), $_SESSION['journal_items']);
	gl_options_controls();
	echo "</td>";
	end_row();
	end_table(1);
	submit_center(
		'Process', _("Process Journal Entry"), true,
		_('Process journal entry only if debits equal to credits'), 'default'
	);
	end_form();
	//------------------------------------------------------------------------------------------------
	end_page();

?>
