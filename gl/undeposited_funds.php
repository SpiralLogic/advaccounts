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
  JS::footerFile('/js/reconcile.js');
  Page::start(_($help_context = "Undeposited Funds"), SA_RECONCILE, Input::request('frame'));
  Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
  if (isset($_SESSION['undeposited'])) {
    foreach ($_SESSION['undeposited'] as $rowid => $row) {
      if (isset($_POST["_" . $rowid . '_update'])) {
        continue;
      }
      $amountid = substr($rowid, 4);
      $_POST['amount_' . $rowid] = $row;
      $_POST[$rowid] = 1;
    }
  }
  $update_pager = FALSE;
  if (list_updated('deposit_date')) {
    $_POST['deposit_date'] = get_post('deposit_date') == '' ? Dates::today() : ($_POST['deposit_date']);
    update_data();
  }
  if (get_post('_deposit_date_changed')) {
    $_POST['deposited'] = 0;
    $_SESSION['undeposited'] = array();
    $_POST['deposit_date'] = check_date() ? (get_post('deposit_date')) : '';
    foreach ($_POST as $rowid => $row) {
      if (substr($rowid, 0, 4) == 'dep_') {
        unset($_POST[$rowid]);
      }
    }
    update_data();
  }
  $id = find_submit('_dep_');
  if ($id != -1) {
    change_tpl_flag($id);
  }
  if (isset($_POST['Deposit'])) {
    $sql = "SELECT * FROM bank_trans WHERE undeposited=1 AND reconciled IS NULL";
    $query = DB::query($sql);
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
      $ref = array();
      foreach ($togroup as $row) {
        $total_amount += $row['amount'];
        $ref[] = $row['ref'];
      }
      $sql = "INSERT INTO bank_trans (type, bank_act, amount, ref, trans_date, person_type_id, person_id, undeposited) VALUES (15, 5, $total_amount," . DB::escape(implode($ref, ',')) . ",'" . Dates::date2sql($_POST['deposit_date']) . "', 6, '" . User::i()->user . "',0)";
      $query = DB::query($sql, "Undeposited Cannot be Added");
      $order_no = DB::insert_id($query);
      if (!isset($order_no) || !empty($order_no) || $order_no == 127) {
        $sql = "SELECT LAST_INSERT_ID()";
        $order_no = DB::query($sql);
        $order_no = DB::fetch_row($order_no);
        $order_no = $order_no[0];
      }
      foreach ($togroup as $row) {
        $sql = "UPDATE bank_trans SET undeposited=" . $order_no . " WHERE id=" . DB::escape($row['id']);
        DB::query($sql, "Can't change undeposited status");
      }
    }
    else {
      $row = reset($togroup);
      $sql = "UPDATE bank_trans SET undeposited=0, trans_date='" . Dates::date2sql($_POST['deposit_date']) . "',deposit_date='" . Dates::date2sql($_POST['deposit_date']) . "' WHERE id=" . DB::escape($row['id']);
      DB::query($sql, "Can't change undeposited status");
    }
    unset($_POST, $_SESSION['undeposited']);
    Display::meta_forward($_SERVER['PHP_SELF']);
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
  Ajax::i()->activate('summary');
  start_form();
  echo "<hr>";
  Display::div_start('summary');
  start_table();
  table_header(_("Deposit Date"));
  start_row();
  date_cells("", "deposit_date", _('Date of funds to deposit'), get_post('deposit_date') == '', 0, 0, 0, NULL, FALSE, array('rebind' => FALSE));
  end_row();
  table_header(_("Total Amount"));
  start_row();
  amount_cell($_POST['deposited'], FALSE, '', "deposited");
  hidden("to_deposit", $_POST['to_deposit'], TRUE);
  end_row();
  end_table();
  submit_center('Deposit', _("Deposit"), TRUE, '', FALSE);
  Display::div_end();
  echo "<hr>";
  $date = Dates::add_days($_POST['deposit_date'], 10);
  $sql = "SELECT	type, trans_no, ref, trans_date,
				amount,	person_id, person_type_id, reconciled, id
		FROM bank_trans
		WHERE undeposited=1 AND trans_date <= '" . Dates::date2sql($date) . "' AND reconciled IS NULL AND amount<>0
		ORDER BY trans_date DESC,bank_trans.id ";
  $cols = array(
    _("Type") => array(
      'fun' => 'systype_name', 'ord' => ''
    ), _("#") => array(
      'fun' => 'trans_view', 'ord' => ''
    ), _("Reference"), _("Date") => array('date', 'ord' => 'desc'), _("Debit") => array(
      'align' => 'right', 'fun' => 'fmt_debit'
    ), _("Credit") => array(
      'align' => 'right', 'insert' => TRUE, 'fun' => 'fmt_credit'
    ), _("Person/Item") => array('fun' => 'fmt_person'), array(
      'insert' => TRUE, 'fun' => 'gl_view'
    ), "X" => array(
      'insert' => TRUE, 'fun' => 'dep_checkbox'
    )
  );
  $table =& db_pager::new_db_pager('trans_tbl', $sql, $cols);
  $table->width = "80%";
  DB_Pager::display($table);
  Display::br(1);
  submit_center('Deposit', _("Deposit"), TRUE, '', FALSE);
  end_form();
  Page::end();
  function check_date() {
    if (!Dates::is_date(get_post('deposit_date'))) {
      Event::error(_("Invalid deposit date format"));
      JS::setFocus('deposit_date');
      return FALSE;
    }
    return TRUE;
  }

  //
  //	This function can be used directly in table pager
  //	if we would like to change page layout.
  //	if we would like to change page layout.
  //
  function dep_checkbox($row) {
    $name = "dep_" . $row['id'];
    $hidden = 'amount_' . $row['id'];
    $value = $row['amount'];
    $chk_value = check_value("dep_" . $row['id']);
    // save also in hidden field for testing during 'Reconcile'
    return checkbox(NULL, $name, $chk_value, TRUE, _('Deposit this transaction')) . hidden($hidden, $value, FALSE);
  }

  function systype_name($dummy, $type) {
    global $systypes_array;
    return $systypes_array[$type];
  }

  function trans_view($trans) {
    return GL_UI::trans_view($trans["type"], $trans["trans_no"]);
  }

  function gl_view($row) {
    return GL_UI::view($row["type"], $row["trans_no"]);
  }

  function fmt_debit($row) {
    $value = $row["amount"];
    return $value >= 0 ? Num::price_format($value) : '';
  }

  function fmt_credit($row) {
    $value = -$row["amount"];
    return $value > 0 ? Num::price_format($value) : '';
  }

  function fmt_person($row) {
    return Bank::payment_person_name($row["person_type_id"], $row["person_id"]);
  }

  function update_data() {
    global $update_pager;
    Ajax::i()->activate('summary');
    $update_pager = TRUE;
  }

  // Update db record if respective checkbox value has changed.
  //
  function change_tpl_flag($deposit_id) {
    if (!check_date() && check_value("dep_" . $deposit_id)) // temporary fix
    {
      return FALSE;
    }
    if (get_post('bank_date') == '') // new reconciliation
    {
      Ajax::i()->activate('bank_date');
    }
    $_POST['bank_date'] = Dates::date2sql(get_post('deposited_date'));
    /*	$sql = "UPDATE ".''."bank_trans SET undeposited=0"
                         ." WHERE id=".DB::escape($deposit_id);

                        DB::query($sql, "Can't change undeposited status");*/
    // save last reconcilation status (date, end balance)
    if (check_value("dep_" . $deposit_id)) {
      $_SESSION['undeposited']["dep_" . $deposit_id] = get_post('amount_' . $deposit_id);
      $_POST['deposited'] = $_POST['to_deposit'] + get_post('amount_' . $deposit_id);
    }
    else {
      unset($_SESSION['undeposited']["dep_" . $deposit_id]);
      $_POST['deposited'] = $_POST['to_deposit'] - get_post('amount_' . $deposit_id);
    }
    return TRUE;
  }
