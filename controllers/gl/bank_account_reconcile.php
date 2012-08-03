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
        /** @var Num Num*/
        protected $Num;
        /** @var Dates Dates*/
        protected $Dates;
        protected $bank_account;
        protected $bank_date;
        protected $reconcile_date;
        protected $begin_date;
        protected $end_date;
        public $accountHasStatements = false;
        protected function before()
        {
            $this->Dates             = Dates::i();
            $this->Num               = Num::i();
            $_POST['bank_account']   = Input::postGlobal('bank_account', INPUT::NUMERIC, Bank_Account::get_default()['id']);
            $this->bank_account      = &$_POST['bank_account'];
            $_POST['bank_date']      = Input::postGlobal('bank_date', null, $this->Dates->_today());
            $this->bank_date         = &$_POST['bank_date'];
            $_POST['reconcile_date'] = $this->Input->_post('reconcile_date', null, $this->Dates->_sqlToDate($_POST['bank_date']));
            $this->reconcile_date    = &$_POST['reconcile_date'];
            $this->JS->_openWindow(950, 500);
            $this->JS->_footerFile('/js/reconcile.js');
            if ($this->Input->_post('reset')) {
                // GL_Account::reset_sql_for_reconcile($this->bank_account, $this->reconcile_date);
                $this->updateData();
            }
            $groupid = Forms::findPostPrefix("_ungroup_");
            if ($groupid > 1) {
                $this->ungroup($groupid);
            }
            $undepositid = Forms::findPostPrefix("_undeposit_");
            if ($undepositid > 1) {
                $this->undeposit($undepositid);
            }
            if (Forms::isListUpdated('bank_account')) {
                $this->Session->_setGlobal('bank_account', $this->bank_account);
                $this->Ajax->_activate('bank_date');
                $this->updateData();
            }
            $this->accountHasStatements = Bank_Account::hasStatements($this->bank_account);
            if (Forms::isListUpdated('bank_date')) {
                $this->reconcile_date = $this->Dates->_sqlToDate($this->bank_date);
                $this->Session->_setGlobal('bank_date', $this->bank_date);
                $this->Ajax->_activate('bank_date');
                $this->updateData();
            }
            if ($this->Input->_post('_reconcile_date_changed')) {
                $this->bank_date = $this->Dates->_dateToSql($this->reconcile_date);
                $this->Ajax->_activate('bank_date');
                $this->updateData();
            }
            if ($this->accountHasStatements && $this->bank_date) {
                $this->begin_date = $this->Dates->_dateToSql($this->Dates->_beginMonth($this->bank_date));
                $this->end_date   = $this->Dates->_dateToSql($this->Dates->_endMonth($this->bank_date));
            } elseif ($this->accountHasStatements) {
                $this->begin_date = "(SELECT max(reconciled) from bank_trans)";
                $this->end_date   = $this->Dates->_today();
            }
            $id = Forms::findPostPrefix('_rec_');
            if ($id != -1) {
                $this->change_tpl_flag($id);
            }
        }
        /**
         * @param $groupid
         */
        protected function ungroup($groupid)
        {
            $group_refs = $_POST['ungroup_' . $groupid];
            $sql        = "UPDATE bank_trans SET undeposited=1, reconciled=null WHERE undeposited =" . $this->DB->_escape($groupid);
            $this->DB->_query($sql, "Couldn't ungroup group deposit");
            $sql = "UPDATE bank_trans SET ref=" . $this->DB->_quote('Removed group: ' . $group_refs) . ", amount=0, reconciled='" . $this->Dates->_today(
            ) . "', undeposited=" . $groupid . " WHERE id=" . $groupid;
            $this->DB->_query($sql, "Couldn't update removed group deposit data");
            $this->updateData();
        }
        /**
         * @param $deposit
         */
        protected function undeposit($deposit)
        {
            $deposit_id = $_POST['undeposit_' . $deposit];
            $sql        = "UPDATE bank_trans SET undeposited=1, reconciled=null WHERE id=" . $deposit_id;
            DB::query($sql, "Can't change undeposited status");
            $this->updateData();
        }
        protected function changeDate()
        {
            $date   = Dates::isDate($_POST['date']);
            $data   = ['newdate'=> null];
            $status = new \ADV\Core\Status();
            if (!$date) {
                $status->set(\ADV\Core\Status::ERROR, 'chnage date', 'Incorrect date format');
            } else {
                $sqldate        = $this->Dates->_dateToSql($date);
                $row            = DB::select('*')->from('bank_trans')->where(
                    'id=',
                    Input::post(
                        'id',
                        Input::NUMERIC,
                        -1
                    )
                )->fetch()->one();
                $data['result'] = $row;
                switch ($row['type']) {
                    case ST_CUSTPAYMENT:
                    case ST_BANKDEPOSIT:
                        {
                        $result = DB::update('debtor_trans')->value('tran_date', $sqldate)->where('trans_no=', $row['trans_no'])->andWhere('type=', $row['type'])->exec();
                        }
                        break;
                    default:
                        {
                        $result = $status->set(\ADV\Core\Status::ERROR, 'chnage date', 'Cannot change date for this transaction');
                        }
                }
                if ($result) {
                    DB::update('bank_trans')->value('trans_date', $sqldate)->where('id=', $row['id'])->exec();
                    DB::update('gl_trans')->value('tran_date', $sqldate)->where('type_no=', $row['trans_no'])->andWhere('type=', $row['type'])->exec();
                    $data = ['newdate'=> $date];
                    $status->set(\ADV\Core\Status::SUCCESS, 'chnage date', 'Successfully change date');
                }
            }
            $data['status'] = $status->get();
            JS::renderJSON($data);
        }
        protected function index()
        {
            $this->runAction();
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
            $this->accountHasStatements ? $this->newWay() : $this->oldWay();
            Forms::end();
            if (!$this->Ajax->_inAjax() || AJAX_REFERRER) {
                $date_dialog  = new View('ui/date_dialog');
                $date_changer = new \ADV\Core\Dialog('Change Date', 'dateChanger', $date_dialog->render(true), ['resizeable'=> false, 'modal'=> true]);
                $date_changer->addButton('Save', 'Adv.Reconcile.changeDate(this)');
                $date_changer->addButton('Cancel', '$(this).dialog("close")');
                $date_changer->setTemplateData(['id'=> '', 'date'=> $this->begin_date]);
                $date_changer->show();
            }
            Page::end();
        }
        /**
         * @return bool
         */
        protected function newWay()
        {
            $sql
              = "SELECT bt.type, bt.trans_no, bt.ref, bt.trans_date,bt.id, IF( bt.trans_no IS null, SUM( g.amount ), bt.amount ) AS amount
 , bt.person_id, bt.person_type_id , bt.reconciled FROM bank_trans bt LEFT OUTER JOIN bank_trans g ON g.undeposited = bt.id
 WHERE bt.bank_act = " . $this->DB->_quote($this->bank_account) . "
 AND bt.trans_date <= '" . ($this->bank_date ? : $this->Dates->_today()) . "'
 AND bt.undeposited=0
 AND (bt.reconciled IS null";
            if ($this->bank_date) {
                $sql .= " OR bt.reconciled='" . $this->bank_date . "'";
            }
            $sql .= ") AND bt.amount!=0 GROUP BY bt.id ORDER BY IF(bt.trans_date>='" . $this->begin_date . "' AND bt.trans_date<='" . $this->end_date . "',1,0) , bt.reconciled DESC ,bt.trans_date , amount ";
            $this->DB->_query($sql);
            $rec = $this->DB->_fetchAll();
            $sql = "SELECT date as state_date, amount as state_amount,memo FROM temprec WHERE date >= '" . $this->begin_date . "' AND date <='" . $this->end_date . "' and bank_account_id=" . $this->DB->_quote(
                $this->bank_account
            ) . " ORDER BY date ,amount";
            $this->DB->_query($sql);
            $statement_trans = $this->DB->_fetchAll();
            if (!$statement_trans) {
                return $this->oldWay();
            }
            $known_trans                 = [];
            $known_headers               = [
                'type',
                'trans_no',
                'ref',
                'trans_date',
                'id',
                'amount',
                'person_id',
                'person_type_id',
                'reconciled'
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
            $cols  = [
                'Type'      => ['fun'=> array($this, 'sysTypeName')], //
                '#'         => ['align'=> 'center', 'fun'=> array($this, 'viewTrans')], //
                'Ref'       => ['fun'=> 'formatReference'], //
                'Date'      => ['align'=> 'center', 'fun'=> [$this, 'formatDate']], //
                'Debit'     => ['align'=> 'right', 'fun'=> array($this, 'formatDebit')], //
                'Credit'    => ['align'=> 'right', 'insert'=> true, 'fun'=> array($this, 'formatCredit')], //
                'Info'      => ['fun'=> array($this, 'formatInfo')], //
                'GL'        => ['fun'=> array($this, 'viewGl')], //
                ['fun'=> array($this, 'reconcileCheckbox')], //
                'Bank Date' => ['type'=> 'date'], //
                'Amount'    => ['align'=> 'right', 'class'=> 'bold'], //
                'Info'
            ];
            $table = DB_Pager::new_db_pager('bank_rec', $known_trans, $cols);

            $table->rowFunction = [$this, 'rowData'];
            $table->display();
            return true;
        }
        protected function displaySummary()
        {
            $this->getTotal();
            echo "<hr>";
            Display::div_start('summary');
            Table::start();
            Table::sectionTitle(_("Reconcile Date"), 1);
            Row::start();
            Forms::dateCells("", "reconcile_date", _('Date of bank statement to reconcile'), $this->bank_date == '', 0, 0, 0, null, true);
            Row::end();
            Table::sectionTitle(_("Beginning Balance"), 1);
            Row::start();
            Forms::amountCellsEx("", "beg_balance", 15);
            Row::end();
            Table::sectionTitle(_("Ending Balance"), 1);
            Row::start();
            Forms::amountCellsEx("", "end_balance", 15);
            $reconciled = Validation::input_num('reconciled');
            $difference = Validation::input_num("end_balance") - Validation::input_num("beg_balance") - $reconciled;
            Row::end();
            Table::sectionTitle(_("Reconciled Amount"), 1);
            Row::start();
            Cell::amount($reconciled, false, '', "reconciled");
            Row::end();
            Table::sectionTitle(_("Difference"), 1);
            Row::start();
            Cell::amount($difference, false, '', "difference");
            Row::end();
            Table::end();
            Display::div_end();
        }
        public function formatDate($row)
        {
            $id   = $row['id'];
            $date = $this->Dates->_sqlToDate($row['trans_date']);
            if ($row['reconciled'] || !$row['trans_date']) {
                return $date;
            }
            return '<span data-id="' . $id . '" class="date">' . $date . '</span>';
        }
        /**
         * @return int
         */
        protected function getTotal()
        {
            if ($this->accountHasStatements) {
                $sql                  = "(select (rb - amount) as amount from temprec where date>='" . $this->begin_date . "' and date<='" . $this->end_date . "' and bank_account_id=" . $this->DB->_quote(
                    $this->bank_account
                ) . " order by date, id desc limit 0,
        1) union (select rb as amount from temprec where date>='" . $this->begin_date . "' and date<='" . $this->end_date . "' and bank_account_id=" . $this->DB->_quote(
                    $this->bank_account
                ) . " order by date desc, id asc limit 0,1)";
                $result               = $this->DB->_query($sql);
                $beg_balance          = $this->DB->_fetch($result)['amount'];
                $end_balance          = $this->DB->_fetch($result)['amount'];
                $_POST["beg_balance"] = $this->Num->_priceFormat($beg_balance);
                $_POST["end_balance"] = $this->Num->_priceFormat($end_balance);
                $_POST["reconciled"]  = $this->Num->_priceFormat($end_balance - $beg_balance);
            }
            $result = GL_Account::get_max_reconciled($this->reconcile_date, $this->bank_account);
            if ($row = $this->DB->_fetch($result)) {
                $_POST["reconciled"] = $this->Num->_priceFormat($row["end_balance"] - $row["beg_balance"]);
                if (!isset($_POST["beg_balance"])) { // new selected account/statement
                    $_POST["last_date"]   = $this->Dates->_sqlToDate($row["last_date"]);
                    $_POST["beg_balance"] = $this->Num->_priceFormat($row["beg_balance"]);
                    $_POST["end_balance"] = $this->Num->_priceFormat($row["end_balance"]);
                    if ($this->bank_date) {
                        // if it is the last updated bank statement retrieve ending balance
                        $row = GL_Account::get_ending_reconciled($this->bank_account, $this->bank_date);
                        if ($row) {
                            $_POST["end_balance"] = $this->Num->_priceFormat($row["ending_reconcile_balance"]);
                        }
                    }
                }
            }
        }
        /**
         * @return bool
         */
        function checkDate()
        {
            if (!$this->Dates->_isDate($this->reconcile_date)) {
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
        function reconcileCheckbox($row)
        {
            if (!$row['amount']) {
                return '';
            }
            $hidden = 'last[' . $row['id'] . ']';
            $value  = $row['reconciled'] != '';
            $name   = $row['id'];

            return Forms::checkbox(null, $name, $value, true, _('Reconcile this transaction')) . Forms::hidden(
                $hidden,
                $value,
                false
            );
        }
        public function rowData($row)
        {
            $name   = $row['id'];
            $amount = $row['amount'];
            $date   = $this->Dates->_sqlToDate($row['trans_date']);

            $class = "class='cangroup'";
            if ($row['reconciled']) {
                return "<tr class='done'>";
            } elseif ($row['reconciled']) {
                return "<tr class='done'>";
            } elseif (!$row['trans_date'] && !$row['reconciled'] && $row['state_date']) {
                $class = "class='overduebg'";
            } elseif (($row['trans_date'] && $row['reconciled'] && !$row['state_date']) || ($row['state_date'] && !$row['transdate'])
            ) {
                $class = "class='cangroup overduebg'";
            }
            // save also in hidden field for testing during 'Reconcile'
            return "<tr  $class data-id='$name' title='Deposit this transaction' data-date='$date' data-amount='$amount'> ";
        }
        /**
         * @param $row
         *
         * @return string
         */
        function ungroupButton($row)
        {
            if (!$row['reconciled'] && $row['type'] == ST_GROUPDEPOSIT) {
                return "<button value='" . $row['id'] . '\' onclick="JsHttpRequest.request(\'_ungroup_' . $row['id'] . '\',
      this.form)" name="_ungroup_' . $row['id'] . '" type="submit" title="Ungroup"
     class="ajaxsubmit">Ungroup</button>' . Forms::hidden("ungroup_" . $row['id'], $row['ref'], true);
            }
        }
        /**
         * @param $row
         *
         * @return string
         */
        function undepositButton($row)
        {
            if (!$row['reconciled'] && in_array($row['type'], [ST_BANKDEPOSIT, ST_CUSTPAYMENT])) {
                return "<button value='" . $row['id'] . '\' onclick="JsHttpRequest.request(\'_undeposit_' . $row['id'] . '\',
 this.form)" name="_undeposit_' . $row['id'] . '" type="submit" title="Undeposit"
 class="ajaxsubmit">Undeposit</button>' . Forms::hidden("undeposit_" . $row['id'], $row['id'], false);
            }
        }
        /**
         * @param $row
         *
         * @internal param $dummy
         * @internal param $type
         * @return mixed
         */
        function sysTypeName($row)
        {
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
        function viewTrans($row)
        {
            $content = "<div class='center'>";
            if (!$row['type']) {
                return '';
            } elseif ($row['type'] == ST_GROUPDEPOSIT) {
                return $content . $this->ungroupButton($row) . '</div>';
            }
            $content .= GL_UI::viewTrans($row["type"], $row["trans_no"]);
            if (!$row['reconciled']) {
                $content .= '<br><a class="button voidlink" data-type="' . $row["type"] . '" data-trans_no="' . $row["trans_no"] . '">void</a><br>';
                $content .= $this->undepositButton($row);
            }
            return $content . '</div>';
        }
        /**
         * @param $row
         *
         * @return string
         */
        function viewGl($row)
        {
            if (!$row['amount']) {
                return '';
            }
            return ($row['type'] != ST_GROUPDEPOSIT) ? GL_UI::view($row["type"], $row["trans_no"]) : '';
        }
        /**
         * @param $row
         *
         * @return int|string
         */
        function formatDebit($row)
        {
            $value = $row["amount"];
            if ($value > 0) {
                return '<span class="bold">' . $this->Num->_priceFormat($value) . '</span>';
            }
            return '';
        }
        /**
         * @param $row
         *
         * @return int|string
         */
        function formatCredit($row)
        {
            $value = -$row["amount"];
            if ($value <= 0) {
                return '';
            }
            return '<span class="bold">' . $this->Num->_priceFormat($value) . '</span>';
        }
        /**
         * @param $row
         *
         * @return string
         */
        function formatInfo($row)
        {
            if ($row['type'] == ST_BANKTRANSFER) {
                return DB_Comments::get_string(ST_BANKTRANSFER, $row['trans_no']);
            } elseif ($row['type'] == ST_GROUPDEPOSIT) {
                $sql
                         = "SELECT bank_trans.ref,bank_trans.person_type_id,bank_trans.trans_no,bank_trans.person_id,bank_trans.amount,
 			comments.memo_ FROM bank_trans LEFT JOIN comments ON (bank_trans.type=comments.type AND bank_trans.trans_no=comments.id)
 			WHERE bank_trans.bank_act='" . $this->bank_account . "' AND bank_trans.type != " . ST_GROUPDEPOSIT . " AND bank_trans.undeposited>0 AND (undeposited = " . $row['id'] . ")";
                $result  = $this->DB->_query($sql, 'Couldn\'t get deposit references');
                $content = '';
                foreach ($result as $trans) {
                    $name = Bank::payment_person_name($trans["person_type_id"], $trans["person_id"], true, $trans["trans_no"]);
                    $content .= $trans['ref'] . ' <span class="u">' . $name . ' ($' . $this->Num->_priceFormat($trans['amount']) . ')</span>: ' . $trans['memo_'] . '<br>';
                }
                return $content;
            }
            return Bank::payment_person_name($row["person_type_id"], $row["person_id"], true, $row["trans_no"]);
        }
        function updateData()
        {
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
        function change_tpl_flag($reconcile_id)
        {
            if (!$this->checkDate() && Input::hasPost("rec_" . $reconcile_id)) // temporary fix
            {
                return false;
            }
            if ($this->bank_date == '') // new reconciliation
            {
                $this->Ajax->_activate('bank_date');
            }
            $reconcile_value = Input::hasPost("rec_" . $reconcile_id) ? ("'" . $this->Dates->_dateToSql($this->reconcile_date) . "'") : 'null';
            GL_Account::update_reconciled_values($reconcile_id, $reconcile_value, $this->reconcile_date, Validation::input_num('end_balance'), $this->bank_account);
            $this->Ajax->_activate('_page_body');
            $this->JS->_setFocus($reconcile_id);
            return true;
        }
        /**
         * @internal param $prefix
         * @return bool|mixed
         */
        protected function runValidation()
        {
            Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
        }
        /**
         * @param $a
         * @param $b
         *
         * @return int
         */
        public function sortByOrder($a, $b)
        {
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
        public function formatReference($row)
        {
            return substr($row['ref'], 0, 7);
        }
        /**
         * @return bool
         */
        private function oldWay()
        {
            $sql = GL_Account::get_sql_for_reconcile($this->bank_account, $this->reconcile_date);
            $act = Bank_Account::get($_POST["bank_account"]);
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
            $table        = DB_Pager::new_db_pager('trans_tbl', $sql, $cols);
            $table->width = "80";
            $table->display($table);
            return true;
        }
        /**
         * @return mixed
         */
        protected function deposit()
        {
            $togroup = Input::post('toDeposit');
            if (count($togroup) > 1) {
                $total = 0;
                foreach ($togroup as $trans) {
                    $total += $trans['amount'];
                }
                $sql        = "INSERT INTO bank_trans (type, bank_act, amount, ref, trans_date, person_type_id, person_id, undeposited) VALUES (" . ST_GROUPDEPOSIT . ", 5, $total," . $this->DB->_quote(
                    'Group Deposit'
                ) . ",'" . $this->Dates->_dateToSql($_POST['deposit_date']) . "', 6, '" . User::i()->user . "',0)";
                $query      = $this->DB->_query($sql, "Undeposited Cannot be Added");
                $deposit_id = $this->DB->_insertId($query);
                foreach ($togroup as $trans) {
                    $sql = "UPDATE bank_trans SET undeposited=" . $deposit_id . " WHERE id=" . $this->DB->_escape($trans['id']);
                    $this->DB->_query($sql, "Can't change undeposited status");
                }
            } else {
                $trans = reset($togroup);
                $sql   = "UPDATE bank_trans SET undeposited=0, trans_date='" . $this->Dates->_dateToSql($_POST['deposit_date']) . "',deposit_date='" . $this->Dates->_dateToSql(
                    $_POST['deposit_date']
                ) . "' WHERE id=" . $this->DB->_escape($trans['id']);
                $this->DB->_query($sql, "Can't change undeposited status");
            }
        }
    }

    new Reconcile();
