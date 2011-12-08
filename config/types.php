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
	global $bank_transfer_types, $class_types, $payment_person_types, $quick_actions, $quick_entry_types, $stock_types, $systypes_array, $tag_types, $wo_cost_types,
				 $wo_types_array;
	$bank_account_types = array(
		BT_TRANSFER => "Savings Account",
		"Chequing Account",
		"Credit Account",
		"Cash Account"
	);
	$bank_transfer_types = array(
		BT_TRANSFER => "Transfer",
		"Cheque",
		"Credit",
		"Cash"
	);
	$payment_person_types = array(
		PT_MISC => "Miscellaneous",
		"Work Order",
		"Customer",
		"Supplier",
		"Quick Entry"
	);
	$wo_types_array = array(
		WO_ASSEMBLY => "Assemble",
		WO_UNASSEMBLY => "Unassemble",
		WO_ADVANCED => "Advanced Manufacture"
	);
	$wo_cost_types = array(
		WO_LABOUR => "Labour Cost",
		WO_OVERHEAD => "Overhead Cost",
	);
	$class_types = array(
		CL_ASSETS => "Assets",
		CL_LIABILITIES => "Liabilities",
		CL_EQUITY => "Equity",
		CL_INCOME => "Income",
		CL_COGS => "Cost of Goods Sold",
		CL_EXPENSE => "Expense",
	);
	$quick_actions = array(
		'=' => 'Remainder', // post current base amount to GL account
		'a' => 'Amount', // post amount to GL account
		'a+' => 'Amount, increase base', // post amount to GL account and increase base
		'a-' => 'Amount, reduce base', // post amount to GL account and reduce base
		'%' => '% amount of base', // store acc*amount% to GL account
		'%+' => '% amount of base, increase base', // ditto & increase base amount
		'%-' => '% amount of base, reduce base', // ditto & reduce base amount
		'T' => 'Taxes added', // post taxes calculated on base amount
		'T+' => 'Taxes added, increase base', // ditto & increase base amount
		'T-' => 'Taxes added, reduce base', // ditto & reduce base amount
		't' => 'Taxes included', // post taxes calculated on base amount
		't+' => 'Taxes included, increase base', // ditto & increase base amount
		't-' => 'Taxes included, reduce base' // ditto & reduce base amount
	);
	$quick_entry_types = array(
		QE_DEPOSIT => "Bank Deposit",
		QE_PAYMENT => "Bank Payment",
		QE_JOURNAL => "Journal Entry",
		QE_SUPPINV => "Supplier Invoice/Credit"
	);
	$stock_types = array(
		STOCK_MANUFACTURE => "Manufactured",
		STOCK_PURCHASED => "Purchased",
		STOCK_SERVICE => "Service",
		STOCK_INFO => "Information"
	);
	$systypes_array = array(
		ST_JOURNAL => "Journal Entry",
		ST_BANKPAYMENT => "Bank Payment",
		ST_BANKDEPOSIT => "Bank Deposit",
		ST_BANKTRANSFER => "Funds Transfer",
		ST_SALESINVOICE => "Sales&nbsp;Invoice",
		ST_CUSTCREDIT => "Customer Credit Note",
		ST_DEPOSIT_GROUP => "Group Deposit",
		ST_CUSTPAYMENT => "Payment",
		ST_CUSTREFUND => "Customer Refund",
		ST_CUSTDELIVERY => "Delivery&nbsp;Note",
		ST_LOCTRANSFER => "Location Transfer",
		ST_INVADJUST => "Inventory Adjustment",
		ST_PURCHORDER => "Purchase Order",
		ST_SUPPINVOICE => "Supplier Invoice",
		ST_SUPPCREDIT => "Supplier Credit Note",
		ST_SUPPAYMENT => "Supplier&nbsp;Payment",
		ST_SUPPRECEIVE => "Purchase Order Delivery",
		ST_WORKORDER => "Work Order",
		ST_MANUISSUE => "Work Order Issue",
		ST_MANURECEIVE => "Work Order Production",
		ST_SALESORDER => "Sales Order",
		ST_SALESQUOTE => "Sales Quotation",
		ST_PROFORMA => "Sales Proforma Invoice",
		ST_COSTUPDATE => "Cost Update",
		ST_DIMENSION => "Dimension"
	);
	$tag_types = array(
		TAG_ACCOUNT => "Account",
		TAG_DIMENSION => "Dimension"
	);
	$wo_cost_types = array(
		WO_LABOUR => "Labour Cost",
		WO_OVERHEAD => "Overhead Cost",
	);
	$wo_types_array = array(
		WO_ASSEMBLY => "Assemble",
		WO_UNASSEMBLY => "Unassemble",
		WO_ADVANCED => "Advanced Manufacture"
	);
