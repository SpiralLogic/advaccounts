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

  Page::set_security($_POST['PARAM_0'] == $_POST['PARAM_1'] ? SA_SALESTRANSVIEW : SA_SALESBULKREP);
  $packing_slip = 0;
  print_deliveries();
  function print_deliveries()
  {
    global $packing_slip;
    require_once(APPPATH . "reports/pdf.php");
    $from         = $_POST['PARAM_0'];
    $to           = $_POST['PARAM_1'];
    $email        = $_POST['PARAM_2'];
    $packing_slip = $_POST['PARAM_3'];
    $comments     = $_POST['PARAM_4'];
    if ($from == null) {
      $from = 0;
    }
    if ($to == null) {
      $to = 0;
    }
    $dec  = User::price_dec();
    $fno  = explode("-", $from);
    $tno  = explode("-", $to);
    $cols = array(4, 60, 225, 450, 515);
    // $headers in doctext.inc
    $aligns = array('left', 'left', 'right', 'right');
    $params = array('comments' => $comments);
    $cur    = DB_Company::get_pref('curr_default');
    if ($email == 0) {
      if ($packing_slip == 0) {
        $rep = new ADVReport(_('DELIVERY'), "DeliveryNoteBulk", User::page_size());
      } else {
        $rep = new ADVReport(_('PACKING SLIP'), "PackingSlipBulk", User::page_size());
      }
      $rep->currency = $cur;
      $rep->Font();
      $rep->Info($params, $cols, null, $aligns);
    }
    for ($i = $fno[0]; $i <= $tno[0]; $i++) {
      if (!Debtor_Trans::exists(ST_CUSTDELIVERY, $i)) {
        continue;
      }
      $myrow       = Debtor_Trans::get($i, ST_CUSTDELIVERY);
      $branch      = Sales_Branch::get($myrow["branch_id"]);
      $sales_order = Sales_Order::get_header($myrow["order_"], ST_SALESORDER); // ?
      if ($email == 1) {
        $rep           = new ADVReport("", "", User::page_size());
        $rep->currency = $cur;
        $rep->Font();
        if ($packing_slip == 0) {
          $rep->title    = _('DELIVERY NOTE');
          $rep->filename = "Delivery" . $myrow['reference'] . ".pdf";
        } else {
          $rep->title    = _('PACKING SLIP');
          $rep->filename = "Packing_slip" . $myrow['reference'] . ".pdf";
        }
        $rep->Info($params, $cols, null, $aligns);
      } else {
        $rep->title = _('DELIVERY NOTE');
      }
      $rep->Header2($myrow, $branch, $sales_order, '', ST_CUSTDELIVERY);
      $result   = Debtor_TransDetail::get(ST_CUSTDELIVERY, $i);
      $SubTotal = 0;
      while ($myrow2 = DB::fetch($result)) {
        if ($myrow2["quantity"] == 0) {
          continue;
        }
        $DisplayPrice = Num::format($myrow2["unit_price"], $dec);
        $DisplayQty   = Num::format($myrow2["quantity"], Item::qty_dec($myrow2['stock_id']));
        $rep->TextCol(0, 1, $myrow2['stock_id'], -2);
        $oldrow = $rep->row;
        $rep->TextColLines(1, 2, $myrow2['StockDescription'], -2);
        $newrow   = $rep->row;
        $rep->row = $oldrow;
        $rep->TextCol(2, 3, $DisplayQty, -2);
        $rep->row = $newrow;
        //$rep->NewLine(1);
        if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight)) {
          $rep->Header2($myrow, $branch, $sales_order, '', ST_CUSTDELIVERY);
        }
      }
      $comments = DB_Comments::get(ST_CUSTDELIVERY, $i);
      if ($comments && DB::num_rows($comments)) {
        $rep->NewLine();
        while ($comment = DB::fetch($comments)) {
          $rep->TextColLines(0, 6, $comment['memo_'], -2);
        }
      }
      $display_sub_total = Num::format($SubTotal, $dec);
      $display_freight   = Num::format($myrow["ov_freight"], $dec);
      $rep->row          = $rep->bottomMargin + (15 * $rep->lineHeight);
      $linetype          = true;
      $doctype           = ST_CUSTDELIVERY;
      if ($rep->currency != $myrow['curr_code']) {
        include(REPORTS_PATH . 'includes' . DS . 'doctext2.php');
      } else {
        include(REPORTS_PATH . 'includes' . DS . 'doctext.php');
      }
      if ($email == 1) {
        if ($myrow['email'] == '') {
          $myrow['email']      = $branch['email'];
          $myrow['DebtorName'] = $branch['br_name'];
        }
        $rep->End($email, $doc_Delivery_no . " " . $myrow['reference'], $myrow, ST_CUSTDELIVERY);
      }
    }
    if ($email == 0) {
      $rep->End();
    }
  }

