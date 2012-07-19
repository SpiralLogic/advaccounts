<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /**

   */
  class Reconcile extends \ADV\App\Controller\Base {
    /** @var Dates Dates*/
    protected $Dates;
    protected function before() {
      $this->Dates = Dates::i();
      $this->JS->_openWindow(800, 500);
      $this->JS->_footerFile('/js/reconcile.js');
      if (!Input::post('bank_account')) {
        $_POST['bank_account'] = 5;
      }
      if ($this->Input->_post('reset')) {
        // GL_Account::reset_sql_for_reconcile($_POST['bank_account'], $this->Input->_post('reconcile_date'));
        $this->updateData();
      }
      $groupid = Forms::findPostPrefix("_ungroup_");
      if ($groupid > 1) {
        $group_refs = $_POST['ungroup_' . $groupid];
        $sql        = "UPDATE bank_trans SET undeposited=1, reconciled=null WHERE undeposited =" . $this->DB->_escape($groupid);
        $this->DB->_query($sql, "Couldn't ungroup group deposit");
        $sql = "UPDATE bank_trans SET ref=" . $this->DB->_quote('Removed group: ' . $group_refs) . ", amount=0, reconciled='" . $this->Dates->_today() . "', undeposited=" . $groupid . " WHERE id=" . $groupid;
        $this->DB->_query($sql, "Couldn't update removed group deposit data");
        $this->updateData();
      }
      if (!isset($_POST['bank_date'])) {
        $_POST['bank_date']         = $this->Session->_getGlobal('bank_date', $this->Dates->_today());
        $_POST['_bank_date_update'] = $_POST['bank_date'];
      }
      if (!isset($_POST['bank_account'])) {
        $_POST['bank_account'] = $this->Session->_getGlobal('bank_account');
        $this->updateData();
      }
      $_POST['reconcile_date'] = $this->Input->_post('reconcile_date', null, $this->Dates->_newDocDate());
      if (Forms::isListUpdated('bank_account')) {
        $this->Session->_setGlobal('bank_account', $_POST['bank_account']);
        $this->Ajax->_activate('bank_date');
        $this->updateData();
      }
      if (Forms::isListUpdated('bank_date')) {
        $_POST['reconcile_date'] = $this->Input->_post('bank_date') == '' ? $this->Dates->_today() : $_POST['bank_date'];
        $this->Session->_setGlobal('bank_date', $_POST['bank_date']);
        $this->Ajax->_activate('bank_date');
        $this->updateData();
      }
      if ($this->Input->_post('_reconcile_date_changed')) {
        $_POST['bank_date'] = $this->Dates->_dateToSql($_POST['reconcile_date']);
        $this->Ajax->_activate('bank_date');
        $this->updateData();
      }
      $id = Forms::findPostPrefix('_rec_');
      if ($id != -1) {
        $this->change_tpl_flag($id);
      }
      if (isset($_POST['Reconcile'])) {
        $this->JS->_setFocus('bank_date');
        foreach ($_POST['last'] as $id => $value) {
          if ($value != Forms::hasPost('rec_' . $id) && !$this->change_tpl_flag($id)) {
            break;
          }
        }
        $this->Ajax->_activate('_page_body');
      }
    }
    protected function index() {
      Page::start(_($help_context = "Reconcile Bank Account"), SA_RECONCILE);
      Forms::start();
      Table::start();
      Row::start();
      Bank_Account::cells(_("Account:"), 'bank_account', null, true);
      Bank_UI::reconcile_cells(_("Bank Statement:"), $this->Input->_post('bank_account'), 'bank_date', null, true, _("New"));
      Forms::buttonCell("reset", "reset", "reset");
      Row::end();
      Table::end();
      $this->displaySummary();
      echo "<hr>";
      Input::post('bank_account') == 5 ? $this->newWay() : $this->oldWay();
      Forms::submit('Reconcile', _("Reconcile"), true, 'Reconcile');
      Forms::end();
      Page::end();
    }
    /**
     * @return bool
     */
    protected function  newWay() {
      $date = $_POST['bank_date'];
      if ($date) {
        $begin = $this->Dates->_dateToSql($this->Dates->_beginMonth($date));
        $end   = $this->Dates->_dateToSql($this->Dates->_endMonth($date));
      } else {
        $begin = "(SELECT max(reconciled) from bank_trans)";
        $end   = $this->Dates->_today();
      }
      $sql = "SELECT bt.type, bt.trans_no, bt.ref,  bt.trans_date,bt.id, IF( bt.trans_no IS null, SUM( g.amount ), bt.amount ) AS amount
                   , bt.person_id, bt.person_type_id , bt.reconciled FROM bank_trans bt LEFT OUTER JOIN bank_trans g ON g.undeposited = bt.id
                   WHERE bt.bank_act = " . $this->DB->_quote(Input::post('bank_account')) . "
                   AND bt.trans_date <= '" . ($_POST['bank_date'] ? : $this->Dates->_today()) . "'
                   AND bt.undeposited=0
                   AND (bt.reconciled IS null";
      if ($_POST['bank_date']) {
        $sql .= " OR bt.reconciled='" . $_POST['bank_date'] . "'";
      }
      $sql .= ") AND bt.amount!=0 GROUP BY bt.id ORDER BY IF(bt.trans_date>='$begin' AND bt.trans_date<='$end',1,0) , bt.reconciled DESC    ,bt.trans_date  , amount ";
      $this->DB->_query($sql);
      $rec = $this->DB->_fetchAll();
      $sql = "SELECT date as state_date, amount as state_amount,memo FROM temprec WHERE  date >= '$begin' AND  date <='" . $end . "' ORDER BY date  ,amount";
      $this->DB->_query($sql);
      $statement_trans = $this->DB->_fetchAll();
      if (!$statement_trans) {
        return $this->oldWay();
      }
      $known_trans                 = [];
      $known_headers               = [
        'type', 'trans_no', 'ref', 'trans_date', 'id', 'amount', 'person_id', 'person_type_id', 'reconciled'
      ];
      $known_headers               = array_combine(array_values($known_headers), array_pad([], count($known_headers), ''));
      $statement_transment_headers = array_combine(array_keys($statement_trans[0]), array_values(array_pad([], count($statement_trans[0]), '')));
      while ($v = array_shift($statement_trans)) {
        $amount = $v['state_amount'];
        foreach ($rec as $p=> $q) {
          if ($q['amount'] == $amount) {
            $matched = $rec[$p] + $v;
            unset($rec[$p]);
            $known_trans[] = $matched;
            continue 2;
          }
        }
        $newv = $known_headers;
        Arr::append($newv, $v);
        $known_trans[] = $newv;
      }
      foreach ($rec as &$r) {
        Arr::append($r, $statement_transment_headers);
      }
      Arr::append($known_trans, $rec);
      usort($known_trans, [$this, 'sortByOrder']);
      $cols            = [
        'Type'              => ['fun'=> array($this, 'sysTypeName')], //
        '#'                 => ['align'=> 'center', 'fun'=> array($this, 'viewTrans')], //
        'Ref'               => ['fun'=> 'formatReference'], //
        'Date'              => ['type'=> 'date'], //
        'Debit'             => ['align'=> 'right', 'fun'=> array($this, 'formatDebit')], //
        'Credit'            => ['align'=> 'right', 'insert'=> true, 'fun'=> array($this, 'formatCredit')], //
        'Info'              => ['fun'=> array($this, 'formatInfo')], //
        'GL'                => ['fun'=> array($this, 'viewGl')], //
        ['fun'=> array($this, 'reconcileCheckbox')], //
        'Bank Date'         => ['type'=> 'date'], //
        'Amount'            => ['align'=> 'right', 'class'=> 'bold'], //
        'Info'
      ];
      $table           = DB_Pager::new_db_pager('bank_rec', $known_trans, $cols);
      $table->rowClass = function($row) {
        if (($row['trans_date'] && $row['reconciled'] && !$row['state_date']) || ($row['state_date'] && !$row['reconciled'])) {
          return "overduebg";
        } elseif ($row['reconciled']) {
          return "done";
        }
        return '';
      };
      $table->display();
      return true;
    }
    protected function displaySummary() {
      $total = $this->getTotal();
      echo "<hr>";
      Display::div_start('summary');
      Table::start();
      Table::header(_("Reconcile Date"));
      Row::start();
      $_POST['reconcile_date'] = $this->Dates->_sqlToDate($_POST['reconcile_date']);
      Forms::dateCells("", "reconcile_date", _('Date of bank statement to reconcile'), $this->Input->_post('bank_date') == '', 0, 0, 0, null, true);
      Row::end();
      Table::header(_("Beginning Balance"));
      Row::start();
      Forms::amountCellsEx("", "beg_balance", 15);
      Row::end();
      Table::header(_("Ending Balance"));
      Row::start();
      Forms::amountCellsEx("", "end_balance", 15);
      $reconciled = Validation::input_num('reconciled');
      $difference = Validation::input_num("end_balance") - Validation::input_num("beg_balance") - $reconciled;
      Row::end();
      Table::header(_("Account Total"));
      Row::start();
      Cell::amount($total);
      Row::end();
      Table::header(_("Reconciled Amount"));
      Row::start();
      Cell::amount($reconciled, false, '', "reconciled");
      Row::end();
      Table::header(_("Difference"));
      Row::start();
      Cell::amount($difference, false, '', "difference");
      Row::end();
      Table::end();
      Display::div_end();
    }
    /**
     * @return int
     */
    protected function getTotal() {
      $total = 0;
      if ($_POST['bank_account'] == 5) {
        $date = $_POST['bank_date'];
        if ($date) {
          $begin = $this->Dates->_dateToSql($this->Dates->_beginMonth($date));
          $end   = $this->Dates->_dateToSql($this->Dates->_endMonth($date));
        } else {
          $begin = "(SELECT max(reconciled) from bank_trans)";
          $end   = $this->Dates->_today();
        }
        $sql                  = "(select (rb - amount) as amount from temprec where date>='$begin' and date<='$end' order by id desc limit 0,1) union (select rb as amount from temprec where date>='$begin' and date<='$end' order by id asc limit 0,1)";
        $result               = $this->DB->_query($sql);
        $beg_balance = $this->DB->_fetch($result)['amount'];
          $end_balance = $this->DB->_fetch($result)['amount'];
        $_POST["beg_balance"] = Num::priceFormat($beg_balance);
        $_POST["end_balance"] = Num::priceFormat($end_balance);
        $_POST["reconciled"]  = Num::priceFormat($end_balance- $beg_balance);
      }
      $result = GL_Account::get_max_reconciled($this->Input->_post('reconcile_date'), $_POST['bank_account']);
      if ($row = $this->DB->_fetch($result)) {
        $_POST["reconciled"] = Num::priceFormat($row["end_balance"] - $row["beg_balance"]);
        $total               = $row["total"];
        if (!isset($_POST["beg_balance"])) { // new selected account/statement
          $_POST["last_date"]   = $this->Dates->_sqlToDate($row["last_date"]);
          $_POST["beg_balance"] = Num::priceFormat($row["beg_balance"]);
          $_POST["end_balance"] = Num::priceFormat($row["end_balance"]);
          if ($this->Input->_post('bank_date')) {
            // if it is the last updated bank statement retrieve ending balance
            $row = GL_Account::get_ending_reconciled($_POST['bank_account'], $_POST['bank_date']);
            if ($row) {
              $_POST["end_balance"] = Num::priceFormat($row["ending_reconcile_balance"]);
            }
          }
        }
      }
      return $total;
    }
    /**
     * @return bool
     */
    function checkDate() {
      if (!$this->Dates->_isDate($this->Input->_post('reconcile_date'))) {
        Event::error(_("Invalid reconcile date format"));
        $this->JS->_setFocus('reconcile_date');
        return false;
      }
      return true;
    }
    /**
     * @param $row
     *
     * @return string
     */
    function reconcileCheckbox($row) {
      if (!$row['amount']) {
        return '';
      }
      $name   = "rec_" . $row['id'];
      $hidden = 'last[' . $row['id'] . ']';
      $value  = $row['reconciled'] != '';
      return Forms::checkbox(null, $name, $value, true, _('Reconcile this transaction')) . Forms::hidden($hidden, $value, false);
    }
    /**
     * @param $row
     *
     * @return string
     */
    function ungroupButton($row) {
      if ($row['type'] != 15) {
        return '';
      }
      return "<div class='center'><button value='" . $row['id'] . '\' onclick="JsHttpRequest.request(\'_ungroup_' . $row['id'] . '\',
      this.form)" name="_ungroup_' . $row['id'] . '" type="submit" title="Ungroup"
     class="ajaxsubmit">Ungroup</button></div>' . Forms::hidden("ungroup_" . $row['id'], $row['ref'], true);
    }
    /**
     * @param $row
     *
     * @internal param $dummy
     * @internal param $type
     * @return mixed
     */
    function sysTypeName($row) {
      $type = $row['type'];
      global $systypes_array;
      if (!$type) {
        return '';
      }
      return $systypes_array[$type];
    }
    /**
     * @param $row
     *
     * @internal param $trans
     * @return null|string
     */
    function viewTrans($row) {
      if (!$row['type']) {
        return '';
      } elseif ($row['type'] == ST_GROUPDEPOSIT) {
        return $this->ungroupButton($row);
      }
      $content = GL_UI::viewTrans($row["type"], $row["trans_no"]);
      if (!$row['reconciled']) {
        $content .= '<br><a href="' . e('/system/void_transaction?type=' . $row['type'] . '&trans_no=' . $row['trans_no'] . '&memo=Deleted during reconcile.') . '" target="_blank"
                                    class="button">void</a>';
      }
      return $content;
    }
    /**
     * @param $row
     *
     * @return string
     */
    function viewGl($row) {
      if (!$row['amount']) {
        return '';
      }
      return ($row['type'] != 15) ? GL_UI::view($row["type"], $row["trans_no"]) : '';
    }
    /**
     * @param $row
     *
     * @return int|string
     */
    function formatDebit($row) {
      $value = $row["amount"];
      if ($value > 0) {
        return '<span class="bold">' . Num::priceFormat($value) . '</span>';
      }
      return '';
    }
    /**
     * @param $row
     *
     * @return int|string
     */
    function formatCredit($row) {
      $value = -$row["amount"];
      if ($value <= 0) {
        return '';
      }
      return '<span class="bold">' . Num::priceFormat($value) . '</span>';
    }
    /**
     * @param $row
     *
     * @return string
     */
    function formatInfo($row) {
      if ($row['type'] == ST_BANKTRANSFER) {
        return DB_Comments::get_string(ST_BANKTRANSFER, $row['trans_no']);
      } elseif ($row['type'] == ST_GROUPDEPOSIT) {
        $sql     = "SELECT bank_trans.ref,bank_trans.person_type_id,bank_trans.trans_no,bank_trans.person_id,bank_trans.amount,
    			comments.memo_ FROM bank_trans LEFT JOIN comments ON (bank_trans.type=comments.type AND bank_trans.trans_no=comments.id)
    			WHERE bank_trans.bank_act='" . $_POST['bank_account'] . "' AND bank_trans.type != " . ST_GROUPDEPOSIT . " AND bank_trans.undeposited>0 AND (undeposited = " . $row['id'] . ")";
        $result  = $this->DB->_query($sql, 'Couldn\'t get deposit references');
        $content = '';
        foreach ($result as $trans) {
          $name = Bank::payment_person_name($trans["person_type_id"], $trans["person_id"], true, $trans["trans_no"]);
          $content .= $trans['ref'] . ' <span class="u">' . $name . ' ($' . Num::priceFormat($trans['amount']) . ')</span>: ' . $trans['memo_'] . '<br>';
        }
        return $content;
      }
      return Bank::payment_person_name($row["person_type_id"], $row["person_id"], true, $row["trans_no"]);
    }
    function updateData() {
      DB_Pager::kill('bank_rec');
      unset($_POST["beg_balance"], $_POST["end_balance"]);
      $this->Ajax->_activate('_page_body');
    }
    // Update db record if respective checkbox value has changed.
    //
    /**
     * @param $reconcile_id
     *
     * @return bool
     */
    function change_tpl_flag($reconcile_id) {
      if (!$this->checkDate() && Forms::hasPost("rec_" . $reconcile_id)) // temporary fix
      {
        return false;
      }
      if ($this->Input->_post('bank_date') == '') // new reconciliation
      {
        $this->Ajax->_activate('bank_date');
      }
      $_POST['bank_date'] = $this->Dates->_dateToSql($this->Input->_post('reconcile_date'));
      $reconcile_value    = Forms::hasPost("rec_" . $reconcile_id) ? ("'" . $_POST['bank_date'] . "'") : 'null';
      GL_Account::update_reconciled_values($reconcile_id, $reconcile_value, $_POST['reconcile_date'], Validation::input_num('end_balance'), $_POST['bank_account']);
      $this->Ajax->_activate('_page_body');
      $this->JS->_setFocus($reconcile_id);
      return true;
    }
    protected function after() {
      // TODO: Implement after() method.
    }
    /**
     * @internal param $prefix
     * @return bool|mixed
     */
    protected function runValidation() {
      Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
    }
    /**
     * @param $a
     * @param $b
     *
     * @return int
     */
    public function sortByOrder($a, $b) {
      $date1 = $a['state_date'] ? : $a['trans_date'];
      $date2 = $b['state_date'] ? : $b['trans_date'];
      if ($date1 == $date2) {
        $amount1 = $a['state_amount'] ? : $a['amount'];
        $amount2 = $b['state_amount'] ? : $b['amount'];
        return $amount1 - $amount2;
      }
      return strcmp($date1, $date2);
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatReference($row) {
      return substr($row['ref'], 0, 7);
    }
    /**
     * @return bool
     */
    private function oldWay() {
      $_POST['bank_account'] = $this->Input->_post('bank_account', Input::NUMERIC, '');
      $sql                   = GL_Account::get_sql_for_reconcile($_POST['bank_account'], $this->Input->_post('reconcile_date'));
      $act                   = Bank_Account::get($_POST["bank_account"]);
      Display::heading($act['bank_account_name'] . " - " . $act['bank_curr_code']);
      $cols         = array(
        _("Type")        => array('fun' => array($this, 'sysTypeName'), 'ord' => ''), //
        _("#")           => array('fun' => array($this, 'viewTrans'), 'ord' => ''), //
        _("Reference")   => array('fun'=> [$this, 'formatReference']), //
        _("Date")        => array('type'=> 'date', 'ord' => ''), //
        _("Debit")       => array('align' => 'right', 'fun' => array($this, 'formatDebit'), 'ord' => ''), //
        _("Credit")      => array('align' => 'right', 'insert' => true, 'fun' => array($this, 'formatCredit'), 'ord' => ''), //
        _("Person/Item") => array('fun' => array($this, 'formatInfo')), //
        array('insert' => true, 'fun' => array($this, 'viewGl')), //
        "X"              => array('insert' => true, 'fun' => array($this, 'reconcileCheckbox')), //
        array('insert' => true, 'fun' => array($this, 'ungroupButton'))
      );
      $table        = db_pager::new_db_pager('trans_tbl', $sql, $cols);
      $table->width = "80";
      $table->display($table);
      return true;
    }
  }

  new Reconcile();
