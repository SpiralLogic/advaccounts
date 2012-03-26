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
  //	Returns next transaction number.
  //	Used only for transactions stored in tables without autoincremented key.
  //
  class SysTypes {

    static public function get_next_trans_no($trans_type) {
      $st = SysTypes::get_db_info($trans_type);
      if (!($st && $st[0] && $st[2])) {
        // this is in fact internal error condition.
        Event::error('Internal error: invalid type passed to SysTypes::get_next_trans_no()');
        return 0;
      }
      $sql = "SELECT MAX(`$st[2]`) FROM $st[0]";
      if ($st[1] != NULL) {
        $sql .= " WHERE `$st[1]`=$trans_type";
      }
      $unique = FALSE;
      $result = DB::query($sql, "The next transaction number for $trans_type could not be retrieved");
      $myrow = DB::fetch_row($result);
      $ref = $myrow[0];
      while (!$unique) {
        $ref++;
        $sql = "SELECT id FROM refs WHERE `id`=" . $ref . " AND `type`=" . $trans_type;
        $result = DB::query($sql);
        $unique = (DB::num_rows($result) > 0) ? FALSE : TRUE;
      }
      return $ref;
    }

    static public function get_db_info($type) {
      switch ($type) {
        case   ST_JOURNAL    :
          return array("gl_trans", "type", "type_no", NULL, "tran_date");
        case   ST_BANKPAYMENT  :
          return array("bank_trans", "type", "trans_no", "ref", "trans_date");
        case   ST_BANKDEPOSIT  :
          return array("bank_trans", "type", "trans_no", "ref", "trans_date");
        case   3         :
          return NULL;
        case   ST_BANKTRANSFER :
          return array("bank_trans", "type", "trans_no", "ref", "trans_date");
        case   ST_SALESINVOICE :
          return array("debtor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_CUSTCREDIT   :
          return array("debtor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_CUSTPAYMENT  :
          return array("debtor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_CUSTREFUND  :
          return array("debtor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_CUSTDELIVERY :
          return array("debtor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_LOCTRANSFER  :
          return array("stock_moves", "type", "trans_no", "reference", "tran_date");
        case   ST_INVADJUST  :
          return array("stock_moves", "type", "trans_no", "reference", "tran_date");
        case   ST_PURCHORDER   :
          return array("purch_orders", NULL, "order_no", "reference", "tran_date");
        case   ST_SUPPINVOICE  :
          return array("creditor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_SUPPCREDIT   :
          return array("creditor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_SUPPAYMENT   :
          return array("creditor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_SUPPRECEIVE  :
          return array("grn_batch", NULL, "id", "reference", "delivery_date");
        case   ST_WORKORDER  :
          return array("workorders", NULL, "id", "wo_ref", "released_date");
        case   ST_MANUISSUE  :
          return array("wo_issues", NULL, "issue_no", "reference", "issue_date");
        case   ST_MANURECEIVE  :
          return array("wo_manufacture", NULL, "id", "reference", "date_");
        case   ST_SALESORDER   :
          return array("sales_orders", "trans_type", "order_no", "reference", "ord_date");
        case   31        :
          return array("service_orders", NULL, "order_no", "cust_ref", "date");
        case   ST_SALESQUOTE   :
          return array("sales_orders", "trans_type", "order_no", "reference", "ord_date");
        case   ST_DIMENSION  :
          return array("dimensions", NULL, "id", "reference", "date_");
        case   ST_COSTUPDATE   :
          return array("gl_trans", "type", "type_no", NULL, "tran_date");
      }
      Errors::db_error("invalid type ($type) sent to get_systype_db_info", "", TRUE);
    }

    static public function get() {
      $sql = "SELECT type_id,type_no,CONCAT(prefix,next_reference)as next_reference FROM sys_types";
      $result = DB::query($sql, "could not query systypes table");
      return $result;
    }

    static public function get_class_type_convert($ctype) {
      if (Config::get('accounts.gl_oldconvertstyle') == 1) {
        return (($ctype >= CL_INCOME || $ctype == CL_NONE) ? -1 : 1);
      }
      else {
        return ((($ctype >= CL_LIABILITIES && $ctype <= CL_INCOME) || $ctype == CL_NONE) ? -1 : 1);
      }
    }

    static public function select($name, $value = NULL, $spec_opt = FALSE, $submit_on_change = FALSE) {
      global $systypes_array;
      return array_selector($name, $value, $systypes_array, array(
        'spec_option' => $spec_opt, 'spec_id' => ALL_NUMERIC, 'select_submit' => $submit_on_change, 'async' => FALSE,
      ));
    }

    static public function cells($label, $name, $value = NULL, $submit_on_change = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo SysTypes::select($name, $value, FALSE, $submit_on_change);
      echo "</td>\n";
    }

    static public function row($label, $name, $value = NULL, $submit_on_change = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      SysTypes::cells(NULL, $name, $value, $submit_on_change);
      echo "</tr>\n";
    }
  }
