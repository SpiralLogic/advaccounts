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
include_once($path_to_root . "/contacts/includes/contacts.inc");

page(_($help_context = "Customers"), @$_REQUEST['popup']);

check_db_has_sales_types(_("There are no sales types defined. Please define at least one sales type before adding a customer."));

if (isset($_GET['debtor_no'])) {
	$customer = new Customer($_GET['debtor_no']);
} elseif (isset($_POST['customer_id']) && $_POST['customer_id'] == "") {
	$customer = new Customer($_POST['customer_id']);
} else {
	$customer = new Customer();
}


//--------------------------------------------------------------------------------------------




//--------------------------------------------------------------------------------------------

function handle_submit() {
	global  $Ajax, $customer;
	if (!$customer->save()) {
		$status= $customer->getStatus();
		display_error($status['message']);
		set_focus($status['var']);

	} else {
		$status= $customer->getStatus();
		display_notification($status['message']);
	}
	$Ajax->activate('customer_id'); // in case of status change
	return $status['status'];
}

//--------------------------------------------------------------------------------------------

if (isset($_POST['submit'])) {
handle_submit();
}
//--------------------------------------------------------------------------------------------

if (isset($_POST['delete'])) {
$customer->delete();
$status = $customer->getStatus();
display_notification($status['message']);
$Ajax->activate('_page_body');
	//the link to delete a selected record was clicked instead of the submit button
}


start_form();

if (db_has_customers()) {
	start_table("class = 'tablestyle_noborder'");
	start_row();
	customer_list_cells(_("Select a customer: "), 'customer_id', null, _('New customer'), true, check_value('show_inactive'));
	check_cells(_("Show inactive:"), 'show_inactive', null, true);
	end_row();
	end_table();
	if (get_post('_show_inactive_update')) {
		$Ajax->activate('customer_id');
		set_focus('customer_id');
	}
} else {
	hidden('customer_id');
}


start_outer_table($table_style2, 5);
table_section(1);
table_section_title(_("Name and Address"));

text_row(_("Customer Name:"), 'name', $_POST['name'], 40, 80);
text_row(_("Customer Short Name:"), 'debtor_ref', null, 30, 30);
textarea_row(_("Address:"), 'address', $_POST['address'], 35, 5);

email_row(_("E-mail:"), 'email', null, 40, 40);
text_row(_("GSTNo:"), 'taxId', null, 40, 40);

if ($new_customer) {
	currencies_list_row(_("Customer's Currency:"), 'curr_code', $_POST['curr_code']);
} else {
	label_row(_("Customer's Currency:"), $_POST['curr_code']);
	hidden('curr_code', $_POST['curr_code']);
}
sales_types_list_row(_("Sales Type/Price List:"), 'sales_type', $_POST['sales_type']);

table_section(2);

table_section_title(_("Sales"));

percent_row(_("Discount Percent:"), 'discount', $_POST['discount']);
percent_row(_("Prompt Payment Discount Percent:"), 'pymt_discount', $_POST['pymt_discount']);
amount_row(_("Credit Limit:"), 'credit_limit', $_POST['credit_limit']);

payment_terms_list_row(_("Payment Terms:"), 'payment_terms', $_POST['payment_terms']);
credit_status_list_row(_("Credit Status:"), 'credit_status', $_POST['credit_status']);
$dim = get_company_pref('use_dimension');
if ($dim >= 1) {
	dimensions_list_row(_("Dimension") . " 1:", 'dimension_id', $_POST['dimension_id'], true, " ", false, 1);
}
if ($dim > 1) {
	dimensions_list_row(_("Dimension") . " 2:", 'dimension2_id', $_POST['dimension2_id'], true, " ", false, 2);
}
if ($dim < 1) {
	hidden('dimension_id', 0);
}
if ($dim < 2) {
	hidden('dimension2_id', 0);
}

if (!$customer->id) {
	start_row();
	echo '<td>' . _('Customer branches') . ':</td>';
	hyperlink_params_td($path_to_root . "/sales/manage/customer_branches.php", '<b>' . (@$_REQUEST['popup'] ? _("Select or &Add") : _("&Add or Edit ")) . '</b>',
			"debtor_no=" . $_POST['customer_id'] . (@$_REQUEST['popup'] ? '&popup=1' : ''));
	end_row();
}

textarea_row(_("General Notes:"), 'notes', null, 35, 5);
record_status_list_row(_("Customer status:"), 'inactive');
end_outer_table(1);

div_start('controls');
if ($customer->id==0) {
	submit_center('submit', _("Add New Customer"), true, '', 'default');
} else {
	submit_center_first('submit', _("Update Customer"), _('Update customer data'), @$_REQUEST['popup'] ? true : 'default');
	submit_return('select', get_post('customer_id'), _("Select this customer and return to document entry."));
	submit_center_last('delete', _("Delete Customer"), _('Delete customer data if have been never used'), true);
}
div_end();
hidden('popup', @$_REQUEST['popup']);
end_form();
end_page(true, true);