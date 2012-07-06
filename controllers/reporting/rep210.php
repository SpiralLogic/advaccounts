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

  print_remittances();
  /**
   * @param $type
   * @param $trans_no
   *
   * @return \ADV\Core\DB\Query\Result|Array|bool
   */
  function get_remittance($type, $trans_no) {
    $sql    = "SELECT creditor_trans.*,
 		(creditor_trans.ov_amount+creditor_trans.ov_gst+creditor_trans.ov_discount) AS Total,
 		suppliers.name, suppliers.account_no, suppliers.city, suppliers.postcode, suppliers.state,
 		suppliers.curr_code, suppliers.payment_terms, suppliers.gst_no AS tax_id,
 		suppliers.email, suppliers.address, suppliers.contact
		FROM creditor_trans, suppliers
		WHERE creditor_trans.supplier_id = suppliers.supplier_id
		AND creditor_trans.type = " . DB::escape($type) . "
		AND creditor_trans.trans_no = " . DB::escape($trans_no);
    $result = DB::query($sql, "The remittance cannot be retrieved");
    if (DB::numRows($result) == 0) {
      return false;
    }
    return DB::fetch($result);
  }

  /**
   * @param $supplier_id
   * @param $type
   * @param $trans_no
   *
   * @return null|PDOStatement
   */
  function get_allocations_for_remittance($supplier_id, $type, $trans_no) {
    $sql = Purch_Allocation::get_sql("amt, supplier_reference, trans.alloc", "trans.trans_no = alloc.trans_no_to
		AND trans.type = alloc.trans_type_to
		AND alloc.trans_no_from=" . DB::escape($trans_no) . "
		AND alloc.trans_type_from=" . DB::escape($type) . "
		AND trans.supplier_id=" . DB::escape($supplier_id), "creditor_allocations as alloc");
    $sql .= " ORDER BY trans_no";
    return DB::query($sql, "Cannot retreive alloc to transactions");
  }

  function print_remittances() {
    global $systypes_array;
    $report_type = '\\ADV\\App\\Reports\\Pdf';
    $from        = $_POST['PARAM_0'];
    $to          = $_POST['PARAM_1'];
    $currency    = $_POST['PARAM_2'];
    $email       = $_POST['PARAM_3'];
    $comments    = $_POST['PARAM_4'];
    if ($from == null) {
      $from = 0;
    }
    if ($to == null) {
      $to = 0;
    }
    $dec  = User::price_dec();
    $fno  = explode("-", $from);
    $tno  = explode("-", $to);
    $cols = array(4, 85, 150, 225, 275, 360, 450, 515);
    // $headers in doctext.inc
    $aligns = array('left', 'left', 'left', 'left', 'right', 'right', 'right');
    $params = array('comments' => $comments);
    $cur    = DB_Company::get_pref('curr_default');
    if ($email == 0) {
      /** @var \ADV\App\Reports\Pdf|\ADV\App\Reports\Excel $rep  */
      $rep           = new $report_type(_('REMITTANCE'), "RemittanceBulk",$_POST['PARAM_0'] == $_POST['PARAM_1'] ? SA_SUPPTRANSVIEW : SA_SUPPBULKREP, User::page_size());
      $rep->currency = $cur;
      $rep->Font();
      $rep->Info($params, $cols, null, $aligns);
    }
    for ($i = $fno[0]; $i <= $tno[0]; $i++) {
      if ($fno[0] == $tno[0]) {
        $types = array($fno[1]);
      } else {
        $types = array(ST_BANKPAYMENT, ST_SUPPAYMENT, ST_SUPPCREDIT);
      }
      foreach ($types as $j) {
        $myrow = get_remittance($j, $i);
        if (!$myrow) {
          continue;
        }
        $baccount              = Bank_Account::get_default($myrow['curr_code']);
        $params['bankaccount'] = $baccount['id'];
        if ($email == 1) {
          $rep           = new $report_type("", "", $_POST['PARAM_0'] == $_POST['PARAM_1'] ? SA_SUPPTRANSVIEW : SA_SUPPBULKREP,User::page_size());
          $rep->currency = $cur;
          $rep->Font();
          $rep->title    = _('REMITTANCE');
          $rep->filename = "Remittance" . $i . ".pdf";
          $rep->Info($params, $cols, null, $aligns);
        } else {
          $rep->title = _('REMITTANCE');
        }
        $rep->Header2($myrow, null, $myrow, $baccount, ST_SUPPAYMENT);
        $result   = get_allocations_for_remittance($myrow['supplier_id'], $myrow['type'], $myrow['trans_no']);
        $linetype = true;
        $doctype  = ST_SUPPAYMENT;
        if ($rep->currency != $myrow['curr_code']) {
          include(REPORTS_PATH . 'includes' . DS . 'doctext2.php');
        } else {
          include(REPORTS_PATH . 'includes' . DS . 'doctext.php');
        }
        $total_allocated = 0;
        $rep->TextCol(0, 4, $doc_Towards, -2);
        $rep->NewLine(2);
        while ($myrow2 = DB::fetch($result)) {
          $rep->TextCol(0, 1, $systypes_array[$myrow2['type']], -2);
          $rep->TextCol(1, 2, $myrow2['supplier_reference'], -2);
          $rep->TextCol(2, 3, Dates::sqlToDate($myrow2['tran_date']), -2);
          $rep->TextCol(3, 4, Dates::sqlToDate($myrow2['due_date']), -2);
          $rep->AmountCol(4, 5, $myrow2['Total'], $dec, -2);
          $rep->AmountCol(5, 6, $myrow2['Total'] - $myrow2['alloc'], $dec, -2);
          $rep->AmountCol(6, 7, $myrow2['amt'], $dec, -2);
          $total_allocated += $myrow2['amt'];
          $rep->NewLine(1);
          if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight)) {
            $rep->Header2($myrow, null, $myrow, $baccount, ST_SUPPAYMENT);
          }
        }
        $rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
        $rep->TextCol(3, 6, $txt_total_allocated, -2);
        $rep->AmountCol(6, 7, $total_allocated, $dec, -2);
        $rep->NewLine();
        $rep->TextCol(3, 6, $txt_left_to_allocate, -2);
        $myrow['Total'] *= -1;
        $rep->AmountCol(6, 7, $myrow['Total'] - $total_allocated, $dec, -2);
        $rep->NewLine();
        $rep->Font('bold');
        $rep->TextCol(3, 6, $txt_total_payment, -2);
        $rep->AmountCol(6, 7, $myrow['Total'], $dec, -2);
        $words = Item_Price::toWords($myrow['Total'], ST_SUPPAYMENT);
        if ($words != "") {
          $rep->NewLine(2);
          $rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, -2);
        }
        $rep->Font();
        if ($email == 1) {
          $myrow['contact_email'] = $myrow['email'];
          $myrow['DebtorName']    = $myrow['name'];
          if ($myrow['contact'] != '') {
            $myrow['DebtorName'] = $myrow['contact'];
          }
          $rep->End($email, $doc_Order_no . " " . $myrow['reference'], $myrow);
        }
      }
    }
    if ($email == 0) {
      $rep->End();
    }
  }


