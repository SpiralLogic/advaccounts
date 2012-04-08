<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  JS::open_window(800, 500);
  Page::start(_($help_context = "Void a Transaction"), SA_VOIDTRANSACTION);
  if (!isset($_POST['date_'])) {
    $_POST['date_'] = Dates::today();
    if (!Dates::is_date_in_fiscalyear($_POST['date_'])) {
      $_POST['date_'] = Dates::end_fiscalyear();
    }
  }
  if (isset($_POST['ConfirmVoiding'])) {
    if ($_SESSION['voiding'] != $_POST['trans_no'] . $_POST['filterType']) {
      unset($_POST['ConfirmVoiding']);
      $_POST['ProcessVoiding'] = TRUE;
    }
    else {
      handle_void_transaction();
    }
    Ajax::i()->activate('_page_body');
  }
  if (isset($_POST['ProcessVoiding'])) {
    if (!check_valid_entries()) {
      unset($_POST['ProcessVoiding']);
    }
    Ajax::i()->activate('_page_body');
  }
  if (isset($_POST['CancelVoiding'])) {
    Ajax::i()->activate('_page_body');
  }
  voiding_controls();
  Page::end();
  /**
   * @param $type
   * @param $type_no
   *
   * @return bool
   */
  function exist_transaction($type, $type_no) {
    $void_entry = Voiding::has($type, $type_no);
    if ($void_entry > 0) {
      return FALSE;
    }
    switch ($type) {
      case ST_JOURNAL : // it's a journal entry
        if (!GL_Trans::exists($type, $type_no)) {
          return FALSE;
        }
        break;
      case ST_BANKPAYMENT : // it's a payment
      case ST_BANKDEPOSIT : // it's a deposit
      case ST_BANKTRANSFER : // it's a transfer
        if (!Bank_Trans::exists($type, $type_no)) {
          return FALSE;
        }
        break;
      case ST_SALESINVOICE : // it's a customer invoice
      case ST_CUSTCREDIT : // it's a customer credit note
      case ST_CUSTPAYMENT : // it's a customer payment
      case ST_CUSTREFUND : // it's a customer refund
      case ST_CUSTDELIVERY : // it's a customer dispatch
        if (!Debtor_Trans::exists($type, $type_no)) {
          return FALSE;
        }
        break;
      case ST_LOCTRANSFER : // it's a stock transfer
        if (Inv_Transfer::get_items($type_no) == NULL) {
          return FALSE;
        }
        break;
      case ST_INVADJUST : // it's a stock adjustment
        if (Inv_Adjustment::get($type_no) == NULL) {
          return FALSE;
        }
        break;
      case ST_PURCHORDER : // it's a PO
      case ST_SUPPRECEIVE : // it's a GRN
        return FALSE;
      case ST_SUPPINVOICE : // it's a suppler invoice
      case ST_SUPPCREDIT : // it's a supplier credit note
      case ST_SUPPAYMENT : // it's a supplier payment
        if (!Creditor_Trans::exists($type, $type_no)) {
          return FALSE;
        }
        break;
      case ST_WORKORDER : // it's a work order
        if (!WO::get($type_no, TRUE)) {
          return FALSE;
        }
        break;
      case ST_MANUISSUE : // it's a work order issue
        if (!WO_Issue::exists($type_no)) {
          return FALSE;
        }
        break;
      case ST_MANURECEIVE : // it's a work order production
        if (!WO_Produce::exists($type_no)) {
          return FALSE;
        }
        break;
      case ST_SALESORDER: // it's a sales order
      case ST_SALESQUOTE: // it's a sales quotation
        return FALSE;
      case ST_COSTUPDATE : // it's a stock cost update
        return FALSE;
        break;
    }
    return TRUE;
  }

  /**

   */
  function voiding_controls() {
    start_form();
    start_table('tablestyle2');
    SysTypes::row(_("Transaction Type:"), "filterType", NULL, TRUE);
    text_row(_("Transaction #:"), 'trans_no', NULL, 12, 12);
    date_row(_("Voiding Date:"), 'date_');
    textarea_row(_("Memo:"), 'memo_', NULL, 30, 4);
    end_table(1);
    if (!isset($_POST['ProcessVoiding'])) {
      submit_center('ProcessVoiding', _("Void Transaction"), TRUE, '', 'default');
    }
    else {
      if (!exist_transaction($_POST['filterType'], $_POST['trans_no'])) {
        Event::error(_("The entered transaction does not exist or cannot be voided."));
        unset($_POST['trans_no'], $_POST['memo_'], $_POST['date_']);
        submit_center('ProcessVoiding', _("Void Transaction"), TRUE, '', 'default');
      }
      else {
        Event::warning(_("Are you sure you want to void this transaction ? This action cannot be undone."), 0, 1);
        $_SESSION['voiding'] = $_POST['trans_no'] . $_POST['filterType'];
        if ($_POST['filterType'] == ST_JOURNAL) // GL transaction are not included in get_trans_view_str
        {
          $view_str = GL_UI::view($_POST['filterType'], $_POST['trans_no'], _("View Transaction"));
        }
        else {
          $view_str = GL_UI::trans_view($_POST['filterType'], $_POST['trans_no'], _("View Transaction"));
        }
        Event::warning($view_str);
        Display::br();
        submit_center_first('ConfirmVoiding', _("Proceed"), '', TRUE);
        submit_center_last('CancelVoiding', _("Cancel"), '', 'cancel');
      }
    }
    end_form();
  }

  /**
   * @return bool
   */
  function check_valid_entries() {
    if (DB_AuditTrail::is_closed_trans($_POST['filterType'], $_POST['trans_no'])) {
      Event::error(_("The selected transaction was closed for edition and cannot be voided."));
      JS::set_focus('trans_no');
      return;
    }
    if (!Dates::is_date($_POST['date_'])) {
      Event::error(_("The entered date is invalid."));
      JS::set_focus('date_');
      return FALSE;
    }
    if (!Dates::is_date_in_fiscalyear($_POST['date_'])) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::set_focus('date_');
      return FALSE;
    }
    if (!is_numeric($_POST['trans_no']) OR $_POST['trans_no'] <= 0) {
      Event::error(_("The transaction number is expected to be numeric and greater than zero."));
      JS::set_focus('trans_no');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @return mixed
   */
  function handle_void_transaction() {
    if (check_valid_entries() == TRUE) {
      unset($_SESSION['voiding']);
      $void_entry = Voiding::get($_POST['filterType'], $_POST['trans_no']);
      if ($void_entry != NULL) {
        Event::error(_("The selected transaction has already been voided."), TRUE);
        unset($_POST['trans_no'], $_POST['memo_'], $_POST['date_']);
        JS::set_focus('trans_no');
        return;
      }
      $ret = Voiding::void($_POST['filterType'], $_POST['trans_no'], $_POST['date_'], $_POST['memo_']);
      if ($ret) {
        Event::success(_("Selected transaction has been voided."));
        unset($_POST['trans_no'], $_POST['memo_'], $_POST['date_']);
      }
      else {
        Event::error(_("The entered transaction does not exist or cannot be voided."));
        JS::set_focus('trans_no');
      }
    }
  }


