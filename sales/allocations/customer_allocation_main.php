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
  Page::start(_($help_context = "Customer Allocations"), SA_SALESALLOC);
  start_form();
  /* show all outstanding receipts and credits to be allocated */
  if (!isset($_POST['customer_id'])) {
    $_POST['customer_id'] = Session::i()->global_customer;
  }
  echo "<div class='center'>" . _("Select a customer: ") . "&nbsp;&nbsp;";
  echo Debtor::select('customer_id', $_POST['customer_id'], TRUE, TRUE);
  echo "<br>";
  check(_("Show Settled Items:"), 'ShowSettled', NULL, TRUE);
  echo "</div><br><br>";
  Session::i()->global_customer = $_POST['customer_id'];
  if (isset($_POST['customer_id']) && ($_POST['customer_id'] == ALL_TEXT)) {
    unset($_POST['customer_id']);
  }
  /*if (isset($_POST['customer_id'])) {
           $custCurr = Bank_Currency::for_debtor($_POST['customer_id']);
           if (!Bank_Currency::is_company($custCurr))
             echo _("Customer Currency:") . $custCurr;
         }*/
  $settled = FALSE;
  if (check_value('ShowSettled')) {
    $settled = TRUE;
  }
  $customer_id = NULL;
  if (isset($_POST['customer_id'])) {
    $customer_id = $_POST['customer_id'];
  }
  $sql = Sales_Allocation::get_allocatable_sql($customer_id, $settled);
  $cols = array(
    _("Transaction Type") => array('fun' => 'Sales_Allocation::systype_name'),
    _("#") => array('fun' => 'Sales_Allocation::trans_view'), _("Reference"),
    _("Date") => array(
      'name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'
    ), _("Customer") => array('ord' => ''),
    _("Currency") => array('align' => 'center'), _("Total") => 'amount', _("Left to Allocate") => array(
      'align' => 'right', 'insert' => TRUE, 'fun' => 'Sales_Allocation::amount_left'
    ), array(
      'insert' => TRUE, 'fun' => 'Sales_Allocation::alloc_link'
    )
  );
  if (isset($_POST['customer_id'])) {
    $cols[_("Customer")] = 'skip';
    $cols[_("Currency")] = 'skip';
  }
  $table =& db_pager::new_db_pager('alloc_tbl', $sql, $cols);
  $table->set_marker('Sales_Allocation::check_settled', _("Marked items are settled."), 'settledbg', 'settledfg');
  $table->width = "75%";
  DB_Pager::display($table);
  end_form();
  Page::end();


?>
