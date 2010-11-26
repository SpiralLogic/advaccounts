<?php


/*     * ********************************************************************
		Copyright (C) FrontAccounting, LLC.
		Released under the terms of the GNU General Public License, GPL,
		as published by the Free Software Foundation, either version 3
		of the License, or (at your option) any later version.
		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
		See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
		* ********************************************************************* */

$page_security = 'SA_CUSTOMER';
$path_to_root = "..";
include_once("includes/contacts.inc");
add_js_ufile("includes/js/customers.js");
page(_($help_context = "Customers"), @$_REQUEST['popup']);

check_db_has_sales_types(_("There are no sales types defined. Please define at least one sales type before adding a customer."));
check_db_has_sales_people(_("There are no sales people defined in the system. At least one sales person is required before proceeding."));
check_db_has_sales_areas(_("There are no sales areas defined in the system. At least one sales area is required before proceeding."));
check_db_has_shippers(_("There are no shipping companies defined in the system. At least one shipping company is required before proceeding."));
check_db_has_tax_groups(_("There are no tax groups defined in the system. At least one tax group is required before proceeding."));

if (isset($_GET['debtor_no'])) {
	$customer = new Customer($_GET['debtor_no']);
} elseif (isset($_POST['id']) && !empty($_POST['id']) && !$_POST['submit']) {
	$customer = new Customer($_POST['id']);
} else {
	$customer = new Customer();
}

//--------------------------------------------------------------------------------------------

if (isset($_POST['submit'])) {
	handle_submit();
}
//--------------------------------------------------------------------------------------------
function handle_submit() {
	global $Ajax, $customer;

	if (!$customer->save($_POST)) {
		$status = $customer->getStatus();
		display_error($status['message']);
		set_focus($status['var']);
	} else {
		$status = $customer->getStatus();
		display_notification($status['message']);
	}
	$Ajax->activate('customers'); // in case of status change
	return $status['status'];
}
//--------------------------------------------------------------------------------------------
if (isset($_POST['delete'])) {
	$customer->delete();
	$status = $customer->getStatus();
	display_notification($status['message']);
	$Ajax->activate('_page_body');
	//the link to delete a selected record was clicked instead of the submit button
}
//--------------------------------------------------------------------------------------------

start_form();

if (db_has_customers()) {
	UI::divStart('search', null, array('style' => 'text-align:center; '));

	//customer_list_cells(_("Select a customer: "), 'customer_id', null, _('New customer'), true, check_value('show_inactive'));
	//	check_cells(_("Show inactive:"), 'show_inactive', null, true);
	UI::search('customers', 'Customer:', 80);
	if ($customer->id) {
		UI::button('submit', 'Update Customer', 'btnCustomer', 'submit', 'submit', 'ajaxsubmit', array('style' => 'margin:10px;'));
	} else {
		UI::button('submit', 'New Customer', 'btnCustomer', 'submit', 'submit', 'ajaxsubmit ui-helper-hidden', array('style' => 'margin:10px;'));
	}
	UI::button('cancel', 'Cancel', 'btnCancel', 'cancel', 'submit', 'ui-helper-hidden', array('style' => 'margin:10px;'));
	//submit('submit', _("Update Customer"), _('Update customer data'), @$_REQUEST['popup'] ? true : 'default');
	UI::divEnd();
}


//-------------------------------- Customer Details---------------------------------------------------
$menu = new MenuUi();
$menu->startTab('Details', 'Customer Details');
start_outer_table($table_style2, 5);


table_section(1);
hidden('id', $customer->id);
table_section_title(_("Name and Address"), 2, 'tableheader3');
text_row(_("Customer Name:"), 'name', $customer->name, 35, 80);
text_row(_("Customer Short Name:"), 'debtor_ref', $customer->debtor_ref, 35, 30);
textarea_row(_("Address:"), 'address', $customer->address, 35, 5);
email_row(_("E-mail:"), 'email', $customer->email, 35, 40);
text_row(_("GSTNo:"), 'taxId', $customer->taxId, 35, 40);

if (!$customer->id) {
	currencies_list_row(_("Customer's Currency:"), 'curr_code', $customer->curr_code);
} else {
	label_row(_("Customer's Currency:"), $customer->curr_code);
	hidden('curr_code', $customer->curr_code);
}

sales_types_list_row(_("Sales Type/Price List:"), 'sales_type', $customer->sales_type);
table_section(2);
table_section_title(_("Sales"), 2, 'tableheader3 ');
percent_row(_("Discount Percent:"), 'discount', $customer->discount);
percent_row(_("Prompt Payment Discount Percent:"), 'pymt_discount', $customer->pymt_discount);
amount_row(_("Credit Limit:"), 'credit_limit', $customer->credit_limit);
payment_terms_list_row(_("Pament Terms:"), 'payment_terms', $customer->payment_terms);
credit_status_list_row(_("Credit Status:"), 'credit_status', $customer->credit_status);
$dim = get_company_pref('use_dimension');

if ($dim >= 1) {
	dimensions_list_row(_("Dimension") . " 1:", 'dimension_id', $customer->dimension_id, true, " ", false, 1);
}
if ($dim > 1) {
	dimensions_list_row(_("Dimension") . " 2:", 'dimension2_id', $customer->dimension2_id, true, " ", false, 2);
}
if ($dim < 1) {
	hidden('dimension_id', 0);
}
if ($dim < 2) {
	hidden('dimension2_id', 0);
}

if ($customer->id) {
	start_row();
	echo '<td>' . _('Customer branches') . ':</td>';
	hyperlink_params_td($path_to_root . "/sales/manage/customer_branches.php", '<b>' . (@$_REQUEST['popup'] ? _("Select or &Add") : _("&Add or Edit ")) . '</b>',
			"debtor_no=" . $customer->id . (@$_REQUEST['popup'] ? '&popup=1' : ''));
	end_row();
}

