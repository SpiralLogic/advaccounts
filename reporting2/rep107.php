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
// Title:	Print Invoices
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

//----------------------------------------------------------------------------------------------------

print_invoices();

//----------------------------------------------------------------------------------------------------

function print_invoices()
{
	global $path_to_root;
	
	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
	$email = $_POST['PARAM_3'];
	$paylink = $_POST['PARAM_4'];
	$comments = $_POST['PARAM_5'];

	if ($from == null)
		$from = 0;
	if ($to == null)
		$to = 0;
	$dec = user_price_dec();

 	$fno = explode("-", $from);
	$tno = explode("-", $to);

	$cols = array(4, 60, 225, 300, 325, 385, 450, 515);

	// $headers in doctext.inc
	$aligns = array('left',	'left',	'right', 'left', 'right', 'right', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
	{
		$rep = new FrontReport(_('INVOICE'), "InvoiceBulk", user_pagesize());
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, null, $aligns);
	}

	for ($i = $fno[0]; $i <= $tno[0]; $i++)
	{
		for ($j = ST_SALESINVOICE; $j <= ST_CUSTCREDIT; $j++)
		{
			if (isset($_POST['PARAM_6']) && $_POST['PARAM_6'] != $j)
				continue;
			if (!exists_customer_trans($j, $i))
				continue;
			$sign = $j==ST_SALESINVOICE ? 1 : -1;
			$myrow = get_customer_trans($i, $j);
			$baccount = get_default_bank_account($myrow['curr_code']);
			$params['bankaccount'] = $baccount['id'];

			$branch = get_branch($myrow["branch_code"]);
			$branch['disable_branch'] = $paylink; // helper
			if ($j == ST_SALESINVOICE)
				$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER);
			else
				$sales_order = null;
			if ($email == 1)
			{
				$rep = new FrontReport("", "", user_pagesize());
				$rep->currency = $cur;
				$rep->Font();
				if ($j == ST_SALESINVOICE)
				{
					$rep->title = _('INVOICE');
					$rep->filename = "Invoice" . $myrow['reference'] . ".pdf";
				}
				else
				{
					$rep->title = _('CREDIT NOTE');
					$rep->filename = "CreditNote" . $myrow['reference'] . ".pdf";
				}
				$rep->Info($params, $cols, null, $aligns);
			}
			else
				$rep->title = ($j == ST_SALESINVOICE) ? _('INVOICE') : _('CREDIT NOTE');
			$rep->Header2($myrow, $branch, $sales_order, $baccount, $j);

   			$result = get_customer_trans_details($j, $i);
			$SubTotal = 0;
			while ($myrow2=db_fetch($result))
			{
				if ($myrow2["quantity"] == 0)
					continue;
					
				$Net = round2($sign * ((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
				   user_price_dec());
				$SubTotal += $Net;
	    		$DisplayPrice = number_format2($myrow2["unit_price"],$dec);
	    		$DisplayQty = number_format2($sign*$myrow2["quantity"],get_qty_dec($myrow2['stock_id']));
	    		$DisplayNet = number_format2($Net,$dec);
	    		if ($myrow2["discount_percent"]==0)
		  			$DisplayDiscount ="";
	    		else
		  			$DisplayDiscount = number_format2($myrow2["discount_percent"]*100,user_percent_dec()) . "%";
				$rep->TextCol(0, 1,	$myrow2['stock_id'], -2);
				$oldrow = $rep->row;
				$rep->TextColLines(1, 2, $myrow2['StockDescription'], -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
				$rep->TextCol(2, 3,	$DisplayQty, -2);
				$rep->TextCol(3, 4,	$myrow2['units'], -2);
				$rep->TextCol(4, 5,	$DisplayPrice, -2);
				$rep->TextCol(5, 6,	$DisplayDiscount, -2);
				$rep->TextCol(6, 7,	$DisplayNet, -2);
				$rep->row = $newrow;
				//$rep->NewLine(1);
				if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
					$rep->Header2($myrow, $branch, $sales_order, $baccount,$j);
			}

			$comments = get_comments($j, $i);
			if ($comments && db_num_rows($comments))
			{
				$rep->NewLine();
    			while ($comment=db_fetch($comments))
    				$rep->TextColLines(0, 6, $comment['memo_'], -2);
			}

   			$DisplaySubTot = number_format2($SubTotal,$dec);
   			$DisplayFreight = number_format2($sign*$myrow["ov_freight"],$dec);

    		$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
			$linetype = true;
			$doctype = $j;
			if ($rep->currency != $myrow['curr_code'])
			{
				include($path_to_root . "/reporting/includes/doctext2.inc");
			}
			else
			{
				include($path_to_root . "/reporting/includes/doctext.inc");
			}

			$rep->TextCol(3, 6, $doc_Sub_total, -2);
			$rep->TextCol(6, 7,	$DisplaySubTot, -2);
			$rep->NewLine();
			$rep->TextCol(3, 6, $doc_Shipping, -2);
			$rep->TextCol(6, 7,	$DisplayFreight, -2);
			$rep->NewLine();
			$tax_items = get_trans_tax_details($j, $i);
    		while ($tax_item = db_fetch($tax_items))
    		{
    			$DisplayTax = number_format2($sign*$tax_item['amount'], $dec);

    			if ($tax_item['included_in_price'])
    			{
					$rep->TextCol(3, 7, $doc_Included . " " . $tax_item['tax_type_name'] .
						" (" . $tax_item['rate'] . "%) " . $doc_Amount . ": " . $DisplayTax, -2);
				}
    			else
    			{
					$rep->TextCol(3, 6, $tax_item['tax_type_name'] . " (" .
						$tax_item['rate'] . "%)", -2);
					$rep->TextCol(6, 7,	$DisplayTax, -2);
				}
				$rep->NewLine();
    		}
    		$rep->NewLine();
			$DisplayTotal = number_format2($sign*($myrow["ov_freight"] + $myrow["ov_gst"] +
				$myrow["ov_amount"]+$myrow["ov_freight_tax"]),$dec);
			$rep->Font('bold');
			$rep->TextCol(3, 6, $doc_TOTAL_INVOICE, - 2);
			$rep->TextCol(6, 7, $DisplayTotal, -2);
			$words = price_in_words($myrow['Total'], $j);
			if ($words != "")
			{
				$rep->NewLine(1);
				$rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, - 2);
			}	
			$rep->Font();
			if ($email == 1)
			{
				$myrow['dimension_id'] = $paylink; // helper for pmt link
				if ($myrow['email'] == '')
				{
					$myrow['email'] = $branch['email'];
					$myrow['DebtorName'] = $branch['br_name'];
				}
				$rep->End($email, $doc_Invoice_no . " " . $myrow['reference'], $myrow, $j);
			}
		}
	}
	if ($email == 0)
		$rep->End();
}

?>