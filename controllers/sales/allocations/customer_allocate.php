<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  JS::open_window(900, 500);
  JS::footerFile('/js/allocate.js');
  Page::start(_($help_context = "Allocate Customer Payment or Credit Note"), SA_SALESALLOC);

  if (isset($_POST['Process'])) {
    if (Gl_Allocation::check()) {
      $_SESSION['alloc']->write();
      Sales_Allocation::clear_allocations();
      $_POST['Cancel'] = 1;
    }
  }

  if (isset($_POST['Cancel'])) {
    Sales_Allocation::clear_allocations();
    $forward = (isset($_POST['inquiry'])) ? "/sales/inquiry/customer_allocation_inquiry.php" : "/sales/allocations/customer_allocation_main.php";
    Display::meta_forward($forward);
  }
  if (isset($_GET['trans_no']) && isset($_GET['trans_type'])) {
    Sales_Allocation::clear_allocations();
    $_SESSION['alloc'] = new Gl_Allocation($_GET['trans_type'], $_GET['trans_no']);
  }
  if (get_post('UpdateDisplay')) {
    $_SESSION['alloc']->read();
    Ajax::i()->activate('alloc_tbl');
  }
  if (isset($_SESSION['alloc'])) {
    Sales_Allocation::edit_allocations_for_transaction($_SESSION['alloc']->type, $_SESSION['alloc']->trans_no);
  }
  Page::end();