textarea_row(_("General Notes:"), 'notes', $customer->notes, 35, 5);
record_status_list_row(_("Customer status:"), 'inactive');
end_outer_table(1);
$menu->endTab();

$menu->startTab('Accounts', 'Accounts');
start_outer_table($table_style2, 5);
table_section(1);
table_section_title(_("Accounts Details:"), 2, ' tableheader3 ');
text_row(_("Customer Name:"), 'acc_br_name', $customer->accounts->br_name, 40, 80);
text_row(_("Contact Person:"), 'acc_contact_name', $customer->accounts->contact_name, 40, 40);
textarea_row(_("Billing Address:"), 'acc_br_address', $customer->accounts->br_address, 35, 5);
email_row(_("E-mail:"), 'acc_email', $customer->accounts->email, 40, 40);
text_row(_("Phone Number:"), 'acc_phone', $customer->accounts->phone, 40, 30);
text_row(_("Secondary Phone Number:"), 'acc_phone2',$customer->accounts->phone2, 40, 30);
text_row(_("Fax Number:"), 'acc_fax', $customer->accounts->fax, 40, 30);
table_section(2,false,'ui-widget');


//table_section_title("<span class='ui-icon ui-icon-circle-plus'>"._("Contact log:")."</span>", 2, 'tableheader3');
table_section_title(_("Contact log:"), 2, 'tableheader3 ');
start_row();
UI::tdStart(null, array('class'=>'ui-widget-content center-content'));
UI::button('addLog',"Add log entry",'addLog');
UI::tdEnd();
end_row();
textarea_cells(null, null, null, 100, 30);
end_outer_table(1);

UI::divStart('contactLog', array('title' => 'New contact log entry', 'class' => 'ui-widget-overlay'));
UI::p('New log entry:', array('class' => 'validateTips'));
start_form();
start_table();
label_row('Date:', Now());
text_row('Contact:', 'acc_contact_name', $customer->accounts->contact_name, 40, 40);
textarea_row('Entry:', 'log_entry', '', 100, 10);
end_table();
end_form();
UI::p();
UI::divEnd();

$menu->endTab();

$menu->startTab('Branches', 'Branches');

UI::select('branchList',$customer->branches);

$currentBranch = $customer->branches[0];
start_outer_table($table_style2, 5);
table_section(1);
hidden('branch_code', $currentBranch->branch_code);
table_section_title(_("Name and Contact"));
text_row(_("Branch Name:"), 'br_name', $currentBranch->br_name, 35, 40);
text_row(_("Branch Short Name:"), 'br_ref', $currentBranch->branch_ref, 30, 30);
text_row(_("Contact Person:"), 'contact_name', $currentBranch->phone, 35, 40);
text_row(_("Phone Number:"), 'phone', $currentBranch->phone, 32, 30);
text_row(_("Secondary Phone Number:"), 'phone2', $currentBranch->phone2, 32, 30);
text_row(_("Fax Number:"), 'fax', $currentBranch->fax, 32, 30);
email_row(_("E-mail:"), 'email', $currentBranch->email, 35, 55);
table_section_title(_("Sales"));
sales_persons_list_row(_("Sales Person:"), 'salesman', $currentBranch->salesman);
sales_areas_list_row(_("Sales Area:"), 'area', $currentBranch->area);
sales_groups_list_row(_("Sales Group:"), 'group_no', $currentBranch->group_no, true);
locations_list_row(_("Default Inventory Location:"), 'default_location', $currentBranch->default_location);
shippers_list_row(_("Default Shipping Company:"), 'default_ship_via', $currentBranch->default_ship_via);
tax_groups_list_row(_("Tax Group:"), 'tax_group_id', $currentBranch->tax_group_id);
yesno_list_row(_("Disable this Branch:"), 'disable_trans', $currentBranch->disable_trans);
table_section(2);
table_section_title(_("GL Accounts"));
// 2006-06-14. Changed gl_al_accounts_list to have an optional all_option 'Use Item Sales Accounts'
gl_all_accounts_list_row(_("Sales Account:"), 'sales_account', $currentBranch->sales_account, false, false, true);
gl_all_accounts_list_row(_("Sales Discount Account:"), 'sales_discount_account');
gl_all_accounts_list_row(_("Accounts Receivable Account:"), 'receivables_account');
gl_all_accounts_list_row(_("Prompt Payment Discount Account:"), 'payment_discount_account');
table_section_title(_("Addresses"));
textarea_row(_("Mailing Address:"), 'br_post_address', $currentBranch->br_post_address, 35, 4);
textarea_row(_("Billing Address:"), 'br_address', $currentBranch->br_address, 35, 4);
textarea_row(_("General Notes:"), 'notes', $currentBranch->notes, 35, 4);
end_outer_table(1);
end_form();
$menu->endTab();

$menu->startTab('Invoices','Invoices');
$menu->endTab();

$menu->render();

//$('#contactLog').dialog({width:700, modal:true,buttons: {Ok: function() {$( this ).dialog( 'close' );},'Cancel': function() {$( this ).dialog( 'close' );}}});;

/*
	   div_start('controls');
	   if ($customer->id==0) {
		   submit_center('submit', _("Add New Customer"), true, '', 'default');
	   } else {
		   submit_center_first('submit', _("Update Customer"), _('Update customer data'), @$_REQUEST['popup'] ? true : 'default');
		   submit_return('select', get_post('id'), _("Select this customer and return to document entry."));
		   submit_center_last('delete', _("Delete Customer"), _('Delete customer data if have been never used'), true);
	   }
	   div_end();*/

hidden('popup', @$_REQUEST['popup']);
end_form();
end_page(true, true);