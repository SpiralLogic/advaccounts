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

	$systypes_array = array(
		ST_JOURNAL => _("Journal Entry"),
		ST_BANKPAYMENT => _("Bank Payment"),
		ST_BANKDEPOSIT => _("Bank Deposit"),
		ST_BANKTRANSFER => _("Funds Transfer"),
		ST_SALESINVOICE => _("Sales&nbsp;Invoice"),
		ST_CUSTCREDIT => _("Customer Credit Note"),
		ST_DEPOSIT_GROUP => _("Group Deposit"),
		ST_CUSTPAYMENT => _("Payment"),
		ST_CUSTREFUND => _("Customer Refund"),
		ST_CUSTDELIVERY => _("Delivery&nbsp;Note"),
		ST_LOCTRANSFER => _("Location Transfer"),
		ST_INVADJUST => _("Inventory Adjustment"),
		ST_PURCHORDER => _("Purchase Order"),
		ST_SUPPINVOICE => _("Supplier Invoice"),
		ST_SUPPCREDIT => _("Supplier Credit Note"),
		ST_SUPPAYMENT => _("Supplier&nbsp;Payment"),
		ST_SUPPRECEIVE => _("Purchase Order Delivery"),
		ST_WORKORDER => _("Work Order"),
		ST_MANUISSUE => _("Work Order Issue"),
		ST_MANURECEIVE => _("Work Order Production"),
		ST_SALESORDER => _("Sales Order"),
		ST_SALESQUOTE => _("Sales Quotation"),
		ST_PROFORMA => _("Sales Proforma Invoice"),
		ST_COSTUPDATE => _("Cost Update"),
		ST_DIMENSION => _("Dimension")
	);
	$bank_account_types = array(
		BT_TRANSFER => _("Savings Account"),
		_("Chequing Account"),
		_("Credit Account"),
		_("Cash Account")
	);
	$bank_transfer_types = array(
		BT_TRANSFER => _("Transfer"),
		_("Cheque"),
		_("Credit"),
		_("Cash")
	);
	$payment_person_types = array(
		PT_MISC => _("Miscellaneous"),
		_("Work Order"),
		_("Customer"),
		_("Supplier"),
		_("Quick Entry")
	);
	$wo_types_array = array(
		WO_ASSEMBLY => _("Assemble"),
		WO_UNASSEMBLY => _("Unassemble"),
		WO_ADVANCED => _("Advanced Manufacture")
	);
	$wo_cost_types = array(
		WO_LABOUR => _("Labour Cost"),
		WO_OVERHEAD => _("Overhead Cost"),
	);
	$class_types = array(
		CL_ASSETS => _("Assets"),
		CL_LIABILITIES => _("Liabilities"),
		CL_EQUITY => _("Equity"),
		CL_INCOME => _("Income"),
		CL_COGS => _("Cost of Goods Sold"),
		CL_EXPENSE => _("Expense"),
	);
	$quick_actions = array(
		'=' => _('Remainder'), // post current base amount to GL account
		'a' => _('Amount'), // post amount to GL account
		'a+' => _('Amount, increase base'), // post amount to GL account and increase base
		'a-' => _('Amount, reduce base'), // post amount to GL account and reduce base
		'%' => _('% amount of base'), // store acc*amount% to GL account
		'%+' => _('% amount of base, increase base'), // ditto & increase base amount
		'%-' => _('% amount of base, reduce base'), // ditto & reduce base amount
		'T' => _('Taxes added'), // post taxes calculated on base amount
		'T+' => _('Taxes added, increase base'), // ditto & increase base amount
		'T-' => _('Taxes added, reduce base'), // ditto & reduce base amount
		't' => _('Taxes included'), // post taxes calculated on base amount
		't+' => _('Taxes included, increase base'), // ditto & increase base amount
		't-' => _('Taxes included, reduce base') // ditto & reduce base amount
	);
	$quick_entry_types = array(
		QE_DEPOSIT => _("Bank Deposit"),
		QE_PAYMENT => _("Bank Payment"),
		QE_JOURNAL => _("Journal Entry"),
		QE_SUPPINV => _("Supplier Invoice/Credit")
	);
	$stock_types = array(
		STOCK_MANUFACTURE => _("Manufactured"),
		STOCK_PURCHASED => _("Purchased"),
		STOCK_SERVICE => _("Service"),
		STOCK_INFO => _("Information")
	);
	$tag_types = array(
		TAG_ACCOUNT => _("Account"),
		TAG_DIMENSION => _("Dimension")
	);

	return array(
		'class_types' => $class_types,
		'quick_actions' => $quick_actions,
		'quick_entry_types' => $quick_entry_types,
		'stock_types' => $stock_types,
		'tag_types' => $tag_types,
		'systypes_array' => $systypes_array,
		'bank_account_types' => $bank_account_types,
		'bank_transfer_types' => $bank_transfer_types,
		'payment_person_types' => $payment_person_types,
		'wo_types_array' => $wo_types_array,
		'wo_cost_types' => $wo_cost_types
	);
