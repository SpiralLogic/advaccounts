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
	$page_security = 'SA_SUPPLIER';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Suppliers"), Input::request('popup'));
	Validation::check(Validation::TAX_GROUP, _("There are no tax groups defined in the system. At least one tax group is required before proceeding."));
	if (isset($_GET['supplier_id'])) {
		$_POST['supplier_id'] = $_GET['supplier_id'];
	}
	$new_supplier = get_post('supplier_id') == '';
	if (isset($_POST['submit'])) {
		//initialise no input errors assumed initially before we test
		$input_error = 0;
		/* actions to take once the user has clicked the submit button
							 ie the page has called itself with some user input */
		//first off validate inputs sensible
		if (strlen($_POST['supp_name']) == 0 || $_POST['supp_name'] == "") {
			$input_error = 1;
			Errors::error(_("The supplier name must be entered."));
			JS::set_focus('supp_name');
		}
		if (strlen($_POST['supp_ref']) == 0 || $_POST['supp_ref'] == "") {
			$input_error = 1;
			Errors::error(_("The supplier short name must be entered."));
			JS::set_focus('supp_ref');
		}
		if ($input_error != 1) {
			if (!$new_supplier) {
				$sql = "UPDATE suppliers SET supp_name=" . DB::escape($_POST['supp_name']) . ",
				supp_ref=" . DB::escape($_POST['supp_ref']) . ",
                address=" . DB::escape($_POST['address']) . ",
                supp_address=" . DB::escape($_POST['supp_address']) . ",
                phone=" . DB::escape($_POST['phone']) . ",
                phone2=" . DB::escape($_POST['phone2']) . ",
                fax=" . DB::escape($_POST['fax']) . ",
                gst_no=" . DB::escape($_POST['gst_no']) . ",
                email=" . DB::escape($_POST['email']) . ",
                website=" . DB::escape($_POST['website']) . ",
                contact=" . DB::escape($_POST['contact']) . ",
                supp_account_no=" . DB::escape($_POST['supp_account_no']) . ",
                bank_account=" . DB::escape($_POST['bank_account']) . ",
                credit_limit=" . Validation::input_num('credit_limit', 0) . ",
                dimension_id=" . DB::escape($_POST['dimension_id']) . ",
                dimension2_id=" . DB::escape($_POST['dimension2_id']) . ",
                curr_code=" . DB::escape($_POST['curr_code']) . ",
                payment_terms=" . DB::escape($_POST['payment_terms']) . ",
				payable_account=" . DB::escape($_POST['payable_account']) . ",
				purchase_account=" . DB::escape($_POST['purchase_account']) . ",
				payment_discount_account=" . DB::escape($_POST['payment_discount_account']) . ",
                notes=" . DB::escape($_POST['notes']) . ",
				tax_group_id=" . DB::escape($_POST['tax_group_id']) . " WHERE supplier_id = " . DB::escape($_POST['supplier_id']);
				DB::query($sql, "The supplier could not be updated");
				DB::update_record_status($_POST['supplier_id'], $_POST['inactive'], 'suppliers', 'supplier_id');
				$Ajax->activate('supplier_id'); // in case of status change
				Errors::notice(_("Supplier has been updated."));
			} else {
				$sql = "INSERT INTO suppliers (supp_name, supp_ref, address, supp_address, phone, phone2, fax, gst_no, email, website,
				contact, supp_account_no, bank_account, credit_limit, dimension_id, dimension2_id, curr_code,
				payment_terms, payable_account, purchase_account, payment_discount_account, notes, tax_group_id)
				VALUES (" . DB::escape($_POST['supp_name']) . ", " . DB::escape($_POST['supp_ref']) . ", " . DB::escape($_POST['address']) . ", " . DB::escape($_POST['supp_address']) . ", " . DB::escape($_POST['phone']) . ", " . DB::escape($_POST['phone2']) . ", " . DB::escape($_POST['fax']) . ", " . DB::escape($_POST['gst_no']) . ", " . DB::escape($_POST['email']) . ", " . DB::escape($_POST['website']) . ", " . DB::escape($_POST['contact']) . ", " . DB::escape($_POST['supp_account_no']) . ", " . DB::escape($_POST['bank_account']) . ", " . Validation::input_num('credit_limit',
																																																																																																																																																																																																																																																																																		0) . ", " . DB::escape($_POST['dimension_id']) . ", " . DB::escape($_POST['dimension2_id']) . ", " . DB::escape($_POST['curr_code']) . ", " . DB::escape($_POST['payment_terms']) . ", " . DB::escape($_POST['payable_account']) . ", " . DB::escape($_POST['purchase_account']) . ", " . DB::escape($_POST['payment_discount_account']) . ", " . DB::escape($_POST['notes']) . ", " . DB::escape($_POST['tax_group_id']) . ")";
				DB::query($sql, "The supplier could not be added");
				$_POST['supplier_id'] = DB::insert_id();
				$new_supplier = false;
				Errors::notice(_("A new supplier has been added."));
				$Ajax->activate('_page_body');
			}
		}
	} elseif (isset($_POST['delete']) && $_POST['delete'] != "") {
		//the link to delete a selected record was clicked instead of the submit button
		$cancel_delete = 0;
		// PREVENT DELETES IF DEPENDENT RECORDS IN 'supp_trans' , purch_orders
		$sql = "SELECT COUNT(*) FROM supp_trans WHERE supplier_id=" . DB::escape($_POST['supplier_id']);
		$result = DB::query($sql, "check failed");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			$cancel_delete = 1;
			Errors::error(_("Cannot delete this supplier because there are transactions that refer to this supplier."));
		} else {
			$sql = "SELECT COUNT(*) FROM purch_orders WHERE supplier_id=" . DB::escape($_POST['supplier_id']);
			$result = DB::query($sql, "check failed");
			$myrow = DB::fetch_row($result);
			if ($myrow[0] > 0) {
				$cancel_delete = 1;
				Errors::error(_("Cannot delete the supplier record because purchase orders have been created against this supplier."));
			}
		}
		if ($cancel_delete == 0) {
			$sql = "DELETE FROM suppliers WHERE supplier_id=" . DB::escape($_POST['supplier_id']);
			DB::query($sql, "check failed");
			unset($_SESSION['supplier_id']);
			$new_supplier = true;
			$Ajax->activate('_page_body');
		} //end if Delete supplier
	}
	start_form();
	if (Validation::check(Validation::SUPPLIERS)) {
		start_table('tablestyle_noborder pad3');
		//	start_table('tablestyle_noborder');
		start_row();
		Purch_UI::suppliers_cells(_("Select a supplier: "), 'supplier_id', null, _('New supplier'), true, check_value('show_inactive'));
		check_cells(_("Show inactive:"), 'show_inactive', null, true);
		end_row();
		end_table();
		if (get_post('_show_inactive_update')) {
			$Ajax->activate('supplier_id');
			JS::set_focus('supplier_id');
		}
	} else {
		hidden('supplier_id', get_post('supplier_id'));
	}
	start_outer_table('tablestyle2');
	table_section(1);
	if (!$new_supplier) {
		//SupplierID exists - either passed when calling the form or from the form itself
		$myrow = Purch_Creditor::get($_POST['supplier_id']);
		$_POST['supp_name'] = $myrow["supp_name"];
		$_POST['supp_ref'] = $myrow["supp_ref"];
		$_POST['address'] = $myrow["address"];
		$_POST['supp_address'] = $myrow["supp_address"];
		$_POST['phone'] = $myrow["phone"];
		$_POST['phone2'] = $myrow["phone2"];
		$_POST['fax'] = $myrow["fax"];
		$_POST['gst_no'] = $myrow["gst_no"];
		$_POST['email'] = $myrow["email"];
		$_POST['website'] = $myrow["website"];
		$_POST['contact'] = $myrow["contact"];
		$_POST['supp_account_no'] = $myrow["supp_account_no"];
		$_POST['bank_account'] = $myrow["bank_account"];
		$_POST['dimension_id'] = $myrow["dimension_id"];
		$_POST['dimension2_id'] = $myrow["dimension2_id"];
		$_POST['curr_code'] = $myrow["curr_code"];
		$_POST['payment_terms'] = $myrow["payment_terms"];
		$_POST['credit_limit'] = Num::price_format($myrow["credit_limit"]);
		$_POST['tax_group_id'] = $myrow["tax_group_id"];
		$_POST['payable_account'] = $myrow["payable_account"];
		$_POST['purchase_account'] = $myrow["purchase_account"];
		$_POST['payment_discount_account'] = $myrow["payment_discount_account"];
		$_POST['notes'] = $myrow["notes"];
		$_POST['inactive'] = $myrow["inactive"];
	} else {
		$_POST['supp_name'] = $_POST['supp_ref'] = $_POST['address'] = $_POST['supp_address'] = $_POST['tax_group_id'] = $_POST['website'] = $_POST['supp_account_no'] = $_POST['notes'] = '';
		$_POST['dimension_id'] = 0;
		$_POST['dimension2_id'] = 0;
		$_POST['sales_type'] = -1;
		$_POST['email'] = $_POST['phone'] = $_POST['phone2'] = $_POST['fax'] = $_POST['gst_no'] = $_POST['contact'] = $_POST['bank_account'] = '';
		$_POST['payment_terms'] = '';
		$_POST['credit_limit'] = Num::price_format(0);
		$company_record = DB_Company::get_prefs();
		$_POST['curr_code'] = $company_record["curr_default"];
		$_POST['payable_account'] = $company_record["creditors_act"];
		$_POST['purchase_account'] = $company_record["default_cogs_act"];
		$_POST['payment_discount_account'] = $company_record['pyt_discount_act'];
		$_POST['inactive'] = 0;
	}
	table_section_title(_("Name and Contact"));
	text_row(_("Supplier Name:"), 'supp_name', null, 42, 40);
	text_row(_("Supplier Short Name:"), 'supp_ref', null, 30, 30);
	text_row(_("Contact Person:"), 'contact', null, 42, 40);
	text_row(_("Phone Number:"), 'phone', null, 32, 30);
	text_row(_("Secondary Phone Number:"), 'phone2', null, 32, 30);
	text_row(_("Fax Number:"), 'fax', null, 32, 30);
	email_row(_("E-mail:"), 'email', null, 35, 55);
	link_row(_("Website:"), 'website', null, 35, 55);
	text_row(_("Our Customer No:"), 'supp_account_no', null, 42, 40);
	table_section_title(_("Addresses"));
	textarea_row(_("Mailing Address:"), 'address', null, 35, 5);
	textarea_row(_("Physical Address:"), 'supp_address', null, 35, 5);
	table_section(2);
	table_section_title(_("Purchasing"));
	text_row(_("GSTNo:"), 'gst_no', null, 42, 40);
	text_row(_("Bank Name/Account:"), 'bank_account', null, 42, 40);
	amount_row(_("Credit Limit:"), 'credit_limit', null);
	if (!$new_supplier) {
		label_row(_("Supplier's Currency:"), $_POST['curr_code']);
		hidden('curr_code', $_POST['curr_code']);
	} else {
		GL_Currency::row(_("Supplier's Currency:"), 'curr_code', null);
	}
	Tax_UI::groups_row(_("Tax Group:"), 'tax_group_id', null);
	GL_UI::payment_terms_row(_("Payment Terms:"), 'payment_terms', null);
	table_section_title(_("Accounts"));
	GL_UI::all_row(_("Accounts Payable Account:"), 'payable_account', $_POST['payable_account']);
	GL_UI::all_row(_("Purchase Account:"), 'purchase_account', $_POST['purchase_account']);
	GL_UI::all_row(_("Purchase Discount Account:"), 'payment_discount_account', $_POST['payment_discount_account']);
	$dim = DB_Company::get_pref('use_dimension');
	if ($dim >= 1) {
		table_section_title(_("Dimension"));
		Dimensions::select_row(_("Dimension") . " 1:", 'dimension_id', null, true, " ", false, 1);
		if ($dim > 1) {
			Dimensions::select_row(_("Dimension") . " 2:", 'dimension2_id', null, true, " ", false, 2);
		}
	}
	if ($dim < 1) {
		hidden('dimension_id', 0);
	}
	if ($dim < 2) {
		hidden('dimension2_id', 0);
	}
	table_section_title(_("General"));
	textarea_row(_("General Notes:"), 'notes', null, 35, 5);
	record_status_list_row(_("Supplier status:"), 'inactive');
	end_outer_table(1);
	Display::div_start('controls');
	if (!$new_supplier) {
		submit_center_first('submit', _("Update Supplier"), _('Update supplier data'), Input::request('popup') ? true : 'default');
		submit_return('select', get_post('supplier_id'), _("Select this supplier and return to document entry."));
		submit_center_last('delete', _("Delete Supplier"), _('Delete supplier data if have been never used'), true);
	} else {
		submit_center('submit', _("Add New Supplier Details"), true, '', 'default');
	}
	Display::div_end();
	hidden('popup', Input::request('popup'));
	end_form();
	end_page();

?>
