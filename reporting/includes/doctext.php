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
	if (!isset($doctype)) {
		$doctype = 0;
	}
	if (isset($header2type)) {
		$doc_Cust_no = _("Cust no");
		$doc_Date = _("Date");
		$doc_customer_id = "Customer ID";
		if ($doctype == ST_PURCHORDER || $doctype == ST_SUPPAYMENT) { // Purchase Order
			$doc_Charge_To = _("Order To");
			if ($doctype == ST_PURCHORDER) {
				$doc_Delivered_To = _("Deliver To");
			} else {
				$doc_Delivered_To = _("Charge To");
			}
		} else {
			if ($doctype == ST_CUSTPAYMENT) {
				$doc_Charge_To = _("Charged To");
			} elseif ($doctype == ST_CUSTREFUND) {
				$doc_Charge_To = _("Refunded To");
			} else {
				$doc_Charge_To = _("Charge To");
			}
			$doc_Delivered_To = _("Delivered To");
		}
		$doc_Shipping_Company = _("Shipping Company");
		if ($doctype == ST_SALESQUOTE) {
			$doc_Due_Date = _("Valid until");
		} elseif ($doctype == ST_SALESORDER) {
			$doc_Due_Date = _("Delivery Date");
		} else {
			$doc_Due_Date = _("Due Date");
		}
		$doc_Your_Ref = _("Your Ref");
		if ($doctype == ST_WORKORDER) {
			$doc_Our_Ref = _("Type");
			$doc_Your_TAX_no = _("Manufactured Item");
			$doc_Payment_Terms = _("Required By");
			$doc_Customers_Ref = _("Reference");
			$doc_Our_Order_No = _("Into Location");
			$doc_Due_Date = _("Quantity");
		} else {
			//	if ($doctype == ST_SUPPAYMENT || $doctype == ST_CUSTPAYMENT)
			//			$doc_Our_Ref = _("Type");
			$doc_Our_Ref = "Contact";
			# __ADVANCEDEDIT__ BEGIN #
			if ($doctype == ST_PURCHORDER) {
				$doc_Customers_Ref = "Acount Number";
				$doc_Our_Ref = "";
				$doc_Your_TAX_no = "Phone";
				$doc_Our_Order_No = "Fax";
				$doc_Due_Date = "";
			} elseif ($doctype == ST_PROFORMA) {
				$doc_Customers_Ref = "";
				$doc_Our_Ref = "Contact";
				$doc_Your_TAX_no = "";
				$doc_Our_Order_No = "";
				$doc_Due_Date = "Due Date";
				$doc_Payment_Terms = _("Payment Terms");
			} elseif ($doctype == ST_STATEMENT) {
				$doc_Date="Statement Date:";
				$doc_Customers_Ref = "";
				$doc_Our_Ref = "Customer ID";
				$doc_Your_TAX_no = "Phone";
				$doc_Our_Order_No = "Fax";
				$doc_Due_Date = "";
				$doc_Payment_Terms = "Payment Terms";
			} elseif ($doctype == ST_CUSTDELIVERY) {
				$doc_Customers_Ref = "Purchase Order#";
				$doc_Payment_Terms = "";
				$doc_Your_TAX_no = "Phone";
				$doc_Our_Order_No = "Order No:";
			} else {
				$doc_Customers_Ref = ($doctype == ST_SALESQUOTE || $doctype == ST_STATEMENT) ? "" : "Purchase Order#";
				$doc_Our_Ref = "Contact";
				$doc_Payment_Terms = "Payment Terms";
				$doc_customer_id = "Customer ID";
				$doc_Your_TAX_no = "Phone";
				$doc_Our_Order_No = "Fax";
			}
			# __ADVANCEDEDIT__ END #
		}
		$doc_Our_TAX_no = _("Our ABN No.");
		//	$doc_Suburb = _("Suburb");
		$doc_Extra = "";
		if ($doctype == ST_CUSTDELIVERY || $doctype == ST_SALESQUOTE || $doctype == ST_PURCHORDER || $doctype == ST_SALESORDER || $doctype == ST_SUPPAYMENT || $doctype == ST_CUSTPAYMENT || $doctype == ST_CUSTREFUND
		) {
			if ($doctype == ST_CUSTPAYMENT) {
				$doc_Extra = _("* Subject to Realisation of the Cheque.");
			}
			$doc_Bank_Account = '';
			$doc_Please_Quote = _("All amounts stated in");
		} else {
			$doc_Bank_Account = _("Bank Account");
			$doc_Please_Quote = $doctype == ST_SALESINVOICE ? _("Please quote Invoice no. when paying. All amounts stated in") : _("Please quote Credit no. when paying. All amounts stated in");
		}
		$doc_Address = _("Address");
		$doc_Phone_Fax_Email = _("Phone/Fax/Email");
		$doc_Bank = _("Bank");
		$doc_Payment_Link = _("You can pay through");
		if ($doctype == ST_SALESQUOTE || $doctype == ST_PURCHORDER || $doctype == ST_SALESORDER || $doctype == ST_SALESINVOICE || $doctype == ST_CUSTCREDIT || $doctype == ST_CUSTDELIVERY || $doctype == ST_PROFORMA || $doctype == ST_WORKORDER || $doctype == ST_SUPPAYMENT || $doctype == ST_CUSTPAYMENT || $doctype == ST_CUSTREFUND
		) {
			if ($doctype == ST_SALESQUOTE) {
				$this->title = _("QUOTATION");
			} elseif ($doctype == ST_PURCHORDER) {
				$this->title = _("PURCHASE ORDER");
			} elseif ($doctype == ST_CUSTDELIVERY) {
				$this->title = ($packing_slip == 1 ? _("PACKING SLIP") : _("DELIVERY NOTE"));
			} elseif ($doctype == ST_SALESORDER) {
				$this->title = ($print_as_quote == 1 ? _("QUOTE") : _("ORDER"));
			} elseif ($doctype == ST_SALESINVOICE) {
				$this->title = _("TAX INVOICE");
			} elseif ($doctype == ST_WORKORDER) {
				$this->title = _("WORK ORDER");
			} elseif ($doctype == ST_SUPPAYMENT) {
				$this->title = _("REMITTANCE");
			} elseif ($doctype == ST_CUSTPAYMENT) {
				$this->title = _("RECEIPT");
			} elseif ($doctype == ST_CUSTREFUND) {
				$this->title = _("REFUND");
			} else {
				$this->title = _("CREDIT NOTE");
			}
			if (isset($isproforma) && $isproforma) {
				$this->title = _("PROFORMA INVOICE");
			}
			if ($doctype == ST_PURCHORDER) {
				$this->headers = array(_("Item Code"), _("Item Description"), '', _("Quantity"), _("Unit"), _("Price"), _("Total"));
			} elseif ($doctype == ST_WORKORDER) {
				$this->headers = array(
					_("Item Code"), _("Item Description"), _("From Location"), _("Work Centre"), _("Unit Quantity"), _("Total Quantity"), _("Units Issued"));
			} elseif ($doctype == ST_SUPPAYMENT || $doctype == ST_CUSTPAYMENT || $doctype == ST_CUSTREFUND) {
				$this->headers = array(
					_("Trans Type"), _("#"), _("Date"), _("Due Date"), _("Total Amount"), _("Left to Allocate"), _("This Allocation"));
			} elseif ($doctype == ST_CUSTDELIVERY) {
				$this->headers = array(_("Item Code"), _("Item Description"), _("Qty"));
			} else {
				$this->headers = array(_("Item Code"), _("Item Description"), _("Qty"), _("Unit"), _("Price"), _("Disc.%"), _("Tax"), _("Total"));
			}
		} else if ($doctype == ST_STATEMENT) {
			$this->title = _("STATEMENT");
			$this->headers = array(_("Transaction"), _("Invoice"), _("PO#"), _("Date"), _("Due"), _("Debits"), _("Credits"),
														 _("Outstanding"), _("Balance"));
		}
	}
	if (isset($emailtype)) {
		$doc_Dear_Sirs = _("Dear");
		$doc_AttachedFile = _("Attached you will find ");
		$doc_Kindest_regards = _("Kindest regards");
		$doc_Payment_Link = _("You can pay through");
	}
	if (isset($header2type) || isset($linetype)) {
		if (isset($header2type) || isset($linetype)) {
			switch ($doctype) {
				case ST_CUSTDELIVERY:
					$doc_Invoice_no = "Delivery Note No.";
					break;
				case ST_CUSTPAYMENT:
					$doc_Invoice_no = "Receipt No.";
					break;
				case ST_CUSTREFUND:
					$doc_Invoice_no = "Refund No.";
					break;
				case ST_SUPPAYMENT:
					$doc_Invoice_no = "Remittance No.";
					break;
				case ST_PURCHORDER:
					$doc_Invoice_no = "Purchase Order #:";
					break;
				case ST_SALESORDER:
					$doc_Invoice_no = "Order No.";
					break;
				case ST_SALESINVOICE:
					$doc_Invoice_no = "Invoice No.";
					break;
				case ST_SALESQUOTE:
					$doc_Invoice_no = "Quotation No.";
					break;
				case ST_WORKORDER:
					$doc_Invoice_no = "Work Order No.";
					break;
				case ST_CUSTCREDIT:
					$doc_Invoice_no = "Credit No.";
					break;
				default:
					$doc_Invoice_no = '';
			}
		}
		$doc_Delivery_no = _("Delivery Note No.");
		$doc_Order_no = _("Order No.");
	}
	if (isset($linetype)) {
		if ($doctype == ST_SALESQUOTE || $doctype == ST_PURCHORDER || $doctype == ST_SALESORDER || $doctype == ST_SALESINVOICE || $doctype == ST_CUSTCREDIT || $doctype == ST_CUSTDELIVERY
		) {
			$doc_Sub_total = _("Subtotal");
			$doc_Shipping = _("Shipping");
			$doc_Included = _("Included");
			$doc_Amount = _("Amount");
			$doc_TOTAL_INVOICE = $doctype == ST_SALESINVOICE ? _("TOTAL INVOICE") : _("TOTAL CREDIT");
			$doc_TOTAL_ORDER = _("TOTAL ORDER EX GST");
			$doc_TOTAL_ORDER2 = _("TOTAL ORDER GST INCL.");
			$doc_TOTAL_PO = _("TOTAL PO EX GST");
			$doc_TOTAL_DELIVERY = _("TOTAL DELIVERY INCL. GST");
		} elseif ($doctype == ST_SUPPAYMENT || ST_CUSTPAYMENT || $doctype == ST_CUSTREFUND) {
			$doc_Towards = _("As advance / full / part / payment towards:");
			$doc_by_Cheque = _("By Cash / Cheque* / Draft No.");
			$doc_Dated = _("Dated");
			$doc_Drawn = _("Drawn on Bank");
			$doc_Drawn_Branch = _("Branch");
			$doc_Received = _("Received / Sign");
			$doc_Total_Allocated = _("Total Allocated");
			$doc_Left_To_Allocate = _("Left to Allocate");
			if ($doctype == ST_CUSTPAYMENT) {
				$doc_Total_Payment = _("TOTAL RECEIPT");
			} elseif ($doctype == ST_CUSTREFUND) {
				$doc_Total_Payment = _("TOTAL REFUND");
			} else {
				$doc_Total_Payment = _("TOTAL REMITTANCE");
			}
		}
	}
	if ($doctype == ST_STATEMENT) {
		$doc_Outstanding = _("Outstanding Transactions");
		$doc_OpeningBalance= "Opening Balance";

		$doc_Current = _("Current");

		$doc_Total_Balance = _("Total Balance");
		$doc_Statement = _("Your Statement");
		$doc_Kindest_regards = _("Please pass this statement on to your accounts department.\n\nIf this is not the correct email address to send future statements to please respond to this email with the correct address.\n\nKindest regards");
		$doc_as_of = _("as of");
		$doc_Days = _("Days");
		$doc_Over = _("Over");
	}
