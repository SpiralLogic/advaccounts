<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class GL_Account {
    /**
     * @static
     *
     * @param $account_code
     * @param $account_name
     * @param $account_type
     * @param $account_code2
     *
     * @return null|PDOStatement
     */
    static public function add($account_code, $account_name, $account_type, $account_code2) {
      $sql
        = "INSERT INTO chart_master (account_code, account_code2, account_name, account_type)
		VALUES (" . DB::escape($account_code) . ", " . DB::escape($account_code2) . ", "
        . DB::escape($account_name) . ", " . DB::escape($account_type) . ")";
      return DB::query($sql);
    }
    /**
     * @static
     *
     * @param $account_code
     * @param $account_name
     * @param $account_type
     * @param $account_code2
     *
     * @return null|PDOStatement
     */
    static public function update($account_code, $account_name, $account_type, $account_code2) {
      $sql = "UPDATE chart_master SET account_name=" . DB::escape($account_name)
        . ",account_type=" . DB::escape($account_type) . ", account_code2=" . DB::escape($account_code2)
        . " WHERE account_code = " . DB::escape($account_code);
      return DB::query($sql);
    }
    /**
     * @static
     *
     * @param $code
     */
    static public function delete($code) {
      $sql = "DELETE FROM chart_master WHERE account_code=" . DB::escape($code);
      DB::query($sql, "could not delete gl account");
    }
    /**
     * @static
     *
     * @param null $from
     * @param null $to
     * @param null $type
     *
     * @return null|PDOStatement
     */
    static public function get_all($from = NULL, $to = NULL, $type = NULL) {
      $sql
        = "SELECT chart_master.*,chart_types.name AS AccountTypeName
				FROM chart_master,chart_types
				WHERE chart_master.account_type=chart_types.id";
      if ($from != NULL) {
        $sql .= " AND chart_master.account_code >= " . DB::escape($from);
      }
      if ($to != NULL) {
        $sql .= " AND chart_master.account_code <= " . DB::escape($to);
      }
      if ($type != NULL) {
        $sql .= " AND account_type=" . DB::escape($type);
      }
      $sql .= " ORDER BY account_code";
      return DB::query($sql, "could not get gl accounts");
    }
    /**
     * @static
     *
     * @param $code
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get($code) {
      $sql = "SELECT * FROM chart_master WHERE account_code=" . DB::escape($code);
      $result = DB::query($sql, "could not get gl account");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $reconcile_id
     * @param $reconcile_value
     * @param $reconcile_date
     * @param $end_balance
     * @param $bank_account
     */
    static public function update_reconciled_values($reconcile_id, $reconcile_value, $reconcile_date, $end_balance, $bank_account) {
      $sql = "UPDATE bank_trans SET reconciled=$reconcile_value"
        . " WHERE id=" . DB::escape($reconcile_id);
      DB::query($sql, "Can't change reconciliation status");
      // save last reconcilation status (date, end balance)
      $sql2 = "UPDATE bank_accounts SET last_reconciled_date='"
        . Dates::date2sql($reconcile_date) . "',
 	 ending_reconcile_balance=$end_balance
			WHERE id=" . DB::escape($bank_account);
      DB::query($sql2, "Error updating reconciliation information");
    }
    /**
     * @static
     *
     * @param $date
     * @param $bank_account
     *
     * @return null|PDOStatement
     */
    static public function get_max_reconciled($date, $bank_account) {
      $date = Dates::date2sql($date);
      // temporary fix to enable fix of invalid entries made in 2.2RC
      if ($date == 0) {
        $date = '0000-00-00';
      }
      $sql
        = "SELECT MAX(reconciled) as last_date,
			 SUM(IF(reconciled<='$date', amount, 0)) as end_balance,
			 SUM(IF(reconciled<'$date', amount, 0)) as beg_balance,
			 SUM(amount) as total
		FROM bank_trans trans
		WHERE undeposited=0 AND bank_act=" . DB::escape($bank_account);
      //	." AND trans.reconciled IS NOT NULL";
      return DB::query($sql, "Cannot retrieve reconciliation data");
    }
    /**
     * @static
     *
     * @param $bank_account
     * @param $bank_date
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get_ending_reconciled($bank_account, $bank_date) {
      $sql
        = "SELECT ending_reconcile_balance
		FROM bank_accounts WHERE id=" . DB::escape($bank_account)
        . " AND last_reconciled_date=" . DB::escape($bank_date);
      $result = DB::query($sql, "Cannot retrieve last reconciliation");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $bank_account
     * @param $date
     *
     * @return string
     */
    static public function get_sql_for_reconcile($bank_account, $date) {
      /*$sql = "SELECT	type, trans_no, ref, trans_date, amount,	person_id, person_type_id, reconciled, id
                FROM bank_trans WHERE bank_trans.bank_act = " .
             DB::quote($bank_account) . " AND undeposited = 0 AND amount!=0 AND  trans_date <= '" .
             Dates::date2sql($date) . "' AND (reconciled IS NULL OR reconciled='" .
             Dates::date2sql($date) . "') ORDER BY trans_date,bank_trans.id";*/

      $sql = "SELECT bt.type, bt.trans_no, bt.ref, bt.trans_date, IF( bt.trans_no IS NULL , SUM( g.amount ) ,
			bt.amount ) AS amount, bt.person_id, bt.person_type_id, bt.reconciled, bt.id
			FROM bank_trans bt
			LEFT OUTER JOIN bank_trans g ON g.undeposited = bt.id
			   WHERE   bt.bank_act = " . DB::quote($bank_account)
        . " AND bt.trans_date <= '" . Dates::date2sql($date) . "' AND bt.undeposited=0 AND (bt.reconciled IS NULL OR bt
			 .reconciled='" .
        Dates::date2sql($date) . "') AND bt.amount!=0 GROUP BY bt.id ORDER BY bt.trans_date ASC";

      // or	ORDER BY reconciled desc, trans_date,".''."bank_trans.id";
      return $sql;
    }
    /**
     * @static
     *
     * @param $bank_account
     * @param $date
     */
    static public function reset_sql_for_reconcile($bank_account, $date) {
      $sql
        = "UPDATE	reconciled
		FROM bank_trans
		WHERE bank_trans.bank_act = " . DB::escape($bank_account) . "
			AND undeposited = 0 AND reconciled = '" . Dates::date2sql($date) . "'";
      // or	ORDER BY reconciled desc, trans_date,".''."bank_trans.id";
      $result = DB::query($sql);
    }
    /**
     * @static
     *
     * @param $code
     *
     * @return bool
     */
    static public function is_balancesheet($code) {
      $sql = "SELECT chart_class.ctype FROM chart_class, "
        . "chart_types, chart_master
		WHERE chart_master.account_type=chart_types.id AND
		chart_types.class_id=chart_class.cid
		AND chart_master.account_code=" . DB::escape($code);
      $result = DB::query($sql, "could not retreive the account class for $code");
      $row = DB::fetch_row($result);
      return $row[0] > 0 && $row[0] < CL_INCOME;
    }
    /**
     * @static
     *
     * @param $code
     *
     * @return mixed
     */
    static public function get_name($code) {
      $sql = "SELECT account_name from chart_master WHERE account_code=" . DB::escape($code);
      $result = DB::query($sql, "could not retreive the account name for $code");
      if (DB::num_rows($result) == 1) {
        $row = DB::fetch_row($result);
        return $row[0];
      }
      Errors::db_error("could not retreive the account name for $code", $sql, TRUE);
    }
  }
