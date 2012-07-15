<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  JS::openWindow(900, 500);
  Page::start(_($help_context = "Customer Allocations"), SA_SALESALLOC);
  Forms::start();
  /* show all outstanding receipts and credits to be allocated */
  if (!isset($_POST['customer_id'])) {
    $_POST['customer_id'] = Session::getGlobal('debtor');
  }
  echo "<div class='center'>" . _("Select a customer: ") . "&nbsp;&nbsp;";
  echo Debtor::select('customer_id', $_POST['customer_id'], true, true);
  echo "<br>";
  Forms::check(_("Show Settled Items:"), 'ShowSettled', null, true);
  echo "</div><br><br>";
  Session::setGlobal('debtor', $_POST['customer_id']);
  if (isset($_POST['customer_id']) && ($_POST['customer_id'] == ALL_TEXT)) {
    unset($_POST['customer_id']);
  }
  /*if (isset($_POST['customer_id'])) {
           $custCurr = Bank_Currency::for_debtor($_POST['customer_id']);
           if (!Bank_Currency::is_company($custCurr))
             echo _("Customer Currency:") . $custCurr;
         }*/
  $settled = false;
  if (Forms::hasPost('ShowSettled')) {
    $settled = true;
  }
  $customer_id = null;
  if (isset($_POST['customer_id'])) {
    $customer_id = $_POST['customer_id'];
  }
  $sql  = Sales_Allocation::get_allocatable_sql($customer_id, $settled);
  $cols = array(
    _("Transaction Type") => array('fun' => 'Sales_Allocation::sysTypeName'),
    _("#")                => array('fun' => 'Sales_Allocation::viewTrans'),
    _("Reference"),
    _("Date")             => array(
      'name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'
    ),
    _("Customer")         => array('ord' => ''),
    _("Currency")         => array('align' => 'center'),
    _("Total")            => 'amount',
    _("Left to Allocate") => array(
      'align' => 'right', 'insert' => true, 'fun' => 'Sales_Allocation::amount_left'
    ),
    array(
      'insert' => true, 'fun' => 'Sales_Allocation::alloc_link'
    )
  );
  if (isset($_POST['customer_id'])) {
    $cols[_("Customer")] = 'skip';
    $cols[_("Currency")] = 'skip';
  }
  $table = db_pager::new_db_pager('alloc_tbl', $sql, $cols);
  $table->setMarker('Sales_Allocation::check_settled', _("Marked items are settled."), 'settledbg', 'settledfg');
  $table->width = "75%";
  $table->display($table);
  Forms::end();
  Page::end();



