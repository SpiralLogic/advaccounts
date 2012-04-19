<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  /* Author Rob Mallon */

  JS::open_window(800, 500);
  JS::footerFile('/js/reconcile.js');
  Page::start(_($help_context = "Reconcile Bank Account"), SA_RECONCILE);
  Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
  $update_pager = FALSE;
  if (Input::post('reset')) {
    GL_Account::reset_sql_for_reconcile($_POST['bank_account'], get_post('reconcile_date'));
    update_data();
  }
  $groupid = find_submit("_ungroup_");
  if (isset($groupid) && $groupid > 1) {
    $grouprefs = $_POST['ungroup_' . $groupid];
    $sql = "UPDATE bank_trans SET undeposited=1, reconciled=NULL WHERE undeposited =" . DB::escape($groupid);
    DB::query($sql, 'Couldn\'t update undesposited status');
    $sql = "UPDATE bank_trans SET ref=" . DB::escape('Removed group: ' . $grouprefs) . ", amount=0, reconciled='" . Dates::date2sql(Dates::today()) . "',
 undeposited=" . $groupid . " WHERE id=" . $groupid;
    DB::query($sql, "Couldn't update removed group data");
    update_data();
  }
  if (isset($_SESSION['wa_current_reconcile_date']) && count($_POST) < 1) {
    if ($_SESSION['wa_current_reconcile_date'] != '') {
      $_POST['bank_date'] = $_SESSION['wa_current_reconcile_date'];
      $_POST['_bank_date_update'] = $_POST['bank_date'];
      update_data();
    }
  }
  if (!isset($_POST['reconcile_date'])) { // init page
    $_POST['reconcile_date'] = Dates::new_doc_date();
    //	$_POST['bank_date'] = Dates::date2sql(Dates::today());
  }
  if (list_updated('bank_account')) {
    Ajax::i()->activate('bank_date');
    update_data();
  }
  if (list_updated('bank_date')) {
    $_POST['reconcile_date'] = get_post('bank_date') == '' ? Dates::today() : Dates::sql2date($_POST['bank_date']);
    update_data();
  }
  if (get_post('_reconcile_date_changed')) {
    $_POST['bank_date'] = check_date() ? Dates::date2sql(get_post('reconcile_date')) : '';
    Ajax::i()->activate('bank_date');
    update_data();
  }
  $id = find_submit('_rec_');
  if ($id != -1) {
    change_tpl_flag($id);
  }
  if (isset($_POST['Reconcile'])) {
    JS::set_focus('bank_date');
    foreach ($_POST['last'] as $id => $value) {
      if ($value != check_value('rec_' . $id)) {
        if (!change_tpl_flag($id)) {
          break;
        }
      }
    }
    Ajax::i()->activate('_page_body');
  }
  start_form();
  start_table();
  start_row();
  Bank_Account::cells(_("Account:"), 'bank_account', NULL, TRUE);
  Bank_UI::reconcile_cells(_("Bank Statement:"), get_post('bank_account'), 'bank_date', NULL, TRUE, _("New"));
  //button_cell("reset", "reset", "reset");
  end_row();
  end_table();
  $_SESSION['wa_current_reconcile_date'] = $_POST['bank_date'];
  $result = GL_Account::get_max_reconciled(get_post('reconcile_date'), $_POST['bank_account']);
  if ($row = DB::fetch($result)) {
    $_POST["reconciled"] = Num::price_format($row["end_balance"] - $row["beg_balance"]);
    $total = $row["total"];
    if (!isset($_POST["beg_balance"])) { // new selected account/statement
      $_POST["last_date"] = Dates::sql2date($row["last_date"]);
      $_POST["beg_balance"] = Num::price_format($row["beg_balance"]);
      $_POST["end_balance"] = Num::price_format($row["end_balance"]);
      if (get_post('bank_date')) {
        // if it is the last updated bank statement retrieve ending balance
        $row = GL_Account::get_ending_reconciled($_POST['bank_account'], $_POST['bank_date']);
        if ($row) {
          $_POST["end_balance"] = Num::price_format($row["ending_reconcile_balance"]);
        }
      }
    }
  }
  echo "<hr>";
  Display::div_start('summary');
  start_table();
  table_header(_("Reconcile Date"));
  start_row();
  date_cells("", "reconcile_date", _('Date of bank statement to reconcile'), get_post('bank_date') == '', 0, 0, 0, NULL, TRUE);
  end_row();
  table_header(_("Beginning Balance"));
  start_row();
  amount_cells_ex("", "beg_balance", 15);
  end_row();
  table_header(_("Ending Balance"));
  start_row();
  amount_cells_ex("", "end_balance", 15);
  $reconciled = Validation::input_num('reconciled');
  $difference = Validation::input_num("end_balance") - Validation::input_num("beg_balance") - $reconciled;
  end_row();
  table_header(_("Account Total"));
  start_row();
  amount_cell($total);
  end_row();
  table_header(_("Reconciled Amount"));
  start_row();
  amount_cell($reconciled, FALSE, '', "reconciled");
  end_row();
  table_header(_("Difference"));
  start_row();
  amount_cell($difference, FALSE, '', "difference");
  end_row();
  end_table();
  Display::div_end();
  echo "<hr>";
  if (!isset($_POST['bank_account'])) {
    $_POST['bank_account'] = "";
  }
  $sql = GL_Account::get_sql_for_reconcile($_POST['bank_account'], get_post('reconcile_date'));
  $act = Bank_Account::get($_POST["bank_account"]);
  Display::heading($act['bank_account_name'] . " - " . $act['bank_curr_code']);
  $cols = array(
    _("Type") => array('fun' => 'systype_name', 'ord' => ''), //
    _("#") => array('fun' => 'trans_view', 'ord' => ''), //
    _("Reference"), //
    _("Date") => array('date', 'ord' => ''), //
    _("Debit") => array('align' => 'right', 'fun' => 'fmt_debit', 'ord' => ''), //
    _("Credit") => array('align' => 'right', 'insert' => TRUE, 'fun' => 'fmt_credit', 'ord' => ''), //
    _("Person/Item") => array('fun' => 'fmt_person'), //
    array('insert' => TRUE, 'fun' => 'gl_view'), //
    "X" => array('insert' => TRUE, 'fun' => 'rec_checkbox'), //
    array('insert' => TRUE, 'fun' => 'ungroup')
  );
  $table =& db_pager::new_db_pager('trans_tbl', $sql, $cols);
  $table->width = "80%";
  DB_Pager::display($table);
  Display::br(1);
  submit_center('Reconcile', _("Reconcile"), TRUE, '', NULL);
  end_form();
  $js
    = <<<JS
	$(function() {
		$("th:nth-child(9)").click(function() {
	jQuery("#_trans_tbl_span").find("input").value("")
	})
	})
