<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  class Sales_Allocation
  {
    /**
     * @static
     *
     * @param $amount
     * @param $trans_type_from
     * @param $trans_no_from
     * @param $trans_type_to
     * @param $trans_no_to
     */
    public static function add($amount, $trans_type_from, $trans_no_from,
                               $trans_type_to, $trans_no_to) {
      $sql
        = "INSERT INTO debtor_allocations (
        amt, date_alloc,
        trans_type_from, trans_no_from, trans_no_to, trans_type_to)
        VALUES ($amount, Now() ," . DB::escape($trans_type_from) . ", " . DB::escape($trans_no_from) . ", " . DB::escape($trans_no_to)
        . ", " . DB::escape($trans_type_to) . ")";
      DB::query($sql, "A customer allocation could not be added to the database");
    }
    /**
     * @static
     *
     * @param $trans_id
     *
     * @return null|PDOStatement
     */
    public static function delete($trans_id)
    {
      $sql = "DELETE FROM debtor_allocations WHERE id = " . DB::escape($trans_id);

      return DB::query($sql, "The existing allocation $trans_id could not be deleted");
    }
    /**
     * @static
     *
     * @param $trans_type
     * @param $trans_no
     *
     * @return mixed
     */
    public static function get_balance($trans_type, $trans_no)
    {
      $sql
              = "SELECT (ov_amount+ov_gst+ov_freight+ov_freight_tax-ov_discount-alloc) AS BalToAllocate
        FROM debtor_trans WHERE trans_no=" . DB::escape($trans_no) . " AND type=" . DB::escape($trans_type);
      $result = DB::query($sql, "calculate the allocation");
      $myrow  = DB::fetch_row($result);

      return $myrow[0];
    }
    /**
     * @static
     *
     * @param $trans_type
     * @param $trans_no
     * @param $alloc
     */
    public static function update($trans_type, $trans_no, $alloc)
    {
      $sql
        = "UPDATE debtor_trans SET alloc = alloc + $alloc
        WHERE type=" . DB::escape($trans_type) . " AND trans_no = " . DB::escape($trans_no);
      DB::query($sql, "The debtor transaction record could not be modified for the allocation against it");
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $type_no
     * @param string $date
     */
    public static function void($type, $type_no, $date = "")
    {
      // clear any allocations for this transaction
      $sql
              = "SELECT * FROM debtor_allocations
        WHERE (trans_type_from=" . DB::escape($type) . " AND trans_no_from=" . DB::escape($type_no) . ")
        OR (trans_type_to=" . DB::escape($type) . " AND trans_no_to=" . DB::escape($type_no) . ")";
      $result = DB::query($sql, "could not void debtor transactions for type=$type and trans_no=$type_no");
      while ($row = DB::fetch($result)) {
        $sql = "UPDATE debtor_trans SET alloc=alloc - " . $row['amt'] . "
            WHERE (type= " . $row['trans_type_from'] . " AND trans_no=" . $row['trans_no_from'] . ")
            OR (type=" . $row['trans_type_to'] . " AND trans_no=" . $row['trans_no_to'] . ")";
        DB::query($sql, "could not clear allocation");
        // 2008-09-20 Joe Hunt
        if ($date != "") {
          Bank::exchange_variation($type, $type_no, $row['trans_type_to'], $row['trans_no_to'], $date,
            $row['amt'], PT_CUSTOMER, true);
        }
        //////////////////////
      }
      // remove any allocations for this transaction
      $sql
        = "DELETE FROM debtor_allocations
        WHERE (trans_type_from=" . DB::escape($type) . " AND trans_no_from=" . DB::escape($type_no) . ")
        OR (trans_type_to=" . DB::escape($type) . " AND trans_no_to=" . DB::escape($type_no) . ")";
      DB::query($sql, "could not void debtor transactions for type=$type and trans_no=$type_no");
    }
    /**
     * @static
     *
     * @param null $extra_fields
     * @param null $extra_conditions
     * @param null $extra_tables
     *
     * @return string
     */
    public static function get_sql($extra_fields = null, $extra_conditions = null, $extra_tables = null)
    {
      $sql
        = "SELECT
        trans.type,
        trans.trans_no,
        trans.reference,
        trans.tran_date,
        debtor.name AS DebtorName,
        debtor.curr_code,
        ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount AS Total,
        trans.alloc,
        trans.due_date,
        debtor.address,
        trans.version ";
      if ($extra_fields) {
        $sql .= ", $extra_fields ";
      }
      $sql .= " FROM debtor_trans as trans, "
        . "debtors as debtor";
      if ($extra_tables) {
        $sql .= ",$extra_tables ";
      }
      $sql .= " WHERE trans.debtor_id=debtor.debtor_id";
      if ($extra_conditions) {
        $sql .= " AND $extra_conditions ";
      }

      return $sql;
    }
    /**
     * @static
     *
     * @param $customer_id
     * @param $settled
     *
     * @return string
     */
    public static function get_allocatable_sql($customer_id, $settled)
    {
      $settled_sql = "";
      if (!$settled) {
        $settled_sql = " AND (round(ov_amount+ov_gst+ov_freight+ov_freight_tax-ov_discount-alloc,2) > 0)";
      }
      $cust_sql = "";
      if ($customer_id != null) {
        $cust_sql = " AND trans.debtor_id = " . DB::quote($customer_id);
      }
      $cust_sql .= ' and  trans.debtor_id<>4721 '; //TODO: REMOVE

      $sql = Sales_Allocation::get_sql("round(ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount-alloc,2) <= 0 AS settled",
        "(type=" . ST_CUSTPAYMENT . " OR type=" . ST_CUSTREFUND . " OR type=" . ST_CUSTCREDIT . " OR type=" . ST_BANKDEPOSIT . ") AND (trans.ov_amount > 0) " . $settled_sql . $cust_sql);

      return $sql;
    }
    /**
     * @static
     *
     * @param      $customer_id
     * @param null $trans_no
     * @param null $type
     *
     * @return null|PDOStatement
     */
    public static function get_to_trans($customer_id, $trans_no = null, $type = null)
    {
      if ($trans_no != null and $type != null) {
        $sql = Sales_Allocation::get_sql("amt", "trans.trans_no = alloc.trans_no_to
            AND trans.type = alloc.trans_type_to
            AND alloc.trans_no_from=$trans_no
            AND alloc.trans_type_from=$type
            AND trans.debtor_id=" . DB::escape($customer_id),
          "debtor_allocations as alloc");
      } else {
        $sql = Sales_Allocation::get_sql(null, "round(ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount-alloc,6) > 0
            AND trans.type <> " . ST_CUSTPAYMENT . "
            AND trans.type <> " . ST_CUSTREFUND . "
            AND trans.type <> " . ST_BANKDEPOSIT . "
            AND trans.type <> " . ST_CUSTCREDIT . "
            AND trans.type <> " . ST_CUSTDELIVERY . "
            AND trans.debtor_id=" . DB::escape($customer_id));
      }

      return DB::query($sql . " ORDER BY trans_no", "Cannot retreive alloc to transactions");
    }
    public static function clear_allocations()
    {
      if (isset($_SESSION['alloc'])) {
        unset($_SESSION['alloc']->allocs, $_SESSION['alloc']);
      }
    }
    /**
     * @static
     *
     * @param $type
     * @param $trans_no
     */
    public static function edit_allocations_for_transaction($type, $trans_no)
    {
      global $systypes_array;
      Display::heading(sprintf(_("Allocation of %s # %d"), $systypes_array[$_SESSION['alloc']->type], $_SESSION['alloc']->trans_no));
      Display::heading($_SESSION['alloc']->person_name);
      Display::heading(_("Date:") . " <span class='bold'>" . $_SESSION['alloc']->date_ . "</span>");
      Display::heading(_("Total:") . " <span class='bold'>" . Num::price_format($_SESSION['alloc']->amount) . "</span>");
      echo "<br>";
      Forms::start();
      if (isset($_POST['inquiry'], $_SERVER['HTTP_REFERER']) || stristr($_SERVER['HTTP_REFERER'], 'customer_allocation_inquiry.php')) {
        Forms::hidden('inquiry', true);
      }
      Display::div_start('alloc_tbl');
      if (count($_SESSION['alloc']->allocs) > 0) {
        Gl_Allocation::show_allocatable(true);
        Forms::submitCenterBegin('UpdateDisplay', _("Refresh"), _('Start again allocation of selected amount'), true);
        Forms::submit('Process', _("Process"), true, _('Process allocations'), 'default');
        Forms::submitCenterEnd('Cancel', _("Back to Allocations"), _('Abandon allocations and return to selection of allocatable amounts'), 'cancel');
      } else {
        Event::warning(_("There are no unsettled transactions to allocate."), 0, 1);
        Forms::submitCenter('Cancel', _("Back to Allocations"), true, _('Abandon allocations and return to selection of allocatable amounts'), 'cancel');
      }
      Display::div_end();
      Forms::end();
    }
    /**
     * @static
     *
     * @param $dummy
     * @param $type
     *
     * @return mixed
     */
    public static function systype_name($dummy, $type)
    {
      global $systypes_array;

      return $systypes_array[$type];
    }
    /**
     * @static
     *
     * @param $trans
     *
     * @return null|string
     */
    public static function trans_view($trans)
    {
      return GL_UI::trans_view($trans["type"], $trans["trans_no"]);
    }
    /**
     * @static
     *
     * @param $row
     *
     * @return string
     */
    public static function alloc_link($row)
    {
      return DB_Pager::link(_("Allocate"), "/sales/allocations/customer_allocate.php?trans_no=" . $row["trans_no"] . "&trans_type=" . $row["type"], ICON_MONEY);
    }
    /**
     * @static
     *
     * @param $row
     *
     * @return int|string
     */
    public static function amount_left($row)
    {
      return Num::price_format($row["Total"] - $row["alloc"]);
    }
    /**
     * @static
     *
     * @param $row
     *
     * @return bool
     */
    public static function check_settled($row)
    {
      return $row['settled'] == 1;
    }
  }
