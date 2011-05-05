<?php

$page_security = 'SA_CUSTOMER';
$path_to_root = "..";
include_once("includes/contacts.inc");
if (isAjaxReferrer()) {
	if (isset($_GET['term'])) {
		$data = Customer::search($_GET['term']);
	} elseif (isset($_POST['id'])) {
		if (isset($_POST['name'])) {
			$data['customer'] = $customer = new Customer($_POST);
			$data['customer']->save();
			$data['status'] = $customer->getStatus();
		} elseif (!isset($_POST['name'])) {
			$data['customer'] = $customer = new Customer($_POST['id']);
		}
		if ($_POST['id'] > 0) {
			$data['contact_log'] = contact_log::read($customer->id, 'C');
			$data['transactions'] = '<pre>' . print_r($customer->getTransactions(), true) . '</pre>';
		}
	} else {
		$data['customer'] = new Customer();
	}
	FB::info($_POST);
	FB::info($data);
	echo json_encode($data);
	exit();
}
add_js_ffile("includes/js/customers.js");
page(_($help_context = "Customers"), @$_REQUEST['popup']);
check_db_has_sales_types(_("There are no sales types defined. Please define at least one sales type before adding a customer."));
check_db_has_sales_people(_("There are no sales people defined in the system. At least one sales person is required before proceeding."));
check_db_has_sales_areas(_("There are no sales areas defined in the system. At least one sales area is required before proceeding."));
check_db_has_shippers(_("There are no shipping companies defined in the system. At least one shipping company is required before proceeding."));
check_db_has_tax_groups(_("There are no tax groups defined in the system. At least one tax group is required before proceeding."));
if (isset($_GET['debtor_no'])) {
	$customer = new Customer($_GET['debtor_no']);
} elseif (isset($_POST['id']) && !empty($_POST['id'])) {
	$customer = new Customer($_POST['id']);
} else {
	$customer = new Customer();
}
$currentContact = $customer->contacts[$customer->defaultContact];
$currentBranch = $customer->branches[$customer->defaultBranch];
if (isset($_POST['delete'])) {
	$customer->delete();
	$status = $customer->getStatus();
	display_notification($status['message']);
}
if (db_has_customers()) {
	/** @noinspection PhpUndefinedMethodInspection */
	HTML::div('custsearch', array('style' => 'text-align:center; '));
	/** @noinspection PhpDynamicAsStaticMethodCallInspection */
	UI::search('customer', array('label' => 'Search Customer:', 'size' => 80, 'url' => 'search.php',
								'callback' => 'Customer.fetch'));
	start_form();
}
$menu = new MenuUi();
$menu->startTab('Details', 'Customer Details');
text_row(_("Customer Name:"), 'name', $customer->name, 35, 80);

start_outer_table($table_style2, 5);

table_section(1);
table_section_title(_("Shipping Details"), 2, 'tableheader3 ');
HTML::tr(true)->td('branchSelect', array('colspan' => 2, 'style' => "text-align:center; margin:0 auto; "), true);
UI::select('branchList', array($currentBranch->br_name => $currentBranch->branch_code), array('name' => 'branchList'));
UI::button('addBranch', 'Add new address', array('name' => 'addBranch'));
HTML::td()->tr;
text_row(_("Contact:"), 'br_contact_name', $currentBranch->contact_name, 35, 40);
text_row(_("Phone Number:"), 'br_phone', $currentBranch->phone, 32, 30);
text_row(_("2nd Phone Number:"), 'br_phone2', $currentBranch->phone2, 32, 30);
text_row(_("Fax Number:"), 'br_fax', $currentBranch->fax, 32, 30);
email_row(_("Email:"), 'br_email', $currentBranch->email, 35, 55);
textarea_row(_("Street:"), 'br_br_address', $currentBranch->br_address, 35, 2);
email_row(_("City"), 'br_city', $currentBranch->city, 35, 40);
email_row(_("State:"), 'br_state', $currentBranch->state, 35, 40);
email_row(_("postcode"), 'br_postcode', $currentBranch->postcode, 35, 40);

