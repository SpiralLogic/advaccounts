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
  class Reconcile extends \ADV\App\Controller\Base
  {

    protected function before() {
      $this->JS->_openWindow(800, 500);
      $this->JS->_footerFile('/js/reconcile.js');
      if ($this->Input->_post('reset')) {
        // GL_Account::reset_sql_for_reconcile($_POST['bank_account'], $this->Input->_post('reconcile_date'));
        $this->update_data();
      }
      $groupid = Forms::findPostPrefix("_ungroup_");
      if ($groupid > 1) {
        $group_refs = $_POST['ungroup_' . $groupid];
        $sql        = "UPDATE bank_trans SET undeposited=1, reconciled=null WHERE undeposited =" . $this->DB->_escape($groupid);
        $this->DB->_query($sql, "Couldn't ungroup group deposit");
        $sql = "UPDATE bank_trans SET ref=" . $this->DB->_quote('Removed group: ' . $group_refs) . ", amount=0, reconciled='" . Dates::dateToSql(Dates::today()) . "', undeposited=" . $groupid . " WHERE id=" . $groupid;
        $this->DB->_query($sql, "Couldn't update removed group deposit data");
        $this->update_data();
      }
      if (!count($_POST)) {
        if ($this->Session->_getGlobal('bank_date')) {
          $_POST['bank_date']         = $this->Session->_getGlobal('bank_date');
          $_POST['_bank_date_update'] = $_POST['bank_date'];
        }
        if ($this->Session->_getGlobal('bank_account')) {
          $_POST['bank_account'] = $this->Session->_getGlobal('bank_account');
        }
        $this->update_data();
      }
      $_POST['reconcile_date'] = $this->Input->_post('reconcile_date', null, Dates::newDocDate());
      if (Forms::isListUpdated('bank_account')) {
        $this->Session->_setGlobal('bank_account', $_POST['bank_account']);
        $this->Ajax->_activate('bank_date');
        $this->update_data();
      }
      if (Forms::isListUpdated('bank_date')) {
        $_POST['reconcile_date'] = $this->Input->_post('bank_date') == '' ? Dates::today() : Dates::sqlToDate($_POST['bank_date']);
        $this->Session->_setGlobal('bank_date', $_POST['bank_date']);
        $this->update_data();
      }
      if ($this->Input->_post('_reconcile_date_changed')) {
        $_POST['bank_date'] = Dates::dateToSql($_POST['reconcile_date']);
        $this->Ajax->_activate('bank_date');
        $this->update_data();
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
      $update_pager = false;
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
      $_POST['bank_account'] = $this->Input->_post('bank_account', Input::NUMERIC, '');
      $sql                   = GL_Account::get_sql_for_reconcile($_POST['bank_account'], $this->Input->_post('reconcile_date'));
      $act                   = Bank_Account::get($_POST["bank_account"]);
      Display::heading($act['bank_account_name'] . " - " . $act['bank_curr_code']);
      $cols         = array(
        _("Type")        => array('fun' => array($this, 'systype_name'), 'ord' => ''), //
        _("#")           => array('fun' => array($this, 'trans_view'), 'ord' => ''), //
        _("Reference")   => array(
          'fun'=> function($row) {
            return substr($row['ref'], 0, 6);
          }
        ), //
        _("Date")        => array('type'=> 'date', 'ord' => ''), //
        _("Debit")       => array('align' => 'right', 'fun' => array($this, 'fmt_debit'), 'ord' => ''), //
        _("Credit")      => array('align' => 'right', 'insert' => true, 'fun' => array($this, 'fmt_credit'), 'ord' => ''), //
        _("Person/Item") => array('fun' => array($this, 'fmt_person')), //
        array('insert' => true, 'fun' => array($this, 'gl_view')), //
        "X"              => array('insert' => true, 'fun' => array($this, 'rec_checkbox')), //
        array('insert' => true, 'fun' => array($this, 'ungroup'))
      );
      $table        = db_pager::new_db_pager('trans_tbl', $sql, $cols);
      $table->width = "80";
      DB_Pager::display($table);
      Display::br(1);
      Forms::submit('Reconcile', _("Reconcile"), true, 'Reconcile', null);
      Forms::end();
      $this->JS->_onload('$(function() { $("th:nth-child(9)").click(function() { jQuery("#_trans_tbl_span").find("input").value("");})})');
      $this->JS->_addLive("$('.grid tr').each(function(){var ischecked = $(this).children().find('input:checkbox').prop('checked'); if (ischecked) $(this).css('background','#33CCFF')})");
      Page::end();
    }
    protected function displaySummary() {
      $total = $this->getTotal();
      echo "<hr>";
      Display::div_start('summary');
      Table::start();
      Table::header(_("Reconcile Date"));
      Row::start();
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
    protected function getTotal() {$total=0;
      $result = GL_Account::get_max_reconciled($this->Input->_post('reconcile_date'), $_POST['bank_account']);
      if ($row = $this->DB->_fetch($result)) {
        $_POST["reconciled"] = Num::priceFormat($row["end_balance"] - $row["beg_balance"]);
        $total               = $row["total"];
        if (!isset($_POST["beg_balance"])) { // new selected account/statement
          $_POST["last_date"]   = Dates::sqlToDate($row["last_date"]);
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
      }        return $total;

    }
    /**
     * @return bool
     */
    function check_date() {
      if (!Dates::isDate($this->Input->_post('reconcile_date'))) {
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
    function rec_checkbox($row) {
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
    function ungroup($row) {
      if ($row['type'] != 15) {
        return '';
      }
      return "<button value='" . $row['id'] . '\' onclick="JsHttpRequest.request(\'_ungroup_' . $row['id'] . '\', this.form)" name="_ungroup_' . $row['id'] . '" type="submit" title="Ungroup"
     class="ajaxsubmit">Ungroup</button>' . Forms::hidden("ungroup_" . $row['id'], $row['ref'], true);
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
      return ($row['type'] != 15) ? GL_UI::view($row["type"], $row["trans_no"]) : '';
    }
    /**
     * @param $row
     *
     * @return int|string
     */
    function fmt_debit($row) {
      $value = $row["amount"];
      if ($value < 0) {
        return '';
      }
      return '<span class="bold">' . Num::priceFormat($value) . '</span>';
    }
    /**
     * @param $row
     *
     * @return int|string
     */
    function fmt_credit($row) {
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
    function fmt_person($row) {
      if ($row['type'] == ST_BANKTRANSFER) {
        return DB_Comments::get_string(ST_BANKTRANSFER, $row['trans_no']);
      } elseif ($row['type'] == ST_GROUPDEPOSIT) {
        $sql
                 = "SELECT bank_trans.ref,bank_trans.person_type_id,bank_trans.trans_no,bank_trans.person_id,bank_trans.amount,

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
    /**

     */
    function update_data() {
      global $update_pager;
      unset($_POST["beg_balance"], $_POST["end_balance"]);
      $this->Ajax->_activate('summary');
      $update_pager = true;
    }
    // Update db record if respective checkbox value has changed.
    //
    /**
     * @param $reconcile_id
     *
     * @return bool
     */
    function change_tpl_flag($reconcile_id) {
      if (!$this->check_date() && Forms::hasPost("rec_" . $reconcile_id)) // temporary fix
      {
        return false;
      }
      if ($this->Input->_post('bank_date') == '') // new reconciliation
      {
        $this->Ajax->_activate('bank_date');
      }
      $_POST['bank_date'] = Dates::dateToSql($this->Input->_post('reconcile_date'));
      $reconcile_value    = Forms::hasPost("rec_" . $reconcile_id) ? ("'" . $_POST['bank_date'] . "'") : 'null';
      GL_Account::update_reconciled_values($reconcile_id, $reconcile_value, $_POST['reconcile_date'], Validation::input_num('end_balance'), $_POST['bank_account']);
      $this->Ajax->_activate('reconciled');
      $this->Ajax->_activate('difference');
      $this->Ajax->_activate('summary');
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
  }

  new Reconcile();