JS;
  JS::onload($js);
  Page::end();
  /**
   * @return bool
   */
  function check_date() {
    if (!Dates::is_date(get_post('reconcile_date'))) {
      Event::error(_("Invalid reconcile date format"));
      JS::set_focus('reconcile_date');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @param $row
   *
   * @return string
   */
  function rec_checkbox($row) {
    $name = "rec_" . $row['id'];
    $hidden = 'last[' . $row['id'] . ']';
    $value = $row['reconciled'] != '';
    // save also in hidden field for testing during 'Reconcile'
    JS::set_focus($name);

    return checkbox(NULL, $name, $value, TRUE, _('Reconcile this transaction')) . hidden($hidden, $value, FALSE);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function ungroup($row) {
    if ($row['type'] != 15) {
      return '';
    }
    return "<button value='" . $row['id'] . '\' onclick="JsHttpRequest.request(\'_ungroup_' . $row['id'] . '\', this.form)" name="_ungroup_' . $row['id'] . '" type="submit" title="Ungroup"
 class="ajaxsubmit">Ungroup</button>' . hidden("ungroup_" . $row['id'], $row['ref'], TRUE);
  }

  /**
   * @param $dummy
   * @param $type
   *
   * @return mixed
   */
  function systype_name($dummy, $type) {
    global $systypes_array;
    return $systypes_array[$type];
  }

  /**
   * @param $trans
   *
   * @return null|string
   */
  function trans_view($trans) {
    return GL_UI::trans_view($trans["type"], $trans["trans_no"]);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function gl_view($row) {
    return ($row['type'] != 15) ?
      GL_UI::view($row["type"], $row["trans_no"]) : '';
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function fmt_debit($row) {
    $value = $row["amount"];
    return $value >= 0 ? Num::price_format($value) : '';
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function fmt_credit($row) {
    $value = -$row["amount"];
    return $value > 0 ? Num::price_format($value) : '';
  }

  /**
   * @param $row
   *
   * @return string
   */
  function fmt_person($row) {
    if ($row['type'] == ST_BANKTRANSFER) {
      return DB_Comments::get_string(ST_BANKTRANSFER, $row['trans_no']);
    }
    elseif ($row['type'] == ST_GROUPDEPOSIT) {
      $sql
        = "SELECT bank_trans.ref,bank_trans.person_type_id,bank_trans.trans_no,bank_trans.person_id,bank_trans.amount,

			comments.memo_ FROM bank_trans LEFT JOIN comments ON (bank_trans.type=comments.type AND bank_trans.trans_no=comments.id)

			WHERE bank_trans.bank_act='" . $_POST['bank_account'] . "' AND bank_trans.type != " . ST_GROUPDEPOSIT .
        " AND bank_trans.undeposited>0 AND (undeposited = " . $row['id'] . ")";
      $result = DB::query($sql, 'Couldn\'t get deposit references');
      $content = '';
      foreach ($result as $trans) {
        $name = Bank::payment_person_name($trans["person_type_id"], $trans["person_id"], TRUE, $trans["trans_no"]);
        $content .= $trans['ref'] . ' <span class="u">' . $name . ' ($' . Num::price_format($trans['amount']) . ')</span>: ' . $trans['memo_'] . '<br>';
      }
      return $content;
    }
    return Bank::payment_person_name($row["person_type_id"], $row["person_id"], TRUE, $row["trans_no"]);
  }

  /**

   */
  function update_data() {
    global $update_pager;
    unset($_POST["beg_balance"], $_POST["end_balance"]);
    Ajax::i()->activate('summary');
    $update_pager = TRUE;
  }

  // Update db record if respective checkbox value has changed.
  //
  /**
   * @param $reconcile_id
   *
   * @return bool
   */
  function change_tpl_flag($reconcile_id) {
    if (!check_date() && check_value("rec_" . $reconcile_id)) // temporary fix
    {
      return FALSE;
    }
    if (get_post('bank_date') == '') // new reconciliation
    {
      Ajax::i()->activate('bank_date');
    }
    $_POST['bank_date'] = Dates::date2sql(get_post('reconcile_date'));
    $reconcile_value = check_value("rec_" . $reconcile_id) ? ("'" . $_POST['bank_date'] . "'") : 'NULL';
    GL_Account::update_reconciled_values($reconcile_id, $reconcile_value, $_POST['reconcile_date'], Validation::input_num('end_balance'), $_POST['bank_account']);
    Ajax::i()->activate('reconciled');
    Ajax::i()->activate('difference');
    JS::reset_focus();
    return TRUE;
  }
