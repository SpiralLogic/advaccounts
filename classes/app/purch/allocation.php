<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Purch_Allocation {

    /**
     * @static
     *
     * @param $amount
     * @param $trans_type_from
     * @param $trans_no_from
     * @param $trans_type_to
     * @param $trans_no_to
     * @param $date_
     */
    public static function add($amount, $trans_type_from, $trans_no_from,
                               $trans_type_to, $trans_no_to, $date_) {
      $date = Dates::date2sql($date_);
      $sql  = "INSERT INTO creditor_allocations (
		amt, date_alloc,
		trans_type_from, trans_no_from, trans_no_to, trans_type_to)
		VALUES (" . DB::escape($amount) . ", '$date', "
        . DB::escape($trans_type_from) . ", " . DB::escape($trans_no_from) . ", "
        . DB::escape($trans_no_to) . ", " . DB::escape($trans_type_to) . ")";
      DB::query($sql, "A supplier allocation could not be added to the database");
    }
    /**
     * @static
     *
     * @param $trans_id
     */
    public static function delete($trans_id) {
      $sql = "DELETE FROM creditor_allocations WHERE id = " . DB::escape($trans_id);
      DB::query($sql, "The existing allocation $trans_id could not be deleted");
    }
    /**
     * @static
     *
     * @param $trans_type
     * @param $trans_no
     *
     * @return mixed
     */
    public static function get_balance($trans_type, $trans_no) {
      $sql    = "SELECT (ov_amount+ov_gst-ov_discount-alloc) AS BalToAllocate
		FROM creditor_trans WHERE trans_no="
        . DB::escape($trans_no) . " AND type=" . DB::escape($trans_type);
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
    public static function update($trans_type, $trans_no, $alloc) {
      $sql = "UPDATE creditor_trans SET alloc = alloc + " . DB::escape($alloc) . "
		WHERE type=" . DB::escape($trans_type) . " AND trans_no = " . DB::escape($trans_no);
      DB::query($sql, "The supp transaction record could not be modified for the allocation against it");
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $type_no
     * @param string $date
     */
    public static function void($type, $type_no, $date = "") {
      return Purch_Allocation::clear($type, $type_no, $date);
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $type_no
     * @param string $date
     */
    public static function clear($type, $type_no, $date = "") {
      // clear any allocations for this transaction
      $sql    = "SELECT * FROM creditor_allocations
		WHERE (trans_type_from=$type AND trans_no_from=$type_no)
		OR (trans_type_to=" . DB::escape($type) . " AND trans_no_to=" . DB::escape($type_no) . ")";
      $result = DB::query($sql, "could not void supp transactions for type=$type and trans_no=$type_no");
      while ($row = DB::fetch($result)) {
        $sql = "UPDATE creditor_trans SET alloc=alloc - " . $row['amt'] . "
			WHERE (type= " . $row['trans_type_from'] . " AND trans_no=" . $row['trans_no_from'] . ")
			OR (type=" . $row['trans_type_to'] . " AND trans_no=" . $row['trans_no_to'] . ")";
        //$sql = "UPDATE ".''."creditor_trans SET alloc=alloc - " . $row['amt'] . "
        //	WHERE type=" . $row['trans_type_to'] . " AND trans_no=" . $row['trans_no_to'];
        DB::query($sql, "could not clear allocation");
        // 2008-09-20 Joe Hunt
        if ($date != "") {
          Bank::exchange_variation($type, $type_no, $row['trans_type_to'], $row['trans_no_to'], $date,
            $row['amt'], PT_SUPPLIER, TRUE);
        }
        //////////////////////
      }
      // remove any allocations for this transaction
      $sql = "DELETE FROM creditor_allocations
		WHERE (trans_type_from=" . DB::escape($type) . " AND trans_no_from=" . DB::escape($type_no) . ")
		OR (trans_type_to=" . DB::escape($type) . " AND trans_no_to=" . DB::escape($type_no) . ")";
      DB::query($sql, "could not void supp transactions for type=$type and trans_no=$type_no");
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
    public static function get_sql($extra_fields = NULL, $extra_conditions = NULL, $extra_tables = NULL) {
      $sql = "SELECT
		trans.type,
		trans.trans_no,
		trans.reference,
		trans.tran_date,
		supplier.name,
		supplier.curr_code, 
		ov_amount+ov_gst+ov_discount AS Total,
		trans.alloc,
		trans.due_date,
		trans.supplier_id,
		supplier.address";
      /*	$sql = "SELECT trans.*,
                             ov_amount+ov_gst+ov_discount AS Total,
                             supplier.name, supplier.address,
                             supplier.curr_code ";
                         */
      if ($extra_fields) {
        $sql .= ", $extra_fields ";
      }
      $sql .= " FROM creditor_trans as trans, suppliers as supplier";
      if ($extra_tables) {
        $sql .= " ,$extra_tables ";
      }
      $sql .= " WHERE trans.supplier_id=supplier.supplier_id";
      if ($extra_conditions) {
        $sql .= " AND $extra_conditions";
      }
      return $sql;
    }
    /**
     * @static
     *
     * @param $supplier_id
     * @param $settled
     *
     * @return string
     */
    public static function get_allocatable_sql($supplier_id, $settled) {
      $settled_sql = "";
      if (!$settled) {
        $settled_sql = "AND round(ABS(ov_amount+ov_gst+ov_discount)-alloc,6) > 0";
      }
      $supplier_sql = "";
      if ($supplier_id != NULL) {
        $supplier_sql = " AND trans.supplier_id = " . DB::quote($supplier_id);
      }
      $sql = Purch_Allocation::get_sql("round(ABS(ov_amount+ov_gst+ov_discount)-alloc,6) <= 0 AS settled",
        "(type=" . ST_SUPPAYMENT . " OR type=" . ST_SUPPCREDIT . " OR type=" . ST_BANKPAYMENT . ") AND (ov_amount < 0) " . $settled_sql . $supplier_sql);
      return $sql;
    }
    /**
     * @static
     *
     * @param      $supplier_id
     * @param null $trans_no
     * @param null $type
     *
     * @return null|PDOStatement
     */
    public static function get_allocatable_to_trans($supplier_id, $trans_no = NULL, $type = NULL) {
      if ($trans_no != NULL && $type != NULL) {
        $sql = Purch_Allocation::get_sql("amt, supplier_reference", "trans.trans_no = alloc.trans_no_to
			AND trans.type = alloc.trans_type_to
			AND alloc.trans_no_from=" . DB::escape($trans_no) . "
			AND alloc.trans_type_from=" . DB::escape($type) . "
			AND trans.supplier_id=" . DB::escape($supplier_id),
          "creditor_allocations as alloc");
      }
      else {
        $sql = Purch_Allocation::get_sql(NULL, "round(ABS(ov_amount+ov_gst+ov_discount)-alloc,6) > 0
			AND trans.type != " . ST_SUPPAYMENT . "
			AND trans.supplier_id=" . DB::escape($supplier_id));
      }
      return DB::query($sql . " ORDER BY trans_no", "Cannot retreive alloc to transactions");
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected
     */
    public static function row($name, $selected = NULL) {
      echo "<td>\n";
      $allocs = array(
        ALL_TEXT => _("All Types"), '1' => _("Invoices"), '2' => _("Overdue Invoices"), '6' => _("Unpaid Invoices"), '3' => _("Payments"), '4' => _("Credit Notes"), '5' => _("Overdue Credit Notes")
      );
      echo Form::arraySelect($name, $selected, $allocs);
      echo "</td>\n";
    }
  }