table_section(2);
hidden('id', $customer->id);
table_section_title(_("Accounts Details"), 2, 'tableheader3');
check_row(_("Use shipping details"), 'useShipAddress',1);
text_row(_("Phone Number:"), 'acc_phone', $customer->accounts->phone, 40, 30);
text_row(_("Secondary Phone Number:"), 'acc_phone2', $customer->accounts->phone2, 40, 30);
text_row(_("Fax Number:"), 'acc_fax', $customer->accounts->fax, 40, 30);
email_row(_("E-mail:"), 'acc_email', $customer->email, 35, 40);
textarea_row(_("Street:"), 'acc_br_address', $customer->accounts->br_address, 35, 2);
email_row(_("City"), 'acc_city', $customer->accounts->city, 35, 40);
email_row(_("State:"), 'acc_state', $customer->accounts->state, 35, 40);
email_row(_("postcode"), 'acc_postcode', $customer->accounts->postcode, 35, 40);
sales_types_list_row(_("Sales Type/Price List:"), 'sales_type', $customer->sales_type);
record_status_list_row(_("Customer status:"), 'inactive');
end_outer_table(1);


$menu->endTab()->startTab('Accounts', 'Accounts');
start_outer_table($table_style2, 5);
table_section(1);
hidden('accounts_id', $customer->accounts->accounts_id);
table_section_title(_("Accounts Details:"), 2, ' tableheader3 ');
text_row(_("Accounts Contact:"), 'acc_contact_name', $customer->accounts->contact_name, 40, 40);
email_row(_("E-mail:"), 'acc_email', $customer->email, 40, 40);
text_row(_("Phone Number:"), 'acc_phone', $customer->accounts->phone, 40, 30);
text_row(_("2nd Phone Number:"), 'acc_phone2', $customer->accounts->phone2, 40, 30);
text_row(_("Fax Number:"), 'acc_fax', $customer->accounts->fax, 40, 30);
textarea_row(_("Street:"), 'acc_br_address', $customer->accounts->br_address, 35, 5);
email_row(_("City"), 'acc_city', $customer->accounts->city, 35, 40);
email_row(_("State:"), 'acc_state', $customer->accounts->state, 35, 40);
email_row(_("postcode"), 'acc_postcode', $customer->accounts->postcode, 35, 40);
textarea_row(_("Postal Address:"), 'acc_br_post_address', $customer->accounts->br_address, 35, 5);
percent_row(_("Discount Percent:"), 'discount', $customer->discount);
percent_row(_("Prompt Payment Discount Percent:"), 'pymt_discount', $customer->pymt_discount);
amount_row(_("Credit Limit:"), 'credit_limit', $customer->credit_limit);
payment_terms_list_row(_("Pament Terms:"), 'payment_terms', $customer->payment_terms);
credit_status_list_row(_("Credit Status:"), 'credit_status', $customer->credit_status);
text_row(_("GSTNo:"), 'tax_id', $customer->tax_id, 35, 40);
if (!$customer->id) {
	currencies_list_row(_("Customer's Currency:"), 'curr_code', $customer->curr_code);
} else {
	label_row(_("Customer's Currency:"), $customer->curr_code);
	hidden('curr_code', $customer->curr_code);
}
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
table_section(2);

table_section_title(_("Contact log:"), 2, 'tableheader3 ');
start_row();
HTML::td(array('class' => 'ui-widget-content center-content','colspan'=>2));
UI::button('addLog', "Add log entry")->td->tr->tr(true)->td(array('colspan'=>2))->textarea('messageLog', array('cols'=>50,'rows'=> 25));
contact_log::read($customer->id, 'C');
HTML::textarea()->td->td;
end_outer_table(1);

$menu->endTab()->startTab('Customer Contacts', 'Customer Contacts');
start_outer_table($table_style2, 5);
table_section(1);
HTML::tr(true);
//$count =0;
//foreach($customer->contacts as $index => $currentContact) {
// if ($count/4 == floor($count/4)) HTML::tr()->tr(true);
HTML::td('contactplace',array('colspan'=>2, 'style'=>'text-align:center'));
HTML::table('contactcell-')->tr(true)->td(array('name'=> 'contactname','class'=>'tableheader3','colspan'=>2))->tr;
text_row("Name:", 'con_name-', $currentContact->name, 35, 40);
text_row("Phone:", 'con_phone1-', $currentContact->phone1, 35, 40);
text_row("Phone2:", 'con_phone2-', $currentContact->phone2, 35, 40);
text_row("Email:", 'con_email-', $currentContact->email, 35, 40);
text_row("Dept:", 'con_department-', $currentContact->department, 35, 40);
HTML::td()->tr()->table()->td();
//$count++;
//}
HTML::tr();
end_outer_table(1);

