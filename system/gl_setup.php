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
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

Page::start(_($help_context = "System and General GL Setup"), SA_GLSETUP);

	if (isset($_POST['submit']) && can_process()) {
		$_POST['allow_negative_stock'] = check_value('allow_negative_stock');
		$_POST['po_over_receive'] = Validation::input_num('po_over_receive');
		$_POST['po_over_charge'] = Validation::input_num('po_over_charge');
		$_POST['accumulate_shipping'] = check_value('accumulate_shipping');
		DB_Company::update_gl_setup($_POST);
		Event::success(_("The general GL setup has been updated."));
	} /* end of if submit */
	start_form();
	//start_outer_table("class='tablestyle'");
	start_outer_table('tablestyle2');
	table_section(1);
	$myrow = DB_Company::get_prefs();
	$_POST['retained_earnings_act'] = $myrow["retained_earnings_act"];
	$_POST['profit_loss_year_act'] = $myrow["profit_loss_year_act"];
	$_POST['debtors_act'] = $myrow["debtors_act"];
	$_POST['creditors_act'] = $myrow["creditors_act"];
	$_POST['freight_act'] = $myrow["freight_act"];
	$_POST['pyt_discount_act'] = $myrow["pyt_discount_act"];
	$_POST['exchange_diff_act'] = $myrow["exchange_diff_act"];
	$_POST['bank_charge_act'] = $myrow["bank_charge_act"];
	$_POST['default_sales_act'] = $myrow["default_sales_act"];
	$_POST['default_sales_discount_act'] = $myrow["default_sales_discount_act"];
	$_POST['default_prompt_payment_act'] = $myrow["default_prompt_payment_act"];
	$_POST['default_inventory_act'] = $myrow["default_inventory_act"];
	$_POST['default_credit_limit'] = $myrow["default_credit_limit"];
	$_POST['default_cogs_act'] = $myrow["default_cogs_act"];
	$_POST['default_adj_act'] = $myrow["default_adj_act"];
	$_POST['default_inv_sales_act'] = $myrow['default_inv_sales_act'];
	$_POST['default_assembly_act'] = $myrow['default_assembly_act'];
	$_POST['allow_negative_stock'] = $myrow['allow_negative_stock'];
	$_POST['po_over_receive'] = Num::percent_format($myrow['po_over_receive']);
	$_POST['po_over_charge'] = Num::percent_format($myrow['po_over_charge']);
	$_POST['past_due_days'] = $myrow['past_due_days'];
	$_POST['default_credit_limit'] = $myrow['default_credit_limit'];
	$_POST['legal_text'] = $myrow['legal_text'];
	$_POST['accumulate_shipping'] = $myrow['accumulate_shipping'];
	$_POST['default_workorder_required'] = $myrow['default_workorder_required'];
	$_POST['default_dim_required'] = $myrow['default_dim_required'];
	$_POST['default_delivery_required'] = $myrow['default_delivery_required'];
	table_section_title(_("General GL"));
	// Not used in FA2.0.
	//GL_UI::all_row(_("Retained Earning Clearing Account:"), 'retained_earnings_act', $_POST['retained_earnings_act']);
	// Not used in FA2.0.
	//GL_UI::all_row(_("Payroll Account:"), 'payroll_act', $_POST['payroll_act']);
	text_row(_("Past Due Days Interval:"), 'past_due_days', $_POST['past_due_days'], 6, 6, '', "", _("days"));
	GL_UI::all_row(_("Retained Earnings:"), 'retained_earnings_act', $_POST['retained_earnings_act']);
	GL_UI::all_row(_("Profit/Loss Year:"), 'profit_loss_year_act', $_POST['profit_loss_year_act']);
	GL_UI::all_row(_("Exchange Variances Account:"), 'exchange_diff_act', $_POST['exchange_diff_act']);
	GL_UI::all_row(_("Bank Charges Account:"), 'bank_charge_act', $_POST['bank_charge_act']);
	table_section_title(_("Customers and Sales"));
	text_row(_("Default Credit Limit:"), 'default_credit_limit', $_POST['default_credit_limit'], 12, 12);
	check_row(_("Accumulate batch shipping:"), 'accumulate_shipping', null);
	textarea_row(_("Legal Text on Invoice:"), 'legal_text', $_POST['legal_text'], 32, 3);
	GL_UI::all_row(_("Shipping Charged Account:"), 'freight_act', $_POST['freight_act']);
	table_section_title(_("Customers and Sales Defaults"));
	// default for customer branch
	GL_UI::all_row(_("Receivable Account:"), 'debtors_act');
	GL_UI::all_row(_("Sales Account:"), 'default_sales_act', null, false, false, true);
	GL_UI::all_row(_("Sales Discount Account:"), 'default_sales_discount_act');
	GL_UI::all_row(_("Prompt Payment Discount Account:"), 'default_prompt_payment_act');
	text_row(_("Delivery Required By:"), 'default_delivery_required', $_POST['default_delivery_required'], 6, 6, '', "", _("days"));
	table_section(2);
	table_section_title(_("Dimension Defaults"));
	text_row(_("Dimension Required By After:"), 'default_dim_required', $_POST['default_dim_required'], 6, 6, '', "", _("days"));
	table_section_title(_("Suppliers and Purchasing"));
	percent_row(_("Delivery Over-Receive Allowance:"), 'po_over_receive');
	percent_row(_("Invoice Over-Charge Allowance:"), 'po_over_charge');
	table_section_title(_("Suppliers and Purchasing Defaults"));
	GL_UI::all_row(_("Payable Account:"), 'creditors_act', $_POST['creditors_act']);
	GL_UI::all_row(_("Purchase Discount Account:"), 'pyt_discount_act', $_POST['pyt_discount_act']);
	table_section_title(_("Inventory"));
	check_row(_("Allow Negative Inventory:"), 'allow_negative_stock', null);
	label_row(null, _("Warning: This may cause a delay in GL postings"), "", "class='stockmankofg' colspan=2");
	table_section_title(_("Items Defaults"));
	GL_UI::all_row(_("Sales Account:"), 'default_inv_sales_act', $_POST['default_inv_sales_act']);
	GL_UI::all_row(_("Inventory Account:"), 'default_inventory_act', $_POST['default_inventory_act']);
	// this one is default for items and suppliers (purchase account)
	GL_UI::all_row(_("C.O.G.S. Account:"), 'default_cogs_act', $_POST['default_cogs_act']);
	GL_UI::all_row(_("Inventory Adjustments Account:"), 'default_adj_act', $_POST['default_adj_act']);
	GL_UI::all_row(_("Item Assembly Costs Account:"), 'default_assembly_act', $_POST['default_assembly_act']);
	table_section_title(_("Manufacturing Defaults"));
	text_row(_("Work Order Required By After:"), 'default_workorder_required', $_POST['default_workorder_required'], 6, 6, '', "", _("days"));
	end_outer_table(1);
	submit_center('submit', _("Update"), true, '', 'default');
	end_form(2);
	Page::end();
	function can_process() {
			if (!Validation::is_num('po_over_receive', 0, 100)) {
				Event::error(_("The delivery over-receive allowance must be between 0 and 100."));
				JS::set_focus('po_over_receive');
				return false;
			}
			if (!Validation::is_num('po_over_charge', 0, 100)) {
				Event::error(_("The invoice over-charge allowance must be between 0 and 100."));
				JS::set_focus('po_over_charge');
				return false;
			}
			if (!Validation::is_num('past_due_days', 0, 100)) {
				Event::error(_("The past due days interval allowance must be between 0 and 100."));
				JS::set_focus('past_due_days');
				return false;
			}
			return true;
		}

?>
