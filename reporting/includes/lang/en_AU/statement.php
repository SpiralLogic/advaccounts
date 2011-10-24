<?php
	if (isset($header2type)) {
		$doc_Cust_no = "Cust no";
		$doc_Date = "Date";
		$doc_Charge_To = "Charge To";
		$doc_Delivered_To = "Delivered To";
		$doc_Shipping_Company = "Shipping Company";
		$doc_Due_Date = "Due Date";
		$doc_Your_Ref = "Your Ref";
		$doc_Our_Ref = "Contact";
		$doc_Your_VAT_no = "Your VAT no.";
		$doc_Payment_Terms = "Payment Terms";
		$doc_Customers_Ref = "Customers Reference";
		$doc_Our_Order_No = "Our Order No";
		$doc_Extra = "";
		$doc_Our_VAT_no = "Our VAT No.";
		$doc_Domicile = "Domicile";
		$doc_Please_Quote = "Please quote " . "Credit" . " no. when paying. All amounts stated in";
		$doc_Bank_Account = "Bank Account";
		$doc_Address = "Address";
		$doc_Phone_Fax_Email = "Phone/Fax/Email";
		$doc_Bank = "Bank";
		$doc_Payment_Link = "You can pay through";
		$this->title = "STATEMENT";
		$this->headers = array('Trans Type', '#', 'Date', 'Due Date', 'Charges', 'Credits', 'Allocated', 'Outstanding');
	}
	if (isset($emailtype)) {
		$doc_Dear_Sirs = "Dear";
		$doc_AttachedFile = "Attached you will find ";
		$doc_Kindest_regards = "Kindest regards";
		$doc_Payment_Link = "You can pay through";
	}
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

		$doc_Delivery_no = "Delivery Note No.";
		$doc_Order_no = "Order No.";
	}
	if (isset($linetype)) {
		$doc_Outstanding = "Outstanding Transactions";
		$doc_Current = "Current";
		$doc_Total_Balance = "Total Balance";
		$doc_Statement = "Statement";
		$doc_as_of = "as of";
		$doc_Days = "Days";
		$doc_Over = "Over";
	}
