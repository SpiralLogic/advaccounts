<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/

  JS::open_window(800, 500);
  if (isset($_GET['ModifyGL'])) {
    $_SESSION['page_title'] = sprintf(_("Modifying Journal Transaction # %d."), $_GET['trans_no']);
    $help_context = "Modifying Journal Entry";
  }
  else {
    $_SESSION['page_title'] = _($help_context = "Journal Entry");
  }
  Page::start($_SESSION['page_title'], SA_JOURNALENTRY);
  if (isset($_GET[ADDED_ID])) {
    $trans_no = $_GET[ADDED_ID];
    $trans_type = ST_JOURNAL;
    Event::success(_("Journal entry has been entered") . " #$trans_no");
    Display::note(GL_UI::view($trans_type, $trans_no, _("&View this Journal Entry")));
    JS::reset_focus();
    Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter &New Journal Entry"), "NewJournal=Yes");
    Page::footer_exit();
  }
  elseif (isset($_GET[UPDATED_ID])) {
    $trans_no = $_GET[UPDATED_ID];
    $trans_type = ST_JOURNAL;
    Event::success(_("Journal entry has been updated") . " #$trans_no");
    Display::note(GL_UI::view($trans_type, $trans_no, _("&View this Journal Entry")));
    Display::link_no_params(DOCROOT . "gl/inquiry/journal_inquiry.php", _("Return to Journal &Inquiry"));
    Page::footer_exit();
  }
  if (isset($_GET['NewJournal'])) {
    create_order(ST_JOURNAL, 0);
  }
  elseif (isset($_GET['ModifyGL'])) {
    if (!isset($_GET['trans_type']) || $_GET['trans_type'] != 0) {
      Event::error(_("You can edit directly only journal entries created via Journal Entry page."));
      Display::link_params("/gl/gl_journal.php", _("Entry &New Journal Entry"), "NewJournal=Yes");
      Page::footer_exit();
    }
    create_order($_GET['trans_type'], $_GET['trans_no']);
  }
  if (isset($_POST['Process'])) {
    $input_error = 0;
    if ($_SESSION['journal_items']->count_gl_items() < 1) {
      Event::error(_("You must enter at least one journal line."));
      JS::set_focus('code_id');
      $input_error = 1;
    }
    if (abs($_SESSION['journal_items']->gl_items_total()) > 0.0001) {
      Event::error(_("The journal must balance (debits equal to credits) before it can be processed."));
      JS::set_focus('code_id');
      $input_error = 1;
    }
    if (!Dates::is_date($_POST['date_'])) {
      Event::error(_("The entered date is invalid."));
      JS::set_focus('date_');
      $input_error = 1;
    }
    elseif (!Dates::is_date_in_fiscalyear($_POST['date_'])) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::set_focus('date_');
      $input_error = 1;
    }
    if (!Ref::is_valid($_POST['ref'])) {
      Event::error(_("You must enter a reference."));
      JS::set_focus('ref');
      $input_error = 1;
    }
    elseif (Ref::exists(ST_JOURNAL, $_POST['ref'])) {
      // The reference can exist already so long as it's the same as the original (when modifying)
      if ($_POST['ref'] != $_POST['ref_original']) {
        Event::error(_("The entered reference is already in use."));
        JS::set_focus('ref');
        $input_error = 1;
      }
    }
    if ($input_error == 1) {
      unset($_POST['Process']);
    }
  }
  if (isset($_POST['Process'])) {
    $order = $_SESSION['journal_items'];
    $new = $order->order_id == 0;
    $order->reference = $_POST['ref'];
    $order->memo_ = $_POST['memo_'];
    $order->tran_date = $_POST['date_'];
    $trans_no = GL_Journal::write($order, check_value('Reverse'));
    $order->clear_items();
    Dates::new_doc_date($_POST['date_']);
    unset($_SESSION['journal_items']);
    if ($new) {
      Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$trans_no");
    }
    else {
      Display::meta_forward($_SERVER['DOCUMENT_URI'], "UpdatedID=$trans_no");
    }
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
    Item_Line::start_focus('_code_id_edit');
  }
  if (isset($_POST['go'])) {
    Display::quick_entries($_SESSION['journal_items'], $_POST['person_id'], Validation::input_num('total_amount'), QE_JOURNAL);
    $_POST['total_amount'] = Num::price_format(0);
    Ajax::i()->activate('total_amount');
    Item_Line::start_focus('_code_id_edit');
  }
  start_form();
  GL_Journal::header($_SESSION['journal_items']);
  start_table('tables_style2 width90 pad10');
  start_row();
  echo "<td>";
  GL_Journal::items(_("Rows"), $_SESSION['journal_items']);
  GL_Journal::option_controls();
  echo "</td>";
  end_row();
  end_table(1);
  submit_center('Process', _("Process Journal Entry"), TRUE, _('Process journal entry only if debits equal to credits'), 'default');
  end_form();
  Page::end();
  /**
   * @return bool
   */
  function check_item_data() {
    if (isset($_POST['dimension_id']) && $_POST['dimension_id'] != 0 && Dimensions::is_closed($_POST['dimension_id'])) {
      Event::error(_("Dimension is closed."));
      JS::set_focus('dimension_id');
      return FALSE;
    }
    if (isset($_POST['dimension2_id']) && $_POST['dimension2_id'] != 0 && Dimensions::is_closed($_POST['dimension2_id'])
    ) {
      Event::error(_("Dimension is closed."));
      JS::set_focus('dimension2_id');
      return FALSE;
    }
    if (!(Validation::input_num('AmountDebit') != 0 ^ Validation::input_num('AmountCredit') != 0)) {
      Event::error(_("You must enter either a debit amount or a credit amount."));
      JS::set_focus('AmountDebit');
      return FALSE;
    }
    if (strlen($_POST['AmountDebit']) && !Validation::post_num('AmountDebit', 0)) {
      Event::error(_("The debit amount entered is not a valid number or is less than zero."));
      JS::set_focus('AmountDebit');
      return FALSE;
    }
    elseif (strlen($_POST['AmountCredit']) && !Validation::post_num('AmountCredit', 0)) {
      Event::error(_("The credit amount entered is not a valid number or is less than zero."));
      JS::set_focus('AmountCredit');
      return FALSE;
    }
    if (!Tax_Types::is_tax_gl_unique(get_post('code_id'))) {
      Event::error(_("Cannot post to GL account used by more than one tax type."));
      JS::set_focus('code_id');
      return FALSE;
    }
    if (!User::i()->can_access(SA_BANKJOURNAL) && Bank_Account::is($_POST['code_id'])) {
      Event::error(_("You cannot make a journal entry for a bank account. Please use one of the banking functions for bank transactions."));
      JS::set_focus('code_id');
      return FALSE;
    }
    return TRUE;
  }

  function handle_update_item() {
    if ($_POST['UpdateItem'] != "" && check_item_data()) {
      if (Validation::input_num('AmountDebit') > 0) {
        $amount = Validation::input_num('AmountDebit');
      }
      else {
        $amount = -Validation::input_num('AmountCredit');
      }
      $_SESSION['journal_items']->update_gl_item($_POST['Index'], $_POST['code_id'], $_POST['dimension_id'], $_POST['dimension2_id'], $amount, $_POST['LineMemo']);
    }
    Item_Line::start_focus('_code_id_edit');
  }

  /**
   * @param $id
   */
  function handle_delete_item($id) {
    $_SESSION['journal_items']->remove_gl_item($id);
    Item_Line::start_focus('_code_id_edit');
  }

  function handle_new_item() {
    if (!check_item_data()) {
      return;
    }
    if (Validation::input_num('AmountDebit') > 0) {
      $amount = Validation::input_num('AmountDebit');
    }
    else {
      $amount = -Validation::input_num('AmountCredit');
    }
    $_SESSION['journal_items']->add_gl_item($_POST['code_id'], $_POST['dimension_id'], $_POST['dimension2_id'], $amount, $_POST['LineMemo']);
    Item_Line::start_focus('_code_id_edit');
  }

  /**
   * @param int $type
   * @param int $trans_no
   */
  function create_order($type = ST_JOURNAL, $trans_no = 0) {
    if (isset($_SESSION['journal_items'])) {
      unset ($_SESSION['journal_items']);
    }
    $order = new Item_Order($type);
    $order->order_id = $trans_no;
    if ($trans_no) {
      $result = GL_Trans::get_many($type, $trans_no);
      if ($result) {
        while ($row = DB::fetch($result)) {
          if ($row['amount'] == 0) {
            continue;
          }
          $date = $row['tran_date'];
          $order->add_gl_item($row['account'], $row['dimension_id'], $row['dimension2_id'], $row['amount'], $row['memo_']);
        }
      }
      $order->memo_ = DB_Comments::get_string($type, $trans_no);
      $order->tran_date = Dates::sql2date($date);
      $order->reference = Ref::get($type, $trans_no);
      $_POST['ref_original'] = $order->reference; // Store for comparison when updating
    }
    else {
      $order->reference = Ref::get_next(ST_JOURNAL);
      $order->tran_date = Dates::new_doc_date();
      if (!Dates::is_date_in_fiscalyear($order->tran_date)) {
        $order->tran_date = Dates::end_fiscalyear();
      }
      $_POST['ref_original'] = -1;
    }
    $_POST['memo_'] = $order->memo_;
    $_POST['ref'] = $order->reference;
    $_POST['date_'] = $order->tran_date;
    $_SESSION['journal_items'] = &$order;
  }