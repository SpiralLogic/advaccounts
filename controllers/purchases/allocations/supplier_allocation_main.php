<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::_openWindow(950, 500);
  Page::start(_($help_context = "Supplier Allocations"), SA_SUPPLIERALLOC);
  Forms::start();
  /* show all outstanding receipts and credits to be allocated */
  if (!isset($_POST['creditor_id'])) {
    $_POST['creditor_id'] = Session::_getGlobal('creditor_id');
  }
  echo "<div class='center'>" . _("Select a Supplier: ") . "&nbsp;&nbsp;";
  echo Creditor::select('creditor_id', $_POST['creditor_id'], true, true);
  echo "<br>";
  Forms::check(_("Show Settled Items:"), 'ShowSettled', null, true);
  echo "</div><br><br>";
  Session::_setGlobal('creditor_id', $_POST['creditor_id']);
  if (isset($_POST['creditor_id']) && ($_POST['creditor_id'] == ALL_TEXT)) {
    unset($_POST['creditor_id']);
  }
  $settled = false;
  if (Input::_hasPost('ShowSettled')) {
    $settled = true;
  }
  $creditor_id = null;
  if (isset($_POST['creditor_id'])) {
    $creditor_id = $_POST['creditor_id'];
  }
  /**
   * @param $dummy
   * @param $type
   *
   * @return mixed
   */
  function sysTypeName($dummy, $type) {


    return SysTypes::$names[$type];
  }

  /**
   * @param $trans
   *
   * @return null|string
   */
  function viewTrans($trans) {
    return GL_UI::viewTrans($trans["type"], $trans["trans_no"]);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function alloc_link($row) {
    return DB_Pager::link(_("Allocate"), "/purchases/allocations/supplier_allocate.php?trans_no=" . $row["trans_no"] . "&trans_type=" . $row["type"], ICON_MONEY);
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function amount_left($row) {
    return Num::_priceFormat(-$row["Total"] - $row["alloc"]);
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function amount_total($row) {
    return Num::_priceFormat(-$row["Total"]);
  }

  /**
   * @param $row
   *
   * @return bool
   */
  function check_settled($row) {
    return $row['settled'] == 1;
  }

  $sql  = Purch_Allocation::get_allocatable_sql($creditor_id, $settled);
  $cols = array(
    _("Transaction Type") => array('fun' => 'sysTypeName'),
    _("#")                => array('fun' => 'viewTrans'),
    _("Reference"),
    _("Date")             => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'),
    _("Supplier")         => array('ord' => ''),
    _("Currency")         => array('align' => 'center'),
    _("Total")            => array('align' => 'right', 'fun' => 'amount_total'),
    _("Left to Allocate") => array('align' => 'right', 'insert' => true, 'fun' => 'amount_left'),
    array('insert' => true, 'fun' => 'alloc_link')
  );
  if (isset($_POST['debtor_id'])) {
    $cols[_("Supplier")] = 'skip';
    $cols[_("Currency")] = 'skip';
  }
  $table = DB_Pager::new_db_pager('alloc_tbl', $sql, $cols);
  $table->setMarker('check_settled', _("Marked items are settled."), 'settledbg', 'settledfg');
  $table->width = "80%";
  $table->display($table);
  Forms::end();
  Page::end();

