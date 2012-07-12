<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::openWindow(800, 500);
  JS::footerFile('/js/reconcile.js');
  Page::start(_($help_context = "Undeposited Funds"), SA_RECONCILE, Input::request('frame'));
  Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
  if (isset($_SESSION['undeposited'])) {
    foreach ($_SESSION['undeposited'] as $rowid => $row) {
      if (isset($_POST["_" . $rowid . '_update'])) {
        continue;
      }
      $amountid                  = substr($rowid, 4);
      $_POST['amount_' . $rowid] = $row;
      $_POST[$rowid]             = 1;
    }
  }
  $update_pager = false;
  if (Forms::isListUpdated('deposit_date')) {
    $_POST['deposit_date'] = Input::post('deposit_date') == '' ? Dates::today() : ($_POST['deposit_date']);
    updateData();
  }
  if (Input::post('_deposit_date_changed')) {
    $_POST['deposited']      = 0;
    $_SESSION['undeposited'] = array();
    $_POST['deposit_date']   = check_date() ? (Input::post('deposit_date')) : '';
    foreach ($_POST as $rowid => $row) {
      if (substr($rowid, 0, 4) == 'dep_') {
        unset($_POST[$rowid]);
      }
    }
    updateData();
  }
  $id = Forms::findPostPrefix('_dep_');
  if ($id != -1) {
    change_tpl_flag($id);
  }
  if (isset($_POST['Deposit'])) {
    $sql         = "SELECT * FROM bank_trans WHERE undeposited=1 AND reconciled IS null";
    $query       = DB::query($sql);
    $undeposited = array();
    while ($row = DB::fetch($query)) {
      $undeposited[$row['id']] = $row;
    }
    $togroup = array();
    foreach ($_POST as $key => $value) {
      $key = explode('_', $key);
      if ($key[0] == 'dep') {
        $togroup[$key[1]] = $undeposited[$key[1]];
      }
    }
    if (count($togroup) > 1) {
      $total_amount = 0;
      $ref          = array();
      foreach ($togroup as $row) {
        $total_amount += $row['amount'];
        $ref[] = $row['ref'];
      }
      $sql      = "INSERT INTO bank_trans (type, bank_act, amount, ref, trans_date, person_type_id, person_id, undeposited) VALUES (" . ST_GROUPDEPOSIT . ", 5, $total_amount," . DB::quote(implode($ref, ', ')) . ",'" . Dates::dateToSql($_POST['deposit_date']) . "', 6, '" . User::i()->user . "',0)";
      $query    = DB::query($sql, "Undeposited Cannot be Added");
      $order_no = DB::insertId($query);
      if (!isset($order_no) || !empty($order_no) || $order_no == 127) {
        $sql      = "SELECT LAST_INSERT_ID()";
        $order_no = DB::query($sql);
        $order_no = DB::fetchRow($order_no);
        $order_no = $order_no[0];
      }
      foreach ($togroup as $row) {
        $sql = "UPDATE bank_trans SET undeposited=" . $order_no . " WHERE id=" . DB::escape($row['id']);
        DB::query($sql, "Can't change undeposited status");
      }
    } else {
      $row = reset($togroup);
      $sql = "UPDATE bank_trans SET undeposited=0, trans_date='" . Dates::dateToSql($_POST['deposit_date']) . "',deposit_date='" . Dates::dateToSql($_POST['deposit_date']) . "' WHERE id=" . DB::escape($row['id']);
      DB::query($sql, "Can't change undeposited status");
    }
    unset($_POST, $_SESSION['undeposited']);
    Display::meta_forward($_SERVER['DOCUMENT_URI']);
  }
  $_POST['to_deposit'] = 0;
  if (isset ($_SESSION['undeposited']) && $_SESSION['undeposited']) {
    foreach ($_SESSION['undeposited'] as $rowid => $row) {
      if (substr($rowid, 0, 4) == 'dep_') {
        $_POST['to_deposit'] += $row;
      }
    }
  }
  $_POST['deposited'] = $_POST['to_deposit'];
  Ajax::activate('summary');
  Forms::start();
  echo "<hr>";
  Display::div_start('summary');
  Table::start();
  Table::header(_("Deposit Date"));
  Row::start();
  Forms::dateCells("", "deposit_date", _('Date of funds to deposit'), Input::post('deposit_date') == '', 0, 0, 0, null, false, array('rebind' => false));
  Row::end();
  Table::header(_("Total Amount"));
  Row::start();
  Cell::amount($_POST['deposited'], false, '', "deposited");
  Forms::hidden("to_deposit", $_POST['to_deposit'], true);
  Row::end();
  Row::start();
  Row::end();
    Table::header(_("Bank fees"));
    Row::start();Cell::amount($_POST['bank_fees'], false, '', "bank_fees");
  Row::end();
  Table::end();
  Forms::submitCenter('Deposit', _("Deposit"), true, '', false);
  Display::div_end();
  echo "<hr>";
  $date = Dates::addDays($_POST['deposit_date'], 10);
  $sql
                = "SELECT	type, trans_no, ref, trans_date,
                amount,	person_id, person_type_id, reconciled, id
        FROM bank_trans
        WHERE undeposited=1 AND trans_date <= '" . Dates::dateToSql($date) . "' AND reconciled IS null AND amount<>0
        ORDER BY trans_date DESC,bank_trans.id ";
  $cols         = array(
    _("Type")                    => array(
      'fun' => 'sysTypeName', 'ord' => ''
    ), _("#")                    => array(
      'fun' => 'viewTrans', 'ord' => ''
    ), _("Reference"), _("Date") => array('type'=> 'date', 'ord' => 'desc'), _("Debit") => array(
      'align' => 'right', 'fun' => 'formatDebit'
    ), _("Credit")               => array(
      'align' => 'right', 'insert' => true, 'fun' => 'formatCredit'
    ), _("Person/Item")          => array('fun' => 'formatPerson'), array(
      'insert' => true, 'fun' => 'viewGl'
    ), "X"                       => array(
      'insert' => true, 'fun' => 'depositCheckbox'
    )
  );
  $table        = db_pager::new_db_pager('trans_tbl', $sql, $cols);
  $table->width = "80%";
  $table->display($table);
  Display::br(1);
  Forms::submitCenter('Deposit', _("Deposit"), true, '', false);
  Forms::end();
  Page::end();
  /**
   * @return bool
   */
  function check_date() {
    if (!Dates::isDate(Input::post('deposit_date'))) {
      Event::error(_("Invalid deposit date format"));
      JS::setFocus('deposit_date');
      return false;
    }
    return true;
  }

  //
  //	This function can be used directly in table pager
  //	if we would like to change page layout.
  //	if we would like to change page layout.
  //
  /**
   * @param $row
   *
   * @return string
   */
  function depositCheckbox($row) {
    $name      = "dep_" . $row['id'];
    $hidden    = 'amount_' . $row['id'];
    $value     = $row['amount'];
    $chk_value = Forms::hasPost("dep_" . $row['id']);
    // save also in hidden field for testing during 'Reconcile'
    return Forms::checkbox(null, $name, $chk_value, true, _('Deposit this transaction')) . Forms::hidden($hidden, $value, false);
  }

  /**
   * @param $dummy
   * @param $type
   *
   * @return mixed
   */
  function sysTypeName($dummy, $type) {
    global $systypes_array;
    return $systypes_array[$type];
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
  function viewGl($row) {
    return GL_UI::view($row["type"], $row["trans_no"]);
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function formatDebit($row) {
    $value = $row["amount"];
    return $value >= 0 ? Num::priceFormat($value) : '';
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function formatCredit($row) {
    $value = -$row["amount"];
    return $value > 0 ? Num::priceFormat($value) : '';
  }

  /**
   * @param $row
   *
   * @return string
   */
  function formatPerson($row) {
    return Bank::payment_person_name($row["person_type_id"], $row["person_id"]);
  }

  function updateData() {
    global $update_pager;
    Ajax::activate('summary');
    $update_pager = true;
  }

  // Update db record if respective checkbox value has changed.
  //
  /**
   * @param $deposit_id
   *
   * @return bool
   */
  function change_tpl_flag($deposit_id) {
    if (!check_date() && Forms::hasPost("dep_" . $deposit_id)) // temporary fix
    {
      return false;
    }
    if (Input::post('bank_date') == '') // new reconciliation
    {
      Ajax::activate('bank_date');
    }
    $_POST['bank_date'] = Dates::dateToSql(Input::post('deposited_date'));
    /*	$sql = "UPDATE ".''."bank_trans SET undeposited=0"
                         ." WHERE id=".DB::escape($deposit_id);

                        DB::query($sql, "Can't change undeposited status");*/
    // save last reconcilation status (date, end balance)
    if (Forms::hasPost("dep_" . $deposit_id)) {
      $_SESSION['undeposited']["dep_" . $deposit_id] = Input::post('amount_' . $deposit_id);
      $_POST['deposited']                            = $_POST['to_deposit'] + Input::post('amount_' . $deposit_id);
    } else {
      unset($_SESSION['undeposited']["dep_" . $deposit_id]);
      $_POST['deposited'] = $_POST['to_deposit'] - Input::post('amount_' . $deposit_id);
    }
    return true;
  }
