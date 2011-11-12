<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	 'SA_SALESTRANSVIEW' : 'SA_SALESBULKREP';
	// ----------------------------------------------------------------
	// $ Revision:	2.0 $
	// Creator:	Joe Hunt
	// date_:	2005-05-19
	// Title:	Purchase Orders
	// ----------------------------------------------------------------
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	//----------------------------------------------------------------------------------------------------
	print_receipts();
	//----------------------------------------------------------------------------------------------------
	function get_receipt($type, $trans_no)
	{
		$sql
		 = "SELECT debtor_trans.*,
				(debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
				debtor_trans.ov_freight_tax + debtor_trans.ov_discount) AS Total,
   				debtors_master.name AS DebtorName,  debtors_master.debtor_ref,
   				debtors_master.curr_code, debtors_master.payment_terms, debtors_master.tax_id AS tax_id,
   				debtors_master.email, debtors_master.address
    			FROM debtor_trans, debtors_master
				WHERE debtor_trans.debtor_no = debtors_master.debtor_no
				AND debtor_trans.type = " . DB::escape($type) . "
				AND debtor_trans.trans_no = " . DB::escape($trans_no);
		$result = DB::query($sql, "The remittance cannot be retrieved");
		if (DB::num_rows($result) == 0) {
			return false;
		}
		return DB::fetch($result);
	}

	function get_allocations_for_receipt($debtor_id, $type, $trans_no)
	{
		$sql = get_alloc_trans_sql("amt, trans.reference, trans.alloc", "trans.trans_no = alloc.trans_no_to
		AND trans.type = alloc.trans_type_to
		AND alloc.trans_no_from=$trans_no
		AND alloc.trans_type_from=$type
		AND trans.debtor_no=" . DB::escape($debtor_id),
			"cust_allocations as alloc");
		$sql .= " ORDER BY trans_no";
		return DB::query($sql, "Cannot retreive alloc to transactions");
	}

	function print_receipts()
	{
		global $systypes_array;
		include_once(APP_PATH . "includes/reports/pdf.php");
		$from = $_POST['PARAM_0'];
		$to = $_POST['PARAM_1'];
		$currency = $_POST['PARAM_2'];
		$comments = $_POST['PARAM_3'];
		if ($from == null) {
			$from = 0;
		}
		if ($to == null) {
			$to = 0;
		}
		$dec = User::price_dec();
		$fno = explode("-", $from);
		$tno = explode("-", $to);
		$cols = array(4, 85, 150, 225, 275, 360, 450, 515);
		// $headers in doctext.inc
		$aligns = array('left', 'left', 'left', 'left', 'right', 'right', 'right');
		$params = array('comments' => $comments);
		$cur = DB_Company::get_pref('curr_default');
		$rep = new FrontReport(_('RECEIPT'), "ReceiptBulk", User::pagesize());
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, null, $aligns);
		for ($i = $fno[0]; $i <= $tno[0]; $i++)
		{
			if ($fno[0] == $tno[0]) {
				$types = array($fno[1]);
			}
			else
			{
				$types = array(ST_BANKDEPOSIT, ST_CUSTPAYMENT, ST_CUSTREFUND, ST_CUSTDELIVERY);
			}
			foreach ($types as $j)
			{
				$myrow = get_receipt($j, $i);
				if (!$myrow) {
					continue;
				}
				$baccount = GL_BankAccount::get_default($myrow['curr_code']);
				$params['bankaccount'] = $baccount['id'];
				$rep->title = _('RECEIPT');
				$rep->Header2($myrow, null, $myrow, $baccount, ST_CUSTPAYMENT);
				$result = get_allocations_for_receipt($myrow['debtor_no'], $myrow['type'], $myrow['trans_no']);
				$linetype = true;
				$doctype = ST_CUSTPAYMENT;
				if ($rep->currency != $myrow['curr_code']) {
					include(APP_PATH . "reporting/includes/doctext2.php");
				} else {
					include(APP_PATH . "reporting/includes/doctext.php");
				}
				$total_allocated = 0;
				$rep->TextCol(0, 4, $doc_Towards, -2);
				$rep->NewLine(2);
				while ($myrow2 = DB::fetch($result))
				{
					$rep->TextCol(0, 1, $systypes_array[$myrow2['type']], -2);
					$rep->TextCol(1, 2, $myrow2['reference'], -2);
					$rep->TextCol(2, 3, Dates::sql2date($myrow2['tran_date']), -2);
					$rep->TextCol(3, 4, Dates::sql2date($myrow2['due_date']), -2);
					$rep->AmountCol(4, 5, $myrow2['Total'], $dec, -2);
					$rep->AmountCol(5, 6, $myrow2['Total'] - $myrow2['alloc'], $dec, -2);
					$rep->AmountCol(6, 7, $myrow2['amt'], $dec, -2);
					$total_allocated += $myrow2['amt'];
					$rep->NewLine(1);
					if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight)) {
						$rep->Header2($myrow, null, $myrow, $baccount, ST_CUSTPAYMENT);
					}
				}
				$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
				$rep->TextCol(3, 6, $doc_Total_Allocated, -2);
				$rep->AmountCol(6, 7, $total_allocated, $dec, -2);
				$rep->NewLine();
				$rep->TextCol(3, 6, $doc_Left_To_Allocate, -2);
				$rep->AmountCol(6, 7, $myrow['Total'] - $total_allocated, $dec, -2);
				$rep->NewLine();
				$rep->Font('bold');
				$rep->TextCol(3, 6, $doc_Total_Payment, -2);
				$rep->AmountCol(6, 7, $myrow['Total'], $dec, -2);
				$words = ui_view::price_in_words($myrow['Total'], ST_CUSTPAYMENT);
				if ($words != "") {
					$rep->NewLine(1);
					$rep->TextCol(0, 7, $myrow['curr_code'] . ": " . $words, -2);
				}
				$rep->Font();
				$rep->NewLine();
				$rep->TextCol(6, 7, $doc_Received, -2);
				$rep->NewLine();
				$rep->TextCol(0, 2, $doc_by_Cheque, -2);
				$rep->TextCol(2, 4, "______________________________", -2);
				$rep->TextCol(4, 5, $doc_Dated, -2);
				$rep->TextCol(5, 6, "__________________", -2);
				$rep->NewLine(1);
				$rep->TextCol(0, 2, $doc_Drawn, -2);
				$rep->TextCol(2, 4, "______________________________", -2);
				$rep->TextCol(4, 5, $doc_Drawn_Branch, -2);
				$rep->TextCol(5, 6, "__________________", -2);
				$rep->TextCol(6, 7, "__________________");
			}
		}
		$rep->End();
	}

?>