<?php
  /**********************************************************************
  Copyright (C) Advanced Group PTY LTD
  Released under the terms of the GNU General Public License, GPL,
  as published by the Free Software Foundation, either version 3
  of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
   ***********************************************************************/

  class Sales_Allocation {

    static public function add($amount, $trans_type_from, $trans_no_from,
                               $trans_type_to, $trans_no_to) {
      $sql
        = "INSERT INTO debtor_allocations (
		amt, date_alloc,
		trans_type_from, trans_no_from, trans_no_to, trans_type_to)
		VALUES ($amount, Now() ," . DB::escape($trans_type_from) . ", " . DB::escape($trans_no_from) . ", " . DB::escape($trans_no_to)
        . ", " . DB::escape($trans_type_to) . ")";
      DB::query($sql, "A customer allocation could not be added to the database");
    }

    static public function delete($trans_id) {
      $sql = "DELETE FROM debtor_allocations WHERE id = " . DB::escape($trans_id);
      return DB::query($sql, "The existing allocation $trans_id could not be deleted");
    }

    static public function get_balance($trans_type, $trans_no) {
      $sql
        = "SELECT (ov_amount+ov_gst+ov_freight+ov_freight_tax-ov_discount-alloc) AS BalToAllocate
		FROM debtor_trans WHERE trans_no=" . DB::escape($trans_no) . " AND type=" . DB::escape($trans_type);
      $result = DB::query($sql, "calculate the allocation");
      $myrow = DB::fetch_row($result);
      return $myrow[0];
    }

    static public function update($trans_type, $trans_no, $alloc) {
      $sql
        = "UPDATE debtor_trans SET alloc = alloc + $alloc
		WHERE type=" . DB::escape($trans_type) . " AND trans_no = " . DB::escape($trans_no);
      DB::query($sql, "The debtor transaction record could not be modified for the allocation against it");
    }

    static public function void($type, $type_no, $date = "") {

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
            $row['amt'], PT_CUSTOMER, TRUE);
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

    static public function get_sql($extra_fields = NULL, $extra_conditions = NULL, $extra_tables = NULL) {
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
      $sql .= " WHERE trans.debtor_no=debtor.debtor_no";
      if ($extra_conditions) {
        $sql .= " AND $extra_conditions ";
      }
      return $sql;
    }

    static public function get_allocatable_sql($customer_id, $settled) {
      $settled_sql = "";
      if (!$settled) {
        $settled_sql = " AND (round(ov_amount+ov_gst+ov_freight+ov_freight_tax-ov_discount-alloc,2) > 0)";
      }
      $cust_sql = "";
      if ($customer_id != NULL) {
        $cust_sql = " AND trans.debtor_no = " . DB::quote($customer_id);
      }
      $sql = Sales_Allocation::get_sql("round(ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount-alloc,2) <= 0 AS settled",
        "(type=" . ST_CUSTPAYMENT . " OR type=" . ST_CUSTREFUND . " OR type=" . ST_CUSTCREDIT . " OR type=" . ST_BANKDEPOSIT . ") AND (trans.ov_amount > 0) " . $settled_sql . $cust_sql);
      return $sql;
    }

    static public function get_to_trans($customer_id, $trans_no = NULL, $type = NULL) {
      if ($trans_no != NULL and $type != NULL) {
        $sql = Sales_Allocation::get_sql("amt", "trans.trans_no = alloc.trans_no_to
			AND trans.type = alloc.trans_type_to
			AND alloc.trans_no_from=$trans_no
			AND alloc.trans_type_from=$type
			AND trans.debtor_no=" . DB::escape($customer_id),
          "debtor_allocations as alloc");
      }
      else {
        $sql = Sales_Allocation::get_sql(NULL, "round(ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount-alloc,6) > 0
			AND trans.type <> " . ST_CUSTPAYMENT . "
			AND trans.type <> " . ST_CUSTREFUND . "
			AND trans.type <> " . ST_BANKDEPOSIT . "
			AND trans.type <> " . ST_CUSTCREDIT . "
			AND trans.type <> " . ST_CUSTDELIVERY . "
			AND trans.debtor_no=" . DB::escape($customer_id));
      }
      return DB::query($sql . " ORDER BY trans_no", "Cannot retreive alloc to transactions");
    }
    static public function clear_allocations() {
      if (isset($_SESSION['alloc'])) {
        unset($_SESSION['alloc']->allocs, $_SESSION['alloc']);
      }
    }

    static public function edit_allocations_for_transaction($type, $trans_no) {
      global $systypes_array;
      Display::heading(sprintf(_("Allocation of %s # %d"), $systypes_array[$_SESSION['alloc']->type], $_SESSION['alloc']->trans_no));
      Display::heading($_SESSION['alloc']->person_name);
      Display::heading(_("Date:") . " <span class='bold'>" . $_SESSION['alloc']->date_ . "</span>");
      Display::heading(_("Total:") . " <span class='bold'>" . Num::price_format($_SESSION['alloc']->amount) . "</span>");
      echo "<br>";
      start_form();
      if (isset($_POST['inquiry'], $_SERVER['HTTP_REFERER']) || stristr($_SERVER['HTTP_REFERER'], 'customer_allocation_inquiry.php')) {
        hidden('inquiry', TRUE);
      }
      Display::div_start('alloc_tbl');
      if (count($_SESSION['alloc']->allocs) > 0) {
        Gl_Allocation::show_allocatable(TRUE);
        submit_center_first('UpdateDisplay', _("Refresh"), _('Start again allocation of selected amount'), TRUE);
        submit('Process', _("Process"), TRUE, _('Process allocations'), 'default');
        submit_center_last('Cancel', _("Back to Allocations"), _('Abandon allocations and return to selection of allocatable amounts'), 'cancel');
      }
      else {
        Event::warning(_("There are no unsettled transactions to allocate."), 0, 1);
        submit_center('Cancel', _("Back to Allocations"), TRUE, _('Abandon allocations and return to selection of allocatable amounts'), 'cancel');
      }
      Display::div_end();
      end_form();
    }
    static public function systype_name($dummy, $type) {
      global $systypes_array;
      return $systypes_array[$type];
    }

    static public function trans_view($trans) {
      return GL_UI::trans_view($trans["type"], $trans["trans_no"]);
    }

    static public function alloc_link($row) {
      return DB_Pager::link(_("Allocate"), "/sales/allocations/customer_allocate.php?trans_no=" . $row["trans_no"] . "&trans_type=" . $row["type"], ICON_MONEY);
    }

    static public function amount_left($row) {
      return Num::price_format($row["Total"] - $row["alloc"]);
    }

    static public function check_settled($row) {
      return $row['settled'] == 1;
    }
  }
