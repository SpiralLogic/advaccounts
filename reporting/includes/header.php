<?php

	/*   * ********************************************************************
		Copyright (C) FrontAccounting, LLC.
		Released under the terms of the GNU General Public License, GPL,
		as published by the Free Software Foundation, either version 3
		of the License, or (at your option) any later version.
		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
		See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
		* ********************************************************************* */
	// New version (without vertical lines)
	global $wo_types_array, $systypes_array;
	$this->row = $this->pageHeight - $this->topMargin;
	$upper = $this->row - 2 * $this->lineHeight;
	$lower = $this->bottomMargin + 8 * $this->lineHeight;
	$iline1 = $upper - 7.5 * $this->lineHeight;
	$iline2 = $iline1 - 8 * $this->lineHeight;
	$iline3 = $iline2 - 1.5 * $this->lineHeight;
	$iline4 = $iline3 - 1.5 * $this->lineHeight;
	$iline5 = $iline4 - 3 * $this->lineHeight;
	$iline6 = $iline5 - 1.5 * $this->lineHeight;
	$iline7 = $lower;
	$right = $this->pageWidth - $this->rightMargin;
	$width = ($right - $this->leftMargin) / 5;
	$icol = $this->pageWidth / 2;
	$ccol = $this->cols[0] + 4;
	$c2col = $ccol + 60;
	$ccol2 = $icol / 2;
	$mcol = $icol + 8;
	$mcol2 = $this->pageWidth - $ccol2;
	$cols = count($this->cols);
	$this->SetDrawColor(205, 205, 205);
	$this->Line($iline1, 3);
	$this->SetDrawColor(128, 128, 128);
	$this->Line($iline1);
	$this->rectangle($this->leftMargin, $iline2, $right - $this->leftMargin, $iline2 - $iline3, "F", null, array(222, 231, 236));
	$this->Line($iline2);
	$this->Line($iline3);
	$this->Line($iline4);
	$this->rectangle($this->leftMargin, $iline5, $right - $this->leftMargin, $iline5 - $iline6, "F", null, array(222, 231, 236));
	$this->Line($iline5);
	$this->Line($iline6);
	$this->Line($iline7);
	$this->LineTo($this->leftMargin, $iline2, $this->leftMargin, $iline4);
	$col = $this->leftMargin;
	for (
		$i = 0; $i < 5; $i++
	) {
		$this->LineTo($col += $width, $iline2, $col, $iline4);
	}
	$this->LineTo($right, $iline2, $right, $iline4);
	$this->LineTo($this->leftMargin, $iline5, $this->leftMargin, $iline7);
	$this->LineTo($this->cols[$cols - 2] + 4, $iline5, $this->cols[$cols - 2] + 4, $iline7);
	$this->LineTo($right, $iline5, $right, $iline7);
	$this->NewLine();
	if ($this->company['coy_logo'] != '') {
		$logo = COMPANY_PATH . "/images/" . $this->company['coy_logo'];
		$this->AddImage($logo, $ccol, $this->row, 0, 40);
	} else {
		$this->fontSize += 4;
		$this->Font('bold');
		$this->Text($ccol, $this->company['coy_name'], $icol);
		$this->Font();
		$this->fontSize -= 4;
	}
	//$this->SetTextColor(190, 190, 190);
	$this->fontSize += 10;
	$this->Font('bold');
	$this->TextWrap($mcol, $this->row, $this->pageWidth - $this->rightMargin - $mcol - 20, $this->title, 'right');
	$this->Font();
	$this->fontSize -= 10;
	$this->NewLine();
	$this->SetTextColor(0, 0, 0);
	$adrline = $this->row;
	$this->TextWrapLines($ccol, $icol, $this->company['postal_address']);
	#  __ADVANCEDEDIT__ BEGIN # new line under address
	$this->NewLine();
	#  __ADVANCEDEDIT__ END #
	$this->Font('italic');
	if (!isset($customer) && isset($myrow['debtor_no'])) {
		$customer = new Contacts_Customer($myrow['debtor_no']);
	}
	if (isset($branch['branch_code'])) {
		$currentBranch = $customer->branches[$branch['branch_code']];
		if (!isset($customer_branch_details)) {
			$customer_branch_details = $currentBranch;
		}
	}
	if ($this->company['phone'] != "") {
		$this->Text($ccol, _("Phone"), $c2col);
		$this->Text($c2col, $this->company['phone'], $mcol);
		$this->NewLine();
	}
	if ($this->company['fax'] != "") {
		$this->Text($ccol, _("Fax"), $c2col);
		$this->Text($c2col, $this->company['fax'], $mcol);
		$this->NewLine();
	}
	if ($this->company['email'] != "") {
		$this->Text($ccol, _("Email"), $c2col);
		$url = "mailto:" . $this->company['email'];
		$this->SetTextColor(0, 0, 255);
		$this->Text($c2col, $this->company['email'], $mcol);
		$this->SetTextColor(0, 0, 0);
		$this->addLink($url, $c2col, $this->row, $mcol, $this->row + $this->lineHeight);
		$this->NewLine();
	}
	if ($this->company['gst_no'] != "") {
		$this->Text($ccol, $doc_Our_VAT_no, $c2col);
		$this->Text($c2col, $this->company['gst_no'], $mcol);
		$this->NewLine();
	}
	if (($doctype == ST_SALESINVOICE || $doctype == ST_STATEMENT) && $this->company['domicile'] != "") {
		$this->Text($ccol, $doc_Domicile, $c2col);
		$this->Text($c2col, $this->company['domicile'], $mcol);
		$this->NewLine();
	}
	$this->Font();
	$this->row = $adrline;
	$this->NewLine(3);
	$this->Text($mcol + 100, $doc_Date);
	if ($doctype == ST_SALESQUOTE || $doctype == ST_PURCHORDER || $doctype == ST_SALESORDER) {
		$this->Text($mcol + 180, Dates::sql2date($myrow['ord_date']));
	} elseif ($doctype == ST_WORKORDER) {
		$this->Text($mcol + 180, Dates::sql2date($myrow['date_']));
	} else {
		$this->Text($mcol + 180, Dates::sql2date($myrow['tran_date']));
	}
	$this->NewLine();
	$this->Text($mcol + 100, $doc_Invoice_no);
	if ($doctype == ST_SALESQUOTE || $doctype == ST_PURCHORDER || $doctype == ST_SALESORDER) { // QUOTE, PO or SO
		if (Config::get('print_useinvoicenumber') == 1) {
			$this->Text($mcol + 180, $myrow['reference']);
		} else {
			$this->Text($mcol + 180, $myrow['order_no']);
		}
	}
	if ($doctype == ST_SALESQUOTE || $doctype == ST_PURCHORDER || $doctype == ST_SALESORDER) { // QUOTE, PO or SO
		if (Config::get('print_useinvoicenumber') == 1) {
			$this->Text($mcol + 180, $myrow['reference']);
		} else {
			$this->Text($mcol + 180, $myrow['order_no']);
		}
	} elseif ($doctype == ST_WORKORDER) {
		$this->Text($mcol + 180, $myrow['id']);
	} else {
		if (isset($myrow['trans_no']) && isset($myrow['reference'])) { // INV/CRE/STA
			if (Config::get('print_useinvoicenumber') == 1) {
				$this->Text($mcol + 180, $myrow['reference']);
			} else {
				$this->Text($mcol + 180, $myrow['order_no']);
			}
		}
	}
	if ($doctype != ST_STATEMENT) {
		$this->NewLine();
		$this->Text($mcol + 100, _('Salesperson:'));
		if ($doctype == ST_SALESINVOICE) {
			$id = $sales_order['salesman'];
		} else {
			$id = $myrow['salesman'];
		}
		$sql = "SELECT salesman_name  FROM salesman WHERE salesman_code='$id'";
		$result = DBOld::query($sql, "could not get sales person");
		$row = DBOld::fetch($result);
		if (empty($row['salesman_name'])) {
			$user = User::get()->name;
		} else {
			$user = $row['salesman_name'];
		}
		//$sql = "SELECT salesman_name  FROM sales_order WHERE salesman_code='$id'";
		//$result = DBOld::query($sql, "could not get sales person");
		//$row = DBOld::fetch($result);
		$this->Text($mcol + 180, $user);
		//$this->TextWrap($col, $this->row, $width, $row['salesman_name'], 'C');
		//$this->TextWrap($col, $this->row, $width, $_SESSION['current_user'], 'C');
	}
	if ($this->pageNumber > 1 && !strstr($this->filename, "Bulk")) {
		$this->Text($this->endLine - 35, _("Page") . ' ' . $this->pageNumber);
	}
	$this->row = $iline1 - $this->lineHeight;
	#  __ADVANCEDEDIT__ BEGIN # increase font size on order to: and delvier to:
	//		$this->fontSize -= 4;
	$this->Font('bold');
	$this->NewLine();
	$this->Text($ccol + 60, $doc_Charge_To . ':', $icol);
	$this->Text($mcol + 60, $doc_Delivered_To . ':');
	$this->Font('');
	//		$this->fontSize += 4;
	$this->row = $this->row - $this->lineHeight - 5;
	$temp = $this->row;
	$name = @$myrow['DebtorName'];
	if (isset($customer)) {
		$addr = $customer->accounts->getAddress();
	}
	if ($doctype == ST_SALESQUOTE || $doctype == ST_SALESORDER) {
		$name = $myrow['name'];
	} elseif ($doctype == ST_WORKORDER) {
		$name = $myrow['location_name'];
		$addr = $myrow['delivery_address'];
	} elseif ($doctype == ST_PURCHORDER || $doctype == ST_SUPPAYMENT) {
		$name = $myrow['supp_name'];
		$addr = $myrow['address'];
	}
	$this->Text($ccol + 60, $name, $icol);
	$this->NewLine();
	$this->TextWrapLines($ccol + 60, $icol - $ccol, $addr);
	$this->row = $temp;
	unset($name);
	if ($doctype != ST_SUPPAYMENT && $doctype != ST_STATEMENT && $doctype != ST_PURCHORDER && isset($sales_order['deliver_to'])) {
		$name = $sales_order['deliver_to'];
	} elseif (isset($customer->name)) {
		$name = $customer->name;
	}
	if ($doctype != ST_SUPPAYMENT && $doctype != ST_STATEMENT && isset($sales_order['delivery_address'])) {
		$addr = $sales_order['delivery_address'];
	} elseif (($doctype == ST_STATEMENT) && (!empty($currentBranch->br_address))) {
		$addr = $currentBranch->getAddress();
	}
	if (isset($name)) {
		$this->Text($mcol + 60, $name, $icol);
		$this->NewLine();
	}
	$this->TextWrapLines($mcol + 60, $this->rightMargin - $mcol, $addr);
	$this->row = $iline2 - $this->lineHeight - 1;
	$col = $this->leftMargin;
	$this->TextWrap($col, $this->row, $width, $doc_Customers_Ref, 'C');
	$col += $width;
	$this->TextWrap($col, $this->row, $width, $doc_Our_Ref, 'C');
	$col += $width;
	$this->TextWrap($col, $this->row, $width, $doc_Your_VAT_no, 'C');
	$col += $width;
	$this->TextWrap($col, $this->row, $width, $doc_Our_Order_No, 'C');
	$col += $width;
	$this->TextWrap($col, $this->row, $width, $doc_Due_Date, 'C');
	$this->row = $iline3 - $this->lineHeight - 1;
	$col = $this->leftMargin;
	if ($doctype == ST_PURCHORDER || $doctype == ST_SUPPAYMENT) {
		$this->TextWrap($col, $this->row, $width, $myrow['supp_account_no'], 'C');
	} elseif ($doctype == ST_WORKORDER) {
		$this->TextWrap($col, $this->row, $width, $myrow['wo_ref'], 'C');
	} elseif (isset($sales_order["customer_ref"])) {
		$this->TextWrap($col, $this->row, $width, $sales_order["customer_ref"], 'C');
	} elseif (isset($myrow["debtor_ref"])) {
		$this->TextWrap($col, $this->row, $width, $myrow["debtor_ref"], 'C');
	}
	$col += $width;
	$report_contact = (!empty($myrow['contact_name'])) ? $myrow['contact_name'] : $branch['contact_name'];
	if ($doctype == ST_PURCHORDER) {
		$id = $branch['salesman'];
		$sql = "SELECT salesman_name  FROM salesman WHERE salesman_code='$id'";
		$result = DBOld::query($sql, "could not get sales person");
		$row = DBOld::fetch($result);
		$this->TextWrap($col, $this->row, $width, $row['salesman_name'], 'C');
		$this->TextWrap($col, $this->row, $width, $_SESSION['current_user'], 'C');
	} #  __ADVANCEDEDIT__ END #
	elseif ($doctype == ST_SUPPAYMENT || $doctype == ST_CUSTPAYMENT || $doctype == ST_CUSTREFUND) {
		$this->TextWrap($col, $this->row, $width, $systypes_array[$myrow["type"]], 'C');
	} elseif ($doctype == ST_WORKORDER) {
		$this->TextWrap($col, $this->row, $width, $wo_types_array[$myrow["type"]], 'C');
	} elseif ($doctype == ST_SALESORDER || $doctype == ST_SALESQUOTE || $doctype == ST_SALESINVOICE) {
		$this->TextWrap($col, $this->row, $width, $report_contact, 'C');
	}
	$col += $width;
	$report_phone = (!empty($myrow["contact_phone"])) ? $myrow["contact_phone"] : ((isset($branch['phone'])) ? $branch['phone'] : ((isset($myrow['phone'])) ? $myrow['phone'] : ''));
	if ($doctype == ST_WORKORDER) {
		$this->TextWrap($col, $this->row, $width, $myrow["StockItemName"], 'C');
	} elseif ($doctype == ST_PURCHORDER) {
		$this->TextWrap($col, $this->row, $width, $report_phone, 'C');
	} elseif ($doctype == ST_STATEMENT) {
		$report_phone = $customer->accounts->phone;
	}
	$this->TextWrap($col, $this->row, $width, $report_phone, 'C');
	#  __ADVANCEDEDIT__ END #
	$col += $width;
	/*if ($doctype == ST_SALESINVOICE) {
		 $deliveries = Sales_Trans::get_parent(ST_SALESINVOICE, $myrow['trans_no']);
		 $line = "";	#  __ADVANCEDEDIT__ END # }

		 foreach ($deliveries as $delivery) {
			 if (Config::get('print_useinvoicenumber') == 0) {
				 $ref = Refs::get_reference(ST_CUSTDELIVERY, $delivery);
				 if ($ref) $delivery = $ref;
			 }
			 if ($line == "") $line .= "$delivery";
			 else $line .= ",$delivery";
		 }
		 $this->TextWrap($col, $this->row, $width, $line, 'C');
	 }
	 else*/
	if ($doctype == ST_CUSTDELIVERY) {
		$ref = $myrow['order_'];
		if (Config::get('print_useinvoicenumber') == 0) {
			$ref = Refs::get_reference(ST_SALESORDER, $myrow['order_']);
			if (!$ref) {
				$ref = $myrow['order_'];
			}
		}
		$this->TextWrap($col, $this->row, $width, $ref, 'C');
	} elseif ($doctype == ST_WORKORDER) {
		$this->TextWrap($col, $this->row, $width, $myrow["location_name"], 'C');
	} elseif ($doctype == ST_SALESQUOTE || $doctype == ST_SALESORDER || $doctype == ST_SALESINVOICE) {
		if (!empty($branch['fax'])) {
			$this->TextWrap($col, $this->row, $width, $branch['fax'], 'C');
		} elseif (isset($myrow['fax'])) {
			$this->TextWrap($col, $this->row, $width, $myrow['fax'], 'C');
		}
	} elseif ($doctype == ST_STATEMENT) {
		$this->TextWrap($col, $this->row, $width, $customer->accounts->fax, 'C');
	} elseif (isset($myrow['order_']) && $myrow['order_'] != 0) {
		$this->TextWrap($col, $this->row, $width, $myrow['order_'], 'C');
	} #  __ADVANCEDEDIT__ BEGIN # add supplier fax to PO
	elseif ($doctype == ST_PURCHORDER) {
		$this->TextWrap($col, $this->row, $width, $myrow["fax"], 'C');
	}
	#  __ADVANCEDEDIT__ END #
	$col += $width;
	if ($doctype == ST_SALESORDER || $doctype == ST_SALESQUOTE) {
		$this->TextWrap($col, $this->row, $width, Dates::sql2date($myrow['delivery_date']), 'C');
	} elseif ($doctype == ST_WORKORDER) {
		$this->TextWrap($col, $this->row, $width, $myrow["units_issued"], 'C');
	} elseif ($doctype != ST_PURCHORDER && $doctype != ST_CUSTCREDIT && $doctype != ST_CUSTPAYMENT && $doctype != ST_CUSTREFUND && $doctype != ST_SUPPAYMENT
	 && isset
	 ($myrow['due_date'])
	) {
		$this->TextWrap($col, $this->row, $width, Dates::sql2date($myrow['due_date']), 'C');
	}
	#  __ADVANCEDEDIT__ BEGIN # remove payment terms from purchase order
	if ($doctype == ST_STATEMENT && isset($currentBranch)) {
		$this->NewLine();
		$this->NewLine();
		$this->TextWrap($ccol, $this->row, $right - $ccol, "Email:  " . $customer->accounts->email);
	}
	if ((!isset($packing_slip) || $packing_slip == 0) && $doctype != ST_PURCHORDER && $doctype != ST_CUSTDELIVERY) {
		#  __ADVANCEDEDIT__ END #
		if ($doctype == ST_STATEMENT) {
			$this->NewLine();
		} else {
			$this->row -= (2 * $this->lineHeight);
		}
		if ($doctype == ST_WORKORDER) {
			$str = Dates::sql2date($myrow["required_by"]);
		} else {
			$id = $myrow['payment_terms'];
			$sql = "SELECT terms FROM payment_terms WHERE terms_indicator='$id'";
			$result = DBOld::query($sql, "could not get paymentterms");
			$row = DBOld::fetch($result);
			$str = $row["terms"];
		}
		$this->Font('italic');
		$this->TextWrap($ccol, $this->row, $right - $ccol, $doc_Payment_Terms . ":  " . $str . "         " . $doc_customer_id . ": " . $customer->id);
		$this->Font();
	}
	$this->row = $iline5 - $this->lineHeight - 1;
	$this->Font('bold');
	$count = count($this->headers);
	$this->cols[$count] = $right - 3;
	for (
		$i = 0; $i < $count; $i++
	) {
		$this->TextCol($i, $i + 1, $this->headers[$i], -2);
	}
	$this->Font();
	$this->Font('italic');
	$this->row = $iline7 - $this->lineHeight - 6;
	//if ($doctype != ST_WORKORDER)
	//    $this->TextWrap($ccol, $this->row, $right - $ccol, $doc_Please_Quote . ": " . $myrow['curr_code'], 'C');
	$this->row -= $this->lineHeight;
	#  __ADVANCEDEDIT__ BEGIN # remove bank details from purchaseo order
	if ($doctype == ST_STATEMENT) {
		$this->Font();
		$this->Font('bold');
		$this->TextWrap($ccol, $this->row, $right - $ccol, _("IMPORTANT PLEASE PASS THIS ON TO YOUR ACCOUNTS DEPARTMENT ASAP"), 'C');
		$this->row -= $this->lineHeight;
		$this->row -= $this->lineHeight;
	}
	$this->Font();
	$this->Font('italic');
	if (isset($bankaccount['bank_name']) && $doctype != ST_PURCHORDER && $doctype != ST_CUSTPAYMENT && $doctype != ST_CUSTREFUND) {
		$txt = "If you do not have an account, our terms are Pre payments only. All accounts are 30 days Cash, cheque, Visa, MasterCard, or Direct deposit";
		$this->TextWrap($ccol, $this->row, $right - $ccol, $txt, 'C');
		$this->row -= $this->lineHeight;
		$txt = $doc_Bank . ": " . $bankaccount['bank_name'] . " " . $doc_Bank_Account . ": " . $bankaccount['bank_account_number'];
		$this->TextWrap($ccol, $this->row, $right - $ccol, $txt, 'C');
		$this->row -= $this->lineHeight;
	}
	if ($doctype == ST_SALESINVOICE && $branch['disable_branch'] > 0) { // payment links
		if ($branch['disable_branch'] == 1) {
			$amt = number_format($myrow["ov_freight"] + $myrow["ov_gst"] + $myrow["ov_amount"], User::price_dec());
			$txt = $doc_Payment_Link . " PayPal: ";
			$name = urlencode($this->title . " " . $myrow['reference']);
			$url = "https://www.paypal.com/xclick/business=" . $this->company['email'] . "&item_name=" . $name . "&amount=" . $amt . "&currency_code=" . $myrow['curr_code'];
			$this->fontSize -= 2;
			$this->TextWrap($ccol, $this->row, $right - $ccol, $txt, 'C');
			$this->row -= $this->lineHeight;
			$this->SetTextColor(0, 0, 255);
			$this->TextWrap($ccol, $this->row, $right - $ccol, $url, 'C');
			$this->SetTextColor(0, 0, 0);
			$this->addLink($url, $ccol, $this->row, $this->pageWidth - $this->rightMargin, $this->row + $this->lineHeight);
			$this->fontSize += 2;
			$this->row -= $this->lineHeight;
		}
	}
	if ($doc_Extra != "") {
		$this->TextWrap($ccol, $this->row, $right - $ccol, $doc_Extra, 'C');
		$this->row -= $this->lineHeight;
	}
	if ($this->params['comments'] != '') {
		$this->TextWrap($ccol, $this->row, $right - $ccol, $this->params['comments'], 'C');
		$this->row -= $this->lineHeight;
	}
	#  __ADVANCEDEDIT__ BEGIN # added legal_text to quotations and orders and payments and receipts
	if (($doctype == ST_SALESINVOICE || $doctype == ST_STATEMENT || $doctype == ST_SALESQUOTE || $doctype == ST_SALESORDER) && $this->company['legal_text'] != "" || $doctype == ST_CUSTDELIVERY || $doctype == ST_CUSTPAYMENT || $doctype == ST_CUSTREFUND
	) {
		#  __ADVANCEDEDIT__ END #
		$this->TextWrapLines($ccol, $right - $ccol, $this->company['legal_text'], 'C');
	}
	$this->Font();
	$temp = $iline6 - $this->lineHeight - 2;
?>