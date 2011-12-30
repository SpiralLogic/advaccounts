<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 12/4/10
	 * Time: 6:28 PM
	 * To change this template use File | Settings | File Templates.
	 */
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
	$page_security = 'SA_SUPPLIER';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Session::i()->App->selected_application = 'contacts';
	if (AJAX_REFERRER) {
		if (isset($_GET['term'])) {
			$data = Creditor::search($_GET['term']);
		} elseif (isset($_POST['id'])) {
			if (isset($_POST['name'])) {
				$data['supplier'] = $supplier = new Creditor($_POST);
				$supplier->save();
				$data['status'] = $supplier->getStatus();
			} elseif (!isset($_POST['name'])) {
				$data['supplier'] = $supplier = new Creditor($_POST['id']);
			}
		} else {
			$data['supplier'] = new Creditor(0);
		}
		 JS::renderJSON($data);
	}
	JS::footerFile("js/suppliers.js");
	Page::start(_($help_context = "Suppliers"), Input::request('frame'));
	if (isset($_GET['id'])) {
		$supplier = new Creditor($_GET['id']);
	} elseif (isset($_POST['id']) && !empty($_POST['id'])) {
		$supplier = new Creditor($_POST['id']);
	} else {
		$supplier = new Creditor();
	}
	if (Validation::check(Validation::SUPPLIERS)) {
		HTML::div('suppliersearch', array('style' => 'text-align:center; '));
		UI::search('supplier', array(
																'label' => 'Supplier:', 'size' => 80, 'callback' => 'Supplier.fetch'));
	}
	$menu = new MenuUI();
	$menu->startTab('Details', 'Supplier Details');
	text_row(_("Supplier Name:"), 'name', $supplier->name, 35, 80);
	start_outer_table('pad5');
	table_section(1);
	table_section_title(_("Contact Information"), 2, 'tableheader3');
	text_row(_("Contact Person:"), 'contact_name', $supplier->contact_name, 42, 40);
	text_row(_("Phone Number:"), 'phone', $supplier->phone, 32, 30);
	text_row(_("Secondary Phone Number:"), 'phone2', $supplier->phone2, 32, 30);
	text_row(_("Fax Number:"), 'fax', $supplier->fax, 32, 30);
	email_row(_("E-mail:"), 'email', $supplier->email, 35, 55);
	link_row(_("Website:"), 'website', $supplier->website, 35, 55);
	text_row(_("Our Account No:"), 'account_no', $supplier->account_no, 42, 40);
	textarea_row(_("Physical Address:"), 'address', $supplier->address, 35, 5);
	textarea_row(_("Mailing Address:"), 'post_address', $supplier->post_address, 35, 5);
	table_section(2);
	table_section_title(_("Accounts"), 2, 'tableheader3');
	text_row(_("GSTNo:"), 'tax_id', $supplier->tax_id, 42, 40);
	text_row(_("Bank Name/Account:"), 'bank_account', $supplier->bank_account, 42, 40);
	amount_row(_("Credit Limit:"), 'credit_limit', $supplier->credit_limit);
	GL_Currency::row(_("Supplier's Currency:"), 'curr_code', $supplier->curr_code);
	Tax_Groups::row(_("Tax Group:"), 'tax_group_id', $supplier->tax_group_id);
	GL_UI::payment_terms_row(_("Payment Terms:"), 'payment_terms', $supplier->payment_terms);
	table_section_title(_("Accounts"), 2, 'tableheader3');
	GL_UI::all_row(_("Accounts Payable Account:"), 'payable_account', $supplier->payable_account);
	GL_UI::all_row(_("Purchase Account:"), 'purchase_account', $supplier->purchase_account);
	GL_UI::all_row(_("Purchase Discount Account:"), 'payment_discount_account', $supplier->payment_discount_account);
	end_outer_table(1);
	$menu->endTab();
	$menu->startTab('General', 'General Details');
	textarea_row(_("General Notes:"), 'notes', $supplier->notes, 35, 5);
	record_status_list_row(_("Supplier status:"), 'inactive', $supplier->inactive);
	$menu->endTab();
	$menu->render();
	if ($customer->id) {
		UI::button('btnSupplier', 'Update Supplier', array(
																											'name' => 'submit', 'type' => 'submit', 'style' => 'margin:10px;'));
	} else {
		UI::button('btnSupplier', 'New Supplier', array(
																									 'name' => 'submit', 'type' => 'submit', 'class' => ' ui-helper-hidden', 'style' => 'margin:10px;'));
	}
	UI::button('btnCancel', 'Cancel', array(
																				 'name' => 'cancel', 'type' => 'submit', 'class' => 'ui-helper-hidden', 'style' => 'margin:10px;'))->div;
	Renderer::end_page(true, true);