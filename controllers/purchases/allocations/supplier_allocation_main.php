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
  Page::start(_($help_context = "Supplier Allocations"), SA_SUPPLIERALLOC);
  Forms::start();
  /* show all outstanding receipts and credits to be allocated */
  if (!isset($_POST['supplier_id'])) {
    $_POST['supplier_id'] = Session::i()->getGlobal('creditor');
  }
  echo "<div class='center'>" . _("Select a Supplier: ") . "&nbsp;&nbsp;";
  echo Creditor::select('supplier_id', $_POST['supplier_id'], true, true);
  echo "<br>";
  Forms::check(_("Show Settled Items:"), 'ShowSettled', null, true);
  echo "</div><br><br>";
  Session::i()->setGlobal('creditor', $_POST['supplier_id']);
  if (isset($_POST['supplier_id']) && ($_POST['supplier_id'] == ALL_TEXT)) {
    unset($_POST['supplier_id']);
  }
  $settled = false;
  if (Forms::hasPost('ShowSettled')) {
    $settled = true;
  }
  $supplier_id = null;
  if (isset($_POST['supplier_id'])) {
    $supplier_id = $_POST['supplier_id'];
  }
  /**
   * @param $dummy
   * @param $type
   *
   * @return mixed
   */
  function systype_name($dummy, $type)
  {
    global $systypes_array;

    return $systypes_array[$type];
  }

  /**
   * @param $trans
   *
   * @return null|string
   */
  function trans_view($trans)
  {
    return GL_UI::trans_view($trans["type"], $trans["trans_no"]);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function alloc_link($row)
  {
    return DB_Pager::link(_("Allocate"), "/purchases/allocations/supplier_allocate.php?trans_no=" . $row["trans_no"] . "&trans_type=" . $row["type"], ICON_MONEY);
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function amount_left($row)
  {
    return Num::price_format(-$row["Total"] - $row["alloc"]);
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function amount_total($row)
  {
    return Num::price_format(-$row["Total"]);
  }

  /**
   * @param $row
   *
   * @return bool
   */
  function check_settled($row)
  {
    return $row['settled'] == 1;
  }

  $sql  = Purch_Allocation::get_allocatable_sql($supplier_id, $settled);
  $cols = array(
    _("Transaction Type") => array('fun' => 'systype_name'),
    _("#")                => array('fun' => 'trans_view'),
    _("Reference"),
    _("Date")             => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'),
    _("Supplier")         => array('ord' => ''),
    _("Currency")         => array('align' => 'center'),
    _("Total")            => array('align' => 'right', 'fun' => 'amount_total'),
    _("Left to Allocate") => array('align' => 'right', 'insert' => true, 'fun' => 'amount_left'),
    array('insert' => true, 'fun' => 'alloc_link')
  );
  if (isset($_POST['customer_id'])) {
    $cols[_("Supplier")] = 'skip';
    $cols[_("Currency")] = 'skip';
  }
  $table =& db_pager::new_db_pager('alloc_tbl', $sql, $cols);
  $table->set_marker('check_settled', _("Marked items are settled."), 'settledbg', 'settledfg');
  $table->width = "80%";
  DB_Pager::display($table);
  Forms::end();
  Page::end();

