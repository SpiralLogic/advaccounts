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
		$doc_Invoice_no = ($doctype == ST_CUSTDELIVERY ? "Delivery Note No." : ($doctype == ST_CUSTPAYMENT ? "Receipt No." : $doctype == ST_CUSTREFUND ? "Refund No." : ($doctype == ST_SUPPAYMENT
		 ? "Remittance No." : ($doctype == ST_PURCHORDER ? "Purchase Order #:" : ($doctype == ST_SALESORDER ? "Order No." : ($doctype == ST_SALESQUOTE ? "Quotation No."
			: ($doctype == ST_SALESINVOICE ? "Invoice No." : ($doctype == ST_WORKORDER ? "Work Order No." : "Credit No."))))))));
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
