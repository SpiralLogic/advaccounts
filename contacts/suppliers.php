<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 12/4/10
 * Time: 6:28 PM
 * To change this template use File | Settings | File Templates.
 */
/**********************************************************************
Copyright (C) FrontAccounting, LLC.
Released under the terms of the GNU General Public License, GPL,
as published by the Free Software Foundation, either version 3
of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
 ***********************************************************************/
$page_security = 'SA_SUPPLIER';
$path_to_root = "../";
include_once("includes/contacts.inc");
if (isAjaxReferrer()) {
	if (isset($_GET['term'])) {
		$data = Supplier::search($_GET['term']);
	} elseif (isset($_POST['id'])) {
		if (isset($_POST['name'])) {
			$data['supplier'] = $supplier = new Supplier($_POST);
			$supplier->save();
			$data['status'] = $supplier->getStatus();
		} elseif (!isset($_POST['name'])) {
			$data['supplier'] = $supplier = new Supplier($_POST['id']);
		}
	} else {
		$data['supplier'] = new Supplier(0);
	}
	echo json_encode($data);
	exit();
}
add_js_ffile("includes/js/suppliers.js");
page(_($help_context = "Suppliers"), @$_REQUEST['popup']);
if (isset($_GET['id'])) {
	$supplier = new Supplier($_GET['id']);
} elseif (isset($_POST['id']) && !empty($_POST['id'])) {
	$supplier = new Supplier($_POST['id']);
} else {
	$supplier = new Supplier();
}
if (db_has_suppliers()) {
	HTML::div('suppliersearch', array('style' => 'text-align:center; '));
	UI::search('item', array('label' => 'Supplier:', 'size' => 80, 'callback' => 'getSupplier'));
}

$menu = new MenuUI();
$menu->startTab('Details', 'Supplier Details');
text_row(_("Supplier Name:"),'name',$supplier->name,35,80);
start_outer_table('',5);
table_section(1);
table_section_title(_("Contact Information"),2,'tableheader3');
text_row(_("Contact Person:"), $supplier->contact_name, null, 42, 40);
text_row(_("Phone Number:"), $supplier->phone, null, 32, 30);
text_row(_("Secondary Phone Number:"), $supplier->phone2, null, 32, 30);
text_row(_("Fax Number:"), $supplier->fax, null, 32, 30);
email_row(_("E-mail:"), $supplier->email, null, 35, 55);
link_row(_("Website:"), $supplier->website, null, 35, 55);
text_row(_("Our Account No:"), $supplier->account_no, null, 42, 40);

textarea_row(_("Physical Address:"), $supplier->address, null, 35, 5);
textarea_row(_("Mailing Address:"), $supplier->post_address, null, 35, 5);
table_section(2);
table_section_title(_("Accounts"), 2,'tableheader3');
text_row(_("GSTNo:"), $supplier->tax_id, null, 42, 40);
text_row(_("Bank Name/Account:"), $supplier->bank_account, null, 42, 40);
amount_row(_("Credit Limit:"), $supplier->credit_limit, null);
	currencies_list_row(_("Supplier's Currency:"), $supplier->curr_code, null);
tax_groups_list_row(_("Tax Group:"), $supplier->tax_group_id, null);
payment_terms_list_row(_("Payment Terms:"), $supplier->payment_terms, null);
table_section_title(_("Accounts"),2, 'tableheader3');
gl_all_accounts_list_row(_("Accounts Payable Account:"), 'payable_account', $supplier->payable_account);
gl_all_accounts_list_row(_("Purchase Account:"), 'purchase_account', $supplier->purchase_account);
gl_all_accounts_list_row(_("Purchase Discount Account:"), 'payment_discount_account', $supplier->payment_discount_account);
end_outer_table(1);
$menu->endTab();

$menu->startTab('General','General Details');

textarea_row(_("General Notes:"), 'notes', null, 35, 5);
record_status_list_row(_("Supplier status:"), 'inactive');

$menu->endTab();
$menu->render();
HTML::div();
end_page(true, true);