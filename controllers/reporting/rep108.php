<?php

  /* * ********************************************************************
           Copyright (C) Advanced Group PTY LTD
           Released under the terms of the GNU General Public License, GPL,
           as published by the Free Software Foundation, either version 3
           of the License, or (at your option) any later version.
           This program is distributed in the hope that it will be useful,
           but WITHOUT ANY WARRANTY; without even the implied warranty of
           MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
           See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
           * ********************************************************************* */

  Page::set_security(SA_CUSTSTATREP);
  print_statements();
  /**
   * @param $debtorno
   * @param $month
   * @param $inc_all
   *
   * @return null|PDOStatement
   */
  function get_transactions($debtorno, $month, $inc_all) {
    $dateend = date('Y-m-d', mktime(0, 0, 0, date('n') - $month, 0));
    $datestart = date('Y-m-d', mktime(0, 0, 0, date('n') - $month - 1, 1));
    $sql
      = "SELECT debtor_trans.*,
				SUM((debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
				debtor_trans.ov_freight_tax + debtor_trans.ov_discount))
				AS TotalAmount, SUM(debtor_trans.alloc) AS Allocated,
				( debtor_trans.due_date < '$datestart') AS OverDue
 			FROM debtor_trans
 			WHERE debtor_trans.due_date <= '$dateend' AND debtor_trans.debtor_no = " . DB::escape($debtorno) . "
 				AND debtor_trans.type <> " . ST_CUSTDELIVERY . "	AND (debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight + debtor_trans.ov_freight_tax + debtor_trans.ov_discount) != 0
						 GROUP BY debtor_no, ";
    $sql .= ($inc_all) ? " debtor_trans.trans_no " : " if(debtor_trans.due_date<'$datestart',0,debtor_trans.trans_no) ";
    $sql .= " ORDER BY  debtor_trans.tran_date,	debtor_trans.type,	debtor_trans.branch_id";

    return DB::query($sql, "No transactions were returned");
  }

  /**
   * @param $no
   *
   * @return mixed
   */
  function getTransactionPO($no) {
    $sql = "SELECT customer_ref FROM sales_orders WHERE order_no=" . DB::escape($no);
    $result = DB::query($sql, "Could not retrieve any branches");
    $myrow = DB::fetch_assoc($result);
    return $myrow['customer_ref'];
  }

  function print_statements() {
    global $systypes_array;
    include_once(APPPATH . "reports/pdf.php");
    $txt_statement = "Statement";
    $txt_opening_balance = 'Opening Balance';
    $doc_as_of = "as of";
    $customer = Input::post_get('PARAM_0',Input::NUMERIC,0);
    $email = Input::post_get('PARAM_2',Input::STRING,0);
    $comments = Input::post_get('PARAM_3',Input::STRING,'');
    $month = Input::post_get('PARAM_4',Input::NUMERIC,0);
    $inc_negatives = Input::post_get('PARAM_5',Input::NUMERIC,0);
    $inc_all = Input::post_get('PARAM_6',Input::NUMERIC,0);
    $doctype = ST_STATEMENT;
    $txt_outstanding = $txt_over = $txt_days = $txt_current = $txt_total_balance = NULL;
    $dec = User::price_dec();
    $cols = array(5, 60, 100, 170, 225, 295, 345, 390, 460, 460);
    $aligns = array('left', 'left', 'left', 'center', 'center', 'left', 'left', 'left', 'left');
    $params = array('comments' => $comments);
    $cur = DB_Company::get_pref('curr_default');
    $past_due1 = DB_Company::get_pref('past_due_days');
    $past_due2 = 2 * $past_due1;
    if ($email == 0) {
      $rep = new ADVReport(_('STATEMENT'), "StatementBulk", User::pagesize());
      $rep->currency = $cur;
      $rep->Font();
      $rep->Info($params, $cols, NULL, $aligns);
    }
    $sql
      = 'SELECT DISTINCT db.*,c.name AS DebtorName,c.tax_id,a.email,c.curr_code, c.payment_terms,
CONCAT(a.br_address,CHARACTER(13),a.city," ",a.state," ",a.postcode) as address FROM debtor_balances db, branches a,
		debtors c WHERE db.debtor_no = a.debtor_no AND c.debtor_no=db.debtor_no AND a.branch_ref = "Accounts" AND Balance>0  ';
    if ($customer > 0) {
      $sql .= " AND c.debtor_no = " . DB::escape($customer);
    }
    else {
      $sql .= " ORDER by name";
    }
    $result = DB::query($sql, "The customers could not be retrieved");
    while ($myrow = DB::fetch($result)) {
      $date = $myrow['tran_date'] = date('Y-m-1', strtotime("now - $month months"));
      $myrow['order_'] = "";
      $customer_record = Debtor::get_details($myrow['debtor_no'], mktime(0, 0, 0, date('n') - $month, 0), TRUE);
      if (round($customer_record["Balance"], 2) == 0) {
        continue;
      }
      if ($customer_record["Balance"] < 0) {
        continue;
      }
      $baccount = Bank_Account::get_default($myrow['curr_code']);
      $params['bankaccount'] = $baccount['id'];
      $trans_rows = get_transactions($myrow['debtor_no'], $month, $inc_all);
      if ((DB::num_rows($trans_rows) == 0)) {
        continue;
      }
      $transactions = array();
      $balance = 0;
      while ($transaction = DB::fetch_assoc($trans_rows)) {
        $balance += $transaction['TotalAmount'] - $transaction['Allocated'];
        $transactions[] = $transaction;
      }
      if ($balance <= 0) {
        continue;
      }
      if ($email == 1) {
        $rep = new ADVReport("", "", User::pagesize());
        $rep->currency = $cur;
        $rep->Font();
        $rep->title = _('STATEMENT');
        $rep->filename = "Statement" . $myrow['debtor_no'] . ".pdf";
        $rep->Info($params, $cols, NULL, $aligns);
      }
      $rep->Header2($myrow, Sales_Branch::get($transactions[0]['branch_id']), NULL, $baccount, ST_STATEMENT);
      $rep->NewLine();
      if ($rep->currency != $myrow['curr_code']) {
        include(REPORTS_PATH . 'includes'.DS.'doctext2.php');
      }
      else {
        include(REPORTS_PATH . 'includes'.DS.'doctext.php');
      }
      $openingbalance = $outstanding = $balance = 0;
      $rep->currency = $cur;
      $rep->Font();
      $rep->Info($params, $cols, NULL, $aligns);
      foreach ($transactions as $i => $trans) {
        $display_outstanding = $display_total = 0;
        if ($trans['OverDue'] && !$inc_all) {
          $openingbalance = $trans['TotalAmount'] - $trans['Allocated'];
          if (!$openingbalance) {
            continue;
          }
          $balance += $openingbalance;
        }
        else {
          $display_total = Num::format(abs($trans["TotalAmount"]), $dec);
          $outstanding = abs($trans["TotalAmount"] - $trans["Allocated"]);
          $display_outstanding = Num::format($outstanding, $dec);
          if ($inc_all) {
            $balance += ($trans['type'] == ST_SALESINVOICE) ?$outstanding:-$outstanding;
          }
          else {
            $balance += ($trans['type'] == ST_SALESINVOICE) ? $trans["TotalAmount"] : -$trans["TotalAmount"];
          }
        }
        $display_balance = Num::format($balance, $dec);

        if ($inc_all && $outstanding == 0) {
          continue;
        }
        if ($openingbalance && !$inc_all) {
          $rep->TextCol(0, 8, $txt_opening_balance);
          $rep->TextCol(8, 9, Num::format($openingbalance, $dec));
          $rep->NewLine(2);
          $openingbalance = 0;
          continue;
        }

        $rep->TextCol(0, 1, $systypes_array[$trans['type']], -2);
        if ($trans['type'] == ST_SALESINVOICE) {
          $rep->Font('bold');
        }
        $rep->TextCol(1, 2, $trans['reference'], -2);
        if ($trans['type'] == ST_SALESINVOICE) {
          $rep->TextCol(2, 3, getTransactionPO($trans['order_']), -2);
        }
        $rep->Font();
        $rep->TextCol(3, 4, Dates::sql2date($trans['tran_date']), -2);
        if ($trans['type'] == ST_SALESINVOICE) {
          $rep->TextCol(4, 5, Dates::sql2date($trans['due_date']), -2);
        }
        if ($trans['type'] == ST_SALESINVOICE && isset($display_total)) {
          $rep->TextCol(5, 6, $display_total, -2);
        }

        elseif (isset($display_total)) {
          $rep->TextCol(6, 7, $display_total, -2);
        }

        if (isset($display_outstanding)) {
          $rep->TextCol(7, 8, $display_outstanding, -2);
        }
        $rep->TextCol(8, 9, $display_balance, -2);
        $rep->NewLine();
        if ($rep->row < $rep->bottomMargin + (10 * $rep->lineHeight)) {
          $rep->Header2($myrow, NULL, NULL, $baccount, ST_STATEMENT);
        }
      }
      $rep->Font('bold');
      $txt_current = _("Current");
      $txt_now_due = "1-" . $past_due1 . " " . $txt_days;
      $txt_past_due1 = $past_due1 + 1 . "-" . $past_due2 . " " . $txt_days;
      $txt_past_due2 = $txt_over . " " . $past_due2 . " " . $txt_days;
      $str = array($txt_current, $txt_now_due, $txt_past_due1, $txt_past_due2, $txt_total_balance);
      $str2 = array(
        Num::format(($customer_record["Due"] - $customer_record["Overdue1"]), $dec),
        Num::format(($customer_record["Overdue1"] - $customer_record["Overdue2"]), $dec),
        Num::format($customer_record["Overdue2"], $dec),
        Num::format(($balance - $customer_record["Due"]), $dec),
        $display_balance
      );
      $col = array(
        $rep->cols[0], $rep->cols[0] + 80, $rep->cols[0] + 170, $rep->cols[0] + 270, $rep->cols[0] + 360, $rep->cols[0] + 450
      );
      $rep->row = $rep->bottomMargin + (13 * $rep->lineHeight - 6);
      if ($customer_record["Balance"] > 0 && $customer_record["Due"] - $customer_record["Overdue1"] < $customer_record["Balance"]) {
        $rep->SetTextColor(255, 0, 0);
        $rep->fontSize += 4;
        $rep->Font('bold');
        $rep->TextWrapLines(0, $rep->pageWidth-50, 'YOUR ACCOUNT IS OVERDUE, IMMEDIATE PAYMENT REQUIRED!', 'C');
        $rep->fontSize -= 4;
        $rep->SetTextColor(0, 0, 0);
      }
      $rep->NewLine();
      for ($i = 0; $i < 5; $i++) {
        $rep->TextWrap($col[$i], $rep->row, $col[$i + 1] - $col[$i], $str[$i], 'center');
      }
      $rep->Font();
      $rep->NewLine();
      for ($i = 0; $i < 5; $i++) {
        $rep->TextWrap($col[$i], $rep->row, $col[$i + 1] - $col[$i], $str2[$i], 'center');
      }
      if ($email == 1) {
        $rep->End($email, $txt_statement . " " . $doc_as_of . " " . Dates::sql2date($date), $myrow, ST_STATEMENT);
      }
    }
    if ($email == 0) {
      $rep->End();
    }
  }
