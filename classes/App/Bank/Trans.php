<?php
    use ADV\App\Bank\Bank;
    use ADV\Core\DB\DB;

    /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
    class Bank_Trans
    {
        // add a bank transaction
        // $amount is in $currency
        // $date_ is display date (non-sql)
        /**
         * @static
         *
         * @param        $type
         * @param        $trans_no
         * @param        $bank_act
         * @param        $ref
         * @param        $date_
         * @param        $amount
         * @param        $person_type_id
         * @param        $person_id
         * @param string $currency
         * @param string $err_msg
         * @param int    $rate
         */
        public static function add($type, $trans_no, $bank_act, $ref, $date_, $amount, $person_type_id, $person_id, $currency = "", $err_msg = "", $rate = 0)
        {
            $sqlDate = Dates::dateToSql($date_);
            // convert $amount to the bank's currency
            if ($currency != "") {
                $bank_account_currency = Bank_Currency::for_company($bank_act);
                if ($rate == 0) {
                    $to_bank_currency = Bank::get_exchange_rate_from_to($currency, $bank_account_currency, $date_);
                } else {
                    $to_bank_currency = 1 / $rate;
                }
                $amount_bank = ($amount / $to_bank_currency);
            } else {
                $amount_bank = $amount;
            }
            // Also store the rate to the home
            //$BankToHomeCurrencyRate = Bank_Currency::exchange_rate_to_home($bank_account_currency, $date_);
            $sql
                         = "INSERT INTO bank_trans (type, trans_no, bank_act, ref,
        trans_date, amount, person_type_id, person_id, undeposited) ";
            $undeposited = ($bank_act == 5 && $type == 12) ? 1 : 0;
            $sql .= "VALUES ($type, $trans_no, '$bank_act', " . DB::escape($ref) . ", '$sqlDate',
        " . DB::escape($amount_bank) . ", " . DB::escape($person_type_id) . ", " . DB::escape($person_id) . ", " . DB::escape($undeposited) . ")";
            if ($err_msg == "") {
                $err_msg = "The bank transaction could not be inserted";
            }
            DB::query($sql, $err_msg);
        }
        /**
         * @static
         *
         * @param $type
         * @param $type_no
         *
         * @return bool
         */
        public static function exists($type, $type_no)
        {
            $sql    = "SELECT trans_no FROM bank_trans WHERE type=" . DB::escape($type) . " AND trans_no=" . DB::escape($type_no);
            $result = DB::query($sql, "Cannot retreive a bank transaction");

            return (DB::numRows($result) > 0);
        }
        /**
         * @static
         *
         * @param      $type
         * @param null $trans_no
         * @param null $person_type_id
         * @param null $person_id
         *
         * @return null|PDOStatement
         */
        public static function get($type, $trans_no = null, $person_type_id = null, $person_id = null)
        {
            $sql
              = "SELECT *, bank_account_name, account_code, bank_curr_code
        FROM bank_trans, bank_accounts
        WHERE bank_accounts.id=bank_trans.bank_act ";
            if ($type != null) {
                $sql .= " AND type=" . DB::escape($type);
            }
            if ($trans_no != null) {
                $sql .= " AND bank_trans.trans_no = " . DB::escape($trans_no);
            }
            if ($person_type_id != null) {
                $sql .= " AND bank_trans.person_type_id = " . DB::escape($person_type_id);
            }
            if ($person_id != null) {
                $sql .= " AND bank_trans.person_id = " . DB::escape($person_id);
            }
            $sql .= " ORDER BY trans_date, bank_trans.id";

            return DB::query($sql, "query for bank transaction");
        }
        /**
         * @static
         *
         * @param      $type
         * @param      $type_no
         * @param bool $nested
         */
        public static function void($type, $type_no, $nested = false)
        {
            if (!$nested) {
                DB::begin();
            }
            $sql
              = "UPDATE bank_trans SET amount=0
        WHERE type=" . DB::escape($type) . " AND trans_no=" . DB::escape($type_no);
            DB::query($sql, "could not void bank transactions for type=$type and trans_no=$type_no");
            GL_Trans::void($type, $type_no, true);
            // in case it's a customer trans - probably better to check first
            Sales_Allocation::void($type, $type_no);
            Debtor_Trans::void($type, $type_no);
            // in case it's a supplier trans - probably better to check first
            Purch_Allocation::void($type, $type_no);
            Creditor_Trans::void($type, $type_no);
            GL_Trans::void_tax_details($type, $type_no);
            if (!$nested) {
                DB::commit();
            }
        }
        /**
         * @static
         *
         * @param $trans_no
         * @param $type
         * @param $from
         * @param $to
         *
         * @return bool
         */
        public static function changeBankAccount($trans_no, $type, $from, $to)
        {
            $fromgl = Bank_Account::get_gl($from);
            $togl   = Bank_Account::get_gl($to);
            if (!$togl || !$fromgl) {
                return false;
            }
            DB::begin();
            DB::update('bank_trans')->value('bank_act', $to)->where('type=', $type)->andWhere('trans_no=', $trans_no)->andwhere('bank_act=', $from)->exec();
            DB::update('gl_trans')->value('account', $togl)->where('type=', $type)->andWhere('type_no=', $trans_no)->andWhere('account=', $fromgl)->exec();
            DB::commit();
            return true;
        }
    }
