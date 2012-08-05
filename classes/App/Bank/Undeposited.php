<?php
  use ADV\Core\DB\DB;

  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 4/08/12
   * Time: 5:25 PM
   * To change this template use File | Settings | File Templates.
   */
  class Bank_Undeposited
  {
    /** @var \ADV\Core\DB\DB */
    static $DB;
    /** @var Dates */
    static $Dates;
    /**
     * @param $groupid
     */
    public static function ungroup($group_id) {
      $sql = "UPDATE bank_trans SET undeposited=0, reconciled=null WHERE undeposited =" . static::$DB->_escape($group_id);
      static::$DB->_query($sql, "Couldn't ungroup group deposit");
      $sql = "UPDATE bank_trans SET ref=" . static::$DB->_quote('Removed group: ' . $group_id) . ", amount=0, reconciled='" . static::$Dates->_today() . "', undeposited=" . $group_id . " WHERE id=" . $group_id;
      static::$DB->_query($sql, "Couldn't update removed group deposit data");
    }
    /**
     * @param $deposit
     */
    public static function undeposit($deposit_id) {
      $sql = "UPDATE bank_trans SET undeposited=0, reconciled=null WHERE id=" . $deposit_id;
      static::$DB->_query($sql, "Can't change undeposited status");
    }
    public static function createGroup($account, $date) {
      $sql        = "INSERT INTO bank_trans (type, bank_act, amount, ref, trans_date, person_type_id, person_id, undeposited)
          VALUES (" . ST_GROUPDEPOSIT . ", " . static::$DB->_quote($account) . ", 0," . static::$DB->_quote('Group Deposit') . "," . //
        static::$DB->_quote(static::$Dates->_dateToSql($date)) . ", 6," . //
        static::$DB->_quote(User::i()->user) . ",0)";
      $query      = static::$DB->_query($sql, "Undeposited Cannot be Added");
      $deposit_id = static::$DB->_insertId($query);
      return $deposit_id;
    }
    public static function addToGroup($trans_id, $account, $togroup) {
      $result1 = static::$DB->_select('type', 'amount', 'trans_date', 'undeposited')->from('bank_trans')->where('id=', $trans_id)->where('bank_act=', $account)->fetch()->one();
      $result2 = static::$DB->_select('type', 'amount', 'trans_date', 'undeposited')->from('bank_trans')->where('id=', $togroup)->where('bank_act=', $account)->fetch()->one();
      $type1   = $result1['type'];
      $amount1 = $result1['amount'];
      $date1   = $result1['trans_date'];
      $group1  = $result1['undeposited'];
      $type2   = $result2['type'];
      $amount2 = $result2['amount'];
      $group2  = $result2['undeposited'];
      if ($group1 > 1 && $group2 > 1) {
        Event::error('Transactions are already grouped!');
        return false;
      }
      if ($type1 == ST_GROUPDEPOSIT && $type2 == ST_GROUPDEPOSIT) {
        Event::error('Transactions are both groups!');
        return false;
      }
      if ($type1 == ST_GROUPDEPOSIT) {
        $sql    = "UPDATE bank_trans SET undeposited=" . $trans_id . " WHERE id=" . static::$DB->_quote($togroup) . " AND bank_act = " . static::$DB->_quote($account);
        $amount = $amount2;
      } elseif ($type2 == ST_GROUPDEPOSIT) {
        $sql    = "UPDATE bank_trans SET undeposited=" . $togroup . " WHERE id=" . static::$DB->_quote($trans_id) . " AND bank_act = " . static::$DB->_quote($account);
        $amount = $amount1;
      } else {
        $group = static::createGroup($account, $date1);
        $sql   = "UPDATE bank_trans SET undeposited=" . $group . " WHERE id=" . static::$DB->_quote($trans_id) . " AND bank_act = " . static::$DB->_quote($account);
        $sql .= "; UPDATE bank_trans SET undeposited=" . $group . " WHERE id=" . static::$DB->_quote($togroup) . " AND bank_act = " . static::$DB->_quote($account);
        $amount = $amount1 + $amount2;
      }
      $sql .= "; UPDATE bank_trans SET amount=amount + $amount WHERE id = " . static::$DB->_quote($togroup) . " AND bank_act = " . static::$DB->_quote($account);
      static::$DB->_query($sql, "Can't change undeposited status");
    }
    /**
     * @static
     *
     * @param $trans_id
     * @param $account
     * @param $fromgroup
     *
     * @return bool
     */
    public static function removeFromGroup($trans_id, $account, $fromgroup) {
      $trans   = static::$DB->_select('amount', 'undeposited')->from('bank_trans')->where('id=', $trans_id)->where('bank_act=', $account)->fetch()->one();
      $amount  = $trans['amount'];
      $current = $trans['undeposited'];
      if ($current != $fromgroup) {
        Event::error('Transaction is not in this group!');
        return false;
      }
      $sql = "UPDATE bank_trans SET undeposited=0 WHERE id=" . static::$DB->_quote($trans_id) . " AND bank_act = " . static::$DB->_quote($account);
      $sql .= "; UPDATE bank_trans SET amount=amount - $amount WHERE id = " . static::$DB->_quote($fromgroup);
      static::$DB->_query($sql, "Can't change undeposited status");
    }
  }

  Bank_Undeposited::$DB    = DB::i();
  Bank_Undeposited::$Dates = Dates::i();