$menu->endTab()->startTab('Extra Shipping Info', 'Extra Shipping Info');
start_outer_table($table_style2, 5);
table_section(1);
hidden('branch_code', $currentBranch->branch_code);
table_section_title(_("Name and Contact"));
text_row(_("Address Name:"), 'br_br_name', $currentBranch->br_name, 35, 40);
text_row(_("Contact:"), 'br_contact_name', $currentBranch->contact_name, 35, 40);
textarea_row(_("General Notes:"), 'br_notes', $currentBranch->notes, 35, 4);
table_section_title(_("Sales"));
sales_persons_list_row(_("Sales Person:"), 'br_salesman', $currentBranch->salesman);
sales_areas_list_row(_("Sales Area:"), 'br_area', $currentBranch->area);
sales_groups_list_row(_("Sales Group:"), 'br_group_no', $currentBranch->group_no);
locations_list_row(_("Default Inventory Location:"), 'br_default_location', $currentBranch->default_location);
shippers_list_row(_("Default Shipping Company:"), 'br_default_ship_via', $currentBranch->default_ship_via);
tax_groups_list_row(_("Tax Group:"), 'br_tax_group_id', $currentBranch->tax_group_id);
yesno_list_row(_("Disable this Branch:"), 'br_disable_trans', $currentBranch->disable_trans);
table_section(2);
table_section_title(_("GL Accounts"));
gl_all_accounts_list_row(_("Sales Account:"), 'br_sales_account', $currentBranch->sales_account, false, false, true);
gl_all_accounts_list_row(_("Sales Discount Account:"), 'br_sales_discount_account', $currentBranch->sales_discount_account);
gl_all_accounts_list_row(_("Accounts Receivable Account:"), 'br_receivables_account', $currentBranch->receivables_account);
gl_all_accounts_list_row(_("Prompt Payment Discount Account:"), 'br_payment_discount_account', $currentBranch->payment_discount_account);
table_section_title(_("Addresses"));
textarea_row(_("Address:"), 'br_br_address', $currentBranch->br_address, 35, 2);
textarea_row(_("Branch Mailing Address:"), 'br_br_post_address', $currentBranch->br_post_address, 35, 4);
end_outer_table(1);
$menu->endTab()->startTab('Invoices', 'Invoices');
HTML::div('transactions');
$menu->endTab()->render();
hidden('popup', @$_REQUEST['popup']);
end_form();
HTML::div('contactLog',
		  array('title' => 'New contact log entry', 'class' => 'ui-widget-overlay', 'style' => 'display:none;'));
HTML::p('New log entry:', array('class' => 'validateTips'));
start_table();
label_row('Date:', date('Y-m-d H:i:s'));
hidden('type', 'C');
text_row('Contact:', 'contact_name', $customer->accounts->contact_name, 40, 40);
textarea_row('Entry:', 'message', '', 100, 10);
end_table();
HTML::p()->div;
if ($customer->id) {
	UI::button('btnCustomer', 'Update Customer',
			   array('name' => 'submit', 'type' => 'submit', 'style' => 'margin:10px;'));
} else {
	UI::button('btnCustomer', 'New Customer',
			   array('name' => 'submit', 'type' => 'submit', 'class' => ' ui-helper-hidden',
					'style' => 'margin:10px;'));
}
UI::button('btnCancel', 'Cancel', array('name' => 'cancel', 'type' => 'submit', 'class' => 'ui-helper-hidden',
									   'style' => 'margin:10px;'));
HTML::div('shortcuts', array('style' => 'width:50%;display:block;margin:0 auto;'));
$shortcuts = new MenuUI();
$shortcuts->startTab('Create Order', 'Create Order for this customer!', '/sales/sales_order_entry.php?NewOrder=Yes');
$shortcuts->endTab();
$shortcuts->startTab('Create Quote', 'Create Quote for this customer!', '/sales/sales_order_entry.php?NewQuote=Yes');
$shortcuts->endTab();
$shortcuts->render();
HTML::_div()->div;
echo '<script>';
echo <<<JS
$(function() {});
JS;
echo '</script>';
end_page(true, true);