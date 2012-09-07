<?php
  use ADV\Core\Row;
  use ADV\App\UI\UI;
  use ADV\App\Bank\Bank;
  use ADV\Core\Cell;
  use ADV\Core\Table;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class UndepositedFunds extends \ADV\App\Controller\Base
  {
    /** @var Dates */
    protected $Dates;
    public $updateData;
    protected function before() {
      $this->Dates = Dates::i();
      JS::_openWindow(950, 500);
      JS::_footerFile('/js/reconcile.js');
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
      if (Input::_post('_deposit_date_changed')) {
        $this->reset();
      }
      $id = Forms::findPostPrefix('_dep_');
      if ($id != -1) {
        $this->change_tpl_flag($id);
      }
      if (isset($_POST['Deposit'])) {
        $row = $this->deposit();
      }
      $_POST['to_deposit'] = 0;
      $this->getTotals();
      $this->Ajax->activate('summary');
    }
    protected function getTotals() {
      if (Input::_session('undeposited')) {
        foreach ($_SESSION['undeposited'] as $rowid => $row) {
          if (substr($rowid, 0, 4) == 'dep_') {
            $_POST['to_deposit'] += $row;
          }
        }
      }
      $_POST['deposited'] = $_POST['to_deposit'];
    }
    protected function reset() {
      $_POST['deposited']      = 0;
      $_SESSION['undeposited'] = [];
      $_POST['deposit_date']   = $this->check_date() ? (Input::_post('deposit_date')) : '';
      foreach ($_POST as $rowid => $row) {
        if (substr($rowid, 0, 4) == 'dep_') {
          unset($_POST[$rowid]);
        }
      }
      $this->updateData();
    }
    /**
     * @return mixed
     */
    protected function deposit() {
      $togroup = Input::_post('toDeposit');
      if (count($togroup) > 1) {
        $total = 0;
        foreach ($togroup as $trans) {
          $total += $trans['amount'];
        }
        $sql        = "INSERT INTO bank_trans (type, bank_act, amount, ref, trans_date, person_type_id, person_id, undeposited) VALUES (" . ST_GROUPDEPOSIT . ", 5, $total," . $this->DB->_quote(
          'Group Deposit'
        ) . ",'" . $this->Dates->dateToSql($_POST['deposit_date']) . "', 6, '" . User::i()->user . "',0)";
        $query      = $this->DB->_query($sql, "Undeposited Cannot be Added");
        $deposit_id = $this->DB->_insertId($query);
        foreach ($togroup as $trans) {
          $sql = "UPDATE bank_trans SET undeposited=" . $deposit_id . " WHERE id=" . $this->DB->_escape($trans['id']);
          $this->DB->_query($sql, "Can't change undeposited status");
        }
      } else {
        $trans = reset($togroup);
        $sql   = "UPDATE bank_trans SET undeposited=0, trans_date='" . $this->Dates->dateToSql($_POST['deposit_date']) . "',deposit_date='" . $this->Dates->dateToSql(
          $_POST['deposit_date']
        ) . "' WHERE id=" . $this->DB->_escape($trans['id']);
        $this->DB->_query($sql, "Can't change undeposited status");
      }
    }
    protected function index() {
      Page::start(_($help_context = "Undeposited Funds"), SA_RECONCILE, Input::_request('frame'));
      Forms::start();
      echo "<hr>";
      Display::div_start('summary');
      Table::start();
      Table::header(_("Deposit Date"));
      Row::start();
      Forms::dateCells("", "deposit_date", _('Date of funds to deposit'), Input::_post('deposit_date') == '', 0, 0, 0, null, false, array('rebind' => false));
      Row::end();
      Table::header(_("Total Amount"));
      Row::start();
      Cell::amount($_POST['deposited'], false, '', "deposited");
      Forms::hidden("to_deposit", $_POST['to_deposit'], true);
      Row::end();
      Row::start();
      Row::end();
      Table::header(_("Bank fees"));
      Row::start();
      Cell::amount($_POST['bank_fees'], false, '', "bank_fees");
      Row::end();
      Table::end();
      echo HTML::button('deposit', _("Deposit"));
      Display::div_end();
      echo "<hr>";
      $date = $this->Dates->addDays($_POST['deposit_date'], 10);
      $sql
                    = "SELECT	type, trans_no, ref, trans_date,
                    amount,	person_id, person_type_id, reconciled, id
            FROM bank_trans
            WHERE undeposited=1 AND trans_date <= '" . $this->Dates->dateToSql($date) . "' AND reconciled IS null AND amount<>0
            ORDER BY trans_date DESC,bank_trans.id ";
      $cols         = array(
        _("Type")                    => ['fun' => [$this, 'sysTypeName'], 'ord' => ''], //
        _("#")                       => ['fun' => [$this, 'viewTrans'], 'ord' => '', 'align'=> 'center'], //
        _("Reference"), //
        _("Date")                    => ['type'=> 'date', 'ord' => 'desc'], //
        _("Debit")                   => ['align' => 'right', 'fun' => [$this, 'formatDebit']], //
        _("Credit")                  => ['align' => 'right', 'insert' => true, 'fun' => [$this, 'formatCredit']], //
        _("Person/Item")             => ['fun' => [$this, 'formatPerson']], //
        ['insert' => true, 'fun' => [$this, 'viewGl']], //
        "X"                          => ['insert' => true, 'fun' => [$this, 'depositCheckbox']]
      );
      $table        = DB_Pager::new_db_pager('trans_tbl', $sql, $cols);
      $table->width = "80%";
      $table->display($table);
      UI::lineSortable();
      Display::br(1);
      Forms::end();
      Page::end();
    }
    /**
     * @return bool
     */
    function check_date() {
      if (!$this->Dates->isDate(Input::_post('deposit_date'))) {
        Event::error(_("Invalid deposit date format"));
        JS::_setFocus('deposit_date');

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
      $amount    = $row['amount'];
      $chk_value = Input::_hasPost($row['id']) ? 'checked' : '';
      // save also in hidden field for testing during 'Reconcile'
      $date = $this->Dates->sqlToDate($row['trans_date']);
      $name = $row['id'];

      return "<input type='checkbox' name='dep' value='$name'  data-id='$name' title='Deposit this transaction' data-date='$date' data-amount='$amount' $chk_value> ";
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
     * @param $row
     *
     * @internal param $trans
     * @return null|string
     */
    function viewTrans($row) {
      $content = GL_UI::viewTrans($row["type"], $row["trans_no"]);
      $content .= '<br><a class="button voidlink" data-type="' . $row["type"] . '" data-trans_no="' . $row["trans_no"] . '">void</a>';

      return $content;
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

      return $value >= 0 ? Num::_priceFormat($value) : '';
    }
    /**
     * @param $row
     *
     * @return int|string
     */
    function formatCredit($row) {
      $value = -$row["amount"];

      return $value > 0 ? Num::_priceFormat($value) : '';
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
      $this->updateData = true;
      $this->Ajax->activate('summary');
    }
    // Update db record if respective checkbox value has changed.
    //
    /**
     * @param $deposit_id
     *
     * @return bool
     */
    function change_tpl_flag($deposit_id) {
      if (!$this->check_date() && Input::_hasPost("dep_" . $deposit_id)) // temporary fix
      {
        return false;
      }
      if (Input::_post('deposit_date') == $this->Dates->today()) {
        $_POST['deposit_date'] = Input::_post('date_' . $deposit_id);
      }
      // save last reconcilation status (date, end balance)
      if (Input::_hasPost("dep_" . $deposit_id)) {
        $_SESSION['undeposited']["dep_" . $deposit_id] = Input::_post('amount_' . $deposit_id);
        $_POST['deposited']                            = $_POST['to_deposit'] + Input::_post('amount_' . $deposit_id);
      } else {
        unset($_SESSION['undeposited']["dep_" . $deposit_id]);
        $_POST['deposited'] = $_POST['to_deposit'] - Input::_post('amount_' . $deposit_id);
      }
      if (!count($_SESSION['undeposited'])) {
        $_POST['deposit_date'] = $this->Dates->today();
      }

      return true;
    }
    protected function runValidation() {
      Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
    }
  }

  new UndepositedFunds();

