<?php
    use ADV\Core\Input\Input;
    use ADV\App\UI\UI;
    use ADV\App\Bank\Bank;
    use ADV\Core\Cell;
    use ADV\Core\Row;
    use ADV\Core\Table;
    use ADV\Core\DB\DB;

    /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
    /**
     * @property \ADV\Core\Input\Input Input
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
        protected $accountHasStatements = false;
        protected function before()
        {
            $this->Dates             = Dates::i();
            $this->Num               = Num::i();
            $_POST['bank_account']   = $this->Input->_postGlobal('bank_account', INPUT::NUMERIC, Bank_Account::get_default()['id']);
            $this->bank_account      = &$_POST['bank_account'];
            $_POST['bank_date']      = $this->Input->_postGlobal('bank_date', null, $this->Dates->_today());
            $this->bank_date         = &$_POST['bank_date'];
            $_POST['reconcile_date'] = $this->Input->_post('reconcile_date', null, $this->Dates->_sqlToDate($_POST['bank_date']));
            $this->reconcile_date    = &$_POST['reconcile_date'];
            $this->JS->_openWindow(950, 500);
            $this->JS->_footerFile('/js/libs/redips-drag-min.js');
            $this->JS->_footerFile('/js/reconcile.js');
            if ($this->Input->_post('reset')) {
                // GL_Account::reset_sql_for_reconcile($this->bank_account, $this->reconcile_date);
                $this->updateData();
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
                $this->begin_date = null;
                $this->end_date   = $this->Dates->_today();
            }
            $id = Forms::findPostPrefix('_rec_');
            if ($id != -1) {
                $this->updateCheckbox($id);
            }
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
            echo "<hr><div id='drag'>";

            echo $this->render();
            echo '</div>';
            Forms::end();
            if (!$this->Ajax->_inAjax() || AJAX_REFERRER) {
                $this->addDialogs();
            }
            //JS::addLive("Adv.Reconcile.setUpGrid();");
            Page::end();
        }
        protected function addDialogs()
        {
            $date_dialog  = new View('ui/date_dialog');
            $date_changer = new \ADV\Core\Dialog('Change Date', 'dateChanger', $date_dialog->render(true), ['resizeable'=> false, 'modal'=> true]);
            $date_changer->addButton('Save', 'Adv.Reconcile.changeDate(this)');
            $date_changer->addButton('Cancel', '$(this).dialog("close")');
            $date_changer->setTemplateData(['id'=> '', 'date'=> $this->begin_date]);
            $date_changer->show();
            $bank_accounts = Bank_Account::getAll();
            $bank_dialog   = new View('ui/bank_dialog');
            $bank_dialog->set('bank_search', UI::select('changeBank', $bank_accounts, [], null, true));
            $bank_dialog->render();
        }
        /**
         * @return string
         */
        protected function render()
        {
            ob_start();
            $this->accountHasStatements ? $this->statementLayout() : $this->simpleLayout();
            return '<div id="newgrid">' . ob_get_clean() . '</div>';
        }
        /**
         * @return bool
         */
        protected function simpleLayout()
        {
            $sql = GL_Account::get_sql_for_reconcile($this->bank_account, $this->reconcile_date);
            $act = Bank_Account::get($_POST["bank_account"]);
            Display::heading($act['bank_account_name'] . " - " . $act['bank_curr_code']);
            $cols               = array(
                _("Type")        => array('fun' => array($this, 'formatType'), 'ord' => ''), //
                _("#")           => array('fun' => array($this, 'formatTrans'), 'ord' => ''), //
                _("Reference")   => array('fun'=> [$this, 'formatReference']), //
                _("Date")        => array('type'=> 'date', 'ord' => ''), //
                _("Debit")       => array('align' => 'right', 'fun' => array($this, 'formatDebit'), 'ord' => ''), //
                _("Credit")      => array('align' => 'right', 'insert' => true, 'fun' => array($this, 'formatCredit'), 'ord' => ''), //
                _("Person/Item") => array('fun' => array($this, 'formatInfo')), //
                array('insert' => true, 'fun' => array($this, 'formatGL')), //
                "X"              => array('insert' => true, 'fun' => array($this, 'formatCheckbox')), //
                ['insert'=> true, 'fun'=> array($this, 'formatDropdown')], ////
            );
            $table              = DB_Pager::new_db_pager('bank_rec', $sql, $cols);
            $table->width       = "80";
            $table->rowFunction = [$this, 'formatRow'];
            $table->display($table);
            return true;
        }
        /**
         * @return bool
         */
        protected function statementLayout()
        {
            $rec             = Bank_Trans::getPeriod($this->bank_account, $this->begin_date, $this->end_date);
            $statement_trans = Bank_Account::getStatement($this->bank_account, $this->begin_date, $this->end_date);
            if (!$statement_trans) {
                return $this->simpleLayout();
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
                if ($v['reconciled_to_id']) {
                    foreach ($rec as $p=> $q) {
                        if ($q['id'] == $v['reconciled_to_id']) {
                            $matched = $rec[$p] + $v;
                            unset($rec[$p]);
                            $known_trans[] = $matched;
                            continue 2;
                        }
                    }
                }
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
            $cols               = [
                'Type'      => ['fun'=> array($this, 'formatType')], //
                '#'         => ['align'=> 'center', 'fun'=> array($this, 'formatTrans')], //
                ['type'=> 'skip'], //
                'Date'      => ['type'=> 'date'], //
                'Debit'     => ['align'=> 'right', 'fun'=> array($this, 'formatDebit')], //
                'Credit'    => ['align'=> 'right', 'insert'=> true, 'fun'=> array($this, 'formatCredit')], //
                'Info'      => ['class'=> 'mark', 'fun'=> array($this, 'formatInfo')], //
                'GL'        => ['fun'=> array($this, 'formatGL')], //
                ['fun'=> array($this, 'formatCheckbox')], //
                'Banked'    => ['type'=> 'date'], //
                'Amount'    => ['align'=> 'right', 'class'=> 'bold'], //
                'Memo'      => ['class'=> 'state_memo'], //
                ['fun'=> array($this, 'formatDropdown')], //
            ];
            $table              = DB_Pager::new_db_pager('bank_rec', $known_trans, $cols);
            $table->class       = 'recgrid';
            $table->rowFunction = [$this, 'formatRow'];
            $table->display();
            return true;
        }
        protected function displaySummary()
        {
            $this->getTotal();
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
        /**
         * @return int
         */
        protected function getTotal()
        {
            if ($this->accountHasStatements) {
                list($beg_balance, $end_balance) = Bank_Account::getBalances($this->bank_account, $this->begin_date, $this->end_date);
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
            return;
        }
        protected function changeDate()
        {
            $bank_trans_id = Input::post('trans_id', Input::NUMERIC, -1);
            $newdate       = Input::post('date');
            Bank_Trans::changeDate($bank_trans_id, $newdate, $status);
            $data['status'] = $status->get();
            $data['grid']   = $this->render();
            JS::renderJSON($data);
        }
        /**
         * @param $row
         *
         * @return string
         */
        protected function changeBank()
        {
            $newbank  = Input::post('newbank', Input::NUMERIC);
            $trans_no = Input::post('trans_no', Input::NUMERIC);
            $type     = Input::post('type', Input::NUMERIC);
            if ($newbank && $type && $trans_no) {
                Bank_Trans::changeBankAccount($trans_no, $type, $this->bank_account, $newbank);
            }
            $data['grid'] = $this->render();
            JS::renderJSON($data);
        }
        /**
         * @return bool
         */
        public function checkDate()
        {
            if (!$this->Dates->_isDate($this->reconcile_date)) {
                Event::error(_("Invalid reconcile date format"));
                $this->JS->_setFocus('reconcile_date');
                return false;
            }
            return true;
        }
        /**
         * @param $reconcile_id
         *
         * @return bool
         */
        public function updateCheckbox($reconcile_id)
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
            GL_Account::update_reconciled_values(
                $reconcile_id,
                $reconcile_value,
                $this->reconcile_date,
                Validation::input_num('end_balance'),
                $this->bank_account,
                Input::post('state_' . $reconcile_id, Input::NUMERIC, -1)
            );
            $this->Ajax->_activate('_page_body');
            $this->JS->_setFocus($reconcile_id);
            return true;
        }
        protected function unGroup()
        {
            $groupid = Input::post('groupid', Input::NUMERIC);
            if ($groupid > 0) {
                Bank_Undeposited::ungroup($groupid);
                $this->updateData();
            }
            $this->updateData();
            $data['grid'] = $this->render();
            JS::renderJSON($data);
        }
        public function updateData()
        {
            DB_Pager::kill('bank_rec');
            unset($_POST["beg_balance"], $_POST["end_balance"]);
            $this->Ajax->_activate('_page_body');
        }
        /**
         * @return mixed
         */
        protected function deposit()
        {
            $trans1 = Input::post('trans1', INPUT::NUMERIC);
            $trans2 = Input::post('trans2', INPUT::NUMERIC);
            Bank_Undeposited::addToGroup($trans1, $this->bank_account, $trans2);

            $data['grid'] = $this->render();
            JS::renderJSON($data);
        }
        /**
         * @param $row
         *
         * @return string
         */
        public function formatCheckbox($row)
        {
            if (!$row['amount']) {
                return '';
            }
            $name     = "rec_" . $row['id'];
            $state_id = $row['state_id'];
            $hidden   = 'last[' . $row['id'] . ']';
            $value    = $row['reconciled'] != '';
            return Forms::checkbox(null, $name, $value, true, _('Reconcile this transaction')) . Forms::hidden($hidden, $value, false) . Forms::hidden(
                'state_' . $row['id'],
                $state_id,
                false
            );
        }
        /**
         * @param $row
         *
         * @return string
         */
        public function formatRow($row)
        {
            if (!$row['trans_date'] && !$row['reconciled'] && $row['state_date']) {
                $class  = "class='overduebg deny mark'";
                $amount = e($row['state_amount']);
                $date   = e($this->Dates->_sqlToDate($row['state_date']));

                return "<tr  $class  data-date='$date' data-amount='$amount'> ";
            }
            $name     = $row['id'];
            $amount   = $row['amount'];
            $date     = $this->Dates->_sqlToDate($row['trans_date']);
            $type     = $row['type'];
            $trans_no = $row['trans_no'];
            $class    = "class='cangroup'";
            if ($row['reconciled'] && $row['state_date']) {
                return "<tr class='done deny'>";
            } elseif (!isset($row['state_date'])) {
                $class = "class='cangroup'";
            } elseif (($row['trans_date'] && $row['reconciled'] && !$row['state_date']) || ($row['state_date'] && !$row['transdate'])
            ) {
                $class = "class='cangroup overduebg'";
            }
            // save also in hidden field for testing during 'Reconcile'
            return "<tr  $class data-id='$name' data-date='$date' data-type='$type' data-transno='$trans_no' data-amount='$amount'> ";
        }
        /**
         * @param $row
         *
         * @internal param $dummy
         * @internal param $type
         * @return mixed
         */
        public function formatType($row)
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
        public function formatTrans($row)
        {
            $content = '';
            if ($row['type'] != ST_GROUPDEPOSIT) {
                $content = GL_UI::viewTrans($row["type"], $row["trans_no"]);
            }
            return $content;
        }
        /**
         * @param $row
         *
         * @return string
         */
        public function formatDropdown($row)
        {
            if ($row['reconciled']) {
                return '';
            }
            $dropdown = new View('ui/dropdown');

            if (!$row['id'] && $row['state_amount']) {
                if ($row['state_amount'] > 0) {
                    if (stripos($row['memo'], 'AMEX')) {
                        preg_match('/([0-9]+\.[0-9]+)/', $row['memo'], $beforefee);
                        $fee     = $beforefee[1] - $row['state_amount'];
                        $items[] = ['class'=> 'createDP', 'label'=> 'Debtor Payment', 'href'=> '/sales/customer_payments', 'data'=> ['fee'=> $fee, 'amount'=> $beforefee[1]]];
                    } else {
                        $items[] = ['class'=> 'createDP', 'label'=> 'Debtor Payment', 'href'=> '/sales/customer_payments'];
                    }
                    $items[] = ['class'=> 'createBD', 'label'=> 'Bank Deposit', 'href'=> '/gl/gl_bank?NewDeposit=Yes'];
                } else {
                    $items[] = ['class'=> 'createCP', 'label'=> 'Creditor Payment', 'href'=> '/purchases/supplier_payment'];
                    $items[] = ['class'=> 'createBP', 'label'=> 'Bank Payment', 'href'=> '/gl/gl_bank?NewPayment=Yes'];
                }
                $items[] = ['class'=> 'createFT', 'label'=> 'Funds Transfer', 'href'=> '/gl/bank_transfer'];

                $title = 'Create';
            } else {
                $items[] = ['class'=> 'changeDate', 'label'=> 'Change Date'];
                switch ($row['type']) {
                    case ST_GROUPDEPOSIT:
                        $items[] = ['class'=> 'unGroup', 'label'=> 'Ungroup'];
                        break;
                    case ST_BANKDEPOSIT:
                    case ST_CUSTPAYMENT:
                    default:
                        $items[] = ['class'=> 'changeBank', 'label'=> 'Move Bank'];
                        $items[] = ['class'=> 'voidTrans', 'label'=> 'Void Trans', 'data'=> ['type'=> $row['type'], 'trans_no'=> $row['trans_no']]];
                }

                $title = ($row['type'] == ST_GROUPDEPOSIT) ? 'Group' : substr($row['ref'], 0, 7);
            }
            $menus[] = ['title'=> $title, 'items'=> $items];
            $dropdown->set('menus', $menus);
            return $dropdown->render(true);
        }
        /**
         * @param $row
         *
         * @return string
         */
        public function formatGL($row)
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
        public function formatDebit($row)
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
        public function formatCredit($row)
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
        public function formatInfo($row)
        {
            $content = '';
            if ($row['type'] == ST_BANKTRANSFER) {
                $content = DB_Comments::get_string(ST_BANKTRANSFER, $row['trans_no']);
            } elseif ($row['type'] == ST_GROUPDEPOSIT) {
                $result = Bank_Trans::getGroupDeposit($this->bank_account, $row['id']);
                foreach ($result as $trans) {
                    $name = Bank::payment_person_name($trans["person_type_id"], $trans["person_id"], true, $trans["trans_no"]);
                    $content .= $trans['ref'] . ' <span class="u">' . $name . ' ($' . $this->Num->_priceFormat($trans['amount']) . ')</span>: ' . $trans['memo_'] . '<br>';
                }
            } else {
                $content = Bank::payment_person_name($row["person_type_id"], $row["person_id"], true, $row["trans_no"]);
            }
            if (!$row['reconciled'] && $row['trans_no']) {
                return '<div class="drag row">' . $content . '</div>';
            }
            return '<div class="deny row">' . $content . '</div>';
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
    }

    new Reconcile();
