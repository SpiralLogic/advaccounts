<?php

	/* * ********************************************************************
				 Copyright (C) Advanced Group PTY LTD
				 Released under the terms of the GNU General Public License, GPL,
				 as published by the Free Software Foundation, either version 3
				 of the License, or (at your option) any later version.
				 This program is distributed in the hope that it will be useful,
				 but WITHOUT ANY WARRANTY; without even the implied warranty of
				 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
				 See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
				* ********************************************************************* */
	$page_security = 'SA_CUSTOMER';
	//$page_security = 3;
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Customer Branches"), Input::request('popup'));
	Validation::check(Validation::CUSTOMERS, _("There are no customers defined in the system. Please define a customer to add customer branches."));
	Validation::check(Validation::SALESPERSONS, _("There are no sales people defined in the system. At least one sales person is required before proceeding."));
	Validation::check(Validation::SALES_AREA, _("There are no sales areas defined in the system. At least one sales area is required before proceeding."));
	Validation::check(Validation::SHIPPERS, _("There are no shipping companies defined in the system. At least one shipping company is required before proceeding."));
	Validation::check(Validation::TAX_GROUP, _("There are no tax groups defined in the system. At least one tax group is required before proceeding."));
	Page::simple_mode(true);
	if (isset($_GET['debtor_no'])) {
		$_POST['customer_id'] = strtoupper($_GET['debtor_no']);
	}
	$_POST['branch_code'] = $selected_id;
	if (isset($_GET['SelectedBranch'])) {
		$br = Sales_Branch::get($_GET['SelectedBranch']);
		$_POST['customer_id'] = $br['debtor_no'];
		$selected_id = $_POST['branch_code'] = $br['branch_code'];
		$Mode = 'Edit';
	}
	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
		//initialise no input errors assumed initially before we test
		$input_error = 0;
		//first off validate inputs sensible
		if (strlen($_POST['br_name']) == 0) {
			$input_error = 1;
			Errors::error(_("The Branch name cannot be empty."));
			JS::set_focus('br_name');
		}
		if (strlen($_POST['br_ref']) == 0) {
			$input_error = 1;
			Errors::error(_("The Branch short name cannot be empty."));
			JS::set_focus('br_ref');
		}
		if ($input_error != 1) {
			if ($selected_id != -1) {
				/* SelectedBranch could also exist if submit had not been clicked this code would not run in this case cos submit is false of course see the 	delete code below */
				$sql = "UPDATE cust_branch SET br_name = " . DB::escape($_POST['br_name']) . ",
				branch_ref = " . DB::escape($_POST['br_ref']) . ",
				br_address = " . DB::escape($_POST['br_address']) . ",
 	 phone=" . DB::escape($_POST['phone']) . ",
 	 phone2=" . DB::escape($_POST['phone2']) . ",
 	 fax=" . DB::escape($_POST['fax']) . ",
 	 contact_name=" . DB::escape($_POST['contact_name']) . ",
 	 salesman= " . DB::escape($_POST['salesman']) . ",
 	 area=" . DB::escape($_POST['area']) . ",
 	 email=" . DB::escape($_POST['email']) . ",
 	 tax_group_id=" . DB::escape($_POST['tax_group_id']) . ",
				sales_account=" . DB::escape($_POST['sales_account']) . ",
				sales_discount_account=" . DB::escape($_POST['sales_discount_account']) . ",
				receivables_account=" . DB::escape($_POST['receivables_account']) . ",
				payment_discount_account=" . DB::escape($_POST['payment_discount_account']) . ",
 	 default_location=" . DB::escape($_POST['default_location']) . ",
 	 br_post_address =" . DB::escape($_POST['br_post_address']) . ",
 	 disable_trans=" . DB::escape($_POST['disable_trans']) . ",
				group_no=" . DB::escape($_POST['group_no']) . ",
 	 default_ship_via=" . DB::escape($_POST['default_ship_via']) . ",
 notes=" . DB::escape($_POST['notes']) . "
 	 WHERE branch_code =" . DB::escape($_POST['branch_code']) . "
 	 AND debtor_no=" . DB::escape($_POST['customer_id']);
				$note = _('Selected customer branch has been updated');
			} else {
				/* Selected branch is null cos no item selected on first time round so must be adding a	record must be submitting new entries in the new Customer Branches form */
				$sql = "INSERT INTO cust_branch (debtor_no, br_name, branch_ref, br_address,
				salesman, phone, phone2, fax,
				contact_name, area, email, tax_group_id, sales_account, receivables_account, payment_discount_account, sales_discount_account, default_location,
				br_post_address, disable_trans, group_no, default_ship_via, notes)
				VALUES (" . DB::escape($_POST['customer_id']) . "," . DB::escape($_POST['br_name']) . ", " . DB::escape($_POST['br_ref']) . ", " . DB::escape($_POST['br_address']) . ", " . DB::escape($_POST['salesman']) . ", " . DB::escape($_POST['phone']) . ", " . DB::escape($_POST['phone2']) . ", " . DB::escape($_POST['fax']) . "," . DB::escape($_POST['contact_name']) . ", " . DB::escape($_POST['area']) . "," . DB::escape($_POST['email']) . ", " . DB::escape($_POST['tax_group_id']) . ", " . DB::escape($_POST['sales_account']) . ", " . DB::escape($_POST['receivables_account']) . ", " . DB::escape($_POST['payment_discount_account']) . ", " . DB::escape($_POST['sales_discount_account']) . ", " . DB::escape($_POST['default_location']) . ", " . DB::escape($_POST['br_post_address']) . "," . DB::escape($_POST['disable_trans']) . ", " . DB::escape($_POST['group_no']) . ", " . DB::escape($_POST['default_ship_via']) . ", " . DB::escape($_POST['notes']) . ")";
				$note = _('New customer branch has been added');
			}
			//run the sql from either of the above possibilites
			DB::query($sql, "The branch record could not be inserted or updated");
			Errors::notice($note);
			$Mode = 'RESET';
			if (Input::request('popup')) {
				JS::set_focus("Select" . ($_POST['branch_code'] == -1 ? DB::insert_id() : $_POST['branch_code']));
			}
		}
	} elseif ($Mode == 'Delete') {
		//the link to delete a selected record was clicked instead of the submit button
		// PREVENT DELETES IF DEPENDENT RECORDS IN 'debtor_trans'
		$sql = "SELECT COUNT(*) FROM debtor_trans WHERE branch_code=" . DB::escape($_POST['branch_code']) . " AND debtor_no = " . DB::escape($_POST['customer_id']);
		$result = DB::query($sql, "could not query debtortrans");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this branch because customer transactions have been created to this branch."));
		} else {
			$sql = "SELECT COUNT(*) FROM sales_orders WHERE branch_code=" . DB::escape($_POST['branch_code']) . " AND debtor_no = " . DB::escape($_POST['customer_id']);
			$result = DB::query($sql, "could not query sales orders");
			$myrow = DB::fetch_row($result);
			if ($myrow[0] > 0) {
				Errors::error(_("Cannot delete this branch because sales orders exist for it. Purge old sales orders first."));
			} else {
				$sql = "DELETE FROM cust_branch WHERE branch_code=" . DB::escape($_POST['branch_code']) . " AND debtor_no=" . DB::escape($_POST['customer_id']);
				DB::query($sql, "could not delete branch");
				Errors::notice(_('Selected customer branch has been deleted'));
			}
		} //end ifs to test if the branch can be deleted
		$Mode = 'RESET';
	}
	if ($Mode == 'RESET' || get_post('_customer_id_update')) {
		$selected_id = -1;
		$cust_id = $_POST['customer_id'];
		$inact = get_post('show_inactive');
		unset($_POST);
		$_POST['show_inactive'] = $inact;
		$_POST['customer_id'] = $cust_id;
		$Ajax->activate('_page_body');
	}
	function branch_email($row) {
		return '<a href = "mailto:' . $row["email"] . '">' . $row["email"] . '</a>';
	}

	function edit_link($row) {
		return button("Edit" . $row["branch_code"], _("Edit"), '', ICON_EDIT);
	}

	function del_link($row) {
		return button("Delete" . $row["branch_code"], _("Delete"), '', ICON_DELETE);
	}

	function select_link($row) {
		return button("Select" . $row["branch_code"], $row["branch_code"], '', ICON_ADD, 'selector');
	}

	start_form();
	echo "<div class='center'>" . _("Select a customer: ") . "&nbsp;&nbsp;";
	echo Debtor::select('customer_id', null, false, true);
	echo "</div><br>";
	$num_branches = -0;
	if (Input::post('customer_id') > 0) {
		$num_branches = Validation::check(Validation::BRANCHES, '', Input::post('customer_id'));
		$sql = "SELECT " . "b.branch_code, " . "b.branch_ref, " . "b.br_name, " . "b.contact_name, " . "s.salesman_name, " . "a.description, " . "b.phone, " . "b.fax, " . "b.email, " . "t.name AS tax_group_name, " . "b.inactive
		FROM cust_branch b, debtors_master c, areas a, salesman s, tax_groups t
		WHERE b.debtor_no=c.debtor_no
		AND b.tax_group_id=t.id
		AND b.area=a.area_code
		AND b.salesman=s.salesman_code
		AND b.debtor_no = " . DB::escape($_POST['customer_id']);
		if (!get_post('show_inactive')) {
			$sql .= " AND !b.inactive";
		}
		if ($num_branches) {
			$cols = array(
				'branch_code' => 'skip', _("Short Name"), _("Name"), _("Contact"), _("Sales Person"), _("Area"), _("Phone No"), _("Fax No"), _("E-mail") => 'email', _("Tax Group"), _("Inactive") => 'inactive', //		array('fun'=>'inactive'),
				' ' => array(
					'insert' => true, 'fun' => 'select_link'), array(
					'insert' => true, 'fun' => 'edit_link'), array(
					'insert' => true, 'fun' => 'del_link'));
			if (!Input::request('popup')) {
				$cols[' '] = 'skip';
			}
			$table = & db_pager::new_db_pager('branch_tbl', $sql, $cols, 'cust_branch');
			$table->set_inactive_ctrl('cust_branch', 'branch_code');
			//$table->width = "85%";
			DB_Pager::display($table);
		} else {
			Errors::warning(_("The selected customer does not have any branches. Please create at least one branch."));
		}
	} else {
		Errors::warning(_("No Customer selected."));
	}
	start_outer_table('tablestyle2');
	table_section(1);
	$_POST['email'] = "";
	if ($selected_id != -1) {
		if ($Mode == 'Edit') {
			//editing an existing branch
			$sql = "SELECT * FROM cust_branch
			WHERE branch_code=" . DB::escape($_POST['branch_code']) . "
			AND debtor_no=" . DB::escape($_POST['customer_id']);
			$result = DB::query($sql, "check failed");
			$myrow = DB::fetch($result);
			JS::set_focus('br_name');
			$_POST['branch_code'] = $myrow["branch_code"];
			$_POST['br_name'] = $myrow["br_name"];
			$_POST['br_ref'] = $myrow["branch_ref"];
			$_POST['br_address'] = $myrow["br_address"];
			$_POST['br_post_address'] = $myrow["br_post_address"];
			$_POST['contact_name'] = $myrow["contact_name"];
			$_POST['salesman'] = $myrow["salesman"];
			$_POST['area'] = $myrow["area"];
			$_POST['phone'] = $myrow["phone"];
			$_POST['phone2'] = $myrow["phone2"];
			$_POST['fax'] = $myrow["fax"];
			$_POST['email'] = $myrow["email"];
			$_POST['tax_group_id'] = $myrow["tax_group_id"];
			$_POST['disable_trans'] = $myrow['disable_trans'];
			$_POST['default_location'] = $myrow["default_location"];
			$_POST['default_ship_via'] = $myrow['default_ship_via'];
			$_POST['sales_account'] = $myrow["sales_account"];
			$_POST['sales_discount_account'] = $myrow['sales_discount_account'];
			$_POST['receivables_account'] = $myrow['receivables_account'];
			$_POST['payment_discount_account'] = $myrow['payment_discount_account'];
			$_POST['group_no'] = $myrow["group_no"];
			$_POST['notes'] = $myrow["notes"];
		}
	} elseif ($Mode != 'ADD_ITEM') { //end of if $SelectedBranch only do the else when a new record is being entered
		if (!$num_branches) {
			$sql = "SELECT name, address, email, debtor_ref
			FROM debtors_master WHERE debtor_no = " . DB::escape($_POST['customer_id']);
			$result = DB::query($sql, "check failed");
			$myrow = DB::fetch($result);
			$_POST['br_name'] = $myrow["name"];
			$_POST['br_ref'] = $myrow["debtor_ref"];
			$_POST['contact_name'] = _('Main Branch');
			$_POST['br_address'] = $_POST['br_post_address'] = $myrow["address"];
			$_POST['email'] = $myrow['email'];
		}
		$_POST['branch_code'] = "";
		if (!isset($_POST['sales_account']) || !isset($_POST['sales_discount_account'])) {
			$company_record = DB_Company::get_prefs();
			// We use the Item Sales Account as default!
			// $_POST['sales_account'] = $company_record["default_sales_act"];
			$_POST['sales_account'] = $_POST['notes'] = '';
			$_POST['sales_discount_account'] = $company_record['default_sales_discount_act'];
			$_POST['receivables_account'] = $company_record['debtors_act'];
			$_POST['payment_discount_account'] = $company_record['default_prompt_payment_act'];
		}
	}
	hidden('selected_id', $selected_id);
	hidden('branch_code');
	hidden('popup', Input::request('popup'));
	table_section_title(_("Name and Contact"));
	text_row(_("Branch Name:"), 'br_name', null, 35, 40);
	text_row(_("Branch Short Name:"), 'br_ref', null, 30, 30);
	text_row(_("Contact Person:"), 'contact_name', null, 35, 40);
	text_row(_("Phone Number:"), 'phone', null, 32, 30);
	text_row(_("Secondary Phone Number:"), 'phone2', null, 32, 30);
	text_row(_("Fax Number:"), 'fax', null, 32, 30);
	email_row(_("E-mail:"), 'email', null, 35, 55);
	table_section_title(_("Sales"));
	Sales_UI::persons_row(_("Sales Person:"), 'salesman', null);
	Sales_UI::areas_row(_("Sales Area:"), 'area', null);
	Sales_UI::groups_row(_("Sales Group:"), 'group_no', null, true);
	Inv_Location::row(_("Default Inventory Location:"), 'default_location', null);
	Sales_UI::shippers_row(_("Default Shipping Company:"), 'default_ship_via', null);
	Tax_Groups::row(_("Tax Group:"), 'tax_group_id', null);
	yesno_list_row(_("Disable this Branch:"), 'disable_trans', null);
	table_section(2);
	table_section_title(_("GL Accounts"));
	// 2006-06-14. Changed gl_al_accounts_list to have an optional all_option 'Use Item Sales Accounts'
	GL_UI::all_row(_("Sales Account:"), 'sales_account', null, false, false, true);
	GL_UI::all_row(_("Sales Discount Account:"), 'sales_discount_account');
	GL_UI::all_row(_("Accounts Receivable Account:"), 'receivables_account');
	GL_UI::all_row(_("Prompt Payment Discount Account:"), 'payment_discount_account');
	table_section_title(_("Addresses"));
	textarea_row(_("Mailing Address:"), 'br_post_address', null, 35, 4);
	textarea_row(_("Billing Address:"), 'br_address', null, 35, 4);
	textarea_row(_("General Notes:"), 'notes', null, 35, 4);
	end_outer_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	end_page();
?>
