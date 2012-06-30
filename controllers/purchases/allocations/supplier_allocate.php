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
  Page::start(_($help_context = "Allocate Supplier Payment or Credit Note"), SA_SUPPLIERALLOC);

  if (isset($_POST['Process'])) {
    if (Gl_Allocation::check()) {
      $_SESSION['alloc']->write();
      clear_allocations();
      $_POST['Cancel'] = 1;
    }
  }
  if (isset($_POST['Cancel'])) {
    clear_allocations();
    $forward = (isset($_POST['inquiry'])) ? "/purchases/inquiry/supplier_allocation_inquiry.php" :
      "/purchases/allocations/supplier_allocation_main.php";
    Display::meta_forward($forward);
  }
  if (isset($_GET['trans_no']) && isset($_GET['trans_type'])) {
    $_SESSION['alloc'] = new Gl_Allocation($_GET['trans_type'], $_GET['trans_no']);
  }
  if (Input::post('UpdateDisplay')) {
    $_SESSION['alloc']->read();
    Ajax::activate('alloc_tbl');
  }
  if (isset($_SESSION['alloc'])) {
    edit_allocations_for_transaction($_SESSION['alloc']->type, $_SESSION['alloc']->trans_no);
  }
  Page::end();
  function clear_allocations()
  {
    if (isset($_SESSION['alloc'])) {
      unset($_SESSION['alloc']->allocs);
      unset($_SESSION['alloc']);
    }
  }

  /**
   * @param $type
   * @param $trans_no
   */
  function edit_allocations_for_transaction($type, $trans_no)
  {
    global $systypes_array;
    Forms::start();
    if (isset($_POST['inquiry']) || stristr($_SERVER['HTTP_REFERER'], 'supplier_allocation_inquiry.php')) {
      Forms::hidden('inquiry', true);
    }
    Display::heading(_("Allocation of") . " " . $systypes_array[$_SESSION['alloc']->type] . " # " . $_SESSION['alloc']->trans_no);
    Display::heading($_SESSION['alloc']->person_name);
    Display::heading(_("Date:") . " <span class='bold'>" . $_SESSION['alloc']->date_ . "</span>");
    Display::heading(_("Total:") . " <span class='bold'>" . Num::price_format(-$_SESSION['alloc']->amount) . "</span>");
    echo "<br>";
    Display::div_start('alloc_tbl');
    if (count($_SESSION['alloc']->allocs) > 0) {
      Gl_Allocation::show_allocatable(true);
      Forms::submitCenterBegin('UpdateDisplay', _("Refresh"), _('Start again allocation of selected amount'), true);
      Forms::submit('Process', _("Process"), true, _('Process allocations'), 'default');
      Forms::submitCenterEnd('Cancel', _("Back to Allocations"), _('Abandon allocations and return to selection of allocatable amounts'), 'cancel');
    } else {
      Event::warning(_("There are no unsettled transactions to allocate."), 0, 1);
      Forms::submitCenter('Cancel', _("Back to Allocations"), true, _('Abandon allocations and return to selection of allocatable amounts'), 'cancel');
    }
    Display::div_end();
    Forms::end();
  }


